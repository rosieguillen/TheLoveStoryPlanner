<?php

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/includes/validation.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: authenticate.php');
    exit;
}

if (empty($_SESSION['user_management_csrf'])) {
    $_SESSION['user_management_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEditing = $userId !== false && $userId !== null && $userId > 0;
$username = '';
$role = 'user';

if ($isEditing) {
    $statement = $db->prepare(
        'SELECT UserID, Username, Role FROM users WHERE UserID = :userID LIMIT 1'
    );
    $statement->execute([':userID' => $userId]);
    $existingUser = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        header('Location: manageusers.php');
        exit;
    }

    $username = $existingUser['Username'];
    $role = $existingUser['Role'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $postedUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $isEditing = $postedUserId !== false && $postedUserId !== null && $postedUserId > 0;
    $userId = $isEditing ? $postedUserId : null;
    $username = sanitizePlainText($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (!hash_equals($_SESSION['user_management_csrf'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($username === '') {
        $error = 'Enter a username or email address.';
    } elseif (mb_strlen($username) > 50) {
        $error = 'The username must be 50 characters or fewer.';
    } elseif (containsInvalidControlCharacters($username)) {
        $error = 'The username contains invalid characters.';
    } elseif (!in_array($role, ['user', 'admin'], true)) {
        $error = 'Select a valid account role.';
    } elseif (!$isEditing && mb_strlen($password) < 8) {
        $error = 'New accounts require a password of at least 8 characters.';
    } elseif ($isEditing && $password !== '' && mb_strlen($password) < 8) {
        $error = 'A replacement password must contain at least 8 characters.';
    } elseif ($isEditing && $userId === (int) $_SESSION['user_id'] && $role !== 'admin') {
        $error = 'You cannot remove administrator access from your current account.';
    } else {
        try {
            if ($isEditing) {
                $query = 'UPDATE users SET Username = :username, Role = :role';
                $parameters = [
                    ':username' => $username,
                    ':role' => $role,
                    ':userID' => $userId
                ];

                if ($password !== '') {
                    $query .= ', Password = :password';
                    $parameters[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                $query .= ' WHERE UserID = :userID';
                $statement = $db->prepare($query);
                $statement->execute($parameters);
                $destination = 'manageusers.php?updated=1';
            } else {
                $statement = $db->prepare(
                    'INSERT INTO users (Username, Password, Role)
                     VALUES (:username, :password, :role)'
                );
                $statement->execute([
                    ':username' => $username,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role
                ]);
                $destination = 'manageusers.php?created=1';
            }

            $_SESSION['user_management_csrf'] = bin2hex(random_bytes(32));
            header('Location: ' . $destination);
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That username is already registered.';
            } else {
                $error = 'The user account could not be saved.';
            }
        }
    }
}

function escapeUserForm(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Edit User' : 'Add User' ?> | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="users.css">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="user-form-page">
        <section class="user-form-card">
            <header>
                <p class="users-label">Admin Area</p>
                <h1><?= $isEditing ? 'Edit User' : 'Add New User' ?></h1>
                <p><?= $isEditing ? 'Update this account’s username, role or password.' : 'Create a registered user or administrator account.' ?></p>
            </header>

            <?php if ($error !== ''): ?>
                <div class="users-error" role="alert"><?= escapeUserForm($error) ?></div>
            <?php endif; ?>

            <form method="post" action="user.php<?= $isEditing ? '?id=' . (int) $userId : '' ?>" class="user-form">
                <input type="hidden" name="csrf_token" value="<?= escapeUserForm($_SESSION['user_management_csrf']) ?>">
                <?php if ($isEditing): ?>
                    <input type="hidden" name="user_id" value="<?= (int) $userId ?>">
                <?php endif; ?>

                <div class="user-field">
                    <label for="username">Username or email</label>
                    <input id="username" name="username" type="text" maxlength="50" value="<?= escapeUserForm($username) ?>" required>
                </div>

                <div class="user-field">
                    <label for="role">Account role</label>
                    <select id="role" name="role" required>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>Regular user</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator</option>
                    </select>
                </div>

                <div class="user-field">
                    <label for="password"><?= $isEditing ? 'New password' : 'Password' ?></label>
                    <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" <?= $isEditing ? '' : 'required' ?>>
                    <p><?= $isEditing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' ?></p>
                </div>

                <div class="user-form-actions">
                    <a href="manageusers.php" class="secondary-button">Cancel</a>
                    <button type="submit" class="primary-button"><?= $isEditing ? 'Update User' : 'Create User' ?></button>
                </div>
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

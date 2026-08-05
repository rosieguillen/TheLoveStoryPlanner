<?php

session_start();

require_once __DIR__ . '/connect.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: authenticate.php');
    exit;
}

if (empty($_SESSION['user_management_csrf'])) {
    $_SESSION['user_management_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $deleteId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['user_management_csrf'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$deleteId || $deleteId < 1) {
        $error = 'The selected user is invalid.';
    } elseif ($deleteId === (int) $_SESSION['user_id']) {
        $error = 'You cannot delete the account you are currently using.';
    } else {
        $statement = $db->prepare(
            'SELECT UserID, Username, Role FROM users WHERE UserID = :userID LIMIT 1'
        );
        $statement->execute([':userID' => $deleteId]);
        $userToDelete = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$userToDelete) {
            $error = 'That user could not be found.';
        } else {
            $adminCount = (int) $db->query(
                "SELECT COUNT(*) FROM users WHERE Role = 'admin'"
            )->fetchColumn();

            if ($userToDelete['Role'] === 'admin' && $adminCount <= 1) {
                $error = 'The final administrator account cannot be deleted.';
            } else {
                $statement = $db->prepare('DELETE FROM users WHERE UserID = :userID');
                $statement->execute([':userID' => $deleteId]);
                $_SESSION['user_management_csrf'] = bin2hex(random_bytes(32));
                header('Location: manageusers.php?deleted=1');
                exit;
            }
        }
    }
}

if (($_GET['created'] ?? '') === '1') {
    $message = 'The user account was created successfully.';
} elseif (($_GET['updated'] ?? '') === '1') {
    $message = 'The user account was updated successfully.';
} elseif (($_GET['deleted'] ?? '') === '1') {
    $message = 'The user account was deleted successfully.';
}

$users = $db->query(
    'SELECT UserID, Username, Role
     FROM users
     ORDER BY Username ASC, UserID ASC'
)->fetchAll(PDO::FETCH_ASSOC);

function escapeUserList(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="users.css">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="users-page">
        <header class="users-header">
            <div>
                <p class="users-label">Admin Area</p>
                <h1>Manage Users</h1>
                <p>View, add, update and remove registered user accounts.</p>
            </div>

            <a href="user.php" class="primary-button">Add New User</a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="users-success" role="status"><?= escapeUserList($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="users-error" role="alert"><?= escapeUserList($error) ?></div>
        <?php endif; ?>

        <section class="users-card" aria-labelledby="registered-users-heading">
            <div class="users-card-heading">
                <h2 id="registered-users-heading">Registered Users</h2>
                <span><?= count($users) ?> total</span>
            </div>

            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th scope="col">Username</th>
                            <th scope="col">Role</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <?= escapeUserList($user['Username']) ?>
                                    <?php if ((int) $user['UserID'] === (int) $_SESSION['user_id']): ?>
                                        <span class="current-user">Current account</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="role-badge role-<?= escapeUserList($user['Role']) ?>"><?= escapeUserList(ucfirst($user['Role'])) ?></span></td>
                                <td class="user-actions">
                                    <a href="user.php?id=<?= (int) $user['UserID'] ?>" class="edit-link">Edit</a>

                                    <?php if ((int) $user['UserID'] !== (int) $_SESSION['user_id']): ?>
                                        <form method="post" action="manageusers.php" onsubmit="return confirm('Delete this user permanently?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= (int) $user['UserID'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= escapeUserList($_SESSION['user_management_csrf']) ?>">
                                            <button type="submit" class="delete-button">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <a href="admin.php" class="back-link">← Back to Admin Area</a>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

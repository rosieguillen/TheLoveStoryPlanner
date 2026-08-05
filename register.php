<?php

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/includes/validation.php';

$error = '';
$username = '';

if (empty($_SESSION['registration_csrf_token'])) {
    $_SESSION['registration_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizePlainText($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['registration_csrf_token'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($username === '') {
        $error = 'Enter a username or email address.';
    } elseif (mb_strlen($username) > 50) {
        $error = 'Your username must be 50 characters or fewer.';
    } elseif (containsInvalidControlCharacters($username)) {
        $error = 'Your username contains invalid characters.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Your password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $statement = $db->prepare(
                "INSERT INTO users (Username, Password, Role)
                 VALUES (:username, :password, 'user')"
            );

            $statement->execute([
                ':username' => $username,
                ':password' => $passwordHash
            ]);

            $_SESSION['registration_csrf_token'] = bin2hex(random_bytes(32));
            header('Location: authenticate.php?registered=1');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That username is already registered.';
            } else {
                $error = 'Your account could not be created. Please try again.';
            }
        }
    }
}

function escapeRegistration(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="signin.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="login-page">
        <section class="login-card">
            <div class="login-logo">
                <img src="photos/logo-long.png" alt="The Love Story Planner">
            </div>

            <p class="login-label">Visitor Account</p>
            <h1>Create an Account</h1>
            <p class="login-intro">Register securely to access member features.</p>

            <?php if ($error !== ''): ?>
                <div class="login-error" role="alert">
                    <?= escapeRegistration($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= escapeRegistration($_SESSION['registration_csrf_token']) ?>">

                <div class="form-group">
                    <label for="username">Username or email</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        maxlength="50"
                        value="<?= escapeRegistration($username) ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>
                    <input
                        id="confirm_password"
                        name="confirm_password"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">Create Account</button>
            </form>

            <a href="authenticate.php" class="return-link">Already registered? Sign in</a>
        </section>
    </main>
</body>
</html>

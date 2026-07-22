<?php

session_start();

require_once __DIR__ . '/connect.php';

$error = '';
$username = '';

/*
 * If the administrator is already signed in and has a
 * database UserID, redirect to the new-post page.
 */
if (
    !empty($_SESSION['admin_logged_in']) &&
    !empty($_SESSION['user_id'])
) {
    header('Location: admin.php');
    exit;
}

/*
 * Process the login form.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $error = 'Enter your username or email address.';
    } elseif ($password === '') {
        $error = 'Enter your password.';
    } else {
        $query = "
            SELECT
                UserID,
                Username,
                Password
            FROM USERS
            WHERE Username = :username
            LIMIT 1
        ";

        $statement = $db->prepare($query);

        $statement->execute([
            ':username' => $username
        ]);

        $user = $statement->fetch(
            PDO::FETCH_ASSOC
        );

        if (
            $user !== false &&
            password_verify(
                $password,
                $user['Password']
            )
        ) {
            session_regenerate_id(true);

            /*
             * Store the signed-in administrator's information.
             * user_id becomes blogposts.author_id.
             */
            $_SESSION['admin_logged_in'] = true;

            $_SESSION['user_id'] =
                (int) $user['UserID'];

            $_SESSION['admin_username'] =
                $user['Username'];

            header('Location: admin.php');
            exit;
        }

        $error = 'Incorrect username or password.';
    }
}

/*
 * Escape values before displaying them.
 */
function escapeLogin(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Admin Sign In | The Love Story Planner
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="signin.css">
</head>

<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="login-page">
        <section class="login-card">
            <div class="login-logo">
                <img
                    src="photos/logo-long.png"
                    alt="The Love Story Planner"
                >
            </div>

            <p class="login-label">
                Administrator Area
            </p>

            <h1>Welcome Back</h1>

            <p class="login-intro">
                Sign in to create and manage blog posts.
            </p>

            <?php if ($error !== ''): ?>
                <div
                    class="login-error"
                    role="alert"
                >
                    <?= escapeLogin($error) ?>
                </div>
            <?php endif; ?>

            <form
                method="post"
                action="authenticate.php"
                class="login-form"
            >
                <div class="form-group">
                    <label for="username">
                        Username or email
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?= escapeLogin($username) ?>"
                        placeholder="admin@lovestoryplanner.ca"
                        autocomplete="username"
                        autofocus
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        Password
                    </label>

                    <div class="password-field">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="password-toggle"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="login-button"
                >
                    Sign In
                </button>
            </form>

            <a
                href="HomePage.php"
                class="return-link"
            >
                Return to the home page
            </a>
        </section>
    </main>

    <script>
        const passwordInput =
            document.querySelector("#password");

        const passwordToggle =
            document.querySelector("#password-toggle");

        passwordToggle?.addEventListener("click", () => {
            const passwordIsHidden =
                passwordInput.type === "password";

            passwordInput.type =
                passwordIsHidden
                    ? "text"
                    : "password";

            passwordToggle.textContent =
                passwordIsHidden
                    ? "Hide"
                    : "Show";

            passwordToggle.setAttribute(
                "aria-label",
                passwordIsHidden
                    ? "Hide password"
                    : "Show password"
            );

            passwordToggle.setAttribute(
                "aria-pressed",
                passwordIsHidden
                    ? "true"
                    : "false"
            );

            passwordInput.focus();
        });
    </script>
</body>
</html>

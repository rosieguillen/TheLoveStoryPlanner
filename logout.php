<?php

session_start();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $cookie['path'],
        $cookie['domain'],
        $cookie['secure'],
        $cookie['httponly']
    );
}

session_destroy();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signed Out | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="logout.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="logout-page">
        <section class="logout-card">
            <div class="logout-mark" aria-hidden="true">✓</div>
            <p class="logout-label">Admin Area</p>
            <h1>You’re signed out.</h1>
            <p class="logout-message">
                Your administrator session has ended successfully.
                Thank you for keeping the wedding journal up to date.
            </p>

            <div class="logout-actions">
                <a href="HomePage.php" class="secondary-button">Return Home</a>
                <a href="authenticate.php" class="primary-button">Sign In Again</a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

<?php

session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: authenticate.php');
    exit;
}

function adminEscape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Area | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="admin-page">
        <header class="admin-header">
            <p class="admin-label">The Love Story Planner</p>
            <h1>Admin Area</h1>
            <p>
                Welcome, <?= adminEscape($_SESSION['admin_username'] ?? 'Administrator') ?>.
                Choose an option below to manage the wedding journal.
            </p>
        </header>

        <section class="admin-options" aria-label="Blog administration options">
            <a href="post.php" class="admin-card admin-card-primary">
                <span class="card-number" aria-hidden="true">01</span>
                <div>
                    <h2>Create New Post</h2>
                    <p>Write and publish a new wedding story, planning guide or inspiration article.</p>
                </div>
                <span class="card-link">Create Post <span aria-hidden="true">→</span></span>
            </a>

            <a href="manageposts.php" class="admin-card">
                <span class="card-number" aria-hidden="true">02</span>
                <div>
                    <h2>Manage Posts</h2>
                    <p>View, sort, edit or delete the blog posts that already exist.</p>
                </div>
                <span class="card-link">Manage Posts <span aria-hidden="true">→</span></span>
            </a>
        </section>

        <div class="admin-footer-actions">
            <a href="blogposts.php">View Public Blog</a>
            <a href="logout.php">Sign Out</a>
        </div>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

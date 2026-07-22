<?php
require __DIR__ . '/connect.php';

$query = "
    SELECT
        blogID,
        author_id,
        title,
        content,
        `timestamp`,
        blog_image
    FROM blogspots
    ORDER BY `timestamp` DESC, blogID DESC
";

$statement = $db->prepare($query);
$statement->execute();

$posts = $statement->fetchAll(PDO::FETCH_ASSOC);

function formatDate($date)
{
    $timestamp = strtotime($date);

    return $timestamp
        ? date('F j, Y \a\t g:i a', $timestamp)
        : '';
}

function createExcerpt($content, $length = 220)
{
    $content = trim(strip_tags($content));

    if (mb_strlen($content) > $length) {
        return mb_substr($content, 0, $length) . '…';
    }

    return $content;
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

    <title>Journal | The Love Story Planner</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="blogstyle.css">
</head>

<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <header class="blog-hero">
        <p class="blog-label">From Our Journal</p>

        <h1>Stories, inspiration and planning advice</h1>

        <p class="blog-description">
            Thoughtful notes to help you plan a wedding that feels
            meaningful, beautiful and completely your own.
        </p>

        <a href="post.php" class="new-post-button">
            Create New Post
        </a>
    </header>

    <main class="blog-main">
        <div class="section-heading">
            <div>
                <p class="section-label">Latest articles</p>
                <h2>Recent Blog Posts</h2>
            </div>
        </div>

        <?php if (!empty($posts)): ?>
            <nav class="article-directory" aria-label="All available blog posts">
                <h2>Browse All Articles</h2>

                <ul>
                    <?php foreach ($posts as $post): ?>
                        <li>
                            <a href="post.php?id=<?= (int) $post['blogID'] ?>">
                                <?= htmlspecialchars(
                                    $post['title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if (!empty($posts)): ?>
            <div class="posts-grid">
                <?php foreach ($posts as $post): ?>
                    <article class="post-card">
                        <?php if (!empty($post['blog_image'])): ?>
                            <a
                                href="post.php?id=<?= (int) $post['blogID'] ?>"
                                class="post-image-link"
                            >
                                <img
                                    src="<?= htmlspecialchars(
                                        $post['blog_image'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $post['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    class="post-image"
                                >
                            </a>
                        <?php endif; ?>

                        <div class="post-content">
                            <p class="post-date">
                                <?= htmlspecialchars(
                                    formatDate($post['timestamp']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <h3>
                                <a href="post.php?id=<?= (int) $post['blogID'] ?>">
                                    <?= htmlspecialchars(
                                        $post['title'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </a>
                            </h3>

                            <p class="post-excerpt">
                                <?= htmlspecialchars(
                                    createExcerpt($post['content']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <div class="post-actions">
                                <a
                                    href="post.php?id=<?= (int) $post['blogID'] ?>"
                                    class="read-link"
                                >
                                    Read full post
                                    <span aria-hidden="true">→</span>
                                </a>

                                <a
                                    href="edit.php?id=<?= (int) $post['blogID'] ?>"
                                    class="edit-link"
                                >
                                    Edit
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-message">
                <h2>No posts yet</h2>

                <p>
                    Your latest wedding stories and planning advice
                    will appear here.
                </p>

                <a href="post.php" class="new-post-button">
                    Create Your First Post
                </a>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

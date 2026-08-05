<?php

session_start();

require_once __DIR__ . '/connect.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: authenticate.php');
    exit;
}

if (empty($_SESSION['comment_moderation_csrf'])) {
    $_SESSION['comment_moderation_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_comment') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['comment_moderation_csrf'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$commentId || $commentId < 1) {
        $error = 'The selected comment is invalid.';
    } else {
        $statement = $db->prepare('DELETE FROM comments WHERE comment_id = :comment_id');
        $statement->execute([':comment_id' => $commentId]);

        if ($statement->rowCount() === 1) {
            $_SESSION['comment_moderation_csrf'] = bin2hex(random_bytes(32));
            header('Location: managecomments.php?deleted=1');
            exit;
        }

        $error = 'That comment could not be found.';
    }
}

if (($_GET['deleted'] ?? '') === '1') {
    $message = 'The comment was deleted successfully.';
}

$comments = $db->query(
    "SELECT
        c.comment_id,
        c.comment_text,
        c.commenter_name,
        c.timestamp_comment,
        c.UserID,
        c.PageID,
        b.title AS post_title,
        u.Username AS registered_username
     FROM comments c
     LEFT JOIN blogspots b ON b.blogID = c.PageID
     LEFT JOIN users u ON u.UserID = c.UserID
     ORDER BY c.timestamp_comment DESC, c.comment_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

function escapeModeration(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function moderationDate(string $value): string
{
    $time = strtotime($value);
    return $time ? date('M j, Y \a\t g:i a', $time) : '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Comments | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="commentsadmin.css">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="moderation-page">
        <header class="moderation-header">
            <div>
                <p class="moderation-label">Admin Area</p>
                <h1>Moderate Comments</h1>
                <p>Review visitor comments and remove content that should not appear publicly.</p>
            </div>

            <a href="admin.php" class="secondary-button">Back to Admin</a>
        </header>

        <?php if ($message !== ''): ?>
            <div class="moderation-success" role="status"><?= escapeModeration($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="moderation-error" role="alert"><?= escapeModeration($error) ?></div>
        <?php endif; ?>

        <section class="moderation-card" aria-labelledby="comments-list-heading">
            <div class="moderation-card-heading">
                <h2 id="comments-list-heading">Submitted Comments</h2>
                <span><?= count($comments) ?> total</span>
            </div>

            <?php if ($comments): ?>
                <div class="moderation-list">
                    <?php foreach ($comments as $comment): ?>
                        <?php
                            $displayName = $comment['commenter_name']
                                ?: ($comment['registered_username'] ?: 'Visitor');
                        ?>
                        <article class="moderation-comment">
                            <div class="comment-details">
                                <div class="comment-meta">
                                    <strong><?= escapeModeration($displayName) ?></strong>
                                    <span class="visitor-badge">
                                        <?= $comment['UserID'] === null ? 'Visitor' : 'Registered user' ?>
                                    </span>
                                    <time datetime="<?= escapeModeration($comment['timestamp_comment']) ?>">
                                        <?= escapeModeration(moderationDate($comment['timestamp_comment'])) ?>
                                    </time>
                                </div>

                                <p class="comment-text"><?= nl2br(escapeModeration($comment['comment_text'])) ?></p>

                                <p class="comment-post">
                                    On:
                                    <?php if ($comment['post_title'] !== null): ?>
                                        <a href="post.php?id=<?= (int) $comment['PageID'] ?>">
                                            <?= escapeModeration($comment['post_title']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span>Deleted or unavailable post</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <form method="post" action="managecomments.php" onsubmit="return confirm('Delete this comment permanently?');">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="comment_id" value="<?= (int) $comment['comment_id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= escapeModeration($_SESSION['comment_moderation_csrf']) ?>">
                                <button type="submit" class="delete-button">Delete Comment</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="moderation-empty">
                    <h3>No comments to moderate</h3>
                    <p>New visitor comments will appear here automatically.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

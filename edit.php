<?php

session_start();

require_once __DIR__ . '/connect.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: authenticate.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escapeEdit(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$error = '';
$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!$postId || $postId < 1) {
        $error = 'The selected post is invalid.';
    } elseif (isset($_POST['delete'])) {
        $statement = $db->prepare(
            'SELECT blog_image FROM blogspots WHERE blogID = :blogID LIMIT 1'
        );
        $statement->execute([':blogID' => $postId]);
        $postToDelete = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$postToDelete) {
            header('Location: blogposts.php');
            exit;
        }

        $statement = $db->prepare(
            'DELETE FROM blogspots WHERE blogID = :blogID'
        );
        $statement->execute([':blogID' => $postId]);

        if (!empty($postToDelete['blog_image'])) {
            $imageFile = __DIR__ . '/' . $postToDelete['blog_image'];

            if (is_file($imageFile)) {
                unlink($imageFile);
            }
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: blogposts.php');
        exit;
    } elseif (isset($_POST['update'])) {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } elseif (mb_strlen($title) > 50) {
            $error = 'The title must be 50 characters or fewer.';
        } elseif (mb_strlen($content) > 1200) {
            $error = 'The content must be 1,200 characters or fewer.';
        } else {
            $statement = $db->prepare(
                'UPDATE blogspots
                 SET title = :title, content = :content
                 WHERE blogID = :blogID'
            );
            $statement->execute([
                ':title' => $title,
                ':content' => $content,
                ':blogID' => $postId
            ]);

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: post.php?id=' . urlencode((string) $postId));
            exit;
        }
    }
}

if (!$postId || $postId < 1) {
    header('Location: blogposts.php');
    exit;
}

$statement = $db->prepare(
    'SELECT blogID, title, content, blog_image
     FROM blogspots
     WHERE blogID = :blogID
     LIMIT 1'
);
$statement->execute([':blogID' => $postId]);
$post = $statement->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: blogposts.php');
    exit;
}

$title = $_POST['title'] ?? $post['title'];
$content = $_POST['content'] ?? $post['content'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="post.css">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="post-page">
        <section class="post-form-section">
            <div class="form-introduction">
                <p class="page-label">Admin Area</p>
                <h1>Edit Blog Post</h1>
                <p>Update the article or permanently remove it from the journal.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error-message" role="alert">
                    <?= escapeEdit($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="edit.php?id=<?= (int) $postId ?>" class="post-form">
                <input type="hidden" name="id" value="<?= (int) $postId ?>">
                <input type="hidden" name="csrf_token" value="<?= escapeEdit($_SESSION['csrf_token']) ?>">

                <div class="form-group">
                    <label for="title">Post title</label>
                    <input id="title" type="text" name="title" value="<?= escapeEdit($title) ?>" maxlength="50" required>
                    <p class="field-help">Maximum 50 characters.</p>
                </div>

                <div class="form-group">
                    <label for="content">Post content</label>
                    <textarea id="content" name="content" rows="14" maxlength="1200" required><?= escapeEdit($content) ?></textarea>
                    <p class="field-help">Maximum 1,200 characters.</p>
                </div>

                <?php if (!empty($post['blog_image'])): ?>
                    <div class="form-group">
                        <label>Current featured image</label>
                        <img class="edit-current-image" src="<?= escapeEdit($post['blog_image']) ?>" alt="<?= escapeEdit($post['title']) ?>">
                    </div>
                <?php endif; ?>

                <div class="form-actions edit-form-actions">
                    <button type="submit" name="delete" class="delete-button" formnovalidate onclick="return confirm('Delete this post permanently?');">Delete Post</button>
                    <div class="edit-primary-actions">
                        <a href="post.php?id=<?= (int) $postId ?>" class="cancel-button">Cancel</a>
                        <button type="submit" name="update" class="btn-submit">Update Post</button>
                    </div>
                </div>
            </form>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

<?php

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/blog-image.php';

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
$postId = positiveInputId(INPUT_GET, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = positiveInputId(INPUT_POST, 'id');
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif ($postId === null) {
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

        $imageFile = blogImageFilePath($postToDelete['blog_image'] ?? null);

        if ($imageFile !== null) {
            unlink($imageFile);
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header('Location: blogposts.php');
        exit;
    } elseif (isset($_POST['update'])) {
        $title = sanitizePlainText($_POST['title'] ?? '');
        $content = sanitizePlainText($_POST['content'] ?? '');
        $removeImage = isset($_POST['remove_image']);
        $hasNewUpload =
            isset($_FILES['blog_image']) &&
            $_FILES['blog_image']['error'] !== UPLOAD_ERR_NO_FILE;
        $newImagePath = null;

        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } elseif (mb_strlen($title) > 50) {
            $error = 'The title must be 50 characters or fewer.';
        } elseif (mb_strlen($content) > 1200) {
            $error = 'The content must be 1,200 characters or fewer.';
        } elseif (containsInvalidControlCharacters($title)) {
            $error = 'The title contains invalid characters.';
        } elseif (containsInvalidControlCharacters($content)) {
            $error = 'The content contains invalid characters.';
        } elseif ($removeImage && $hasNewUpload) {
            $error = 'Choose either a replacement image or remove the current image, not both.';
        }

        $statement = $db->prepare(
            'SELECT blog_image FROM blogspots WHERE blogID = :blogID LIMIT 1'
        );
        $statement->execute([':blogID' => $postId]);
        $currentPost = $statement->fetch(PDO::FETCH_ASSOC);

        if ($error === '' && !$currentPost) {
            $error = 'That blog post could not be found.';
        }

        if ($error === '' && $hasNewUpload) {
            $uploadedImage = $_FILES['blog_image'];

            if ($uploadedImage['error'] !== UPLOAD_ERR_OK) {
                $error = 'The replacement image could not be uploaded.';
            } elseif ($uploadedImage['size'] > 5 * 1024 * 1024) {
                $error = 'The replacement image must be smaller than 5 MB.';
            } elseif (!is_uploaded_file($uploadedImage['tmp_name'])) {
                $error = 'The replacement image upload is invalid.';
            } else {
                $fileInformation = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInformation->file($uploadedImage['tmp_name']);
                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                if (!isset($allowedTypes[$mimeType])) {
                    $error = 'Only JPG, PNG and WebP replacement images are allowed.';
                } else {
                    $uploadDirectory = __DIR__ . '/uploads/blog/';

                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
                        $error = 'The blog image directory could not be created.';
                    } else {
                        $filename = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                        $destination = $uploadDirectory . $filename;

                        if (!move_uploaded_file($uploadedImage['tmp_name'], $destination)) {
                            $error = 'The replacement image could not be saved.';
                        } else {
                            $newImagePath = 'uploads/blog/' . $filename;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            $oldImagePath = $currentPost['blog_image'] ?? '';
            $savedImagePath = $removeImage
                ? ''
                : ($newImagePath ?? $oldImagePath);

            try {
                $statement = $db->prepare(
                    'UPDATE blogspots
                     SET title = :title,
                         content = :content,
                         blog_image = :blog_image
                     WHERE blogID = :blogID'
                );
                $statement->execute([
                    ':title' => $title,
                    ':content' => $content,
                    ':blog_image' => $savedImagePath,
                    ':blogID' => $postId
                ]);

                if (($removeImage || $newImagePath !== null) && $oldImagePath !== '') {
                    $oldImageFile = blogImageFilePath($oldImagePath);

                    if ($oldImageFile !== null) {
                        unlink($oldImageFile);
                    }
                }

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: post.php?id=' . urlencode((string) $postId));
                exit;
            } catch (PDOException $exception) {
                if ($newImagePath !== null) {
                    $newImageFile = blogImageFilePath($newImagePath);

                    if ($newImageFile !== null) {
                        unlink($newImageFile);
                    }
                }

                $error = 'The blog post could not be updated.';
            }
        }
    }
}

if ($postId === null) {
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
    <link rel="stylesheet" href="post.css?v=20260804-1">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
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

            <form method="post" action="edit.php?id=<?= (int) $postId ?>" class="post-form" enctype="multipart/form-data">
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

                <?php if (blogImageExists($post['blog_image'] ?? null)): ?>
                    <div class="form-group edit-image-group">
                        <label>Current featured image</label>
                        <img class="edit-current-image" src="<?= escapeEdit($post['blog_image']) ?>" alt="<?= escapeEdit($post['title']) ?>">

                        <label class="remove-image-option">
                            <input type="checkbox" name="remove_image" value="1">
                            <span>Remove the current image</span>
                        </label>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="blog_image">
                        <?= blogImageExists($post['blog_image'] ?? null)
                            ? 'Replace featured image'
                            : 'Add featured image' ?>
                    </label>
                    <input
                        id="blog_image"
                        type="file"
                        name="blog_image"
                        class="edit-image-input"
                        accept="image/jpeg,image/png,image/webp"
                    >
                    <p class="field-help">Optional. JPG, PNG or WebP, up to 5 MB.</p>
                </div>

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

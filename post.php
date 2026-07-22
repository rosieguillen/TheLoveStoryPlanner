<?php

session_start();

require_once __DIR__ . '/connect.php';

$error = '';
$post = false;
$imagePath = null;

/*
 * Escape content before displaying it in HTML.
 */
function escape(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
 * Format the database timestamp.
 */
function formatPostDate(string $date): string
{
    $timestamp = strtotime($date);

    return $timestamp
        ? date('F j, Y \a\t g:i a', $timestamp)
        : '';
}

/*
 * Create a CSRF token for the new-post form.
 */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(
        random_bytes(32)
    );
}

/*
 * A visitor can read an existing post.
 * Only a signed-in administrator can access the new-post form.
 */
if (
    !isset($_GET['id']) &&
    empty($_SESSION['admin_logged_in'])
) {
    header('Location: authenticate.php');
    exit;
}

/*
 * CREATE A NEW POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: authenticate.php');
        exit;
    }

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $submittedToken
        )
    ) {
        $error = 'Your session expired. Refresh the page and try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    /*
     * This was saved during a successful login:
     * $_SESSION['user_id'] = $user['UserID'];
     */
    $authorId = $_SESSION['user_id'] ?? null;

    if ($error === '' && $title === '') {
        $error = 'A post title is required.';
    }

    if ($error === '' && $content === '') {
        $error = 'Post content is required.';
    }

    if ($error === '' && !$authorId) {
        $error = 'Your account does not have a valid author ID.';
    }

    /*
     * PROCESS THE OPTIONAL FEATURED IMAGE
     */
    if (
        $error === '' &&
        isset($_FILES['blog_image']) &&
        $_FILES['blog_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        $uploadedImage = $_FILES['blog_image'];

        if ($uploadedImage['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE =>
                    'The image exceeds the server upload limit.',

                UPLOAD_ERR_FORM_SIZE =>
                    'The selected image is too large.',

                UPLOAD_ERR_PARTIAL =>
                    'The image was only partially uploaded.',

                UPLOAD_ERR_NO_TMP_DIR =>
                    'The server upload folder is missing.',

                UPLOAD_ERR_CANT_WRITE =>
                    'The server could not save the image.',

                UPLOAD_ERR_EXTENSION =>
                    'The server stopped the image upload.'
            ];

            $error = $uploadErrors[$uploadedImage['error']]
                ?? 'The image could not be uploaded.';
        } elseif (
            $uploadedImage['size'] > 5 * 1024 * 1024
        ) {
            $error = 'The image must be smaller than 5 MB.';
        } else {
            $fileInformation = new finfo(
                FILEINFO_MIME_TYPE
            );

            $mimeType = $fileInformation->file(
                $uploadedImage['tmp_name']
            );

            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            if (!isset($allowedTypes[$mimeType])) {
                $error = 'Only JPG, PNG and WebP images are allowed.';
            } else {
                $uploadDirectory =
                    __DIR__ . '/uploads/blog/';

                if (
                    !is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true)
                ) {
                    $error = 'The blog image directory could not be created.';
                } else {
                    $extension = $allowedTypes[$mimeType];

                    $filename =
                        bin2hex(random_bytes(16)) .
                        '.' .
                        $extension;

                    $destination =
                        $uploadDirectory .
                        $filename;

                    if (
                        !move_uploaded_file(
                            $uploadedImage['tmp_name'],
                            $destination
                        )
                    ) {
                        $error = 'The uploaded image could not be saved.';
                    } else {
                        /*
                         * Save the browser-accessible path
                         * in the database.
                         */
                        $imagePath =
                            'uploads/blog/' .
                            $filename;
                    }
                }
            }
        }
    }

    /*
     * INSERT THE POST INTO THE DATABASE
     */
    if ($error === '') {
        try {
            $query = "
                INSERT INTO blogspots (
                    author_id,
                    title,
                    content,
                    blog_image,
                    comment_ID
                )
                VALUES (
                    :author_id,
                    :title,
                    :content,
                    :blog_image,
                    ''
                )
            ";

            $statement = $db->prepare($query);

            $statement->bindValue(
                ':author_id',
                (int) $authorId,
                PDO::PARAM_INT
            );

            $statement->bindValue(
                ':title',
                $title,
                PDO::PARAM_STR
            );

            $statement->bindValue(
                ':content',
                $content,
                PDO::PARAM_STR
            );

            $statement->bindValue(
                ':blog_image',
                $imagePath ?? '',
                PDO::PARAM_STR
            );

            $statement->execute();

            $newPostId = (int) $db->lastInsertId();

            /*
             * Generate a new token after publishing.
             */
            $_SESSION['csrf_token'] = bin2hex(
                random_bytes(32)
            );

            header(
                'Location: post.php?id=' .
                urlencode((string) $newPostId)
            );

            exit;
        } catch (PDOException $exception) {
            /*
             * Remove the image if the database insert fails.
             */
            if ($imagePath !== null) {
                $uploadedFile =
                    __DIR__ . '/' .
                    $imagePath;

                if (is_file($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }

            $error = 'The blog post could not be saved.';
        }
    }
}

/*
 * DISPLAY AN EXISTING POST
 */
if (isset($_GET['id'])) {
    $postId = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT
    );

    if (
        $postId === false ||
        $postId === null ||
        $postId < 1
    ) {
        header('Location: blogposts.php');
        exit;
    }

    $query = "
        SELECT
            blogID,
            author_id,
            title,
            content,
            `timestamp`,
            blog_image
        FROM blogspots
        WHERE blogID = :blogID
        LIMIT 1
    ";

    $statement = $db->prepare($query);

    $statement->bindValue(
        ':blogID',
        $postId,
        PDO::PARAM_INT
    );

    $statement->execute();

    $post = $statement->fetch(
        PDO::FETCH_ASSOC
    );

    if (!$post) {
        header('Location: blogposts.php');
        exit;
    }
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
        <?= $post
            ? escape($post['title'])
            : 'Create New Blog Post'
        ?>
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="post.css">
</head>

<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="post-page">
        <?php if ($post): ?>
            <article class="single-post">
                <header class="single-post-header">
                    <p class="page-label">
                        From Our Journal
                    </p>

                    <h1>
                        <?= escape($post['title']) ?>
                    </h1>

                    <div class="post-meta">
                        <time
                            datetime="<?= escape(
                                $post['timestamp']
                            ) ?>"
                        >
                            <?= escape(
                                formatPostDate(
                                    $post['timestamp']
                                )
                            ) ?>
                        </time>

                        <?php if (
                            !empty(
                                $_SESSION['admin_logged_in']
                            )
                        ): ?>
                            <span
                                class="meta-separator"
                                aria-hidden="true"
                            >
                                •
                            </span>

                            <a
                                href="edit.php?id=<?= (int) $post['blogID'] ?>"
                                class="edit-link"
                            >
                                Edit Post
                            </a>
                        <?php endif; ?>
                    </div>
                </header>

                <?php if (!empty($post['blog_image'])): ?>
                    <img
                        src="<?= escape(
                            $post['blog_image']
                        ) ?>"
                        alt="<?= escape(
                            $post['title']
                        ) ?>"
                        class="single-post-image"
                    >
                <?php endif; ?>

                <div class="single-post-content">
                    <?= nl2br(
                        escape($post['content'])
                    ) ?>
                </div>

                <footer class="single-post-footer">
                    <a
                        href="blogposts.php"
                        class="back-link"
                    >
                        <span aria-hidden="true">←</span>
                        Back to All Posts
                    </a>
                </footer>
            </article>
        <?php else: ?>
            <section class="post-form-section">
                <div class="form-introduction">
                    <p class="page-label">
                        Admin Area
                    </p>

                    <h1>Create a New Blog Post</h1>

                    <p>
                        Share wedding inspiration, helpful planning
                        advice and meaningful stories.
                    </p>
                </div>

                <?php if ($error !== ''): ?>
                    <div
                        class="error-message"
                        role="alert"
                    >
                        <?= escape($error) ?>
                    </div>
                <?php endif; ?>

                <form
                    method="post"
                    action="post.php"
                    class="post-form"
                    enctype="multipart/form-data"
                >
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escape(
                            $_SESSION['csrf_token']
                        ) ?>"
                    >

                    <div class="form-group">
                        <label for="title">
                            Post title
                        </label>

                        <input
                            id="title"
                            type="text"
                            name="title"
                            value="<?= escape(
                                $_POST['title'] ?? ''
                            ) ?>"
                            placeholder="Enter a descriptive post title"
                            maxlength="255"
                            required
                        >

                        <p class="field-help">
                            Choose a short title that explains the article.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="content">
                            Post content
                        </label>

                        <textarea
                            id="content"
                            name="content"
                            rows="14"
                            placeholder="Write your blog post here..."
                            required
                        ><?= escape(
                            $_POST['content'] ?? ''
                        ) ?></textarea>

                        <p class="field-help">
                            Separate your ideas into clear paragraphs.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="blog_image">
                            Featured image
                        </label>

                        <div class="image-upload-area">
                            <input
                                id="blog_image"
                                type="file"
                                name="blog_image"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <div class="upload-message">
                                <span
                                    class="upload-icon"
                                    aria-hidden="true"
                                >
                                    +
                                </span>

                                <strong>
                                    Choose a featured image
                                </strong>

                                <span>
                                    JPG, PNG or WebP — maximum 5 MB
                                </span>
                            </div>
                        </div>

                        <div
                            id="image-preview-container"
                            class="image-preview-container"
                            hidden
                        >
                            <img
                                id="image-preview"
                                class="image-preview"
                                src=""
                                alt="Selected image preview"
                            >

                            <button
                                type="button"
                                id="remove-image"
                                class="remove-image-button"
                            >
                                Remove Image
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a
                            href="blogposts.php"
                            class="cancel-button"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn-submit"
                        >
                            Publish Post
                        </button>
                    </div>
                </form>
            </section>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>

    <script>
        const imageInput =
            document.querySelector("#blog_image");

        const previewContainer =
            document.querySelector(
                "#image-preview-container"
            );

        const previewImage =
            document.querySelector("#image-preview");

        const removeButton =
            document.querySelector("#remove-image");

        let previewUrl = null;

        imageInput?.addEventListener("change", () => {
            const file = imageInput.files[0];

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            if (!file) {
                previewContainer.hidden = true;
                previewImage.removeAttribute("src");
                return;
            }

            previewUrl = URL.createObjectURL(file);
            previewImage.src = previewUrl;
            previewContainer.hidden = false;
        });

        removeButton?.addEventListener("click", () => {
            imageInput.value = "";

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }

            previewImage.removeAttribute("src");
            previewContainer.hidden = true;
        });
    </script>
</body>
</html>

<?php

session_start();

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/blog-image.php';

$error = '';
$post = false;
$imagePath = null;
$commentError = '';
$comments = [];

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

$isCommentSubmission =
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'add_comment';

/*
 * ADD A PUBLIC VISITOR COMMENT
 */
if ($isCommentSubmission) {
    $commentPostId = positiveInputId(INPUT_GET, 'id');
    $submittedToken = $_POST['csrf_token'] ?? '';
    $commenterName = sanitizePlainText($_POST['commenter_name'] ?? '');
    $commentText = sanitizePlainText($_POST['comment_text'] ?? '');
    $captchaAnswer = strtoupper(sanitizePlainText($_POST['captcha_answer'] ?? ''));
    $expectedCaptcha = $_SESSION['comment_captcha'] ?? '';
    $captchaCreatedAt = (int) ($_SESSION['comment_captcha_created_at'] ?? 0);

    // A CAPTCHA is single-use, whether the answer succeeds or fails.
    unset($_SESSION['comment_captcha'], $_SESSION['comment_captcha_created_at']);

    if ($commentPostId === null) {
        $commentError = 'The selected blog post is invalid.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $commentError = 'Your session expired. Refresh the page and try again.';
    } elseif ($commenterName === '') {
        $commentError = 'Enter your name before posting a comment.';
    } elseif (mb_strlen($commenterName) > 60) {
        $commentError = 'Your name must be 60 characters or fewer.';
    } elseif (containsInvalidControlCharacters($commenterName)) {
        $commentError = 'Your name contains invalid characters.';
    } elseif ($commentText === '') {
        $commentError = 'Enter a comment before submitting.';
    } elseif (mb_strlen($commentText) > 1200) {
        $commentError = 'Your comment must be 1,200 characters or fewer.';
    } elseif (containsInvalidControlCharacters($commentText)) {
        $commentError = 'Your comment contains invalid characters.';
    } elseif ($captchaAnswer === '') {
        $commentError = 'Enter the verification code shown in the image.';
    } elseif (
        $expectedCaptcha === '' ||
        $captchaCreatedAt < time() - 900 ||
        !hash_equals($expectedCaptcha, $captchaAnswer)
    ) {
        $commentError = 'The verification code was incorrect. Please try the new code below.';
    } else {
        $statement = $db->prepare(
            'INSERT INTO comments
                (comment_text, commenter_name, UserID, PageID)
             SELECT :comment_text, :commenter_name, NULL, blogID
             FROM blogspots
             WHERE blogID = :blogID'
        );

        $statement->execute([
            ':comment_text' => $commentText,
            ':commenter_name' => $commenterName,
            ':blogID' => $commentPostId
        ]);

        if ($statement->rowCount() === 1) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header(
                'Location: post.php?id=' .
                urlencode((string) $commentPostId) .
                '&comment=posted#comments'
            );
            exit;
        }

        $commentError = 'That blog post could not be found.';
    }
}

/*
 * CREATE A NEW POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isCommentSubmission) {
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

    $title = sanitizePlainText($_POST['title'] ?? '');
    $content = sanitizePlainText($_POST['content'] ?? '');

    /*
     * This was saved during a successful login:
     * $_SESSION['user_id'] = $user['UserID'];
     */
    $authorId = $_SESSION['user_id'] ?? null;

    if ($error === '' && $title === '') {
        $error = 'A post title is required.';
    }

    if ($error === '' && mb_strlen($title) > 50) {
        $error = 'The post title must be 50 characters or fewer.';
    }

    if ($error === '' && containsInvalidControlCharacters($title)) {
        $error = 'The post title contains invalid characters.';
    }

    if ($error === '' && $content === '') {
        $error = 'Post content is required.';
    }

    if ($error === '' && mb_strlen($content) > 1200) {
        $error = 'Post content must be 1,200 characters or fewer.';
    }

    if ($error === '' && containsInvalidControlCharacters($content)) {
        $error = 'Post content contains invalid characters.';
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
    $postId = positiveInputId(INPUT_GET, 'id');

    if (
        $postId === null
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

    $commentStatement = $db->prepare(
        'SELECT
            comment_id,
            commenter_name,
            comment_text,
            timestamp_comment
         FROM comments
         WHERE PageID = :blogID
         ORDER BY timestamp_comment DESC, comment_id DESC'
    );
    $commentStatement->execute([':blogID' => $postId]);
    $comments = $commentStatement->fetchAll(PDO::FETCH_ASSOC);
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

    <link rel="stylesheet" href="post.css?v=20260724-1">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
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

                <?php if (blogImageExists($post['blog_image'] ?? null)): ?>
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

            <section class="comments-section" id="comments" aria-labelledby="comments-heading">
                <header class="comments-heading">
                    <div>
                        <p class="page-label">Join the Conversation</p>
                        <h2 id="comments-heading">
                            Comments <span>(<?= count($comments) ?>)</span>
                        </h2>
                    </div>
                </header>

                <?php if (($_GET['comment'] ?? '') === 'posted'): ?>
                    <div class="comment-success" role="status">
                        Your comment was posted successfully.
                    </div>
                <?php endif; ?>

                <?php if ($commentError !== ''): ?>
                    <div class="comment-error" role="alert">
                        <?= escape($commentError) ?>
                    </div>
                <?php endif; ?>

                <?php if ($comments): ?>
                    <ol class="comments-list">
                        <?php foreach ($comments as $comment): ?>
                            <li class="comment-card">
                                <div class="comment-avatar" aria-hidden="true">
                                    <?= escape(mb_strtoupper(mb_substr($comment['commenter_name'], 0, 1))) ?>
                                </div>

                                <div class="comment-body">
                                    <div class="comment-meta">
                                        <strong><?= escape($comment['commenter_name']) ?></strong>
                                        <time datetime="<?= escape($comment['timestamp_comment']) ?>">
                                            <?= escape(formatPostDate($comment['timestamp_comment'])) ?>
                                        </time>
                                    </div>

                                    <p><?= nl2br(escape($comment['comment_text'])) ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="no-comments">
                        No comments yet. Be the first to join the conversation.
                    </p>
                <?php endif; ?>

                <div class="comment-form-card">
                    <h3>Leave a Comment</h3>
                    <p>Your name and comment will be displayed publicly.</p>

                    <form method="post" action="post.php?id=<?= (int) $post['blogID'] ?>#comments" class="comment-form">
                        <input type="hidden" name="action" value="add_comment">
                        <input type="hidden" name="csrf_token" value="<?= escape($_SESSION['csrf_token']) ?>">

                        <div class="comment-field">
                            <label for="commenter_name">Your name</label>
                            <input
                                id="commenter_name"
                                name="commenter_name"
                                type="text"
                                maxlength="60"
                                value="<?= escape($_POST['commenter_name'] ?? '') ?>"
                                autocomplete="name"
                                required
                            >
                        </div>

                        <div class="comment-field">
                            <label for="comment_text">Comment</label>
                            <textarea
                                id="comment_text"
                                name="comment_text"
                                rows="6"
                                maxlength="1200"
                                required
                            ><?= escape($_POST['comment_text'] ?? '') ?></textarea>
                        </div>

                        <div class="comment-field captcha-field">
                            <label for="captcha_answer">Human verification</label>
                            <p class="captcha-instructions">Enter the five-character code shown below.</p>

                            <div class="captcha-row">
                                <img
                                    id="comment-captcha-image"
                                    src="captcha.php"
                                    alt="CAPTCHA verification code"
                                    width="210"
                                    height="62"
                                >

                                <button type="button" id="refresh-captcha" class="captcha-refresh">
                                    New code
                                </button>
                            </div>

                            <input
                                id="captcha_answer"
                                name="captcha_answer"
                                type="text"
                                maxlength="5"
                                minlength="5"
                                pattern="[A-Za-z0-9]{5}"
                                autocomplete="off"
                                autocapitalize="characters"
                                aria-describedby="captcha-help"
                                required
                            >
                            <p id="captcha-help" class="captcha-help">The code is not case-sensitive.</p>
                        </div>

                        <button type="submit" class="comment-submit">Post Comment</button>
                    </form>
                </div>

                <script>
                    const captchaImage = document.querySelector('#comment-captcha-image');
                    const captchaRefresh = document.querySelector('#refresh-captcha');
                    const captchaAnswer = document.querySelector('#captcha_answer');

                    captchaRefresh?.addEventListener('click', () => {
                        captchaImage.src = `captcha.php?refresh=${Date.now()}`;
                        captchaAnswer.value = '';
                        captchaAnswer.focus();
                    });
                </script>
            </section>
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
                            maxlength="50"
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
                            maxlength="1200"
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

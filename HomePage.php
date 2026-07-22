<?php
require_once __DIR__ . '/connect.php';

$latestPostQuery = "
    SELECT
        blogID,
        title,
        content,
        `timestamp`,
        blog_image
    FROM blogspots
    ORDER BY `timestamp` DESC, blogID DESC
    LIMIT 3
";

$latestPostStatement = $db->prepare($latestPostQuery);
$latestPostStatement->execute();

$latestPosts = $latestPostStatement->fetchAll(
    PDO::FETCH_ASSOC
);

function homeBlogExcerpt(
    string $content,
    int $length = 130
): string {
    $content = trim(strip_tags($content));

    if (mb_strlen($content) > $length) {
        return mb_substr($content, 0, $length) . '…';
    }

    return $content;
}

function homeBlogReadTime(string $content): int
{
    $words = str_word_count(strip_tags($content));

    return max(1, (int) ceil($words / 200));
}

function homeEscape(?string $value): string
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Love Story Planner</title>
    <link rel="stylesheet" href="HomePageStyle.css?v=20260722-2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:ital,wght@0,100..900;1,100..900&family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap" rel="stylesheet">
</head>

<body>

<?php include __DIR__ . '/includes/topbar.php'; ?>

<header class="header-banner" id="home">
    <div class="headerpic">
        <img
            src="photos//header.png"
            alt="The Love Story Planner Header Image"
            class="header-img"
        >
    </div>

<button
    type="button"
    class="btn-quote"
    onclick="document.querySelector('#quote-form').scrollIntoView({behavior: 'smooth' })">
    PLAN YOUR LOVE STORY WITH US
</button>


<section class="about" id="about">
    <div class="about-content">
        <div class="about-visual">
            <div class="about-image-frame">
                <img src="photos/about-photo.png" alt="Wedding planner meeting with a couple" class="about-image">
            </div>

        </div>

        <div class="about-text">
            <p class="about-label">Meet Your Planning Team</p>
            <h2>Your love story deserves a celebration that feels like you.</h2>

            <p class="about-lead">
                The Love Story Planner is a boutique wedding planning studio
                creating celebrations that feel personal, meaningful and
                completely unique to every couple.
            </p>

            <p>
                We handle the timelines, details and behind-the-scenes
                coordination so you can stay present, enjoy the journey and
                make lasting memories with the people you love.
            </p>

            <ul class="about-highlights" aria-label="What we provide">
                <li>Personalized planning</li>
                <li>Calm coordination</li>
                <li>Intentional design</li>
            </ul>

            <a href="#quote-form" class="about-cta">Start Planning With Us</a>
        </div>
    </div>
</section>

<section class="services" id="services">
    <h2>Our Services</h2>
    <div class="services-container">
        <div class="service-card">
            <div class="service-icon">
                <img src="photos/icon-fullplanning.png" alt="Full Planning icon">
            </div>
            <h3>Full Planning</h3>
            <p>Complete wedding planning from start to finish, including venue selection, vendor coordination, and design consultation.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <img src="photos/icon-dayof.png" alt="Day-of Coordination icon">
            </div>
            <h3>Day-of Coordination</h3>
            <p>Professional coordination on your wedding day to ensure everything runs smoothly and stress-free.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">
                <img src="photos/icon-design.png" alt="Design and Decor icon">
            </div>
            <h3>Design & Decor</h3>
            <p>Custom design concepts and decorative styling to bring your wedding vision to life.</p>
        </div>
    </div>
</section>


<section id="blog" class="home-blog">
    <div class="home-blog-heading">
        <p class="home-blog-label">From Our Blog</p>

        <h2>Helpful notes for the road to “I do.”</h2>

        <p class="home-blog-introduction">
            Wedding inspiration, thoughtful planning advice and
            meaningful ideas for your celebration.
        </p>
    </div>

    <?php if (!empty($latestPosts)): ?>
        <div class="home-blog-grid">
            <?php foreach ($latestPosts as $index => $post): ?>
                <?php
                    $readTime = homeBlogReadTime(
                        $post['content']
                    );

                    $cardNumber = str_pad(
                        (string) ($index + 1),
                        2,
                        '0',
                        STR_PAD_LEFT
                    );
                ?>

                <article class="home-blog-card">
                    <a
                        href="post.php?id=<?= (int) $post['blogID'] ?>"
                        class="home-blog-image-link"
                        aria-label="Read <?= homeEscape($post['title']) ?>"
                    >
                        <div
                            class="home-blog-image
                                   home-blog-image-<?= $index + 1 ?>"
                        >
                            <?php if (!empty($post['blog_image'])): ?>
                                <img
                                    src="<?= homeEscape(
                                        $post['blog_image']
                                    ) ?>"
                                    alt="<?= homeEscape(
                                        $post['title']
                                    ) ?>"
                                >
                            <?php else: ?>
                                <div
                                    class="decorative-arch"
                                    aria-hidden="true"
                                ></div>
                            <?php endif; ?>

                            <span class="image-category">
                                Journal
                            </span>

                            <span
                                class="card-number"
                                aria-hidden="true"
                            >
                                <?= $cardNumber ?>
                            </span>
                        </div>
                    </a>

                    <div class="home-blog-meta">
                        <span>Journal</span>

                        <span>
                            <?= $readTime ?> min read
                        </span>
                    </div>

                    <h3>
                        <a
                            href="post.php?id=<?= (int) $post['blogID'] ?>"
                        >
                            <?= homeEscape($post['title']) ?>
                        </a>
                    </h3>

                    <p class="home-blog-excerpt">
                        <?= homeEscape(
                            homeBlogExcerpt($post['content'])
                        ) ?>
                    </p>

                    <a
                        href="post.php?id=<?= (int) $post['blogID'] ?>"
                        class="home-blog-read"
                    >
                        Read article
                        <span aria-hidden="true">↗</span>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="all-posts-container">
            <a href="blogposts.php" class="all-posts-button">
                View All Blog Posts
            </a>
        </div>
    <?php else: ?>
        <div class="home-blog-empty">
            <h3>No blog posts yet</h3>

            <p>
                Our latest wedding planning articles will appear here.
            </p>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/site-footer.php'; ?>

</body>
</html>

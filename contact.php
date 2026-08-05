<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | The Love Story Planner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="contact.css?v=20260731-1">
    <link rel="stylesheet" href="includes/shared.css?v=20260805-1">
</head>
<body>
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <main class="contact-page">
        <section class="contact-hero">
            <div class="contact-introduction">
                <p class="contact-label">Let’s Connect</p>
                <h1>We’d love to hear your love story.</h1>
                <p>
                    Whether you’re ready to begin planning, exploring our
                    services or simply have a question, getting in touch is
                    easy. Tell us what you’re dreaming about and we’ll help
                    you take the next step.
                </p>
            </div>

            <div class="contact-logo-card" aria-hidden="true">
                <img src="photos/logo.png" alt="">
            </div>
        </section>

        <section class="contact-details" aria-labelledby="contact-information-heading">
            <header class="contact-details-heading">
                <p class="contact-label">Contact Details</p>
                <h2 id="contact-information-heading">Contact Information</h2>
            </header>

            <div class="contact-grid">
                <article class="contact-card">
                    <span class="contact-card-number" aria-hidden="true">01</span>
                    <h3>Email</h3>
                    <a href="mailto:info@thelovestoryplanner.com">
                        info@thelovestoryplanner.com
                    </a>
                </article>

                <article class="contact-card">
                    <span class="contact-card-number" aria-hidden="true">02</span>
                    <h3>Phone</h3>
                    <a href="tel:+15551234567">+1 (555) 123-4567</a>
                </article>

                <article class="contact-card">
                    <span class="contact-card-number" aria-hidden="true">03</span>
                    <h3>Visit</h3>
                    <address>123 Wedding Lane<br>Winnipeg, Manitoba</address>
                </article>

                <article class="contact-card">
                    <span class="contact-card-number" aria-hidden="true">04</span>
                    <h3>Business Hours</h3>
                    <p>Monday–Friday<br>9:00 a.m.–5:00 p.m.</p>
                </article>
            </div>
        </section>

        <section class="contact-social" aria-labelledby="social-heading">
            <div>
                <p class="contact-label">Follow Along</p>
                <h2 id="social-heading">Find daily wedding inspiration.</h2>
            </div>

            <nav class="social-links" aria-label="Social media">
                <a href="https://www.facebook.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Facebook <span aria-hidden="true">↗</span></a>
                <a href="https://www.instagram.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a>
                <a href="https://www.pinterest.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Pinterest <span aria-hidden="true">↗</span></a>
            </nav>
        </section>
    </main>

    <?php include __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>

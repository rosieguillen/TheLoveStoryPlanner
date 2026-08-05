<style>
    .site-footer {
        position: relative;
        overflow: hidden;
        padding: 76px 40px 24px;
        border-top: 1px solid #efd8df;
        background: linear-gradient(145deg, #fffafa 0%, #fff1f5 100%);
        color: #444142;
        font-family: "Elms Sans", sans-serif;
    }

    .site-footer::before {
        position: absolute;
        top: -150px;
        right: -100px;
        width: 320px;
        height: 320px;
        border: 1px solid rgba(249, 147, 178, 0.28);
        border-radius: 50%;
        content: "";
    }

    .footer-main {
        position: relative;
        display: grid;
        grid-template-columns: minmax(260px, 1.5fr) repeat(3, minmax(140px, 0.65fr));
        gap: 55px;
        width: min(1180px, 100%);
        margin: 0 auto;
        padding-bottom: 52px;
    }

    .footer-brand {
        max-width: 390px;
    }

    .footer-logo {
        display: block;
        width: min(290px, 100%);
        height: auto;
        margin-bottom: 21px;
    }

    .footer-brand p {
        margin: 0;
        color: #706b6d;
        font-size: 15px;
        line-height: 1.8;
    }

    .footer-column h2 {
        margin: 7px 0 20px;
        color: #d96f91;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .footer-links {
        display: grid;
        gap: 13px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .footer-links a {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        color: #444142;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.45;
        text-decoration: none;
        transition: color 0.2s ease, transform 0.2s ease;
    }

    .footer-links a:hover {
        color: #d96f91;
        transform: translateX(3px);
    }

    .footer-links a:focus-visible {
        border-radius: 3px;
        outline: 3px solid rgba(249, 147, 178, 0.4);
        outline-offset: 4px;
    }

    .footer-social a::after {
        margin-left: 8px;
        content: "\2197";
        color: #d96f91;
        font-size: 13px;
    }

    .footer-bottom {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        width: min(1180px, 100%);
        margin: 0 auto;
        padding-top: 22px;
        border-top: 1px solid #e9d4da;
    }

    .footer-bottom p {
        margin: 0;
        color: #8c8588;
        font-size: 12px;
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .footer-main {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 42px 55px;
        }

        .footer-brand {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 560px) {
        .site-footer {
            padding: 58px 22px 22px;
        }

        .footer-main {
            grid-template-columns: 1fr;
            gap: 34px;
            padding-bottom: 40px;
        }

        .footer-brand {
            grid-column: auto;
        }

        .footer-bottom {
            align-items: flex-start;
            flex-direction: column;
            gap: 7px;
        }
    }
</style>

<footer class="site-footer">
    <div class="footer-main">
        <div class="footer-brand">
            <a href="HomePage.php" aria-label="The Love Story Planner home">
                <img
                    src="photos/logo-long.png"
                    alt="The Love Story Planner"
                    class="footer-logo"
                >
            </a>
            <p>
                Thoughtful wedding planning for celebrations that feel
                personal, beautiful and completely yours.
            </p>
        </div>

        <nav class="footer-column" aria-label="Footer navigation">
            <h2>Explore</h2>
            <ul class="footer-links">
                <li><a href="HomePage.php">Home</a></li>
                <li><a href="HomePage.php#about">About Us</a></li>
                <li><a href="HomePage.php#services">Services</a></li>
                <li><a href="blogposts.php">Journal</a></li>
            </ul>
        </nav>

        <div class="footer-column">
            <h2>Contact</h2>
            <ul class="footer-links">
                <li><a href="contact.php">Contact Us</a></li>
                <li><a href="mailto:info@thelovestoryplanner.com">Email Us</a></li>
                <li><a href="tel:+15551234567">(555) 123-4567</a></li>
                <li><a href="contact.php">Winnipeg, Manitoba</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h2>Follow</h2>
            <ul class="footer-links footer-social">
                <li><a href="https://www.instagram.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Instagram</a></li>
                <li><a href="https://www.facebook.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Facebook</a></li>
                <li><a href="https://www.pinterest.com/thelovestoryplanner" target="_blank" rel="noopener noreferrer">Pinterest</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> The Love Story Planner. All rights reserved.</p>
        <p>Images generated with ChatGPT by OpenAI, July 2026.</p>
    </div>
</footer>


<style>

    html {
    margin: 0;
    font-family: "Elms Sans", sans-serif;
    background-color: rgb(255, 255, 255);
}

    .topbar {
        position: sticky;
        top: 0;
        z-index: 1000;

        display: flex;
        align-items: center;
        justify-content: space-between;

        width: 100%;
        padding: 20px 60px;
        box-sizing: border-box;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .topbar .logo {
        display: block;
        width: 300px;
        max-width: 100%;
        height: auto;
    }

    .topbar nav {
        display: flex;
        align-items: center;
        gap: 45px;
    }

    .topbar nav a {
        color: #444142;
        font-family: "Elms Sans", sans-serif;
        font-size: 18px;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .topbar nav a:hover {
        color: #f993b2;
        text-decoration: underline;
        text-decoration-thickness: 3px;
        text-underline-offset: 12px;
    }

    @media (max-width: 900px) {
        .topbar {
            flex-direction: column;
            gap: 20px;
            padding: 18px 24px;
        }

        .topbar .logo {
            width: 220px;
        }

        .topbar nav {
            justify-content: center;
            flex-wrap: wrap;
            gap: 18px 25px;
        }

        .topbar nav a {
            font-size: 15px;
        }
    }
</style>

<div class="topbar">
    <a href="/HomePage.php">
        <img
            src="photos/logo-long.png"
            alt="The Love Story Planner Logo"
            class="logo"
        >
    </a>

<nav aria-label="Main navigation">
    <a href="HomePage.php">Home</a>
    <a href="HomePage.php#about">About Us</a>
    <a href="blogposts.php">Blog</a>
    <a href="connect.php">Contact</a>
    <a href="HomePage.php#services">Services</a>
    <a href="authenticate.php">Admin Area</a>
</nav>
</div>

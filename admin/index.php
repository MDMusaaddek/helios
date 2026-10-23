
<?php
session_start();

if (empty($_SESSION['helios_admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HELIOS Admin Dashboard</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f7f1e8;
            color: #302017;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 6%;
            background: #fffdf9;
            border-bottom: 1px solid #e8ded2;
        }

        .logo {
            font-family: Georgia, serif;
            font-size: 27px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #4b3024;
        }

        .logout {
            padding: 10px 18px;
            background: #4b3024;
            color: white;
            text-decoration: none;
            font-size: 13px;
        }

        main {
            max-width: 1100px;
            margin: 45px auto;
            padding: 0 22px;
        }

        h1 {
            font-family: Georgia, serif;
            font-size: 34px;
            font-weight: normal;
            margin-bottom: 10px;
        }

        .welcome {
            color: #71645b;
            margin-bottom: 30px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 22px;
        }

        .card {
            padding: 28px;
            background: #fffdf9;
            border: 1px solid #e8ded2;
        }

        .card h2 {
            font-family: Georgia, serif;
            font-size: 21px;
            margin-bottom: 12px;
        }

        .card p {
            color: #71645b;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .card a {
            display: inline-block;
            padding: 11px 16px;
            background: #4b3024;
            color: white;
            text-decoration: none;
            font-size: 12px;
        }

        .card a:hover,
        .logout:hover {
            background: #9a734b;
        }

        footer {
            padding: 25px;
            text-align: center;
            color: #71645b;
            font-size: 12px;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">HELIOS</div>

    <a href="logout.php" class="logout">LOG OUT</a>
</header>

<main>
    <h1>Admin Dashboard</h1>

    <p class="welcome">
        Welcome back, <?= htmlspecialchars(
            $_SESSION['helios_admin_username'] ?? 'Admin',
            ENT_QUOTES,
            'UTF-8'
        ) ?>.
        Manage your HELIOS fashion store here.
    </p>

    <div class="dashboard-grid">

        <section class="card">
            <h2>Products</h2>
            <p>Add new products, upload photos, and update prices.</p>
            <a href="products.php">MANAGE PRODUCTS</a>
        </section>

        <section class="card">
            <h2>Storefront</h2>
            <p>View your public HELIOS website.</p>
            <a href="/helios/" target="_blank">OPEN WEBSITE</a>
        </section>

        <section class="card">
            <h2>Store Settings</h2>
            <p>Store management features can be added here.</p>
            <a href="/helios/">VIEW STORE</a>
        </section>

    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> HELIOS Admin Panel
</footer>

</body>
</html>

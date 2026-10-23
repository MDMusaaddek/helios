
<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
     * Temporary login for local development.
     * Replace these credentials with a database-backed admin account
     * before putting the website online.
     */
    $admin_username = 'admin';
    $admin_password = 'Helios@2026';

    if (
        hash_equals($admin_username, $username) &&
        hash_equals($admin_password, $password)
    ) {
        session_regenerate_id(true);
        $_SESSION['helios_admin_logged_in'] = true;
        $_SESSION['helios_admin_username'] = $username;

        header('Location: index.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | HELIOS</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f7f1e8;
            color: #302017;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 42px 35px;
            background: #fffdf9;
            border: 1px solid #e8ded2;
            box-shadow: 0 15px 45px rgba(48, 32, 23, 0.08);
        }

        .logo {
            margin-bottom: 8px;
            font-family: Georgia, serif;
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 4px;
            text-align: center;
            color: #4b3024;
        }

        .subtitle {
            margin-bottom: 30px;
            color: #817166;
            font-size: 13px;
            text-align: center;
        }

        h1 {
            margin-bottom: 22px;
            font-family: Georgia, serif;
            font-size: 25px;
            font-weight: normal;
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: bold;
        }

        input {
            width: 100%;
            margin-bottom: 20px;
            padding: 13px;
            border: 1px solid #d9cbbd;
            background: white;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: #9a734b;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            background: #4b3024;
            color: white;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
            cursor: pointer;
        }

        button:hover {
            background: #9a734b;
        }

        .error {
            margin-bottom: 18px;
            padding: 10px;
            background: #fbe9e7;
            color: #a12a20;
            font-size: 13px;
        }

        .back-link {
            display: block;
            margin-top: 22px;
            color: #79583f;
            font-size: 13px;
            text-align: center;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 22px;
            }
        }
    </style>
</head>
<body>

    <main class="login-card">
        <div class="logo">HELIOS</div>
        <p class="subtitle">TIMELESS ELEGANCE • ADMINISTRATION</p>

        <h1>Admin Login</h1>

        <?php if ($error !== ''): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">SIGN IN</button>
        </form>

        <a href="/helios/" class="back-link">
            ← Back to HELIOS Store
        </a>
    </main>

</body>
</html>

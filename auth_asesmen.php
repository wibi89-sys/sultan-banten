<?php
session_start();

$valid_username = 'asesor';
$valid_password = 'PasBanten123!';

if (isset($_GET['logout'])) {
    unset($_SESSION['asesor_logged_in']);
    header("Location: asesmen.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_asesor'])) {
    if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
        $_SESSION['asesor_logged_in'] = true;
    } else {
        $login_error = "Username atau Password salah!";
    }
}

if (empty($_SESSION['asesor_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Asesor - Sultan Banten</title>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #e9ecef; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; box-sizing: border-box; }
            .login-box h2 { text-align: center; margin-bottom: 30px; color: #1F3A2E; margin-top: 0; font-size: 24px; }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
            .form-group input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
            .form-group input:focus { border-color: #2F5D46; outline: none; box-shadow: 0 0 0 3px rgba(47, 93, 70, 0.2); }
            .btn-login { width: 100%; padding: 12px; background: #2F5D46; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
            .btn-login:hover { background: #1F3A2E; }
            .error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid #ffcdd2; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #6c757d; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Login Asesor Khusus</h2>
            <?php if(!empty($login_error)) echo "<div class='error'>$login_error</div>"; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Masukkan username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <button type="submit" name="login_asesor" class="btn-login">Masuk ke Asesmen</button>
            </form>
            <div class="footer">
                &copy; <?= date('Y') ?> Kanwil Kemenkumham Banten<br>Portal Khusus Rehabilitasi
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

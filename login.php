<?php
require_once __DIR__ . '/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter your username/email and password.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1><a href="login.php" class="brand"><img src="logo-dark.svg" alt="Shelfmark - A Reading Log"></a></h1>
        <h2>Log in</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log In</button>
        </form>

        <p>Don't have an account? <a href="register.php">Sign up</a></p>
    </div>
</body>
</html>
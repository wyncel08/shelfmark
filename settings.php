<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$userStmt = $pdo->prepare('SELECT username, email, password FROM users WHERE id = ?');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
if (!$user) {
    header('Location: logout.php');
    exit;
}

$profileErrors = [];
$passwordErrors = [];
$message = $_GET['msg'] ?? '';
$profileValues = ['username' => $user['username'], 'email' => $user['email']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $profileValues['username'] = trim($_POST['username'] ?? '');
        $profileValues['email'] = trim($_POST['email'] ?? '');

        if ($profileValues['username'] === '') {
            $profileErrors['username'] = 'Username is required.';
        } elseif (strlen($profileValues['username']) > 50) {
            $profileErrors['username'] = 'Username must be 50 characters or fewer.';
        }
        if (!filter_var($profileValues['email'], FILTER_VALIDATE_EMAIL)) {
            $profileErrors['email'] = 'Enter a valid email address.';
        } elseif (strlen($profileValues['email']) > 100) {
            $profileErrors['email'] = 'Email must be 100 characters or fewer.';
        }

        if (empty($profileErrors)) {
            $duplicateStmt = $pdo->prepare(
                'SELECT username, email FROM users WHERE id <> ? AND (username = ? OR email = ?)'
            );
            $duplicateStmt->execute([$userId, $profileValues['username'], $profileValues['email']]);
            $duplicate = $duplicateStmt->fetch();
            if ($duplicate) {
                if (strcasecmp($duplicate['username'], $profileValues['username']) === 0) {
                    $profileErrors['username'] = 'That username is already taken.';
                }
                if (strcasecmp($duplicate['email'], $profileValues['email']) === 0) {
                    $profileErrors['email'] = 'That email address is already in use.';
                }
            }
        }

        if (empty($profileErrors)) {
            $updateStmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
            $updateStmt->execute([$profileValues['username'], $profileValues['email'], $userId]);
            $_SESSION['username'] = $profileValues['username'];
            header('Location: settings.php?msg=' . urlencode('Profile updated.'));
            exit;
        }
    } elseif ($action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($currentPassword, $user['password'])) {
            $passwordErrors['current_password'] = 'Current password is incorrect.';
        }
        if (strlen($newPassword) < 8) {
            $passwordErrors['new_password'] = 'New password must be at least 8 characters.';
        }
        if ($newPassword !== $confirmPassword) {
            $passwordErrors['confirm_password'] = 'Passwords do not match.';
        }

        if (empty($passwordErrors)) {
            $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateStmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            header('Location: settings.php?msg=' . urlencode('Password updated.'));
            exit;
        }
    } elseif ($action === 'delete_account') {
        if (($_POST['delete_confirmation'] ?? '') !== 'DELETE') {
            $message = 'Type DELETE exactly to confirm account deletion.';
        } else {
            $avatarStmt = $pdo->prepare('SELECT avatar FROM users WHERE id = ?');
            $avatarStmt->execute([$userId]);
            $avatar = $avatarStmt->fetchColumn();

            $deleteStmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $deleteStmt->execute([$userId]);
            if ($avatar && file_exists(__DIR__ . '/uploads/' . $avatar)) {
                unlink(__DIR__ . '/uploads/' . $avatar);
            }
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: login.php?msg=' . urlencode('Your account has been deleted.'));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
    </header>
    <main class="container settings-page">
        <a href="index.php" class="back-link">← Back to My Library</a>
        <div class="settings-heading">
            <p class="reader-kicker">Your account</p>
            <h1>Account Settings</h1>
            <p>Manage your Shelfmark identity and sign-in details.</p>
        </div>

        <?php if ($message): ?><div class="alert <?= str_contains($message, 'DELETE') ? 'alert-error' : 'alert-success' ?>"><?= e($message) ?></div><?php endif; ?>

        <section class="settings-panel">
            <div class="settings-panel-heading"><div><p class="reader-kicker">Profile info</p><h2>How you appear</h2></div></div>
            <form method="POST" action="settings.php" class="settings-form">
                <input type="hidden" name="action" value="profile">
                <div class="settings-field">
                    <label for="settings-username">Display name / username</label>
                    <input type="text" id="settings-username" name="username" value="<?= e($profileValues['username']) ?>" maxlength="50">
                    <?php if (isset($profileErrors['username'])): ?><p class="field-error"><?= e($profileErrors['username']) ?></p><?php endif; ?>
                </div>
                <div class="settings-field">
                    <label for="settings-email">Email address</label>
                    <input type="email" id="settings-email" name="email" value="<?= e($profileValues['email']) ?>" maxlength="100">
                    <?php if (isset($profileErrors['email'])): ?><p class="field-error"><?= e($profileErrors['email']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </section>

        <section class="settings-panel">
            <div class="settings-panel-heading"><div><p class="reader-kicker">Change password</p><h2>Keep your account secure</h2></div></div>
            <form method="POST" action="settings.php" class="settings-form">
                <input type="hidden" name="action" value="password">
                <div class="settings-field">
                    <label for="current-password">Current password</label>
                    <input type="password" id="current-password" name="current_password">
                    <?php if (isset($passwordErrors['current_password'])): ?><p class="field-error"><?= e($passwordErrors['current_password']) ?></p><?php endif; ?>
                </div>
                <div class="settings-field">
                    <label for="new-password">New password</label>
                    <input type="password" id="new-password" name="new_password" minlength="8">
                    <?php if (isset($passwordErrors['new_password'])): ?><p class="field-error"><?= e($passwordErrors['new_password']) ?></p><?php endif; ?>
                </div>
                <div class="settings-field">
                    <label for="confirm-password">Confirm new password</label>
                    <input type="password" id="confirm-password" name="confirm_password" minlength="8">
                    <?php if (isset($passwordErrors['confirm_password'])): ?><p class="field-error"><?= e($passwordErrors['confirm_password']) ?></p><?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Update password</button>
            </form>
        </section>

        <section class="settings-panel danger-panel">
            <div class="settings-panel-heading"><div><p class="reader-kicker">Danger zone</p><h2>Delete account</h2></div></div>
            <p>This permanently deletes your profile, books, and annotations. This cannot be undone.</p>
            <form method="POST" action="settings.php" class="settings-form delete-form">
                <input type="hidden" name="action" value="delete_account">
                <label for="delete-confirmation">Type DELETE to confirm</label>
                <input type="text" id="delete-confirmation" name="delete_confirmation" autocomplete="off">
                <button type="submit" class="btn btn-danger">Delete my account</button>
            </form>
        </section>
    </main>
</body>
</html>

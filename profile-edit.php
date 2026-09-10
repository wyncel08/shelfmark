<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$userStmt = $pdo->prepare('SELECT username, bio, avatar FROM users WHERE id = ?');
$userStmt->execute([currentUserId()]);
$profile = $userStmt->fetch();
if (!$profile) {
    header('Location: index.php');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) > 50) {
        $errors[] = 'Username must be 50 characters or fewer.';
    }

    if (empty($errors)) {
        $duplicateStmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
        $duplicateStmt->execute([$username, currentUserId()]);
        if ($duplicateStmt->fetch()) {
            $errors[] = 'That username is already taken.';
        }
    }

    $avatar = $profile['avatar'];
    if (empty($errors)) {
        try {
            $avatar = handleCoverUpload('avatar', $profile['avatar']);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $updateStmt = $pdo->prepare('UPDATE users SET username = ?, bio = ?, avatar = ? WHERE id = ?');
        $updateStmt->execute([$username, $bio ?: null, $avatar, currentUserId()]);
        $_SESSION['username'] = $username;
        header('Location: profile.php');
        exit;
    }

    $profile['username'] = $username;
    $profile['bio'] = $bio;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
    </header>
    <main class="container container-narrow profile-page">
        <div class="page-heading">
            <div><p class="reader-kicker">Your reading identity</p><h1>Edit profile</h1></div>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="profile-edit.php" enctype="multipart/form-data" class="book-form profile-form">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($profile['username']) ?>" maxlength="50" required>
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="5" maxlength="500" placeholder="Tell us about your reading taste..."><?= e($profile['bio']) ?></textarea>
            <label for="avatar">Profile photo</label>
            <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif">
            <?php if (!empty($profile['avatar'])): ?><img src="uploads/<?= e($profile['avatar']) ?>" alt="Current profile photo" class="profile-edit-avatar"><?php endif; ?>
            <div class="profile-actions profile-edit-actions">
                <button type="submit" class="btn btn-primary">Save profile</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </main>
</body>
</html>

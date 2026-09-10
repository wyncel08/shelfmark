<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$userStmt = $pdo->prepare('SELECT username, bio, avatar, created_at FROM users WHERE id = ?');
$userStmt->execute([currentUserId()]);
$profile = $userStmt->fetch() ?: [
    'username' => $_SESSION['username'] ?? 'Reader', 'bio' => null, 'avatar' => null, 'created_at' => null,
];
$statsStmt = $pdo->prepare(
    'SELECT COUNT(*) AS total_books, SUM(status = "currently_reading") AS currently_reading,
        SUM(status = "finished") AS finished, SUM(is_favorite = 1) AS favorites
     FROM books WHERE user_id = ?'
);
$statsStmt->execute([currentUserId()]);
$stats = $statsStmt->fetch() ?: [];
$username = $profile['username'] ?: ($_SESSION['username'] ?? 'Reader');
$initial = strtoupper(mb_substr($username, 0, 1));
$readingSince = !empty($profile['created_at']) ? date('F Y', strtotime($profile['created_at'])) : 'your first entry';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
    </header>
    <main class="container container-narrow profile-page">
        <div class="profile-header">
            <?php if (!empty($profile['avatar'])): ?>
                <img src="uploads/<?= e($profile['avatar']) ?>" alt="<?= e($username) ?> avatar" class="profile-avatar profile-avatar-image">
            <?php else: ?>
                <div class="profile-avatar" aria-hidden="true"><?= e($initial) ?></div>
            <?php endif; ?>
            <div><h1><?= e($username) ?></h1><p class="profile-since">Reading since <?= e($readingSince) ?></p></div>
        </div>
        <section class="profile-bio" aria-labelledby="bio-heading">
            <h2 id="bio-heading">About this reader</h2>
            <p><?= $profile['bio'] ? nl2br(e($profile['bio'])) : 'Add a bio to tell others about your reading taste.' ?></p>
        </section>
        <section class="profile-stats" aria-label="Reading statistics">
            <div><strong><?= (int)($stats['total_books'] ?? 0) ?></strong><span>Total</span></div>
            <div><strong><?= (int)($stats['currently_reading'] ?? 0) ?></strong><span>Reading</span></div>
            <div><strong><?= (int)($stats['finished'] ?? 0) ?></strong><span>Finished</span></div>
            <div><strong><?= (int)($stats['favorites'] ?? 0) ?></strong><span>Favorites</span></div>
        </section>
        <div class="profile-actions">
            <a href="profile-edit.php" class="btn btn-primary">Edit profile</a>
            <a href="index.php" class="btn btn-secondary">Back to my library</a>
        </div>
    </main>
</body>
</html>

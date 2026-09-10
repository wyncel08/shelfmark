<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$types = annotationTypes();
$selectedType = $_GET['type'] ?? 'all';
if ($selectedType !== 'all' && !isset($types[$selectedType])) {
    $selectedType = 'all';
}

$query = 'SELECT a.*, b.title, b.author FROM annotations a JOIN books b ON b.id = a.book_id WHERE b.user_id = ?';
$params = [currentUserId()];
if ($selectedType !== 'all') {
    $query .= ' AND a.type = ?';
    $params[] = $selectedType;
}
$query .= ' ORDER BY a.created_at DESC, a.id DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$annotations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annotations - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav class="section-nav" aria-label="Library views">
            <a href="index.php">My Library</a>
            <a href="index.php?view=currently_reading">Currently Reading</a>
            <a href="index.php?view=want_to_read">Want to Read</a>
            <a href="index.php?view=finished">Finished</a>
            <a href="index.php?view=favorite">Favorites</a>
            <a href="insights.php">Insights</a>
            <a href="annotations.php" class="active" aria-current="page">Annotations</a>
        </nav>
        <nav class="account-nav" aria-label="Account actions">
            <a href="addabook.php" class="btn btn-primary">+ Add Book</a>
            <div class="profile-menu">
                <button type="button" class="profile-trigger" aria-expanded="false" aria-haspopup="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EDE6D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg> <?= e($_SESSION['username']) ?> <span aria-hidden="true">▾</span></button>
                <div class="profile-dropdown">
                    <a href="profile.php">My Profile</a>
                    <a href="settings.php">Account Settings</a>
                    <a href="logout.php">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container annotations-page">
        <div class="page-heading">
            <div>
                <p class="reader-kicker">Reading journal</p>
                <h1>Annotations</h1>
                <p>Your thoughts, theories, and favorite moments.</p>
            </div>
            <a href="addannotation.php" class="btn btn-primary">+ New Annotation</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success"><?= e($_GET['msg']) ?></div>
        <?php endif; ?>

        <nav class="annotation-filters" aria-label="Filter annotations">
            <a href="annotations.php" class="<?= $selectedType === 'all' ? 'active' : '' ?>">All</a>
            <?php foreach ($types as $typeKey => $type): ?>
                <a href="annotations.php?type=<?= e($typeKey) ?>" class="<?= $selectedType === $typeKey ? 'active' : '' ?>"><?= e($type['label']) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if (empty($annotations)): ?>
            <p class="empty-state annotation-empty">No annotations here yet. <a href="addannotation.php">Add your first annotation</a>.</p>
        <?php else: ?>
            <div class="annotation-list">
                <?php foreach ($annotations as $annotation): ?>
                    <?php $type = $types[$annotation['type']]; ?>
                    <article class="annotation-card">
                        <div class="annotation-card-header">
                            <div>
                                <a class="annotation-book" href="editbook.php?id=<?= (int)$annotation['book_id'] ?>"><?= e($annotation['title']) ?></a>
                                <?php if ($annotation['author']): ?><span class="annotation-author">by <?= e($annotation['author']) ?></span><?php endif; ?>
                            </div>
                            <form method="POST" action="toggleannotation.php" class="annotation-favorite-form">
                                <input type="hidden" name="id" value="<?= (int)$annotation['id'] ?>">
                                <input type="hidden" name="return_to" value="annotations.php<?= $selectedType !== 'all' ? '?type=' . e($selectedType) : '' ?>">
                                <button type="submit" class="annotation-favorite <?= !empty($annotation['is_favorite']) ? 'is-favorite' : '' ?>" aria-label="<?= !empty($annotation['is_favorite']) ? 'Remove favorite' : 'Favorite annotation' ?>"><?= !empty($annotation['is_favorite']) ? '&#9829;' : '&#9825;' ?></button>
                            </form>
                        </div>
                        <div class="annotation-meta">
                            <span class="annotation-type"><span aria-hidden="true"><?= e($type['icon']) ?></span> <?= e($type['label']) ?></span>
                            <?php if ($annotation['chapter_or_page']): ?><span><?= e($annotation['chapter_or_page']) ?></span><?php endif; ?>
                            <time datetime="<?= e($annotation['created_at']) ?>"><?= e(date('M j, Y', strtotime($annotation['created_at']))) ?></time>
                        </div>
                        <p class="annotation-content"><?= nl2br(e($annotation['content'])) ?></p>
                        <div class="book-actions">
                            <a href="editannotation.php?id=<?= (int)$annotation['id'] ?>">Edit</a>
                            <form method="POST" action="deleteannotation.php" onsubmit="return confirm('Delete this annotation?');" class="inline-form">
                                <input type="hidden" name="id" value="<?= (int)$annotation['id'] ?>">
                                <button type="submit" class="link-btn">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <script src="script.js"></script>
</body>
</html>

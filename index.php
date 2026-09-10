<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$views = [
    'all' => ['label' => 'My Library', 'title' => 'My Library'],
    'currently_reading' => ['label' => 'Currently Reading', 'title' => 'Currently Reading'],
    'want_to_read' => ['label' => 'Want to Read', 'title' => 'Want to Read'],
    'finished' => ['label' => 'Finished', 'title' => 'Finished'],
    'favorite' => ['label' => 'Favorites', 'title' => 'Favorites'],
];
$selectedView = $_GET['view'] ?? 'all';
if (!isset($views[$selectedView])) {
    $selectedView = 'all';
}

$query = 'SELECT * FROM books WHERE user_id = ?';
$params = [currentUserId()];
if ($selectedView === 'favorite') {
    $query .= ' AND is_favorite = 1';
} elseif ($selectedView !== 'all') {
    $query .= ' AND status = ?';
    $params[] = $selectedView;
}
$query .= ' ORDER BY updated_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$allBooks = $stmt->fetchAll();
$snapshotStmt = $pdo->prepare('SELECT COUNT(*) AS total_books, SUM(status = "currently_reading") AS currently_reading, SUM(status = "finished") AS finished, SUM(is_favorite = 1) AS favorites FROM books WHERE user_id = ?');
$snapshotStmt->execute([currentUserId()]);
$snapshot = $snapshotStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav class="section-nav" aria-label="Library views">
            <?php foreach ($views as $viewKey => $view): ?>
                <a href="index.php<?= $viewKey === 'all' ? '' : '?view=' . e($viewKey) ?>" class="<?= $selectedView === $viewKey ? 'active' : '' ?>" <?= $selectedView === $viewKey ? 'aria-current="page"' : '' ?>><?= e($view['label']) ?></a>
            <?php endforeach; ?>
            <a href="insights.php" class="<?= basename($_SERVER['PHP_SELF']) === 'insights.php' ? 'active' : '' ?>">Insights</a>
            <a href="annotations.php">Annotations</a>
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

    <main class="container library-layout">
        <aside class="reader-rail">
            <p class="reader-kicker">Private collection</p>
            <h2><?= e($_SESSION['username']) ?>'s shelf</h2>
            <p>A quiet place for the books you are reading, finishing, and saving for later.</p>
            <div class="shelf-snapshot">
                <p class="snapshot-heading">Shelf snapshot</p>
                <ul class="snapshot-list">
                    <li><span class="snapshot-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-library-big"><rect width="8" height="18" x="3" y="3" rx="1"/><path d="M7 3v18"/><path d="M20.4 18.9c.2.5-.1 1.1-.6 1.3l-1.9.7c-.5.2-1.1-.1-1.3-.6L11.1 5.1c-.2-.5.1-1.1.6-1.3l1.9-.7c.5-.2 1.1.1 1.3.6Z"/></svg></span><span>Total books</span><strong><?= (int)$snapshot['total_books'] ?></strong></li>
                    <li><span class="snapshot-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open-text"><path d="M12 5v16"/><path d="M16 13h2"/><path d="M16 9h2"/><path d="M20.001 19A2 2 0 0022 17V5a2 2 0 00-1.999-2L16 3.002A5 5 0 0012 5a5 5 0 00-4-2H4a2 2 0 00-2 2v12a2 2 0 001.999 2H8a5 5 0 014 2 5 5 0 014-2z"/><path d="M6 13h2"/><path d="M6 9h2"/></svg></span><span>Currently reading</span><strong><?= (int)$snapshot['currently_reading'] ?></strong></li>
                    <li><span class="snapshot-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check"><path d="M20 6 9 17l-5-5"/></svg></span><span>Finished</span><strong><?= (int)$snapshot['finished'] ?></strong></li>
                    <li><span class="snapshot-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg></span><span>Favorites</span><strong><?= (int)$snapshot['favorites'] ?></strong></li>
                </ul>
            </div>
            <div class="rail-decoration" aria-hidden="true">
                <svg viewBox="0 0 96 72" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 58h60M24 58V47h48v11M29 47V36h38v11M34 36V25h28v11M48 25V14"/><path d="M48 20c-8-7-15-5-18 0 7 1 13 2 18 0ZM48 20c8-7 15-5 18 0-7 1-13 2-18 0Z"/></svg>
            </div>
        </aside>
        <div class="library-content">
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= e($_GET['msg']) ?></div>
            <?php endif; ?>

            <?php if (empty($allBooks)): ?>
                <p class="empty-state"><?= $selectedView === 'all' ? "You haven't added any books yet." : 'No books match this view yet.' ?> <a href="addabook.php">Add a book</a>.</p>
            <?php endif; ?>

        <?php if (!empty($allBooks)): ?>
            <section class="book-section">
                <h2><?= e($views[$selectedView]['title']) ?> <span>(<?= count($allBooks) ?>)</span></h2>
                <div class="book-grid">
                    <?php foreach ($allBooks as $book): ?>
                        <div class="book-card">
                            <span class="book-status <?= e($book['status']) ?>"><?= e(statusLabel($book['status'])) ?></span>
                            <a href="editbook.php?id=<?= (int)$book['id'] ?>">
                                <?php if ($book['cover_image']): ?>
                                    <img src="uploads/<?= e($book['cover_image']) ?>" alt="<?= e($book['title']) ?> cover" class="book-cover">
                                <?php else: ?>
                                    <div class="book-cover book-cover-placeholder">No Cover</div>
                                <?php endif; ?>
                            </a>
                            <div class="book-info">
                                <h3><?= e($book['title']) ?></h3>
                                <?php if ($book['author']): ?>
                                    <p class="book-author"><?= e($book['author']) ?></p>
                                <?php endif; ?>
                                <?php if ($book['rating']): ?>
                                    <div class="stars" aria-label="<?= (int)$book['rating'] ?> out of 5 stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?= $i <= $book['rating'] ? 'filled' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($book['is_favorite'])): ?>
                                    <span class="favorite-mark" aria-label="Favorite">&#9733; Favorite</span>
                                <?php endif; ?>
                                <?php if ($book['description']): ?>
                                    <p class="book-desc"><?= e(mb_strimwidth($book['description'], 0, 100, '...')) ?></p>
                                <?php endif; ?>
                                <div class="book-actions">
                                    <a href="editbook.php?id=<?= (int)$book['id'] ?>">Edit</a>
                                    <form method="POST" action="deletebook.php" onsubmit="return confirm('Delete this book?');" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">
                                        <button type="submit" class="link-btn">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
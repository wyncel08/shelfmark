<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$types = annotationTypes();
$booksStmt = $pdo->prepare('SELECT id, title, author FROM books WHERE user_id = ? ORDER BY title');
$booksStmt->execute([currentUserId()]);
$books = $booksStmt->fetchAll();

$bookId = (int)($_GET['book_id'] ?? $_POST['book_id'] ?? 0);
$chapterOrPage = '';
$type = 'note';
$content = '';
$isFavorite = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $chapterOrPage = trim($_POST['chapter_or_page'] ?? '');
    $type = $_POST['type'] ?? 'note';
    $content = trim($_POST['content'] ?? '');
    $isFavorite = isset($_POST['is_favorite']) ? 1 : 0;

    $bookCheck = $pdo->prepare('SELECT id FROM books WHERE id = ? AND user_id = ?');
    $bookCheck->execute([$bookId, currentUserId()]);
    if (!$bookCheck->fetch()) {
        $errors[] = 'Please choose one of your books.';
    }
    if (!isset($types[$type])) {
        $errors[] = 'Invalid annotation type.';
    }
    if ($content === '') {
        $errors[] = 'Annotation content is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO annotations (book_id, chapter_or_page, type, content, is_favorite) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$bookId, $chapterOrPage ?: null, $type, $content, $isFavorite]);
        header('Location: annotations.php?msg=' . urlencode('Annotation saved!'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Annotation - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav><a href="annotations.php" class="btn btn-secondary">← Back to Annotations</a></nav>
    </header>
    <main class="container container-narrow">
        <h2>New Annotation</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="addannotation.php" class="book-form">
            <label for="book_id">Book</label>
            <select id="book_id" name="book_id" required>
                <option value="">Choose a book</option>
                <?php foreach ($books as $book): ?>
                    <option value="<?= (int)$book['id'] ?>" <?= $bookId === (int)$book['id'] ? 'selected' : '' ?>><?= e($book['title']) ?><?= $book['author'] ? ' — ' . e($book['author']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <label for="chapter_or_page">Chapter / Page</label>
            <input type="text" id="chapter_or_page" name="chapter_or_page" value="<?= e($chapterOrPage) ?>" placeholder="Chapter 12 or Page 183">
            <label for="type">Type</label>
            <select id="type" name="type">
                <?php foreach ($types as $typeKey => $typeOption): ?>
                    <option value="<?= e($typeKey) ?>" <?= $type === $typeKey ? 'selected' : '' ?>><?= e($typeOption['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="content">Annotation Content</label>
            <textarea id="content" name="content" rows="8" required><?= e($content) ?></textarea>
            <label class="checkbox-label"><input type="checkbox" name="is_favorite" value="1" <?= $isFavorite ? 'checked' : '' ?>> Favorite annotation</label>
            <button type="submit" class="btn btn-primary">Save Annotation</button>
        </form>
    </main>
</body>
</html>

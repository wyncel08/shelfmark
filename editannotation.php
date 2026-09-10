<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$types = annotationTypes();
$annotationId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT a.* FROM annotations a JOIN books b ON b.id = a.book_id WHERE a.id = ? AND b.user_id = ?');
$stmt->execute([$annotationId, currentUserId()]);
$annotation = $stmt->fetch();
if (!$annotation) {
    header('Location: annotations.php?msg=' . urlencode('Annotation not found.'));
    exit;
}

$booksStmt = $pdo->prepare('SELECT id, title, author FROM books WHERE user_id = ? ORDER BY title');
$booksStmt->execute([currentUserId()]);
$books = $booksStmt->fetchAll();
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
        $stmt = $pdo->prepare('UPDATE annotations SET book_id = ?, chapter_or_page = ?, type = ?, content = ?, is_favorite = ? WHERE id = ?');
        $stmt->execute([$bookId, $chapterOrPage ?: null, $type, $content, $isFavorite, $annotationId]);
        header('Location: annotations.php?msg=' . urlencode('Annotation updated!'));
        exit;
    }

    $annotation['book_id'] = $bookId;
    $annotation['chapter_or_page'] = $chapterOrPage;
    $annotation['type'] = $type;
    $annotation['content'] = $content;
    $annotation['is_favorite'] = $isFavorite;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Annotation - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav><a href="annotations.php" class="btn btn-secondary">← Back to Annotations</a></nav>
    </header>
    <main class="container container-narrow">
        <h2>Edit Annotation</h2>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" action="editannotation.php?id=<?= $annotationId ?>" class="book-form">
            <input type="hidden" name="id" value="<?= $annotationId ?>">
            <label for="book_id">Book</label>
            <select id="book_id" name="book_id" required>
                <?php foreach ($books as $book): ?>
                    <option value="<?= (int)$book['id'] ?>" <?= (int)$annotation['book_id'] === (int)$book['id'] ? 'selected' : '' ?>><?= e($book['title']) ?><?= $book['author'] ? ' — ' . e($book['author']) : '' ?></option>
                <?php endforeach; ?>
            </select>
            <label for="chapter_or_page">Chapter / Page</label>
            <input type="text" id="chapter_or_page" name="chapter_or_page" value="<?= e($annotation['chapter_or_page']) ?>">
            <label for="type">Type</label>
            <select id="type" name="type">
                <?php foreach ($types as $typeKey => $typeOption): ?>
                    <option value="<?= e($typeKey) ?>" <?= $annotation['type'] === $typeKey ? 'selected' : '' ?>><?= e($typeOption['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="content">Annotation Content</label>
            <textarea id="content" name="content" rows="8" required><?= e($annotation['content']) ?></textarea>
            <label class="checkbox-label"><input type="checkbox" name="is_favorite" value="1" <?= !empty($annotation['is_favorite']) ? 'checked' : '' ?>> Favorite annotation</label>
            <button type="submit" class="btn btn-primary">Save Annotation</button>
        </form>
    </main>
</body>
</html>

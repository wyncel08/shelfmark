<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$bookId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT * FROM books WHERE id = ? AND user_id = ?');
$stmt->execute([$bookId, currentUserId()]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php?msg=' . urlencode('Book not found.'));
    exit;
}

$annotationStmt = $pdo->prepare('SELECT * FROM annotations WHERE book_id = ? ORDER BY created_at DESC, id DESC');
$annotationStmt->execute([$bookId]);
$bookAnnotations = $annotationStmt->fetchAll();
$annotationTypes = annotationTypes();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $status = $_POST['status'] ?? 'want_to_read';
    $isFavorite = isset($_POST['is_favorite']) ? 1 : 0;
    $rating = $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = 'Title is required.';
    }
    if (!in_array($status, ['want_to_read', 'currently_reading', 'finished'], true)) {
        $errors[] = 'Invalid status.';
    }
    if ($rating !== null && ($rating < 1 || $rating > 5)) {
        $errors[] = 'Rating must be between 1 and 5.';
    }

    $coverFilename = $book['cover_image'];
    if (empty($errors)) {
        try {
            $coverFilename = handleCoverUpload('cover_image', $book['cover_image']);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'UPDATE books SET title = ?, author = ?, genre = ?, cover_image = ?, status = ?, is_favorite = ?, rating = ?, description = ?, date_finished = ?
             WHERE id = ? AND user_id = ?'
        );
        $dateFinished = $status === 'finished'
            ? ($book['status'] === 'finished' && !empty($book['date_finished']) ? $book['date_finished'] : date('Y-m-d'))
            : null;
        $stmt->execute([
            $title, $author ?: null, $genre ?: null, $coverFilename, $status, $isFavorite, $rating,
            $description ?: null, $dateFinished, $bookId, currentUserId(),
        ]);

        header('Location: index.php?msg=' . urlencode('Book updated!'));
        exit;
    }

    // Keep form values on error.
    $book['title'] = $title;
    $book['author'] = $author;
    $book['genre'] = $genre;
    $book['status'] = $status;
    $book['is_favorite'] = $isFavorite;
    $book['rating'] = $rating;
    $book['description'] = $description;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - Book Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav>
            <a href="index.php" class="btn btn-secondary">← Back to My Books</a>
        </nav>
    </header>

    <main class="container container-narrow">
        <h2>Edit Book</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="editbook.php?id=<?= (int)$book['id'] ?>" enctype="multipart/form-data" class="book-form">
            <input type="hidden" name="id" value="<?= (int)$book['id'] ?>">

            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="<?= e($book['title']) ?>" required>

            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="<?= e($book['author']) ?>">

            <label for="genre">Genre</label>
            <input type="text" id="genre" name="genre" value="<?= e($book['genre']) ?>" placeholder="Fantasy, memoir, history...">

            <?php if ($book['cover_image']): ?>
                <label>Current Cover</label>
                <img src="uploads/<?= e($book['cover_image']) ?>" alt="Current cover" class="cover-preview" style="display:block;">
            <?php endif; ?>

            <label for="cover_image">Replace Cover Image</label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*">
            <img id="cover-preview" class="cover-preview" style="display:none;" alt="Preview">

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="want_to_read" <?= $book['status'] === 'want_to_read' ? 'selected' : '' ?>>Want to Read</option>
                <option value="currently_reading" <?= $book['status'] === 'currently_reading' ? 'selected' : '' ?>>Currently Reading</option>
                <option value="finished" <?= $book['status'] === 'finished' ? 'selected' : '' ?>>Finished</option>
            </select>

            <label class="checkbox-label">
                <input type="checkbox" name="is_favorite" value="1" <?= !empty($book['is_favorite']) ? 'checked' : '' ?>>
                Favorite
            </label>

            <label>Rating</label>
            <div class="star-picker" data-input="rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star-pick <?= $book['rating'] && $i <= $book['rating'] ? 'filled' : '' ?>" data-value="<?= $i ?>">★</span>
                <?php endfor; ?>
                <button type="button" class="star-clear">Clear</button>
            </div>
            <input type="hidden" name="rating" id="rating" value="<?= e($book['rating']) ?>">

            <label for="description">Notes / Description</label>
            <textarea id="description" name="description" rows="5"><?= e($book['description']) ?></textarea>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
        <section class="book-annotations">
            <div class="subsection-heading">
                <h3>Annotations (<?= count($bookAnnotations) ?>)</h3>
                <a href="addannotation.php?book_id=<?= (int)$book['id'] ?>">+ Add Annotation</a>
            </div>
            <?php if (empty($bookAnnotations)): ?>
                <p class="empty-state">No annotations for this book yet.</p>
            <?php else: ?>
                <div class="book-annotation-list">
                    <?php foreach ($bookAnnotations as $annotation): ?>
                        <?php $annotationType = $annotationTypes[$annotation['type']]; ?>
                        <article class="book-annotation-item">
                            <div class="annotation-meta"><span class="annotation-type"><span aria-hidden="true"><?= e($annotationType['icon']) ?></span> <?= e($annotationType['label']) ?></span><?php if ($annotation['chapter_or_page']): ?><span><?= e($annotation['chapter_or_page']) ?></span><?php endif; ?></div>
                            <p class="annotation-content"><?= nl2br(e($annotation['content'])) ?></p>
                            <div class="book-actions"><a href="editannotation.php?id=<?= (int)$annotation['id'] ?>">Edit</a></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script src="script.js"></script>
</body>
</html>
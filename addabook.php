<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$errors = [];
$title = '';
$author = '';
$genre = '';
$status = 'want_to_read';
$isFavorite = 0;
$rating = '';
$description = '';

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

    $coverFilename = null;
    if (empty($errors)) {
        try {
            $coverFilename = handleCoverUpload('cover_image');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
              'INSERT INTO books (user_id, title, author, genre, cover_image, status, is_favorite, rating, description, date_added, date_finished)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?)' 
        );
           $stmt->execute([
               currentUserId(), $title, $author ?: null, $genre ?: null, $coverFilename, $status,
               $isFavorite, $rating, $description ?: null, $status === 'finished' ? date('Y-m-d') : null,
           ]);

        header('Location: index.php?msg=' . urlencode('Book added!'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - Book Tracker</title>
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
        <h2>Add a Book</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="addabook.php" enctype="multipart/form-data" class="book-form">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" value="<?= e($title) ?>" required>

            <label for="author">Author</label>
            <input type="text" id="author" name="author" value="<?= e($author) ?>">

            <label for="genre">Genre</label>
            <input type="text" id="genre" name="genre" value="<?= e($genre) ?>" placeholder="Fantasy, memoir, history...">

            <label for="cover_image">Cover Image</label>
            <input type="file" id="cover_image" name="cover_image" accept="image/*">
            <img id="cover-preview" class="cover-preview" style="display:none;" alt="Preview">

            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="want_to_read" <?= $status === 'want_to_read' ? 'selected' : '' ?>>Want to Read</option>
                <option value="currently_reading" <?= $status === 'currently_reading' ? 'selected' : '' ?>>Currently Reading</option>
                <option value="finished" <?= $status === 'finished' ? 'selected' : '' ?>>Finished</option>
            </select>

            <label class="checkbox-label">
                <input type="checkbox" name="is_favorite" value="1" <?= $isFavorite ? 'checked' : '' ?>>
                Favorite
            </label>

            <label>Rating</label>
            <div class="star-picker" data-input="rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star-pick <?= $rating && $i <= $rating ? 'filled' : '' ?>" data-value="<?= $i ?>">★</span>
                <?php endfor; ?>
                <button type="button" class="star-clear">Clear</button>
            </div>
            <input type="hidden" name="rating" id="rating" value="<?= e($rating) ?>">

            <label for="description">Notes / Description</label>
            <textarea id="description" name="description" rows="5" placeholder="What did you think? Any notes about this book..."><?= e($description) ?></textarea>

            <button type="submit" class="btn btn-primary">Add Book</button>
        </form>
    </main>

    <script src="script.js"></script>
</body>
</html>
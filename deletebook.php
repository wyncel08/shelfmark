<?php
require_once __DIR__ . '/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookId = (int)($_POST['id'] ?? 0);
    $pdo = getDbConnection();

    // Verify ownership before deleting.
    $stmt = $pdo->prepare('SELECT cover_image FROM books WHERE id = ? AND user_id = ?');
    $stmt->execute([$bookId, currentUserId()]);
    $book = $stmt->fetch();

    if ($book) {
        $stmt = $pdo->prepare('DELETE FROM books WHERE id = ? AND user_id = ?');
        $stmt->execute([$bookId, currentUserId()]);

        if ($book['cover_image']) {
            $path = __DIR__ . '/uploads/' . $book['cover_image'];
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

header('Location: index.php?msg=' . urlencode('Book deleted.'));
exit;
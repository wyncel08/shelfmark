<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$annotationId = (int)($_POST['id'] ?? 0);
$pdo = getDbConnection();
$stmt = $pdo->prepare('DELETE a FROM annotations a JOIN books b ON b.id = a.book_id WHERE a.id = ? AND b.user_id = ?');
$stmt->execute([$annotationId, currentUserId()]);
header('Location: annotations.php?msg=' . urlencode('Annotation deleted.'));
exit;

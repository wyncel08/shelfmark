<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$annotationId = (int)($_POST['id'] ?? 0);
$returnTo = $_POST['return_to'] ?? 'annotations.php';
if (!preg_match('/^annotations\.php(?:\?type=[a-z_]+)?$/', $returnTo)) {
    $returnTo = 'annotations.php';
}

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'UPDATE annotations a
     JOIN books b ON b.id = a.book_id
     SET a.is_favorite = NOT a.is_favorite
     WHERE a.id = ? AND b.user_id = ?'
);
$stmt->execute([$annotationId, currentUserId()]);
header('Location: ' . $returnTo);
exit;

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

/** Redirect to login if the user isn't authenticated. */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Handle an uploaded cover image. Returns the stored filename on success,
 * null if no file was uploaded, or throws an Exception on invalid input.
 */
function handleCoverUpload($fileField, $existingFile = null) {
    if (empty($_FILES[$fileField]['name'])) {
        return $existingFile; // Keep existing cover if none uploaded (used on edit).
    }

    $file = $_FILES[$fileField];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error uploading file.');
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        throw new Exception('Only JPG, PNG, GIF, and WEBP images are allowed.');
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        throw new Exception('Image must be smaller than 5MB.');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $uploadDir = __DIR__ . '/uploads/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
        throw new Exception('Failed to save uploaded file.');
    }

    // Clean up the old cover if we're replacing it.
    if ($existingFile && file_exists($uploadDir . $existingFile)) {
        unlink($uploadDir . $existingFile);
    }

    return $newName;
}

function statusLabel($status) {
    $labels = [
        'want_to_read' => 'Want to Read',
        'currently_reading' => 'Currently Reading',
        'finished' => 'Finished',
    ];
    return $labels[$status] ?? $status;
}

function annotationTypes() {
    return [
        'note' => ['label' => 'Note', 'icon' => '✎'],
        'thought' => ['label' => 'Thought', 'icon' => '◌'],
        'question' => ['label' => 'Question', 'icon' => '?'],
        'insight' => ['label' => 'Insight', 'icon' => '✦'],
        'theory' => ['label' => 'Theory', 'icon' => '◇'],
        'quote' => ['label' => 'Favorite Quote', 'icon' => '“'],
        'important' => ['label' => 'Important', 'icon' => '!'],
    ];
}
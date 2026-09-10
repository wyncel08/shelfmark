<?php
// Database connection settings.
// Update these to match your local/hosting environment.

define('DB_HOST', 'localhost');
define('DB_NAME', 'booktracker');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Keep existing installations compatible with fields used by the library and Insights page.
            $bookColumns = [
                'is_favorite' => 'ALTER TABLE books ADD COLUMN is_favorite TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
                'date_added' => 'ALTER TABLE books ADD COLUMN date_added DATE NULL AFTER created_at',
                'date_finished' => 'ALTER TABLE books ADD COLUMN date_finished DATE NULL AFTER date_added',
                'genre' => 'ALTER TABLE books ADD COLUMN genre VARCHAR(100) DEFAULT NULL AFTER author',
            ];
            foreach ($bookColumns as $columnName => $alterSql) {
                $column = $pdo->query("SHOW COLUMNS FROM books LIKE '" . $columnName . "'")->fetch();
                if (!$column) {
                    $pdo->exec($alterSql);
                }
            }
            $userColumns = [
                'bio' => 'ALTER TABLE users ADD COLUMN bio TEXT NULL AFTER email',
                'avatar' => 'ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER bio',
            ];
            foreach ($userColumns as $columnName => $alterSql) {
                $column = $pdo->query("SHOW COLUMNS FROM users LIKE '" . $columnName . "'")->fetch();
                if (!$column) {
                    $pdo->exec($alterSql);
                }
            }
            $pdo->exec('UPDATE books SET date_added = DATE(created_at) WHERE date_added IS NULL');
            $pdo->exec('UPDATE books SET date_finished = DATE(updated_at) WHERE status = "finished" AND date_finished IS NULL');

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS annotations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    book_id INT NOT NULL,
                    chapter_or_page VARCHAR(255) DEFAULT NULL,
                    type ENUM('note', 'thought', 'question', 'insight', 'theory', 'quote', 'important') NOT NULL DEFAULT 'note',
                    content TEXT NOT NULL,
                    is_favorite TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_annotations_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
                )"
            );
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
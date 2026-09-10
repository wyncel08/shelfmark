<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$pdo = getDbConnection();
$userId = currentUserId();
$currentYear = (int)date('Y');
$requestedYear = $_GET['year'] ?? (string)$currentYear;
$selectedYear = $requestedYear === 'all' ? 'all' : (int)$requestedYear;
if ($selectedYear !== 'all' && ($selectedYear < 2000 || $selectedYear > $currentYear + 1)) {
    $selectedYear = $currentYear;
}

$yearStmt = $pdo->prepare(
    'SELECT DISTINCT YEAR(date_finished) AS reading_year
     FROM books
     WHERE user_id = ? AND status = "finished" AND date_finished IS NOT NULL
     ORDER BY reading_year DESC'
);
$yearStmt->execute([$userId]);
$availableYears = array_map('intval', array_column($yearStmt->fetchAll(), 'reading_year'));
if (!in_array($currentYear, $availableYears, true)) {
    array_unshift($availableYears, $currentYear);
}

$statsStmt = $pdo->prepare(
    'SELECT
        SUM(status = "finished" AND YEAR(date_finished) = ?) AS finished_this_year,
        AVG(rating) AS average_rating,
        AVG(CASE WHEN date_added IS NOT NULL AND date_finished IS NOT NULL THEN DATEDIFF(date_finished, date_added) END) AS average_days,
        COUNT(DISTINCT CASE WHEN status = "finished" AND author IS NOT NULL AND TRIM(author) <> "" THEN author END) AS distinct_authors
     FROM books
     WHERE user_id = ?'
);
$statsStmt->execute([$currentYear, $userId]);
$stats = $statsStmt->fetch();

if ($selectedYear === 'all') {
    $paceStmt = $pdo->prepare(
        'SELECT MONTH(date_finished) AS month_number, COUNT(*) AS book_count
         FROM books
         WHERE user_id = ? AND status = "finished" AND date_finished IS NOT NULL
         GROUP BY MONTH(date_finished)
         ORDER BY month_number'
    );
    $paceStmt->execute([$userId]);
} else {
    $paceStmt = $pdo->prepare(
        'SELECT MONTH(date_finished) AS month_number, COUNT(*) AS book_count
         FROM books
         WHERE user_id = ? AND status = "finished" AND YEAR(date_finished) = ? AND date_finished IS NOT NULL
         GROUP BY MONTH(date_finished)
         ORDER BY month_number'
    );
    $paceStmt->execute([$userId, $selectedYear]);
}
$paceByMonth = [];
foreach ($paceStmt->fetchAll() as $row) {
    $paceByMonth[(int)$row['month_number']] = (int)$row['book_count'];
}
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$paceMax = max($paceByMonth ?: [0]);
$paceTotal = array_sum($paceByMonth);

$genreStmt = $pdo->prepare(
    'SELECT COALESCE(NULLIF(TRIM(genre), ""), "Unspecified") AS genre_name, COUNT(*) AS book_count
     FROM books
     WHERE user_id = ? AND status = "finished"
     GROUP BY COALESCE(NULLIF(TRIM(genre), ""), "Unspecified")
     ORDER BY book_count DESC, genre_name'
);
$genreStmt->execute([$userId]);
$genres = $genreStmt->fetchAll();
$finishedTotal = array_sum(array_map('intval', array_column($genres, 'book_count')));
$genreColors = ['#8a5a26', '#b8863f', '#4b5d3a', '#7b2d26', '#6d5a44', '#b9a27b'];

$ratingStmt = $pdo->prepare(
    'SELECT rating, COUNT(*) AS book_count
     FROM books
     WHERE user_id = ? AND status = "finished" AND rating BETWEEN 1 AND 5
     GROUP BY rating
     ORDER BY rating'
);
$ratingStmt->execute([$userId]);
$ratingCounts = array_fill(1, 5, 0);
foreach ($ratingStmt->fetchAll() as $row) {
    $ratingCounts[(int)$row['rating']] = (int)$row['book_count'];
}
$ratingMax = max($ratingCounts);

$authorsStmt = $pdo->prepare(
    'SELECT author, COUNT(*) AS book_count,
        SUM(status = "finished") AS finished_count
     FROM books
     WHERE user_id = ? AND author IS NOT NULL AND TRIM(author) <> ""
     GROUP BY author
     ORDER BY book_count DESC, author
     LIMIT 5'
);
$authorsStmt->execute([$userId]);
$authors = $authorsStmt->fetchAll();

$averageRating = $stats['average_rating'] !== null ? number_format((float)$stats['average_rating'], 1) : '—';
$averageDays = $stats['average_days'] !== null ? number_format((float)$stats['average_days'], 1) : '—';
$selectedYearLabel = $selectedYear === 'all' ? 'All time' : (string)$selectedYear;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insights - Shelfmark</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand"><img src="logo.svg" alt="Shelfmark - A Reading Log"></a>
        <nav class="section-nav" aria-label="Library views">
            <a href="index.php">My Library</a>
            <a href="index.php?view=currently_reading">Currently Reading</a>
            <a href="index.php?view=want_to_read">Want to Read</a>
            <a href="index.php?view=finished">Finished</a>
            <a href="index.php?view=favorite">Favorites</a>
            <a href="insights.php" class="active" aria-current="page">Insights</a>
            <a href="annotations.php">Annotations</a>
        </nav>
        <nav class="account-nav" aria-label="Account actions">
            <a href="addabook.php" class="btn btn-primary">+ Add Book</a>
            <div class="profile-menu">
                <button type="button" class="profile-trigger" aria-expanded="false" aria-haspopup="true"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EDE6D3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg> <?= e($_SESSION['username']) ?> <span aria-hidden="true">▾</span></button>
                <div class="profile-dropdown">
                    <a href="profile.php">My Profile</a>
                    <a href="settings.php">Account Settings</a>
                    <a href="logout.php">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container insights-page">
        <div class="page-heading insights-heading">
            <div>
                <p class="reader-kicker">Your reading year in review</p>
                <h1>Insights</h1>
                <p>A private look at the rhythms and shape of your shelf.</p>
            </div>
            <form method="GET" class="year-filter">
                <label for="insights-year">Reading year</label>
                <select id="insights-year" name="year" onchange="this.form.submit()">
                    <option value="all" <?= $selectedYear === 'all' ? 'selected' : '' ?>>All time</option>
                    <?php foreach ($availableYears as $year): ?>
                        <option value="<?= $year ?>" <?= $selectedYear === $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <section class="insight-stat-grid" aria-label="Reading summary">
            <article class="insight-stat"><span>Finished in <?= $currentYear ?></span><strong><?= (int)$stats['finished_this_year'] ?></strong><small>books completed</small></article>
            <article class="insight-stat"><span>Average rating</span><strong><?= e($averageRating) ?><?= $averageRating !== '—' ? '<small class="stat-unit"> / 5</small>' : '' ?></strong><small>across rated books</small></article>
            <article class="insight-stat"><span>Average days per book</span><strong><?= e($averageDays) ?><?= $averageDays !== '—' ? '<small class="stat-unit"> days</small>' : '' ?></strong><small>from added to finished</small></article>
            <article class="insight-stat"><span>Authors read</span><strong><?= (int)$stats['distinct_authors'] ?></strong><small>distinct finished authors</small></article>
        </section>

        <section class="insight-panel pace-panel">
            <div class="panel-heading"><div><p class="reader-kicker">Reading pace</p><h2><?= e($selectedYearLabel) ?></h2></div><strong class="panel-total"><?= $paceTotal ?> <span>finished</span></strong></div>
            <?php if ($paceTotal === 0): ?>
                <p class="empty-state">No books finished <?= $selectedYear === 'all' ? 'yet' : 'in ' . e((string)$selectedYear) ?>.</p>
            <?php else: ?>
                <div class="pace-chart" aria-label="Books finished per month">
                    <?php foreach ($months as $monthIndex => $month): $count = $paceByMonth[$monthIndex + 1] ?? 0; $height = $paceMax > 0 ? max(4, ($count / $paceMax) * 100) : 0; ?>
                        <div class="pace-column"><span class="pace-count"><?= $count ?: '' ?></span><div class="pace-bar-track"><div class="pace-bar" style="height: <?= $height ?>%"></div></div><span class="pace-label"><?= $month ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <div class="insight-panel-grid">
            <section class="insight-panel genre-panel">
                <div class="panel-heading"><div><p class="reader-kicker">Finished shelf</p><h2>Genre breakdown</h2></div></div>
                <?php if ($finishedTotal === 0): ?>
                    <p class="empty-state">Finish a book to see your genres take shape.</p>
                <?php else: ?>
                    <div class="genre-layout">
                        <div class="donut-wrap">
                            <svg class="donut-chart" viewBox="0 0 120 120" role="img" aria-label="Finished books by genre">
                                <circle class="donut-base" cx="60" cy="60" r="42" />
                                <?php $offset = 0; foreach ($genres as $index => $genre): $percentage = ((int)$genre['book_count'] / $finishedTotal) * 100; $dash = $percentage * 2.6389; ?>
                                    <circle class="donut-segment" cx="60" cy="60" r="42" stroke="<?= $genreColors[$index % count($genreColors)] ?>" stroke-dasharray="<?= $dash ?> <?= 263.89 - $dash ?>" stroke-dashoffset="<?= -$offset * 2.6389 ?>" />
                                    <?php $offset += $percentage; endforeach; ?>
                            </svg>
                            <div class="donut-total"><strong><?= $finishedTotal ?></strong><span>books</span></div>
                        </div>
                        <ul class="genre-legend">
                            <?php foreach ($genres as $index => $genre): $percentage = round(((int)$genre['book_count'] / $finishedTotal) * 100); ?>
                                <li><span class="legend-swatch" style="background: <?= $genreColors[$index % count($genreColors)] ?>"></span><span><?= e($genre['genre_name']) ?></span><strong><?= $percentage ?>%</strong></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>

            <section class="insight-panel rating-panel">
                <div class="panel-heading"><div><p class="reader-kicker">Finished shelf</p><h2>Rating distribution</h2></div></div>
                <?php if ($finishedTotal === 0 || $ratingMax === 0): ?>
                    <p class="empty-state">Rated finished books will appear here.</p>
                <?php else: ?>
                    <div class="rating-bars">
                        <?php for ($rating = 5; $rating >= 1; $rating--): $count = $ratingCounts[$rating]; $width = $ratingMax > 0 ? ($count / $ratingMax) * 100 : 0; ?>
                            <div class="rating-row"><span class="rating-label"><?= $rating ?> <span aria-hidden="true">★</span></span><div class="rating-track"><div class="rating-fill" style="width: <?= $width ?>%"></div></div><strong><?= $count ?></strong></div>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="insight-panel authors-panel">
            <div class="panel-heading"><div><p class="reader-kicker">Across your shelf</p><h2>Most read authors</h2></div><span class="panel-note">Any status</span></div>
            <?php if (empty($authors)): ?>
                <p class="empty-state">Add authors to your books to see your reading companions.</p>
            <?php else: ?>
                <div class="author-list">
                    <?php foreach ($authors as $index => $author): ?>
                        <div class="author-row"><span class="author-rank">0<?= $index + 1 ?></span><strong><?= e($author['author']) ?></strong><span><?= (int)$author['book_count'] ?> <?= (int)$author['book_count'] === 1 ? 'book' : 'books' ?> · <?= (int)$author['finished_count'] ?> finished</span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="script.js"></script>
</body>
</html>

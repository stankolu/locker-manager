<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
<header class="main-header">
    <div class="header-brand">
        <img src="assets/img/logo.png" alt="Logo" class="header-logo" onerror="this.style.display='none'">
        <span class="header-title">Lycée Ermesinde</span>
        <small class="header-subtitle">Locker Management System</small>
    </div>
    <nav class="main-nav">
        <a href="index.php" class="<?= ($pageTitle ?? '') === 'Dashboard' ? 'active' : '' ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="students.php" class="<?= ($pageTitle ?? '') === 'Students' ? 'active' : '' ?>"><i class="fas fa-user-graduate"></i> Students</a>
        <a href="lockers.php" class="<?= ($pageTitle ?? '') === 'Lockers' ? 'active' : '' ?>"><i class="fas fa-box"></i> Lockers</a>
        <a href="locks.php" class="<?= ($pageTitle ?? '') === 'Locks' ? 'active' : '' ?>"><i class="fas fa-lock"></i> Locks</a>
        <a href="classes.php" class="<?= ($pageTitle ?? '') === 'Classes' ? 'active' : '' ?>"><i class="fas fa-chalkboard"></i> Classes</a>
        <a href="houses.php" class="<?= ($pageTitle ?? '') === 'Houses' ? 'active' : '' ?>"><i class="fas fa-home"></i> Houses</a>
        <a href="rooms.php" class="<?= ($pageTitle ?? '') === 'Rooms' ? 'active' : '' ?>"><i class="fas fa-door-open"></i> Rooms</a>
        <a href="assignments.php" class="<?= ($pageTitle ?? '') === 'Assignments' ? 'active' : '' ?>"><i class="fas fa-link"></i> Assignments</a>
        <a href="map.php" class="<?= ($pageTitle ?? '') === 'Locker Map' ? 'active' : '' ?>"><i class="fas fa-map"></i> Map</a>
    </nav>
    <div class="header-actions">
        <?php $allYears = getAllYears(); $currentYear = getSelectedYear(); ?>
        <select onchange="window.location='?year_id='+this.value" class="year-selector">
            <?php foreach ($allYears as $y): ?>
                <option value="<?= $y['id'] ?>" <?= ($currentYear && $currentYear['id'] == $y['id']) ? 'selected' : '' ?>>
                    <?= h($y['start_year'] . '-' . $y['end_year']) ?><?= $y['is_active'] ? ' ★' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</header>

<main class="container">
    <?php foreach (getFlashes() as $flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= h($flash['message']) ?>
            <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endforeach; ?>

<?php
require_once 'includes/helpers.php';
$pageTitle = 'Dashboard';
$year = getSelectedYear();
$yearId = $year ? $year['id'] : 0;

// Stats
$totalStudents = DB::fetchOne("SELECT COUNT(*) c FROM students")['c'];
$totalLockers  = DB::fetchOne("SELECT COUNT(*) c FROM lockers")['c'];
$totalLocks    = DB::fetchOne("SELECT COUNT(*) c FROM locks")['c'];
$totalClasses  = DB::fetchOne("SELECT COUNT(*) c FROM classes")['c'];

$assignedLockers = DB::fetchOne("SELECT COUNT(*) c FROM locker_assignments WHERE school_year_id = ?", [$yearId])['c'];
$availableLockers = $totalLockers - $assignedLockers;

$reserveLockers = DB::fetchOne("SELECT COUNT(*) c FROM lockers WHERE status = 'reserve'")['c'];
$maintenanceLockers = DB::fetchOne("SELECT COUNT(*) c FROM lockers WHERE status = 'maintenance'")['c'];

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <h3><?= $totalStudents ?></h3>
            <p>Students</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <h3><?= $totalLockers ?></h3>
            <p>Total Lockers</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fas fa-link"></i></div>
        <div class="stat-info">
            <h3><?= $assignedLockers ?></h3>
            <p>Assigned (this year)</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?= $availableLockers ?></h3>
            <p>Available</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-lock"></i></div>
        <div class="stat-info">
            <h3><?= $totalLocks ?></h3>
            <p>Locks</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-tools"></i></div>
        <div class="stat-info">
            <h3><?= $maintenanceLockers ?></h3>
            <p>Maintenance</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Quick Actions</div>
    <div class="card-body">
        <div class="btn-group">
            <a href="assignments.php" class="btn btn-primary"><i class="fas fa-link"></i> Manage Assignments</a>
            <a href="import.php" class="btn btn-success"><i class="fas fa-file-import"></i> Import Data</a>
            <a href="map.php" class="btn btn-secondary"><i class="fas fa-map"></i> View Map</a>
            <a href="export.php?type=lockers" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export Lockers PDF</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

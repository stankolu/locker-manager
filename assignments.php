<?php
require_once 'includes/helpers.php';
$pageTitle = 'Assignments';
$year = getSelectedYear();
$yearId = $year ? $year['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $lockerId  = (int)($_POST['locker_id'] ?? 0);
        $lockId    = (int)($_POST['lock_id'] ?? 0) ?: null;

        if ($studentId && $lockerId && $yearId) {
            // Check if locker is already assigned this year
            $existing = DB::fetchOne("SELECT id FROM locker_assignments WHERE locker_id = ? AND school_year_id = ?", [$lockerId, $yearId]);
            if ($existing) {
                flash('danger', 'This locker is already assigned for this school year.');
            } else {
                try {
                    DB::insert("INSERT INTO locker_assignments (student_id, locker_id, lock_id, school_year_id) VALUES (?,?,?,?)",
                        [$studentId, $lockerId, $lockId, $yearId]);
                    flash('success', 'Assignment created.');
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            }
        } else {
            flash('danger', 'Student, locker, and school year are required.');
        }
        redirect('assignments.php');
    }

    if ($action === 'unassign') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM locker_assignments WHERE id = ?", [$id]);
        flash('success', 'Assignment removed.');
        redirect('assignments.php');
    }
}

$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$where  = ["la.school_year_id = ?"];
$params = [$yearId];

if ($search) {
    $where[] = "(s.last_name LIKE ? OR s.first_name LIKE ? OR l.locker_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereStr = implode(' AND ', $where);

$total = DB::fetchOne("
    SELECT COUNT(*) c FROM locker_assignments la
    JOIN students s ON la.student_id = s.id
    JOIN lockers l ON la.locker_id = l.id
    WHERE $whereStr
", $params)['c'];

$assignments = DB::fetchAll("
    SELECT la.*, s.last_name, s.first_name, l.locker_number, l.building,
           c.class_name, h.name as house_name, h.color,
           lk.lock_number, lk.combination
    FROM locker_assignments la
    JOIN students s ON la.student_id = s.id
    JOIN lockers l ON la.locker_id = l.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN houses h ON c.house_id = h.id
    LEFT JOIN locks lk ON la.lock_id = lk.id
    WHERE $whereStr
    ORDER BY s.last_name, s.first_name
    LIMIT $perPage OFFSET $offset
", $params);

$totalPages = ceil($total / $perPage);

// For the assign form
$allStudents = DB::fetchAll("SELECT id, last_name, first_name FROM students ORDER BY last_name, first_name");
$availableLockers = DB::fetchAll("
    SELECT l.id, l.locker_number FROM lockers l
    WHERE l.status = 'available'
    AND l.id NOT IN (SELECT locker_id FROM locker_assignments WHERE school_year_id = ?)
    ORDER BY l.locker_number
", [$yearId]);
$allLocks = DB::fetchAll("SELECT id, lock_number, combination FROM locks ORDER BY lock_number");

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-link"></i> Assignments
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= $total ?> this year</span>
    </h1>
    <button class="btn btn-primary" onclick="openModal('assignModal')"><i class="fas fa-plus"></i> New Assignment</button>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="get" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search student or locker..." value="<?= h($search) ?>">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="assignments.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>House</th>
                    <th>Locker</th>
                    <th>Lock</th>
                    <th>Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $a): ?>
                <tr>
                    <td><strong><?= h($a['last_name'] . ', ' . $a['first_name']) ?></strong></td>
                    <td><?= $a['class_name'] ? h($a['class_name']) : '—' ?></td>
                    <td>
                        <?php if ($a['house_name']): ?>
                        <span class="house-dot" style="background:<?= h($a['color'] ?? '#999') ?>"></span>
                        <?= h($a['house_name']) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= h($a['locker_number']) ?></td>
                    <td><?= $a['lock_number'] ? h($a['lock_number']) : '—' ?></td>
                    <td><?= $a['combination'] ? h($a['combination']) : '—' ?></td>
                    <td>
                        <form method="post" style="display:inline">
                            <input type="hidden" name="action" value="unassign">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button class="btn btn-danger btn-sm" type="submit" data-confirm="Remove this assignment?"><i class="fas fa-unlink"></i> Unassign</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($assignments)): ?>
                <tr><td colspan="7" class="text-center text-muted">No assignments for this school year.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <?php $q = http_build_query(array_merge($_GET, ['page' => $p])); ?>
        <a href="?<?= $q ?>" class="<?= $p == $page ? 'current' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- Assign Modal -->
<div class="modal-overlay" id="assignModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-link"></i> New Assignment</h3>
            <button class="modal-close" onclick="closeModal('assignModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="assign">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">— Select Student —</option>
                        <?php foreach ($allStudents as $st): ?>
                            <option value="<?= $st['id'] ?>"><?= h($st['last_name'] . ', ' . $st['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Locker *</label>
                    <select name="locker_id" class="form-control" required>
                        <option value="">— Select Available Locker —</option>
                        <?php foreach ($availableLockers as $lk): ?>
                            <option value="<?= $lk['id'] ?>"><?= h($lk['locker_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Lock (optional)</label>
                    <select name="lock_id" class="form-control">
                        <option value="">— No Lock —</option>
                        <?php foreach ($allLocks as $lk): ?>
                            <option value="<?= $lk['id'] ?>"><?= h($lk['lock_number']) ?><?= $lk['combination'] ? ' (' . h($lk['combination']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('assignModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Assign</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

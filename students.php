<?php
require_once 'includes/helpers.php';
$pageTitle = 'Students';
$year = getSelectedYear();
$yearId = $year ? $year['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $classId   = (int)($_POST['class_id'] ?? 0) ?: null;
        $email     = trim($_POST['email'] ?? '');

        if ($firstName && $lastName) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO students (first_name, last_name, class_id, email) VALUES (?,?,?,?)",
                        [$firstName, $lastName, $classId, $email ?: null]);
                    flash('success', "Student '$lastName, $firstName' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE students SET first_name=?, last_name=?, class_id=?, email=? WHERE id=?",
                    [$firstName, $lastName, $classId, $email ?: null, $id]);
                flash('success', "Student updated.");
            }
        } else {
            flash('danger', 'First name and last name are required.');
        }
        redirect('students.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM students WHERE id = ?", [$id]);
        flash('success', 'Student deleted.');
        redirect('students.php');
    }
}

$search  = trim($_GET['search'] ?? '');
$classFilter = $_GET['class_id'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

// Sorting
$allowedSort = [
    's.last_name'  => 's.last_name',
    's.first_name' => 's.first_name',
    'c.class_name' => 'c.class_name',
    'h.name'       => 'h.name',
];
$sortCol = $allowedSort[$_GET['sort'] ?? ''] ?? 's.last_name';
$sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$sortKey = array_search($sortCol, $allowedSort);

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(s.last_name LIKE ? OR s.first_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($classFilter) {
    $where[] = "s.class_id = ?";
    $params[] = (int)$classFilter;
}

$whereStr = implode(' AND ', $where);

$total = DB::fetchOne("SELECT COUNT(*) c FROM students s LEFT JOIN classes c ON s.class_id = c.id LEFT JOIN houses h ON c.house_id = h.id WHERE $whereStr", $params)['c'];

$students = DB::fetchAll("
    SELECT s.*, c.class_name, h.name as house_name, h.color
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN houses h ON c.house_id = h.id
    WHERE $whereStr
    ORDER BY $sortCol $sortDir
    LIMIT $perPage OFFSET $offset
", $params);

$totalPages = ceil($total / $perPage);
$allClasses = DB::fetchAll("SELECT * FROM classes ORDER BY class_name");

$editStudent = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editStudent = DB::fetchOne("SELECT * FROM students WHERE id = ?", [(int)$_GET['id']]);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-graduate"></i> Students
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= $total ?> total</span>
    </h1>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="openModal('addStudentModal')"><i class="fas fa-plus"></i> Add Student</button>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="get" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search name..." value="<?= h($search) ?>">
            <select name="class_id" class="form-control" style="max-width:170px">
                <option value="">All Classes</option>
                <?php foreach ($allClasses as $cls): ?>
                    <option value="<?= $cls['id'] ?>" <?= $classFilter == $cls['id'] ? 'selected' : '' ?>><?= h($cls['class_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="students.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <?php $extra = array_filter(['search'=>$search,'class_id'=>$classFilter?:null,'page'=>$page>1?$page:null]); ?>
                <tr>
                    <?= sortTh('s.last_name',  'Last Name',  $sortKey, $sortDir, $extra) ?>
                    <?= sortTh('s.first_name', 'First Name', $sortKey, $sortDir, $extra) ?>
                    <?= sortTh('c.class_name', 'Class',      $sortKey, $sortDir, $extra) ?>
                    <?= sortTh('h.name',       'House',      $sortKey, $sortDir, $extra) ?>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td><strong><?= h($s['last_name']) ?></strong></td>
                    <td><?= h($s['first_name']) ?></td>
                    <td><?= $s['class_name'] ? h($s['class_name']) : '—' ?></td>
                    <td>
                        <?php if ($s['house_name']): ?>
                        <span class="house-dot" style="background:<?= h($s['color'] ?? '#999') ?>"></span>
                        <?= h($s['house_name']) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= $s['email'] ? h($s['email']) : '—' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="students.php?action=edit&id=<?= $s['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete student '<?= h($s['last_name']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                <tr><td colspan="6" class="text-center text-muted">No students found.</td></tr>
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

<!-- Add Student Modal -->
<div class="modal-overlay" id="addStudentModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add Student</h3>
            <button class="modal-close" onclick="closeModal('addStudentModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-control">
                            <option value="">— No Class —</option>
                            <?php foreach ($allClasses as $cls): ?>
                                <option value="<?= $cls['id'] ?>"><?= h($cls['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addStudentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<?php if ($editStudent): ?>
<div class="modal-overlay open" id="editStudentModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Student</h3>
            <button class="modal-close" onclick="window.location='students.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" value="<?= h($editStudent['last_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" value="<?= h($editStudent['first_name']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Class</label>
                        <select name="class_id" class="form-control">
                            <option value="">— No Class —</option>
                            <?php foreach ($allClasses as $cls): ?>
                                <option value="<?= $cls['id'] ?>" <?= ($editStudent['class_id'] ?? 0) == $cls['id'] ? 'selected' : '' ?>><?= h($cls['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= h($editStudent['email'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="students.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

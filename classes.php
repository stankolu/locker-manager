<?php
require_once 'includes/helpers.php';
$pageTitle = 'Classes';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $className = trim($_POST['class_name'] ?? '');
        $houseId   = (int)($_POST['house_id'] ?? 0) ?: null;
        $level     = trim($_POST['level'] ?? '');

        if ($className) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO classes (class_name, house_id, level) VALUES (?,?,?)",
                        [$className, $houseId, $level ?: null]);
                    flash('success', "Class '$className' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE classes SET class_name=?, house_id=?, level=? WHERE id=?",
                    [$className, $houseId, $level ?: null, $id]);
                flash('success', "Class updated.");
            }
        } else {
            flash('danger', 'Class name is required.');
        }
        redirect('classes.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM classes WHERE id = ?", [$id]);
        flash('success', 'Class deleted.');
        redirect('classes.php');
    }
}

$classes = DB::fetchAll("
    SELECT c.*, h.name as house_name, h.color,
           (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count
    FROM classes c
    LEFT JOIN houses h ON c.house_id = h.id
    ORDER BY c.class_name
");

$allHouses = DB::fetchAll("SELECT * FROM houses ORDER BY name");

$editClass = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editClass = DB::fetchOne("SELECT * FROM classes WHERE id = ?", [(int)$_GET['id']]);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-chalkboard"></i> Classes
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= count($classes) ?> total</span>
    </h1>
    <button class="btn btn-primary" onclick="openModal('addClassModal')"><i class="fas fa-plus"></i> Add Class</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>House</th>
                    <th>Level</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classes as $c): ?>
                <tr>
                    <td><strong><?= h($c['class_name']) ?></strong></td>
                    <td>
                        <?php if ($c['house_name']): ?>
                        <span class="house-dot" style="background:<?= h($c['color'] ?? '#999') ?>"></span>
                        <?= h($c['house_name']) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= $c['level'] ? h($c['level']) : '—' ?></td>
                    <td><?= $c['student_count'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="classes.php?action=edit&id=<?= $c['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete class '<?= h($c['class_name']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($classes)): ?>
                <tr><td colspan="5" class="text-center text-muted">No classes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal-overlay" id="addClassModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add Class</h3>
            <button class="modal-close" onclick="closeModal('addClassModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="class_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">House</label>
                        <select name="house_id" class="form-control">
                            <option value="">— No House —</option>
                            <?php foreach ($allHouses as $house): ?>
                                <option value="<?= $house['id'] ?>"><?= h($house['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <input type="text" name="level" class="form-control" placeholder="e.g. 7e, 6e, 5e...">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addClassModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Class Modal -->
<?php if ($editClass): ?>
<div class="modal-overlay open" id="editClassModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Class</h3>
            <button class="modal-close" onclick="window.location='classes.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editClass['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="class_name" class="form-control" value="<?= h($editClass['class_name']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">House</label>
                        <select name="house_id" class="form-control">
                            <option value="">— No House —</option>
                            <?php foreach ($allHouses as $house): ?>
                                <option value="<?= $house['id'] ?>" <?= ($editClass['house_id'] ?? 0) == $house['id'] ? 'selected' : '' ?>><?= h($house['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Level</label>
                        <input type="text" name="level" class="form-control" value="<?= h($editClass['level'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="classes.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

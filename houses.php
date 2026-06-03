<?php
require_once 'includes/helpers.php';
$pageTitle = 'Houses';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name  = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? '#999999');

        if ($name) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO houses (name, color) VALUES (?,?)", [$name, $color]);
                    flash('success', "House '$name' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE houses SET name=?, color=? WHERE id=?", [$name, $color, $id]);
                flash('success', "House updated.");
            }
        } else {
            flash('danger', 'House name is required.');
        }
        redirect('houses.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM houses WHERE id = ?", [$id]);
        flash('success', 'House deleted.');
        redirect('houses.php');
    }
}

$houses = DB::fetchAll("
    SELECT h.*,
           (SELECT COUNT(*) FROM classes WHERE house_id = h.id) as class_count,
           (SELECT COUNT(*) FROM students s JOIN classes c ON s.class_id = c.id WHERE c.house_id = h.id) as student_count
    FROM houses h ORDER BY h.name
");

$editHouse = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editHouse = DB::fetchOne("SELECT * FROM houses WHERE id = ?", [(int)$_GET['id']]);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-home"></i> Houses
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= count($houses) ?> total</span>
    </h1>
    <button class="btn btn-primary" onclick="openModal('addHouseModal')"><i class="fas fa-plus"></i> Add House</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Color</th>
                    <th>Name</th>
                    <th>Classes</th>
                    <th>Students</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($houses as $house): ?>
                <tr>
                    <td><span class="house-dot" style="background:<?= h($house['color']) ?>; width:20px; height:20px;"></span></td>
                    <td><strong><?= h($house['name']) ?></strong></td>
                    <td><?= $house['class_count'] ?></td>
                    <td><?= $house['student_count'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="houses.php?action=edit&id=<?= $house['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $house['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete house '<?= h($house['name']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($houses)): ?>
                <tr><td colspan="5" class="text-center text-muted">No houses found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add House Modal -->
<div class="modal-overlay" id="addHouseModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add House</h3>
            <button class="modal-close" onclick="closeModal('addHouseModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">House Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control" value="#4FC3F7" style="height:40px">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addHouseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit House Modal -->
<?php if ($editHouse): ?>
<div class="modal-overlay open" id="editHouseModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit House</h3>
            <button class="modal-close" onclick="window.location='houses.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editHouse['id'] ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">House Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= h($editHouse['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control" value="<?= h($editHouse['color'] ?? '#999999') ?>" style="height:40px">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="houses.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

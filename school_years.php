<?php
require_once 'includes/helpers.php';
$pageTitle = 'School Years';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $startYear = (int)($_POST['start_year'] ?? 0);
        $endYear   = (int)($_POST['end_year'] ?? 0);
        $isActive  = isset($_POST['is_active']) ? 1 : 0;

        if ($startYear && $endYear) {
            if ($isActive) {
                DB::execute("UPDATE school_years SET is_active = 0");
            }
            try {
                DB::insert("INSERT INTO school_years (start_year, end_year, is_active) VALUES (?,?,?)",
                    [$startYear, $endYear, $isActive]);
                flash('success', "School year $startYear-$endYear added.");
            } catch (Exception $e) {
                flash('danger', 'Error: ' . $e->getMessage());
            }
        } else {
            flash('danger', 'Start and end year are required.');
        }
        redirect('school_years.php');
    }

    if ($action === 'set_active') {
        $id = (int)$_POST['id'];
        DB::execute("UPDATE school_years SET is_active = 0");
        DB::execute("UPDATE school_years SET is_active = 1 WHERE id = ?", [$id]);
        $_SESSION['selected_year_id'] = $id;
        flash('success', 'Active school year updated.');
        redirect('school_years.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM school_years WHERE id = ?", [$id]);
        flash('success', 'School year deleted.');
        redirect('school_years.php');
    }
}

$years = DB::fetchAll("SELECT *, (SELECT COUNT(*) FROM locker_assignments WHERE school_year_id = school_years.id) as assignment_count FROM school_years ORDER BY start_year DESC");

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-calendar-alt"></i> School Years</h1>
    <button class="btn btn-primary" onclick="openModal('addYearModal')"><i class="fas fa-plus"></i> Add Year</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>School Year</th>
                    <th>Status</th>
                    <th>Assignments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($years as $y): ?>
                <tr>
                    <td><strong><?= $y['start_year'] ?>-<?= $y['end_year'] ?></strong></td>
                    <td>
                        <?php if ($y['is_active']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $y['assignment_count'] ?></td>
                    <td>
                        <div class="btn-group">
                            <?php if (!$y['is_active']): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="set_active">
                                <input type="hidden" name="id" value="<?= $y['id'] ?>">
                                <button class="btn btn-success btn-sm" type="submit"><i class="fas fa-star"></i> Set Active</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $y['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete school year <?= $y['start_year'] ?>-<?= $y['end_year'] ?>? This will also delete all assignments for this year."><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($years)): ?>
                <tr><td colspan="4" class="text-center text-muted">No school years configured.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Year Modal -->
<div class="modal-overlay" id="addYearModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add School Year</h3>
            <button class="modal-close" onclick="closeModal('addYearModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Year *</label>
                        <input type="number" name="start_year" class="form-control" value="<?= date('Y') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Year *</label>
                        <input type="number" name="end_year" class="form-control" value="<?= date('Y') + 1 ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; cursor:pointer">
                        <input type="checkbox" name="is_active" value="1" style="width:18px; height:18px; margin-right:10px">
                        Set as active year
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addYearModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'includes/helpers.php';
$pageTitle = 'Locks';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $lockNumber  = trim($_POST['lock_number'] ?? '');
        $combination = trim($_POST['combination'] ?? '');
        $brand       = trim($_POST['brand'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');

        if ($lockNumber) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO locks (lock_number, combination, brand, notes) VALUES (?,?,?,?)",
                        [$lockNumber, $combination ?: null, $brand ?: null, $notes ?: null]);
                    flash('success', "Lock '$lockNumber' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE locks SET lock_number=?, combination=?, brand=?, notes=? WHERE id=?",
                    [$lockNumber, $combination ?: null, $brand ?: null, $notes ?: null, $id]);
                flash('success', "Lock updated.");
            }
        } else {
            flash('danger', 'Lock number is required.');
        }
        redirect('locks.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM locks WHERE id = ?", [$id]);
        flash('success', 'Lock deleted.');
        redirect('locks.php');
    }
}

$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = "(lock_number LIKE ? OR combination LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereStr = implode(' AND ', $where);

$total = DB::fetchOne("SELECT COUNT(*) c FROM locks WHERE $whereStr", $params)['c'];
$locks = DB::fetchAll("SELECT * FROM locks WHERE $whereStr ORDER BY lock_number LIMIT $perPage OFFSET $offset", $params);
$totalPages = ceil($total / $perPage);

$editLock = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editLock = DB::fetchOne("SELECT * FROM locks WHERE id = ?", [(int)$_GET['id']]);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-lock"></i> Locks
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= $total ?> total</span>
    </h1>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="openModal('addLockModal')"><i class="fas fa-plus"></i> Add Lock</button>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="get" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search lock number or code..." value="<?= h($search) ?>">
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="locks.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Lock Number</th>
                    <th>Combination</th>
                    <th>Brand</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($locks as $lk): ?>
                <tr>
                    <td><strong><?= h($lk['lock_number']) ?></strong></td>
                    <td><?= $lk['combination'] ? h($lk['combination']) : '—' ?></td>
                    <td><?= $lk['brand'] ? h($lk['brand']) : '—' ?></td>
                    <td class="text-muted"><?= $lk['notes'] ? h(substr($lk['notes'], 0, 40)) : '' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="locks.php?action=edit&id=<?= $lk['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $lk['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete lock '<?= h($lk['lock_number']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($locks)): ?>
                <tr><td colspan="5" class="text-center text-muted">No locks found.</td></tr>
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

<!-- Add Lock Modal -->
<div class="modal-overlay" id="addLockModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add Lock</h3>
            <button class="modal-close" onclick="closeModal('addLockModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Lock Number *</label>
                        <input type="text" name="lock_number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Combination</label>
                        <input type="text" name="combination" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLockModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Lock Modal -->
<?php if ($editLock): ?>
<div class="modal-overlay open" id="editLockModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Lock</h3>
            <button class="modal-close" onclick="window.location='locks.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editLock['id'] ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Lock Number *</label>
                        <input type="text" name="lock_number" class="form-control" value="<?= h($editLock['lock_number']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Combination</label>
                        <input type="text" name="combination" class="form-control" value="<?= h($editLock['combination'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Brand</label>
                        <input type="text" name="brand" class="form-control" value="<?= h($editLock['brand'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= h($editLock['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="locks.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

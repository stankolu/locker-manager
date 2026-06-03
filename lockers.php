<?php
require_once 'includes/helpers.php';
$pageTitle = 'Lockers';
$year = getSelectedYear();
$yearId = $year ? $year['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $num        = strtoupper(trim($_POST['locker_number'] ?? ''));
        $building   = strtoupper(trim($_POST['building'] ?? ''));
        $status     = $_POST['status'] ?? 'available';
        if ($status === 'assigned') $status = 'available';
        $notes      = trim($_POST['notes'] ?? '');
        $roomId     = (int)($_POST['room_id'] ?? 0) ?: null;
        $isVerified = isset($_POST['is_verified']) ? 1 : 0;

        if (!$building && $num) {
            $building = substr($num, 0, 2);
        }

        if ($num) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO lockers (locker_number, building, status, room_id, notes, is_verified) VALUES (?,?,?,?,?,?)",
                        [$num, $building, $status, $roomId, $notes ?: null, $isVerified]);
                    flash('success', "Locker '$num' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE lockers SET locker_number=?, building=?, status=?, room_id=?, notes=?, is_verified=? WHERE id=?",
                    [$num, $building, $status, $roomId, $notes ?: null, $isVerified, $id]);
                flash('success', "Locker updated.");
            }
        } else {
            flash('danger', 'Locker number is required.');
        }
        redirect('lockers.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM lockers WHERE id = ?", [$id]);
        flash('success', 'Locker deleted.');
        redirect('lockers.php');
    }
}

$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$building     = $_GET['building'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($page - 1) * $perPage;

$allowedSort = [
    'l.locker_number' => 'l.locker_number',
    'l.building'      => 'l.building',
    'r.room_number'   => 'r.room_number',
    'l.status'        => 'l.status',
    'l.is_verified'   => 'l.is_verified',
    's.last_name'     => 's.last_name',
    'c.class_name'    => 'c.class_name',
    'h.name'          => 'h.name',
    'lk.lock_number'  => 'lk.lock_number',
];
$sortCol = $allowedSort[$_GET['sort'] ?? ''] ?? 'l.locker_number';
$sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
$sortKey = array_search($sortCol, $allowedSort);

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = "l.locker_number LIKE ?";
    $params[] = "%$search%";
}
if ($building) {
    $where[] = "l.building = ?";
    $params[] = $building;
}

if (in_array($statusFilter, ['reserve', 'maintenance'])) {
    $where[] = "l.status = ?";
    $params[] = $statusFilter;
} elseif ($statusFilter === 'assigned') {
    $where[] = "l.status NOT IN ('reserve','maintenance')";
    $where[] = "la.id IS NOT NULL";
} elseif ($statusFilter === 'available') {
    $where[] = "l.status = 'available'";
    $where[] = "la.id IS NULL";
}

$whereStr = implode(' AND ', $where);

$total = DB::fetchOne("
    SELECT COUNT(*) c FROM lockers l
    LEFT JOIN locker_assignments la ON la.locker_id = l.id AND la.school_year_id = ?
    WHERE $whereStr
", array_merge([$yearId], $params))['c'];

$lockers = DB::fetchAll("
    SELECT l.*,
           r.room_number as locker_room,
           la.id as assign_id,
           s.last_name, s.first_name,
           c.class_name, h.name as house_name, h.color,
           lk.lock_number, lk.combination
    FROM lockers l
    LEFT JOIN rooms r ON l.room_id = r.id
    LEFT JOIN locker_assignments la ON la.locker_id = l.id AND la.school_year_id = ?
    LEFT JOIN students s ON la.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN houses h ON c.house_id = h.id
    LEFT JOIN locks lk ON la.lock_id = lk.id
    WHERE $whereStr
    ORDER BY $sortCol $sortDir
    LIMIT $perPage OFFSET $offset
", array_merge([$yearId], $params));

$totalPages = ceil($total / $perPage);
$buildings  = DB::fetchAll("SELECT DISTINCT building FROM lockers ORDER BY building");
$allRooms   = DB::fetchAll("SELECT id, room_number, building FROM rooms ORDER BY room_number");

$editLocker = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editLocker = DB::fetchOne("SELECT * FROM lockers WHERE id = ?", [(int)$_GET['id']]);
}

function lockerEffectiveStatus(array $l): string {
    if (in_array($l['status'], ['reserve', 'maintenance'])) return $l['status'];
    return $l['assign_id'] ? 'assigned' : 'available';
}

include 'includes/header.php';
?>
<div class="page-header">
    <h1><i class="fas fa-box"></i> Lockers
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= $total ?> total</span>
    </h1>
    <div class="btn-group">
        <?php
        $exportParams = array_filter(['type'=>'lockers','search'=>$search,'building'=>$building,'status'=>$statusFilter]);
        $exportUrl = 'export.php?' . http_build_query($exportParams);
        ?>
        <a href="<?= $exportUrl ?>" class="btn btn-danger"><i class="fas fa-file-pdf"></i> Export PDF<?= ($search||$building||$statusFilter) ? ' (filtered)' : '' ?></a>
        <button class="btn btn-primary" onclick="openModal('addLockerModal')"><i class="fas fa-plus"></i> Add Locker</button>
    </div>
</div>

<div class="card mb-2">
    <div class="card-body">
        <form method="get" class="search-bar">
            <input type="text" name="search" class="form-control" placeholder="Search locker number..." value="<?= h($search) ?>">
            <select name="building" class="form-control" style="max-width:120px">
                <option value="">All Buildings</option>
                <?php foreach ($buildings as $b): ?>
                    <option value="<?= h($b['building']) ?>" <?= $building === $b['building'] ? 'selected' : '' ?>><?= h($b['building']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="max-width:170px">
                <option value="">All Statuses</option>
                <option value="available"   <?= $statusFilter === 'available'   ? 'selected' : '' ?>>Available</option>
                <option value="assigned"    <?= $statusFilter === 'assigned'    ? 'selected' : '' ?>>Assigned (this year)</option>
                <option value="reserve"     <?= $statusFilter === 'reserve'     ? 'selected' : '' ?>>Reserve</option>
                <option value="maintenance" <?= $statusFilter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            <a href="lockers.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <?php $loExtra = array_filter(['search'=>$search,'status'=>$statusFilter?:null,'building'=>$building?:null,'page'=>$page>1?$page:null]); ?>
                <tr>
                    <?= sortTh('l.locker_number', 'Locker #',  $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('l.building',      'Building',  $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('r.room_number',   'Room',      $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('l.status',        'Status',    $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('l.is_verified',   'Verified',  $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('s.last_name',     'Student',   $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('c.class_name',    'Class',     $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('h.name',          'House',     $sortKey, $sortDir, $loExtra) ?>
                    <?= sortTh('lk.lock_number',  'Lock',      $sortKey, $sortDir, $loExtra) ?>
                    <th>Code</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lockers as $l): ?>
                <?php $effStatus = lockerEffectiveStatus($l); ?>
                <tr>
                    <td><strong><?= h($l['locker_number']) ?></strong></td>
                    <td><?= h($l['building']) ?></td>
                    <td><?= $l['locker_room'] ? h($l['locker_room']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php
                        $badgeMap = ['available'=>'success','assigned'=>'info','reserve'=>'warning','maintenance'=>'danger'];
                        $badge = $badgeMap[$effStatus] ?? 'secondary';
                        ?>
                        <span class="badge badge-<?= $badge ?>"><?= ucfirst($effStatus) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($l['is_verified']): ?>
                            <i class="fas fa-check-circle text-success" title="Verified"></i>
                        <?php else: ?>
                            <i class="far fa-circle text-muted" title="Not Verified"></i>
                        <?php endif; ?>
                    </td>
                    <td><?= $l['last_name'] ? h($l['last_name'] . ', ' . $l['first_name']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $l['class_name'] ? h($l['class_name']) : '—' ?></td>
                    <td>
                        <?php if ($l['house_name']): ?>
                        <span class="house-dot" style="background:<?= h($l['color'] ?? '#999') ?>"></span>
                        <?= h($l['house_name']) ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= $l['lock_number'] ? h($l['lock_number']) : '—' ?></td>
                    <td><?= $l['combination'] ? h($l['combination']) : '—' ?></td>
                    <td class="text-muted"><?= $l['notes'] ? h(substr($l['notes'], 0, 30)) : '' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="lockers.php?action=edit&id=<?= $l['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete locker '<?= h($l['locker_number']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($lockers)): ?>
                <tr><td colspan="12" class="text-center text-muted">No lockers found.</td></tr>
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

<!-- Add Locker Modal -->
<div class="modal-overlay" id="addLockerModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add Locker</h3>
            <button class="modal-close" onclick="closeModal('addLockerModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Locker Number *</label>
                        <input type="text" name="locker_number" class="form-control" placeholder="e.g. B2-806" required>
                        <div class="form-text">Format: B[digit]-[3 digits], e.g. B2-806</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Building</label>
                        <input type="text" name="building" class="form-control" placeholder="e.g. B1">
                        <div class="form-text">Auto-detected from locker number</div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Physical Status</label>
                        <select name="status" class="form-control">
                            <option value="available">Available</option>
                            <option value="reserve">Reserve</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">— No Room —</option>
                            <?php foreach ($allRooms as $rm): ?>
                                <option value="<?= $rm['id'] ?>"><?= h($rm['room_number']) ?><?= $rm['building'] ? ' (' . h($rm['building']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; cursor:pointer">
                        <input type="checkbox" name="is_verified" value="1" style="width:18px; height:18px; margin-right:10px">
                        Verified
                    </label>
                    <div class="form-text">Mark this locker as verified.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addLockerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Locker Modal -->
<?php if ($editLocker): ?>
<div class="modal-overlay open" id="editLockerModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Locker</h3>
            <button class="modal-close" onclick="window.location='lockers.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editLocker['id'] ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Locker Number *</label>
                        <input type="text" name="locker_number" class="form-control" value="<?= h($editLocker['locker_number']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Building</label>
                        <input type="text" name="building" class="form-control" value="<?= h($editLocker['building']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Physical Status</label>
                        <select name="status" class="form-control">
                            <?php foreach (['available','reserve','maintenance'] as $st): ?>
                                <option value="<?= $st ?>" <?= $editLocker['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">— No Room —</option>
                            <?php foreach ($allRooms as $rm): ?>
                                <option value="<?= $rm['id'] ?>" <?= ($editLocker['room_id'] ?? 0) == $rm['id'] ? 'selected' : '' ?>><?= h($rm['room_number']) ?><?= $rm['building'] ? ' (' . h($rm['building']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" style="display:flex; align-items:center; cursor:pointer">
                        <input type="checkbox" name="is_verified" value="1" style="width:18px; height:18px; margin-right:10px" <?= $editLocker['is_verified'] ? 'checked' : '' ?>>
                        Verified
                    </label>
                    <div class="form-text">Mark this locker as verified.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= h($editLocker['notes'] ?? '') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <a href="lockers.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

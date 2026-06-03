<?php
require_once 'includes/helpers.php';
$pageTitle = 'Rooms';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $building   = trim($_POST['building'] ?? '');
        $floor      = trim($_POST['floor'] ?? '');

        if ($roomNumber) {
            if ($action === 'add') {
                try {
                    DB::insert("INSERT INTO rooms (room_number, building, floor) VALUES (?,?,?)",
                        [$roomNumber, $building ?: null, $floor ?: null]);
                    flash('success', "Room '$roomNumber' added.");
                } catch (Exception $e) {
                    flash('danger', 'Error: ' . $e->getMessage());
                }
            } else {
                $id = (int)$_POST['id'];
                DB::execute("UPDATE rooms SET room_number=?, building=?, floor=? WHERE id=?",
                    [$roomNumber, $building ?: null, $floor ?: null, $id]);
                flash('success', "Room updated.");
            }
        } else {
            flash('danger', 'Room number is required.');
        }
        redirect('rooms.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        DB::execute("DELETE FROM rooms WHERE id = ?", [$id]);
        flash('success', 'Room deleted.');
        redirect('rooms.php');
    }
}

$rooms = DB::fetchAll("
    SELECT r.*,
           (SELECT COUNT(*) FROM lockers WHERE room_id = r.id) as locker_count
    FROM rooms r ORDER BY r.room_number
");

$editRoom = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editRoom = DB::fetchOne("SELECT * FROM rooms WHERE id = ?", [(int)$_GET['id']]);
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-door-open"></i> Rooms
        <span class="badge badge-info" style="font-size:13px; margin-left:8px"><?= count($rooms) ?> total</span>
    </h1>
    <button class="btn btn-primary" onclick="openModal('addRoomModal')"><i class="fas fa-plus"></i> Add Room</button>
</div>

<div class="card">
    <div class="card-body" style="padding:0">
        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Building</th>
                    <th>Floor</th>
                    <th>Lockers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $r): ?>
                <tr>
                    <td><strong><?= h($r['room_number']) ?></strong></td>
                    <td><?= $r['building'] ? h($r['building']) : '—' ?></td>
                    <td><?= $r['floor'] ? h($r['floor']) : '—' ?></td>
                    <td><?= $r['locker_count'] ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="rooms.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete room '<?= h($r['room_number']) ?>'?"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rooms)): ?>
                <tr><td colspan="5" class="text-center text-muted">No rooms found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Add Room Modal -->
<div class="modal-overlay" id="addRoomModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus-square"></i> Add Room</h3>
            <button class="modal-close" onclick="closeModal('addRoomModal')">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Room Number *</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g. B.1.06" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Building</label>
                        <input type="text" name="building" class="form-control" placeholder="e.g. B1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor</label>
                        <input type="text" name="floor" class="form-control" placeholder="e.g. 1">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addRoomModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<?php if ($editRoom): ?>
<div class="modal-overlay open" id="editRoomModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Room</h3>
            <button class="modal-close" onclick="window.location='rooms.php'">×</button>
        </div>
        <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editRoom['id'] ?>">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Room Number *</label>
                    <input type="text" name="room_number" class="form-control" value="<?= h($editRoom['room_number']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Building</label>
                        <input type="text" name="building" class="form-control" value="<?= h($editRoom['building'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Floor</label>
                        <input type="text" name="floor" class="form-control" value="<?= h($editRoom['floor'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="rooms.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

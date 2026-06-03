<?php
require_once 'includes/helpers.php';
$pageTitle = 'Import';

$importResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $type = $_POST['import_type'] ?? '';
    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('danger', 'File upload failed.');
        redirect('import.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
        flash('danger', 'Only .xlsx, .xls, and .csv files are supported.');
        redirect('import.php');
    }

    // Move uploaded file
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $uploadPath = $uploadDir . uniqid() . '_' . basename($file['name']);
    move_uploaded_file($file['tmp_name'], $uploadPath);

    try {
        require_once 'vendor/autoload.php';
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($uploadPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($uploadPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;

        if ($type === 'students') {
            // Expected columns: Last Name, First Name, Class, Email
            foreach (array_slice($rows, 1) as $row) {
                $lastName  = trim($row[0] ?? '');
                $firstName = trim($row[1] ?? '');
                $className = trim($row[2] ?? '');
                $email     = trim($row[3] ?? '');

                if (!$lastName || !$firstName) { $skipped++; continue; }

                $classId = null;
                if ($className) {
                    $cls = DB::fetchOne("SELECT id FROM classes WHERE class_name = ?", [$className]);
                    $classId = $cls ? $cls['id'] : null;
                }

                try {
                    DB::insert("INSERT INTO students (first_name, last_name, class_id, email) VALUES (?,?,?,?)",
                        [$firstName, $lastName, $classId, $email ?: null]);
                    $imported++;
                } catch (Exception $e) {
                    $skipped++;
                }
            }
        } elseif ($type === 'lockers') {
            // Expected columns: Locker Number, Building, Room, Status
            foreach (array_slice($rows, 1) as $row) {
                $lockerNum = strtoupper(trim($row[0] ?? ''));
                $building  = strtoupper(trim($row[1] ?? ''));
                $roomNum   = trim($row[2] ?? '');
                $status    = trim($row[3] ?? 'available');

                if (!$lockerNum) { $skipped++; continue; }
                if (!$building) $building = substr($lockerNum, 0, 2);

                $roomId = null;
                if ($roomNum) {
                    $room = DB::fetchOne("SELECT id FROM rooms WHERE room_number = ?", [$roomNum]);
                    $roomId = $room ? $room['id'] : null;
                }

                try {
                    DB::insert("INSERT INTO lockers (locker_number, building, room_id, status) VALUES (?,?,?,?)",
                        [$lockerNum, $building, $roomId, $status]);
                    $imported++;
                } catch (Exception $e) {
                    $skipped++;
                }
            }
        } elseif ($type === 'locks') {
            // Expected columns: Lock Number, Combination, Brand
            foreach (array_slice($rows, 1) as $row) {
                $lockNum     = trim($row[0] ?? '');
                $combination = trim($row[1] ?? '');
                $brand       = trim($row[2] ?? '');

                if (!$lockNum) { $skipped++; continue; }

                try {
                    DB::insert("INSERT INTO locks (lock_number, combination, brand) VALUES (?,?,?)",
                        [$lockNum, $combination ?: null, $brand ?: null]);
                    $imported++;
                } catch (Exception $e) {
                    $skipped++;
                }
            }
        }

        flash('success', "Import complete: $imported imported, $skipped skipped.");
    } catch (Exception $e) {
        flash('danger', 'Import error: ' . $e->getMessage());
    }

    @unlink($uploadPath);
    redirect('import.php');
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-file-import"></i> Import Data</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Import Type *</label>
                    <select name="import_type" class="form-control" required>
                        <option value="">— Select Type —</option>
                        <option value="students">Students</option>
                        <option value="lockers">Lockers</option>
                        <option value="locks">Locks</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Excel/CSV File *</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Import</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Expected File Formats</div>
    <div class="card-body">
        <h4>Students</h4>
        <p>Columns: <strong>Last Name</strong>, <strong>First Name</strong>, <strong>Class</strong>, <strong>Email</strong></p>

        <h4 style="margin-top:16px">Lockers</h4>
        <p>Columns: <strong>Locker Number</strong>, <strong>Building</strong>, <strong>Room</strong>, <strong>Status</strong></p>

        <h4 style="margin-top:16px">Locks</h4>
        <p>Columns: <strong>Lock Number</strong>, <strong>Combination</strong>, <strong>Brand</strong></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

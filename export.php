<?php
require_once 'includes/helpers.php';
require_once 'vendor/autoload.php';

$year = getSelectedYear();
$yearId = $year ? $year['id'] : 0;
$type = $_GET['type'] ?? '';
$search       = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$building     = $_GET['building'] ?? '';

class LockerPDF extends TCPDF {
    public string $docTitle = '';
    public string $docSubtitle = '';

    public function Header() {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 8, $this->docTitle, 0, 1, 'C');
        if ($this->docSubtitle) {
            $this->SetFont('helvetica', '', 9);
            $this->Cell(0, 5, $this->docSubtitle, 0, 1, 'C');
        }
        $this->Ln(4);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

if ($type === 'lockers') {
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

    $lockers = DB::fetchAll("
        SELECT l.*, r.room_number as locker_room,
               la.id as assign_id,
               s.last_name, s.first_name,
               c.class_name, h.name as house_name,
               lk.lock_number, lk.combination
        FROM lockers l
        LEFT JOIN rooms r ON l.room_id = r.id
        LEFT JOIN locker_assignments la ON la.locker_id = l.id AND la.school_year_id = ?
        LEFT JOIN students s ON la.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN houses h ON c.house_id = h.id
        LEFT JOIN locks lk ON la.lock_id = lk.id
        WHERE $whereStr
        ORDER BY l.locker_number
    ", array_merge([$yearId], $params));

    $pdf = new LockerPDF('L', 'mm', 'A4');
    $pdf->docTitle = 'Locker List';
    $pdf->docSubtitle = ($year ? $year['start_year'] . '-' . $year['end_year'] : '') . ' — Generated ' . date('d/m/Y');
    $pdf->SetCreator('Locker Manager');
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 8);

    // Table header
    $headers = ['Locker #', 'Room', 'Building', 'Status', 'Verified', 'Student', 'Class', 'House', 'Lock', 'Code', 'Notes'];
    $widths  = [22, 20, 18, 20, 16, 45, 25, 28, 22, 25, 36];

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    for ($i = 0; $i < count($headers); $i++) {
        $pdf->Cell($widths[$i], 6, $headers[$i], 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', '', 7);

    foreach ($lockers as $l) {
        $effStatus = in_array($l['status'], ['reserve','maintenance']) ? $l['status'] : ($l['assign_id'] ? 'assigned' : 'available');
        $student = $l['last_name'] ? $l['last_name'] . ', ' . $l['first_name'] : '';
        $verified = $l['is_verified'] ? 'Yes' : 'No';

        $row = [
            $l['locker_number'],
            $l['locker_room'] ?? '',
            $l['building'],
            ucfirst($effStatus),
            $verified,
            $student,
            $l['class_name'] ?? '',
            $l['house_name'] ?? '',
            $l['lock_number'] ?? '',
            $l['combination'] ?? '',
            substr($l['notes'] ?? '', 0, 30),
        ];

        for ($i = 0; $i < count($row); $i++) {
            $pdf->Cell($widths[$i], 5, $row[$i], 1, 0, 'L');
        }
        $pdf->Ln();
    }

    $pdf->Output('lockers.pdf', 'D');
    exit;
}

// Default: redirect back
flash('warning', 'Unknown export type.');
redirect('index.php');

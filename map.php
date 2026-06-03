<?php
require_once 'includes/helpers.php';
$pageTitle = 'Locker Map';
$year = getSelectedYear();
$yearId = $year ? $year['id'] : null;

// Load the layout JSON if it exists
$layoutFile = __DIR__ . '/locker_map_layout.json';
$layout = file_exists($layoutFile) ? json_decode(file_get_contents($layoutFile), true) : null;

// House colors
$houseColors = [
    'Ansembourg'      => '#4FC3F7',
    'Hollenfels'      => '#FFB74D',
    'Koerich'         => '#A5D6A7',
    'Simmern'         => '#FFD54F',
    'Schoenfels'      => '#CE93D8',
    'Larochette'      => '#4DD0E1',
    'Mersch'          => '#FF8A65',
    'Pettingen'       => '#F48FB1',
    'cycle_superieur' => '#B0BEC5',
    'accueil'         => '#EF9A9A',
    'empty'           => '#ECEFF1',
    'reserved'        => '#FF5252',
    'unknown'         => '#ECEFF1',
];

// Fetch locker data
$lockerData = [];
if ($yearId) {
    $rows = DB::fetchAll("
        SELECT 
            lo.locker_number,
            CAST(SUBSTRING_INDEX(lo.locker_number, '-', -1) AS UNSIGNED) as locker_num,
            lo.status as physical_status,
            lo.id as locker_id,
            s.last_name, s.first_name,
            c.class_name,
            h.name as house_name,
            lk.lock_number,
            la.id as assignment_id
        FROM lockers lo
        LEFT JOIN locker_assignments la ON la.locker_id = lo.id AND la.school_year_id = ?
        LEFT JOIN students s ON la.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        LEFT JOIN houses h ON c.house_id = h.id
        LEFT JOIN locks lk ON la.lock_id = lk.id
    ", [$yearId]);
    foreach ($rows as $row) {
        $lockerData[$row['locker_num']] = $row;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-map"></i> Locker Map</h1>
</div>

<?php if (!$layout): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted">No map layout file found. Please import the locker map layout (locker_map_layout.json) to display the visual map.</p>
        <p>You can still manage lockers from the <a href="lockers.php">Lockers page</a>.</p>
    </div>
</div>
<?php else: ?>

<div class="card">
    <div class="card-body">
        <div style="margin-bottom:12px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
            <strong>Houses:</strong>
            <?php foreach ($houseColors as $name => $color): ?>
                <?php if (!in_array($name, ['empty','reserved','unknown'])): ?>
                <span style="display:inline-flex; align-items:center; gap:4px; font-size:12px;">
                    <span style="width:14px; height:14px; border-radius:3px; background:<?= $color ?>; display:inline-block;"></span>
                    <?= ucfirst($name) ?>
                </span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom:12px;">
            <label>Filter: </label>
            <input type="checkbox" id="showAvailable" checked> <label for="showAvailable">Available</label>
            <input type="checkbox" id="showOccupied" checked> <label for="showOccupied">Occupied</label>
            <input type="checkbox" id="showReserved" checked> <label for="showReserved">Reserved</label>
            <input type="checkbox" id="showMaintenance" checked> <label for="showMaintenance">Maintenance</label>
            &nbsp;&nbsp;
            <input type="text" id="lockerSearch" placeholder="Search locker #..." style="padding:4px 8px; border:1px solid #ccc; border-radius:4px;">
            <button id="resetFilters" class="btn btn-sm btn-secondary">Reset</button>
        </div>

        <div id="mapTabs" style="margin-bottom:8px;">
            <?php
            $floors = array_keys($layout);
            foreach ($floors as $i => $floor):
            ?>
                <button class="btn btn-sm <?= $i === 0 ? 'btn-primary' : 'btn-secondary' ?>" onclick="showFloor('<?= h($floor) ?>', this)"><?= h($floor) ?></button>
            <?php endforeach; ?>
        </div>

        <div style="overflow:auto; border:1px solid #eee; border-radius:8px; padding:10px; background:#fafafa;">
            <canvas id="lockerCanvas" width="800" height="600"></canvas>
        </div>
    </div>
</div>

<script>
const layout = <?= json_encode($layout) ?>;
const lockerData = <?= json_encode($lockerData) ?>;
const houseColors = <?= json_encode($houseColors) ?>;
const floors = Object.keys(layout);
let currentFloor = floors[0] || '';

const canvas = document.getElementById('lockerCanvas');
const ctx = canvas.getContext('2d');

function showFloor(floor, btn) {
    currentFloor = floor;
    document.querySelectorAll('#mapTabs button').forEach(b => { b.className = 'btn btn-sm btn-secondary'; });
    if (btn) btn.className = 'btn btn-sm btn-primary';
    drawMap();
}

function drawMap() {
    const floorData = layout[currentFloor];
    if (!floorData) return;

    const cellSize = 18;
    const padding = 10;
    let maxRow = 0, maxCol = 0;

    floorData.forEach(item => {
        if (item.row > maxRow) maxRow = item.row;
        if (item.col > maxCol) maxCol = item.col;
    });

    canvas.width = (maxCol + 2) * cellSize + padding * 2;
    canvas.height = (maxRow + 2) * cellSize + padding * 2;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    floorData.forEach(item => {
        const x = padding + item.col * cellSize;
        const y = padding + item.row * cellSize;

        if (item.type === 'label') {
            ctx.fillStyle = '#666';
            ctx.font = '9px sans-serif';
            ctx.fillText(item.text || '', x, y + cellSize - 4);
            return;
        }

        // Locker
        const num = item.num;
        const data = lockerData[num];
        let color = houseColors['empty'];

        if (data) {
            if (data.physical_status === 'reserve') color = houseColors['reserved'];
            else if (data.physical_status === 'maintenance') color = '#FF5252';
            else if (data.assignment_id) color = houseColors[data.house_name] || '#4CAF50';
            else color = '#4CAF50';
        }

        ctx.fillStyle = color;
        ctx.fillRect(x, y, cellSize - 2, cellSize - 2);
        ctx.strokeStyle = '#ccc';
        ctx.strokeRect(x, y, cellSize - 2, cellSize - 2);

        ctx.fillStyle = '#333';
        ctx.font = '7px sans-serif';
        ctx.fillText(num, x + 2, y + cellSize - 5);
    });
}

// Initial draw
if (floors.length > 0) drawMap();

document.getElementById('resetFilters').addEventListener('click', function() {
    document.getElementById('lockerSearch').value = '';
    drawMap();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

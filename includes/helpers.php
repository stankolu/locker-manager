<?php
require_once __DIR__ . '/db.php';

session_start();

/**
 * Escape HTML output
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Flash messages
 */
function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Get the currently selected school year (from session or default to active)
 */
function getSelectedYear(): ?array {
    if (isset($_GET['year_id'])) {
        $_SESSION['selected_year_id'] = (int)$_GET['year_id'];
    }

    $selectedId = $_SESSION['selected_year_id'] ?? null;

    if ($selectedId) {
        $year = DB::fetchOne("SELECT * FROM school_years WHERE id = ?", [$selectedId]);
        if ($year) return $year;
    }

    // Default to the active year
    $year = DB::fetchOne("SELECT * FROM school_years WHERE is_active = 1 LIMIT 1");
    if ($year) {
        $_SESSION['selected_year_id'] = $year['id'];
    }
    return $year ?: null;
}

/**
 * Get all school years for the selector
 */
function getAllYears(): array {
    return DB::fetchAll("SELECT * FROM school_years ORDER BY start_year DESC");
}

/**
 * Generate a sortable table header
 */
function sortTh(string $col, string $label, string $currentSort, string $currentDir, array $extra = []): string {
    $newDir = ($currentSort === $col && $currentDir === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($currentSort === $col) {
        $icon = $currentDir === 'ASC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>';
    }
    $params = array_merge($extra, ['sort' => $col, 'dir' => $newDir]);
    $qs = http_build_query($params);
    return "<th><a href=\"?$qs\" class=\"sort-link\">$label$icon</a></th>";
}

/**
 * Format a date for display
 */
function formatDate(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

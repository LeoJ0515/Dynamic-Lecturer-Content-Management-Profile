<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$table = $_GET['table'] ?? '';

$allowedTables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'research_areas'];

if (!in_array($table, $allowedTables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

// Get data with appropriate ordering
$orderBy = ($table === 'publications') ? 'year.desc' : 'display_order.asc';
$data = getSupabaseData($table, [], $orderBy);

echo json_encode([
    'success' => true,
    'data' => $data,
    'count' => count($data)
]);
?>
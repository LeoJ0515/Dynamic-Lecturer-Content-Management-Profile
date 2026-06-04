<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$table = $input['table'] ?? '';
$id = $input['id'] ?? '';

if (empty($table) || empty($id)) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$allowedTables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'research_areas'];

if (!in_array($table, $allowedTables)) {
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

$result = supabaseRequest('DELETE', $table . '?id=eq.' . $id, null, true);

if ($result['http_code'] === 204 || $result['http_code'] === 200) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete content', 'error' => $result['error']]);
}
?>
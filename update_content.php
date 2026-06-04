<?php
// DEBUG VERSION - REPLACE YOUR update_content.php WITH THIS
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Temporarily turn on
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Create a debug array to track everything
$debug = [
    'steps' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

try {
    $debug['steps'][] = 'Started update_content.php';
    
    session_start();
    $debug['steps'][] = 'Session started';
    $debug['session'] = $_SESSION;
    
    require_once 'database.php';
    $debug['steps'][] = 'database.php loaded';
    
    // Check authentication
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $debug['steps'][] = 'Authentication failed';
        echo json_encode(['success' => false, 'message' => 'Unauthorized', 'debug' => $debug]);
        exit;
    }
    $debug['steps'][] = 'Authentication passed';
    
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $debug['raw_input'] = $rawInput;
    $debug['steps'][] = 'Got raw input';
    
    $input = json_decode($rawInput, true);
    $debug['decoded_input'] = $input;
    $debug['steps'][] = 'Decoded JSON';
    
    $table = $input['table'] ?? '';
    $id = $input['id'] ?? '';
    $data = $input['data'] ?? [];
    
    $debug['table'] = $table;
    $debug['id'] = $id;
    $debug['data'] = $data;
    $debug['steps'][] = 'Extracted parameters';
    
    // Validate
    if (empty($table) || empty($id) || empty($data)) {
        $debug['steps'][] = 'Missing parameters';
        echo json_encode(['success' => false, 'message' => 'Missing parameters', 'debug' => $debug]);
        exit;
    }
    $debug['steps'][] = 'Parameters validated';
    
    $allowedTables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'research_areas'];
    if (!in_array($table, $allowedTables)) {
        $debug['steps'][] = 'Invalid table';
        echo json_encode(['success' => false, 'message' => 'Invalid table', 'debug' => $debug]);
        exit;
    }
    $debug['steps'][] = 'Table validated';
    
    // Remove id from data if present
    if (isset($data['id'])) {
        unset($data['id']);
        $debug['steps'][] = 'Removed id from data';
    }
    
    // Log the request we're about to make
    $debug['supabase_request'] = [
        'method' => 'PATCH',
        'endpoint' => $table . '?id=eq.' . $id,
        'data' => $data
    ];
    $debug['steps'][] = 'About to call supabaseRequest';
    
    // Make the update request
    $result = supabaseRequest('PATCH', $table . '?id=eq.' . $id, $data, true);
    
    $debug['supabase_response'] = $result;
    $debug['steps'][] = 'Received supabase response';
    
    // Clear output buffer
    ob_clean();
    
    // Check result
    if ($result['error'] === null) {
        $debug['steps'][] = 'No error from supabase';
        
        if ($result['http_code'] === 200 || $result['http_code'] === 204) {
            $debug['steps'][] = 'HTTP code success: ' . $result['http_code'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Content updated successfully',
                'data' => $result['data'] ?? null,
                'debug' => $debug
            ]);
        } else {
            $debug['steps'][] = 'Unexpected HTTP code: ' . $result['http_code'];
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update content',
                'http_code' => $result['http_code'],
                'debug' => $debug
            ]);
        }
    } else {
        $debug['steps'][] = 'Error from supabase: ' . $result['error'];
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update content',
            'error' => $result['error'],
            'debug' => $debug
        ]);
    }
    
} catch (Exception $e) {
    $debug['steps'][] = 'Exception caught: ' . $e->getMessage();
    $debug['exception'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'debug' => $debug
    ]);
}

ob_end_flush();
?>
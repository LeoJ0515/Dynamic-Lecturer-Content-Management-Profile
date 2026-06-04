<?php
// Turn on output buffering to catch any warnings/errors
ob_start();

// Set error reporting - log errors but don't display them
error_reporting(E_ALL);
ini_set('display_errors', 0); // CRITICAL: Don't display errors
ini_set('log_errors', 1);

// Set JSON header FIRST
header('Content-Type: application/json');

try {
    // Start session
    session_start();
    
    // Include database
    require_once 'database.php';
    
    // Check authentication
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        ob_end_flush();
        exit;
    }
    
    // Get parameters
    $table = $_GET['table'] ?? '';
    $id = $_GET['id'] ?? '';
    
    if (empty($table) || empty($id)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        ob_end_flush();
        exit;
    }
    
    // Validate table
    $allowedTables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'research_areas'];
    
    if (!in_array($table, $allowedTables)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid table']);
        ob_end_flush();
        exit;
    }
    
    // Make request to Supabase using your function
    $result = supabaseRequest('GET', $table . '?id=eq.' . $id . '&select=*', null, true);
    
    // Clear any warnings/errors that might have been output
    ob_clean();
    
    // Check if request was successful (no error and HTTP 200)
    if ($result['error'] === null && $result['http_code'] === 200) {
        // Check if data exists and is an array with items
        if (isset($result['data']) && is_array($result['data']) && count($result['data']) > 0) {
            // Return the first item directly (not wrapped in success)
            echo json_encode($result['data'][0]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Content not found']);
        }
    } else {
        // Handle error cases
        $errorMsg = $result['error'] ?? 'Unknown error';
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch content',
            'error' => $errorMsg,
            'http_code' => $result['http_code']
        ]);
    }
    
} catch (Exception $e) {
    // Catch any exceptions
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>
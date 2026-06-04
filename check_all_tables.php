<?php
require_once 'database.php';

header('Content-Type: application/json');

$tables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'profile'];

$results = [];

foreach ($tables as $table) {
    // Try to get count first
    $countResult = supabaseRequest('GET', $table . '?select=id', null, false);
    
    $results[$table] = [
        'exists' => $countResult['http_code'] === 200,
        'http_code' => $countResult['http_code'],
        'count' => $countResult['data'] ? count($countResult['data']) : 0,
        'sample' => []
    ];
    
    // If table exists and has data, get first record
    if ($results[$table]['count'] > 0) {
        $dataResult = supabaseRequest('GET', $table . '?select=*&limit=1', null, false);
        $results[$table]['sample'] = $dataResult['data'][0] ?? [];
    }
    
    // Also check if table might have different column names
    if ($table === 'supervision' && $results[$table]['count'] === 0) {
        // Try to get any data without column specification
        $anyData = supabaseRequest('GET', $table . '?limit=5', null, false);
        $results[$table]['raw_check'] = $anyData;
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("=== ADD CONTENT REQUEST ===");

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("Unauthorized access attempt");
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("Input received: " . print_r($input, true));

$table = $input['table'] ?? '';
$data = $input['data'] ?? [];

if (empty($table) || empty($data)) {
    error_log("Missing parameters");
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$allowedTables = ['supervision', 'teaching', 'research_projects', 'publications', 'awards', 'appointments', 'invited_talks', 'research_areas'];

if (!in_array($table, $allowedTables)) {
    error_log("Invalid table: $table");
    echo json_encode(['success' => false, 'message' => 'Invalid table']);
    exit;
}

// Define column structures for each table based on your actual database schema
$tableSchemas = [
    'supervision' => [
        'student_name' => 'string',
        'thesis_title' => 'string',
        'degree' => 'string',
        'program' => 'string',
        'role' => 'string',
        'start_year' => 'integer',
        'completion_year' => 'integer',
        'status' => 'string',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'teaching' => [
        'id' => 'integer',
        'course_code' => 'string',
        'course_name' => 'string',
        'institution' => 'string',
        'semester' => 'string',
        'year' => 'integer',
        'description' => 'text',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'publications' => [
        'title' => 'string',
        'authors' => 'string',
        'journal' => 'string',
        'year' => 'integer',
        'volume' => 'string',
        'issue' => 'string',
        'pages' => 'string',
        'doi' => 'string',
        'type' => 'string',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'awards' => [
        'title' => 'string',
        'organization' => 'string',
        'year' => 'integer',
        'description' => 'string',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'appointments' => [
        'id' => 'integer',
        'position' => 'string',
        'organization' => 'string',
        'start_year' => 'integer',
        'end_year' => 'integer',
        'is_current' => 'boolean',
        'description' => 'text',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'invited_talks' => [
        'id' => 'integer',
        'event_name' => 'string',
        'talk_title' => 'string',
        'venue' => 'string',
        'year' => 'integer',
        'description' => 'text',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'research_areas' => [
        'area_name' => 'string',
        'display_order' => 'integer',
        'created_at' => 'datetime'
    ],
    'research_projects' => [
        'id' => 'integer',
        'project_title' => 'string',
        'type_of_grant' => 'string',
        'funding_body' => 'string',
        'start_year' => 'integer',
        'end_year' => 'integer',
        'status' => 'string',
        'description' => 'text',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ]
];

// Clean and validate data based on table schema
if (isset($tableSchemas[$table])) {
    $cleanData = [];
    $schema = $tableSchemas[$table];

    foreach ($schema as $field => $type) {
        if (isset($data[$field])) {
            // Convert to appropriate type
            $value = $data[$field];

            switch ($type) {
                case 'integer':
                    $cleanData[$field] = intval($value);
                    break;
                case 'datetime':
                    if (empty($value)) {
                        $cleanData[$field] = date('Y-m-d H:i:s');
                    } else {
                        $cleanData[$field] = $value;
                    }
                    break;
                default:
                    $cleanData[$field] = $value;
            }
        }
    }

    // Handle special case for supervision table (mapping from frontend)
    if ($table === 'supervision') {
        // Map any frontend field names to database column names
        $fieldMapping = [
            'name' => 'student_name',
            'title' => 'thesis_title'
        ];

        foreach ($fieldMapping as $frontendField => $dbField) {
            if (isset($data[$frontendField]) && !isset($cleanData[$dbField])) {
                $cleanData[$dbField] = $data[$frontendField];
            }
        }
    }

    $data = $cleanData;
}

// Add display_order if not present and table has it
if (!isset($data['display_order']) && isset($tableSchemas[$table]['display_order'])) {
    // Get max display_order and add 1
    $existingData = getSupabaseData($table, [], 'display_order.desc');
    $maxOrder = 0;
    if (!empty($existingData) && isset($existingData[0]['display_order'])) {
        $maxOrder = (int) $existingData[0]['display_order'];
    }
    $data['display_order'] = $maxOrder + 1;
}

// Add created_at if not present and table has it
if (!isset($data['created_at']) && isset($tableSchemas[$table]['created_at'])) {
    $data['created_at'] = date('Y-m-d H:i:s');
}

error_log("Cleaned data for $table: " . print_r($data, true));

// Use the addContent function from database.php
$result = addContent($table, $data);

if ($result['success']) {
    error_log("Success! Added to $table");

    // ONLY return success message, NO data
    echo json_encode([
        'success' => true,
        'message' => 'Content added successfully'
    ]);
} else {
    $errorMessage = $result['error'] ?? 'Failed to add content';
    error_log("Error adding to $table: $errorMessage");

    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
}
?>
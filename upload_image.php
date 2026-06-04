<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (php.ini limit)',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE (form limit)',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];
    $errorMessage = $uploadErrors[$_FILES['image']['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $errorMessage]);
    exit;
}

$imageType = $_POST['type'] ?? '';
if (!in_array($imageType, ['profile', 'background'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid image type']);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed. Detected: ' . $mimeType]);
    exit;
}

// Validate file size (max 5MB)
if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB. Your file: ' . round($_FILES['image']['size'] / 1024 / 1024, 2) . 'MB']);
    exit;
}

// Step 1: Upload image to Supabase Storage
$uploadResult = uploadImage($_FILES['image'], 'images', $imageType);

if (!$uploadResult['success']) {
    echo json_encode(['success' => false, 'message' => $uploadResult['error']]);
    exit;
}

// Step 2: Get ALL profiles with direct API call to ensure we get accurate data
error_log("=== IMAGE UPLOAD DEBUG ===");
error_log("Fetching all profiles...");

// Try direct API call first for debugging
$directCheck = supabaseRequest('GET', 'profile?select=*', null, true);
error_log("Direct API response: " . print_r($directCheck, true));

if (!empty($directCheck['data'])) {
    $profiles = $directCheck['data'];
    error_log("Found " . count($profiles) . " profiles via direct API");
} else {
    // Fallback to your helper function
    $profiles = getSupabaseData('profile', [], 'id.asc');
    error_log("Found " . count($profiles) . " profiles via getSupabaseData");
}

// Log all profile IDs found
if (!empty($profiles)) {
    $ids = array_column($profiles, 'id');
    error_log("Profile IDs found: " . implode(', ', $ids));
} else {
    error_log("NO PROFILES FOUND in database");
}

$field = $imageType === 'profile' ? 'profile_picture' : 'background_picture';
$updateData = [
    $field => $uploadResult['url'],
    'updated_at' => date('Y-m-d H:i:s')
];

if (empty($profiles)) {
    // NO profile exists - create ONE profile
    error_log("NO PROFILES EXIST - Creating new profile with image");
    
    $createData = [
        'full_name' => 'New User', // Add a default name so it's not empty
        'designation' => '',
        'department' => '',
        'institution' => '',
        'email' => '',
        'contact' => '',
        'bio' => '',
        'profile_picture' => '',
        'background_picture' => '',
        'cv_file' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add the image field
    $createData[$field] = $uploadResult['url'];
    
    error_log("Creating new profile with data: " . print_r($createData, true));
    
    $createResult = supabaseRequest('POST', 'profile', $createData, true);
    error_log("Create result: " . print_r($createResult, true));
    
    if ($createResult['http_code'] === 201 || $createResult['http_code'] === 200) {
        echo json_encode([
            'success' => true,
            'url' => $uploadResult['url'],
            'message' => 'Image uploaded and profile created successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Image uploaded but failed to create profile. HTTP Code: ' . $createResult['http_code']
        ]);
    }
} else {
    // Profile EXISTS - update the FIRST profile (lowest ID)
    // Sort profiles by ID to ensure we get the oldest
    usort($profiles, function($a, $b) {
        return $a['id'] - $b['id'];
    });
    
    $firstProfile = $profiles[0];
    $profileId = $firstProfile['id'];
    
    error_log("PROFILES EXIST - Updating profile ID: $profileId with $field = " . $uploadResult['url']);
    error_log("Current profile data: " . print_r($firstProfile, true));
    
    // Update the profile
    $updateResult = supabaseRequest('PATCH', 'profile?id=eq.' . $profileId, $updateData, true);
    error_log("Update result: HTTP Code: " . $updateResult['http_code'] . ", Response: " . print_r($updateResult, true));
    
    if ($updateResult['http_code'] === 204 || $updateResult['http_code'] === 200) {
        $response = [
            'success' => true,
            'url' => $uploadResult['url'],
            'message' => 'Image uploaded successfully'
        ];
        
        // If there are multiple profiles, warn about duplicates but don't delete automatically
        if (count($profiles) > 1) {
            $response['warning'] = 'Multiple profiles found. Only the first profile was updated.';
            $response['profile_count'] = count($profiles);
            $response['updated_profile_id'] = $profileId;
            error_log("WARNING: Multiple profiles exist. Profile IDs: " . implode(', ', array_column($profiles, 'id')));
        }
        
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Image uploaded but failed to update profile. HTTP Code: ' . $updateResult['http_code']
        ]);
    }
}
?>
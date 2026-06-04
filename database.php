<?php
// database.php - Supabase API integration with error handling

// Configuration - Replace with your actual Supabase credentials
define('SUPABASE_URL', 'https://hglignqegzahrscqnpeu.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhnbGlnbnFlZ3phaHJzY3FucGV1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzMzNzE0NjQsImV4cCI6MjA4ODk0NzQ2NH0.cc2ihTcQEnwhSI25A6oS2OMYVKJ1L4JgVGIoSnA5IwU');
define('SUPABASE_SERVICE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImhnbGlnbnFlZ3phaHJzY3FucGV1Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc3MzM3MTQ2NCwiZXhwIjoyMDg4OTQ3NDY0fQ.0QUoj7eiv1zQTY7uhreE57mgVolN9946tfnFTT-xoWM'); // For admin operations

/**
 * Make a request to Supabase API
 */
function supabaseRequest($method, $endpoint, $data = null, $useServiceKey = false) {
    $url = SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . ($useServiceKey ? SUPABASE_SERVICE_KEY : SUPABASE_ANON_KEY),
        'Content-Type: application/json'
    ];
    
    // Add prefer header for different request types
    if ($method === 'GET') {
        $headers[] = 'Prefer: count=exact';
    } elseif ($method === 'POST') {
        $headers[] = 'Prefer: return=representation';
    } elseif ($method === 'PATCH' || $method === 'PUT') {
        $headers[] = 'Prefer: return=representation';
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($data && ($method === 'POST' || $method === 'PATCH' || $method === 'PUT')) {
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    // Log for debugging
    error_log("Supabase Request - Method: $method, URL: $url, HTTP Code: $httpCode");
    error_log("Supabase Response - Body: " . substr($response, 0, 500)); // Log first 500 chars
    
    if ($error) {
        error_log("Supabase cURL Error: " . $error);
        return [
            'error' => $error,
            'http_code' => $httpCode,
            'data' => null
        ];
    }
    
    // Try to decode JSON response
    $decoded = json_decode($response, true);
    
    // For 204 (No Content) responses
    if ($httpCode === 204) {
        return [
            'error' => null,
            'http_code' => $httpCode,
            'data' => []
        ];
    }
    
    // For successful responses
    if ($httpCode >= 200 && $httpCode < 300) {
        return [
            'error' => null,
            'http_code' => $httpCode,
            'data' => $decoded // This is what getSupabaseData expects
        ];
    }
    
    // For error responses
    return [
        'error' => $error ?: 'HTTP Error: ' . $httpCode,
        'http_code' => $httpCode,
        'data' => $decoded
    ];
}

/**
 * Get data from a table with optional filters
 */
function getSupabaseData($table, $filters = [], $orderBy = 'display_order.asc') {
    // Start with select all
    $query = $table . "?select=*";
    
    // Add filters if any
    if (!empty($filters)) {
        foreach ($filters as $key => $value) {
            $query .= "&" . urlencode($key) . "=eq." . urlencode($value);
        }
    }
    
    // Add ordering if specified
    if ($orderBy) {
        $query .= "&order=" . urlencode($orderBy);
    }
    
    // Log the query for debugging
    error_log("getSupabaseData Query for $table: " . $query);
    
    // Make the request - use false for anon key
    $result = supabaseRequest('GET', $query, null, false);
    
    // Log the full result for debugging
    error_log("getSupabaseData Result for $table: " . print_r($result, true));
    
    // Check for errors - your supabaseRequest returns 'error' key
    if (isset($result['error']) && $result['error']) {
        error_log("getSupabaseData Error for table $table: " . $result['error']);
        return [];
    }
    
    // Check HTTP code
    if (!isset($result['http_code']) || $result['http_code'] !== 200) {
        error_log("getSupabaseData HTTP Error for table $table: " . ($result['http_code'] ?? 'unknown'));
        return [];
    }
    
    // IMPORTANT: Based on your debug output, the data is in $result['data']
    // But your supabaseRequest might return it in a different structure
    if (isset($result['data']) && is_array($result['data'])) {
        error_log("getSupabaseData Found " . count($result['data']) . " records for $table");
        return $result['data'];
    }
    
    // If data is in a nested structure
    if (isset($result['data']['data']) && is_array($result['data']['data'])) {
        error_log("getSupabaseData Found nested data for $table");
        return $result['data']['data'];
    }
    
    // If response is directly an array
    if (is_array($result) && !isset($result['data']) && !isset($result['error'])) {
        error_log("getSupabaseData Result is directly an array for $table");
        return $result;
    }
    
    error_log("getSupabaseData No valid data found for $table");
    return [];
}

/**
 * Update profile - will create if doesn't exist, update if exists
 * This function ensures only ONE profile record exists
 */
function updateProfile($data) {
    // Get ALL profiles
    $profiles = getSupabaseData('profile', [], 'id.asc');
    
    // Remove id from data if present
    unset($data['id']);
    
    if (empty($profiles)) {
        // No profile exists - create one
        error_log("No profile found. Creating new profile...");
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $result = supabaseRequest('POST', 'profile', $data, true);
        
        if ($result['http_code'] === 201 || $result['http_code'] === 200) {
            error_log("Profile created successfully");
            return true;
        } else {
            error_log("Failed to create profile. HTTP Code: " . $result['http_code']);
            return false;
        }
    } else {
        // Profile exists - update the FIRST one
        $profileId = $profiles[0]['id'];
        error_log("Updating profile with ID: " . $profileId);
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $result = supabaseRequest('PATCH', 'profile?id=eq.' . $profileId, $data, true);
        
        // If there are multiple profiles, delete the extras
        if (count($profiles) > 1) {
            for ($i = 1; $i < count($profiles); $i++) {
                supabaseRequest('DELETE', 'profile?id=eq.' . $profiles[$i]['id'], null, true);
                error_log("Deleted duplicate profile ID: " . $profiles[$i]['id']);
            }
        }
        
        if ($result['http_code'] === 204 || $result['http_code'] === 200) {
            error_log("Profile updated successfully");
            return true;
        } else {
            error_log("Failed to update profile. HTTP Code: " . $result['http_code']);
            return false;
        }
    }
}

/**
 * Add content to any table - handles timestamps based on table
 */
function addContent($table, $data) {
    // Add timestamps based on table schema
    // Profile table has NO created_at, only updated_at
    if ($table === 'profile') {
        $data['updated_at'] = date('Y-m-d H:i:s');
    } else {
        // All other tables have created_at
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
    }
    
    $result = supabaseRequest('POST', $table, $data, true);
    
    if ($result['http_code'] === 201 || $result['http_code'] === 200) {
        // Return the created data (with id if available)
        if (isset($result['data'][0])) {
            return ['success' => true, 'data' => $result['data'][0]];
        }
        return ['success' => true, 'data' => $data];
    }
    
    return ['success' => false, 'error' => 'Failed to add content'];
}

/**
 * Update content in any table
 */
function updateContent($table, $id, $data) {
    // Remove id from data if present
    unset($data['id']);
    
    // Add updated_at if table has it
    if ($table === 'profile') {
        $data['updated_at'] = date('Y-m-d H:i:s');
    }
    // Other tables don't have updated_at in your schema
    
    $result = supabaseRequest('PATCH', $table . '?id=eq.' . $id, $data, true);
    
    return $result['http_code'] === 204 || $result['http_code'] === 200;
}

/**
 * Delete content from any table
 */
function deleteContent($table, $id) {
    $result = supabaseRequest('DELETE', $table . '?id=eq.' . $id, null, true);
    
    return $result['http_code'] === 204 || $result['http_code'] === 200;
}

/**
 * Authenticate user with email and password
 */
function authenticateUser($email, $password) {
    $result = supabaseRequest('GET', "users?email=eq." . urlencode($email) . "&select=*");
    
    if ($result['error'] || $result['http_code'] !== 200) {
        error_log("Authentication error: " . print_r($result, true));
        return false;
    }
    
    $users = $result['data'] ?? [];
    
    if (count($users) > 0) {
        // Direct password comparison (as per your schema with plain text)
        if (isset($users[0]['password']) && $password === $users[0]['password']) {
            return [
                'id' => $users[0]['id'],
                'email' => $users[0]['email']
            ];
        }
    }
    
    return false;
}

/**
 * Upload image to Supabase Storage
 */
function uploadImage($file, $bucket = 'images', $path = 'profile') {
    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $path . '_' . time() . '_' . uniqid() . '.' . $extension;
    
    // Read file content
    $fileContent = file_get_contents($file['tmp_name']);
    
    // Upload to Supabase Storage (using the correct endpoint)
    $uploadUrl = SUPABASE_URL . '/storage/v1/object/' . $bucket . '/' . $filename;
    
    $ch = curl_init($uploadUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fileContent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_SERVICE_KEY,
        'Content-Type: ' . $mimeType
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log for debugging
    error_log("Supabase Upload - Code: $httpCode, File: $filename");
    
    if ($httpCode === 200 || $httpCode === 201) {
        // Return the public URL
        $publicUrl = SUPABASE_URL . '/storage/v1/object/public/' . $bucket . '/' . $filename;
        return [
            'success' => true,
            'url' => $publicUrl,
            'filename' => $filename
        ];
    } else {
        // Try to parse error message
        $errorMsg = "Upload failed with code: $httpCode";
        $responseData = json_decode($response, true);
        if (isset($responseData['error'])) {
            $errorMsg .= " - " . $responseData['error'];
        }
        if (isset($responseData['message'])) {
            $errorMsg .= ": " . $responseData['message'];
        }
        if ($curlError) {
            $errorMsg .= " (CURL: $curlError)";
        }
        
        return [
            'success' => false,
            'error' => $errorMsg
        ];
    }
}

/**
 * Test database connection
 */
function testConnection() {
    $result = supabaseRequest('GET', 'profile?select=id&limit=1');
    return !$result['error'] && $result['http_code'] === 200;
}

/**
 * Ensure only ONE profile exists - keeps the first profile, deletes others
 * Returns the ID of the kept profile
 */
function ensureSingleProfile() {
    $profiles = getSupabaseData('profile', [], 'id.asc');
    
    if (empty($profiles)) {
        return null; // No profile exists
    }
    
    if (count($profiles) === 1) {
        return $profiles[0]['id']; // Only one profile, return its ID
    }
    
    // Multiple profiles exist - keep the first one, delete others
    $keepProfile = $profiles[0];
    $keptId = $keepProfile['id'];
    
    error_log("Multiple profiles found. Keeping ID: $keptId, deleting " . (count($profiles) - 1) . " others");
    
    for ($i = 1; $i < count($profiles); $i++) {
        $deleteId = $profiles[$i]['id'];
        $result = supabaseRequest('DELETE', 'profile?id=eq.' . $deleteId, null, true);
        
        if ($result['http_code'] === 204 || $result['http_code'] === 200) {
            error_log("Deleted duplicate profile ID: $deleteId");
        } else {
            error_log("Failed to delete profile ID: $deleteId");
        }
    }
    
    return $keptId;
}
?>
<?php
session_start();
header('Content-Type: application/json');

// Configuration - Replace with your actual Supabase credentials
define('SUPABASE_URL', '');
define('SUPABASE_ANON_KEY', '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

// Get user from Supabase with plain text password
$url = SUPABASE_URL . "/rest/v1/users?email=eq." . urlencode($email) . "&select=*";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . SUPABASE_ANON_KEY,
    'Authorization: Bearer ' . SUPABASE_ANON_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$users = json_decode($response, true);

if (empty($users)) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$user = $users[0];

// Direct string comparison (no password_verify)
if ($password === $user['password']) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid password']);
}
?>

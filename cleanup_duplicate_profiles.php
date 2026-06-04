<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

echo "<h2>🧹 Profile Cleanup Tool</h2>";

// Get all profiles ordered by ID
$profiles = getSupabaseData('profile', [], 'id.asc');

if (empty($profiles)) {
    echo "<p>No profiles found.</p>";
    echo '<p><a href="index.php">Back to Portfolio</a></p>';
    exit;
}

echo "<p>Found <strong>" . count($profiles) . "</strong> profile(s).</p>";

if (count($profiles) === 1) {
    echo "<p style='color:green'>✅ Only one profile exists. No cleanup needed.</p>";
    echo "<pre>";
    print_r($profiles[0]);
    echo "</pre>";
    echo '<p><a href="index.php">Back to Portfolio</a></p>';
    exit;
}

// Keep the first profile (oldest by ID), delete the rest
$keepProfile = $profiles[0];
$deleted = 0;

echo "<h3>Keeping Profile ID: " . $keepProfile['id'] . "</h3>";
echo "<pre>";
print_r($keepProfile);
echo "</pre>";

echo "<h3>Deleting duplicate profiles:</h3>";

for ($i = 1; $i < count($profiles); $i++) {
    $profileId = $profiles[$i]['id'];
    
    // Check if this profile has images we want to keep
    if (!empty($profiles[$i]['profile_picture']) || !empty($profiles[$i]['background_picture'])) {
        echo "<p style='color:orange'>⚠ Profile $profileId has images. Moving them to main profile...</p>";
        
        // Move images to main profile if main doesn't have them
        $updateMain = [];
        if (empty($keepProfile['profile_picture']) && !empty($profiles[$i]['profile_picture'])) {
            $updateMain['profile_picture'] = $profiles[$i]['profile_picture'];
            echo "<p>→ Moving profile picture to main profile</p>";
        }
        if (empty($keepProfile['background_picture']) && !empty($profiles[$i]['background_picture'])) {
            $updateMain['background_picture'] = $profiles[$i]['background_picture'];
            echo "<p>→ Moving background picture to main profile</p>";
        }
        
        if (!empty($updateMain)) {
            $updateMain['updated_at'] = date('Y-m-d H:i:s');
            $updateResult = supabaseRequest('PATCH', 'profile?id=eq.' . $keepProfile['id'], $updateMain, true);
            if ($updateResult['http_code'] === 204 || $updateResult['http_code'] === 200) {
                echo "<p style='color:green'>✓ Images moved successfully</p>";
            }
        }
    }
    
    // Delete the duplicate profile
    $result = supabaseRequest('DELETE', 'profile?id=eq.' . $profileId, null, true);
    
    if ($result['http_code'] === 204 || $result['http_code'] === 200) {
        echo "<p style='color:green'>✓ Deleted profile ID: " . $profileId . "</p>";
        $deleted++;
    } else {
        echo "<p style='color:red'>✗ Failed to delete profile ID: " . $profileId . "</p>";
    }
}

echo "<h3>✅ Cleanup Complete!</h3>";
echo "<p>Deleted <strong>" . $deleted . "</strong> duplicate profile(s).</p>";

// Show the remaining profile
echo "<h3>Remaining Profile:</h3>";
$remainingProfiles = getSupabaseData('profile', [], 'id.asc');
echo "<pre>";
print_r($remainingProfiles[0]);
echo "</pre>";

echo '<p><a href="index.php">View Portfolio</a> | <a href="edit_profile.php">Edit Profile</a></p>';
?>
<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Get profile data with debugging
error_log("Fetching profile data...");
$profiles = getSupabaseData('profile', [], 'id.asc');
error_log("getSupabaseData returned: " . print_r($profiles, true));

if (empty($profiles)) {
    error_log("No profiles found via getSupabaseData, checking direct API...");
    $directCheck = supabaseRequest('GET', 'profile?select=*', null, true);
    error_log("Direct API check: " . print_r($directCheck, true));
    
    if (!empty($directCheck['data'])) {
        $profiles = $directCheck['data'];
        error_log("Using direct API data: " . count($profiles) . " profiles found");
    }
}

$profile = !empty($profiles) ? $profiles[0] : [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [
        'full_name' => $_POST['full_name'] ?? '',
        'designation' => $_POST['designation'] ?? '',
        'department' => $_POST['department'] ?? '',
        'institution' => $_POST['institution'] ?? '',
        'email' => $_POST['email'] ?? '',
        'contact' => $_POST['contact'] ?? '',
        'bio' => $_POST['bio'] ?? ''
    ];
    
    $updateData['updated_at'] = date('Y-m-d H:i:s');
    
    if (empty($profiles)) {
        $result = supabaseRequest('POST', 'profile', $updateData, true);
        if ($result['http_code'] === 201 || $result['http_code'] === 200) {
            $_SESSION['profile_just_created'] = true;
            header('Location: edit_profile.php?created=1');
            exit;
        } else {
            $error = 'Failed to create profile. Please try again.';
            error_log("Profile creation failed: " . print_r($result, true));
        }
    } else {
        $profileId = $profiles[0]['id'];
        $result = supabaseRequest('PATCH', 'profile?id=eq.' . $profileId, $updateData, true);
        if ($result['http_code'] === 204 || $result['http_code'] === 200) {
            $_SESSION['profile_just_updated'] = true;
            header('Location: edit_profile.php?updated=1');
            exit;
        } else {
            $error = 'Failed to update profile. Please try again.';
            error_log("Profile update failed: " . print_r($result, true));
        }
    }
}

if (isset($_GET['created']) || isset($_GET['updated'])) {
    unset($_SESSION['profile_just_created']);
    unset($_SESSION['profile_just_updated']);
    
    $maxRetries = 3;
    $retryDelay = 500000;
    $profiles = [];
    
    for ($i = 0; $i < $maxRetries; $i++) {
        if ($i > 0) usleep($retryDelay);
        $profiles = getSupabaseData('profile', [], 'id.asc');
        if (!empty($profiles)) break;
    }
    
    $profile = !empty($profiles) ? $profiles[0] : [];
    $success = isset($_GET['created']) ? 'Profile created successfully!' : 'Profile updated successfully!';
    if (empty($profiles)) {
        $warning = 'Your profile was saved successfully, but the data is taking a moment to appear. Please refresh the page in a few seconds.';
    }
}

$profileCount = count($profiles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo empty($profile) ? 'Create' : 'Edit'; ?> Profile - Academic Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    /* Color Hunt Palette: #B5E18B, #F0FFC2, #EAE6BC, #28396C */
    --deep-blue: #d8e2ff;
    --soft-green: #B5E18B;
    --light-mint: #F0FFC2;
    --warm-beige: #1e1e1e;
    
    --primary-color: var(--deep-blue);
    --secondary-color: var(--soft-green);
    --accent-color: var(--light-mint);
    --bg-light: var(--warm-beige);
    
    --text-dark: #ffffff;
    --text-light: #0f2f4f;
    --card-bg: #ffffff;
    
    --primary-gradient: linear-gradient(135deg, var(--deep-blue) 0%, var(--soft-green) 100%);
    --secondary-gradient: linear-gradient(135deg, var(--soft-green) 0%, var(--light-mint) 100%);
    --accent-gradient: linear-gradient(135deg, var(--light-mint) 0%, var(--warm-beige) 100%);
    
    --shadow-sm: 0 4px 12px rgba(40, 57, 108, 0.08);
    --shadow-md: 0 8px 24px rgba(40, 57, 108, 0.12);
    --shadow-lg: 0 16px 32px rgba(40, 57, 108, 0.16);
    
    --radius-sm: 12px;
    --radius-md: 20px;
    --radius-lg: 30px;
    --radius-xl: 40px;
    --radius-full: 50px;
    
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-bounce: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

body {
    font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    background: var(--bg-light);
    position: relative;
    overflow-x: hidden;
    color: var(--text-dark);
    line-height: 1.6;
}

body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, rgba(40, 57, 108, 0.06) 0%, transparent 30%),
        radial-gradient(circle at 80% 70%, rgba(181, 225, 139, 0.06) 0%, transparent 30%),
        radial-gradient(circle at 40% 80%, rgba(240, 255, 194, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 90% 20%, rgba(234, 230, 188, 0.08) 0%, transparent 40%);
    pointer-events: none;
    z-index: -1;
}

.edit-container {
    max-width: 1000px;
    margin: 2rem auto;
    padding: 0 1.5rem;
    position: relative;
    z-index: 10;
    animation: containerSlideUp 0.8s var(--transition-bounce) forwards;
}

@keyframes containerSlideUp {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-back {
    position: fixed;
    top: 25px;
    left: 25px;
    background: white;
    color: var(--deep-blue);
    padding: 0.8rem 1.8rem;
    border-radius: var(--radius-full);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-weight: 600;
    box-shadow: var(--shadow-md);
    transition: var(--transition-smooth);
    z-index: 1000;
    border: 1px solid rgba(40, 57, 108, 0.1);
}

.btn-back:hover {
    transform: translateX(-5px);
    box-shadow: var(--shadow-lg);
    background: var(--deep-blue);
    color: white;
}

.btn-back i {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.btn-back:hover i {
    transform: translateX(-3px);
}

.edit-card {
    background: white;
    border-radius: var(--radius-xl);
    padding: 3rem;
    box-shadow: var(--shadow-lg);
    transform: translateY(0);
    transition: var(--transition-smooth);
    border: 1px solid rgba(40, 57, 108, 0.05);
}

.edit-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(40, 57, 108, 0.2);
}

.edit-card h2 {
    color: var(--deep-blue);
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 3rem;
    position: relative;
    padding-bottom: 1.5rem;
}

.edit-card h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: var(--primary-gradient);
    border-radius: 2px;
    transition: width 0.3s ease;
}

.edit-card:hover h2::after {
    width: 120px;
}

.alert {
    background: white;
    border: none;
    border-radius: var(--radius-md);
    padding: 1.2rem 1.8rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
    animation: alertSlide 0.5s ease forwards;
    box-shadow: var(--shadow-sm);
    border-left: 4px solid;
}

@keyframes alertSlide {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

.alert-success { border-left-color: #10b981; color: #065f46; }
.alert-warning { border-left-color: #f59e0b; color: #92400e; }
.alert-danger { border-left-color: #ef4444; color: #991b1b; }

.profile-data {
    background: var(--bg-light);
    border-radius: var(--radius-lg);
    padding: 2rem;
    margin-bottom: 2.5rem;
    border: 1px solid rgba(40, 57, 108, 0.1);
}

.profile-data h4 {
    color: var(--deep-blue);
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.profile-data p {
    color: var(--text-dark);
    margin-bottom: 0.8rem;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
}

.profile-data strong {
    color: var(--deep-blue);
    font-weight: 600;
    min-width: 120px;
}

.empty-state {
    background: var(--bg-light);
    border-radius: var(--radius-lg);
    padding: 3rem;
    text-align: center;
    margin-bottom: 2.5rem;
    border: 2px dashed var(--soft-green);
}

.empty-state i {
    font-size: 4rem;
    color: var(--soft-green);
    margin-bottom: 1.5rem;
}

.empty-state h5 {
    color: var(--deep-blue);
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.empty-state p {
    color: var(--text-light);
    font-size: 1rem;
}

.form-label {
    color: var(--deep-blue);
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control {
    background: var(--bg-light);
    border: 2px solid rgba(40, 57, 108, 0.1);
    border-radius: var(--radius-md);
    padding: 0.9rem 1.2rem;
    color: var(--text-dark);
    font-size: 1rem;
    transition: var(--transition-smooth);
}

.form-control:focus {
    border-color: var(--soft-green);
    box-shadow: 0 0 0 4px rgba(181, 225, 139, 0.15);
    outline: none;
    background: white;
}

.form-control::placeholder {
    color: var(--text-light);
    opacity: 0.6;
}

textarea.form-control {
    min-height: 120px;
    resize: vertical;
}

.action-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 1.2rem;
    margin-top: 3rem;
    position: relative;
}

.btn-cancel-modern {
    padding: 1rem 2.5rem;
    border-radius: 60px;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    background: transparent;
    color: var(--deep-blue);
    border: 2px solid var(--soft-green);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(40, 57, 108, 0.05);
}

.btn-cancel-modern::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(181, 225, 139, 0.15);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
    z-index: 0;
}

.btn-cancel-modern:hover::before {
    width: 300px;
    height: 300px;
}

.btn-cancel-modern:hover {
    border-color: var(--deep-blue);
    color: var(--deep-blue);
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(40, 57, 108, 0.15);
}

.btn-cancel-modern i, 
.btn-cancel-modern span {
    position: relative;
    z-index: 1;
}

.btn-cancel-modern i {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.btn-cancel-modern:hover i {
    transform: translateX(-3px) scale(1.1);
}

.btn-save-modern {
    padding: 1rem 3rem;
    border-radius: 60px;
    font-weight: 600;
    font-size: 1rem;
    letter-spacing: 0.5px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    background: var(--primary-gradient);
    color: white;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(40, 57, 108, 0.2);
}

.btn-save-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s ease;
}

.btn-save-modern:hover::before {
    left: 100%;
}

.btn-glow {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 60px;
    background: var(--primary-gradient);
    filter: blur(15px);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.btn-save-modern:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 15px 30px rgba(40, 57, 108, 0.3);
}

.btn-save-modern:hover .btn-glow {
    opacity: 0.5;
}

.btn-save-modern:active {
    transform: translateY(-1px) scale(1);
    box-shadow: 0 8px 15px rgba(40, 57, 108, 0.3);
}

.btn-save-modern i {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
}

.btn-save-modern:hover i {
    transform: scale(1.2) rotate(2deg);
}

.btn-save-modern.loading {
    pointer-events: none;
    opacity: 0.9;
}

.btn-save-modern.loading i {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.btn-cleanup {
    background: white;
    border: 1px solid var(--soft-green);
    color: var(--deep-blue);
    padding: 0.5rem 1.2rem;
    border-radius: var(--radius-full);
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition-smooth);
    cursor: pointer;
}

.btn-cleanup:hover {
    background: var(--soft-green);
    color: var(--deep-blue);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.input-icon-wrapper {
    position: relative;
}

.input-icon-wrapper i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--soft-green);
    font-size: 1.2rem;
}

.input-icon-wrapper .form-control {
    padding-left: 45px;
}

@media (max-width: 768px) {
    .edit-card { padding: 2rem 1.5rem; border-radius: var(--radius-lg); }
    .edit-card h2 { font-size: 2rem; margin-bottom: 2rem; }
    .btn-back { top: 15px; left: 15px; padding: 0.6rem 1.2rem; font-size: 0.9rem; }
    .action-buttons { flex-direction: column; gap: 1rem; }
    .btn-cancel-modern, .btn-save-modern { width: 100%; padding: 0.9rem 2rem; }
    .profile-data p { flex-direction: column; align-items: flex-start; }
    .profile-data strong { margin-bottom: 0.3rem; }
}

@media (max-width: 480px) {
    .edit-card { padding: 1.5rem 1rem; }
    .edit-card h2 { font-size: 1.8rem; }
    .profile-data { padding: 1.5rem; }
}

/* Make the main form title dark, but leave other headings untouched */
.edit-card h2 {
    color: #1e1e1e !important;
}

/* Make form labels dark */
.form-label {
    color: #1e1e1e !important;
}

/* Make Cancel button text dark */
.btn-cancel-modern {
    color: #1e1e1e !important;
    border-color: #1e1e1e !important;
}
.btn-cancel-modern:hover {
    color: #1e1e1e !important;
    border-color: #1e1e1e !important;
}

/* (Optional) Make Back button text dark */
.btn-back {
    color: #1e1e1e !important;
}
.btn-back:hover {
    color: white !important;  /* keep white on hover for contrast */
}

/* Make Save button background dark */
.btn-save-modern {
    background: #1e1e1e !important;
    background-image: none !important;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}
.btn-save-modern:hover {
    background: #333333 !important;
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
}
</style>
</head>
<body>
    <a href="index.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> 
        <span>Back to Portfolio</span>
    </a>
    
    <div class="container edit-container">
        <div class="edit-card">
            <h2>
                <i class="bi bi-pencil-square me-3"></i>
                <?php echo empty($profile) ? 'Create Your Profile' : 'Edit Profile'; ?>
            </h2>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($warning)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $warning; ?>
                <div class="mt-3">
                    <a href="edit_profile.php" class="btn-cleanup">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh Now
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($profileCount > 1): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Multiple profiles found (<?php echo $profileCount; ?> records)</strong>
                <p class="mt-2 mb-2">Only the first profile will be edited.</p>
                <button class="btn-cleanup" onclick="cleanupProfiles()">
                    <i class="bi bi-trash3 me-2"></i>Clean Up Duplicates
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($profile)): ?>
            <div class="profile-data">
                <h4><i class="bi bi-info-circle-fill me-2"></i>Current Profile Data</h4>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-person-circle me-2"></i>Full Name:</strong> <?php echo htmlspecialchars($profile['full_name'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                        <p><strong><i class="bi bi-briefcase me-2"></i>Designation:</strong> <?php echo htmlspecialchars($profile['designation'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                        <p><strong><i class="bi bi-building me-2"></i>Department:</strong> <?php echo htmlspecialchars($profile['department'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                        <p><strong><i class="bi bi-shield me-2"></i>Institution:</strong> <?php echo htmlspecialchars($profile['institution'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="bi bi-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($profile['email'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                        <p><strong><i class="bi bi-telephone me-2"></i>Contact:</strong> <?php echo htmlspecialchars($profile['contact'] ?? '<span class="text-muted">Not set</span>'); ?></p>
                        <p><strong><i class="bi bi-clock me-2"></i>Last Updated:</strong> <?php echo isset($profile['updated_at']) ? date('d M Y, H:i', strtotime($profile['updated_at'])) : '<span class="text-muted">Never</span>'; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (empty($profile)): ?>
            <div class="empty-state">
                <i class="bi bi-stars"></i>
                <h5>Welcome to Your Academic Journey!</h5>
                <p>Let's create your professional profile together</p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="profileForm">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-person-circle me-2"></i>Full Name</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-person"></i>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" placeholder="Dr. John Smith">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-briefcase me-2"></i>Designation</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-tag"></i>
                            <input type="text" class="form-control" name="designation" value="<?php echo htmlspecialchars($profile['designation'] ?? ''); ?>" placeholder="Professor of Computer Science">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-building me-2"></i>Department</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-columns"></i>
                            <input type="text" class="form-control" name="department" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>" placeholder="Department of Computer Science">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-shield me-2"></i>Institution</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-bank"></i>
                            <input type="text" class="form-control" name="institution" value="<?php echo htmlspecialchars($profile['institution'] ?? ''); ?>" placeholder="University of Technology">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-envelope me-2"></i>Email</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" placeholder="john.smith@university.edu">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-telephone me-2"></i>Contact</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-phone"></i>
                            <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>" placeholder="+60 12-345 6789">
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label"><i class="bi bi-journal-text me-2"></i>Biography</label>
                        <textarea class="form-control" name="bio" rows="6" placeholder="Tell us about your research interests, academic achievements, publications, and professional experience..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="index.php" class="btn-cancel-modern">
                        <i class="bi bi-x-lg me-2"></i>
                        <span>Cancel</span>
                    </a>
                    <button type="submit" class="btn-save-modern" id="submitBtn">
                        <i class="bi bi-check-lg me-2"></i>
                        <span><?php echo empty($profile) ? 'Create Profile' : 'Save Changes'; ?></span>
                        <div class="btn-glow"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cleanupProfiles() {
            if (confirm('This will delete all but the first profile. Are you sure?')) {
                window.location.href = 'cleanup_profiles.php';
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <i class="bi bi-arrow-repeat me-2"></i>
                <span>Saving...</span>
                <div class="btn-glow"></div>
            `;
        });

        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        <?php if (isset($warning) && empty($profile)): ?>
        setTimeout(() => {
            window.location.href = 'edit_profile.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
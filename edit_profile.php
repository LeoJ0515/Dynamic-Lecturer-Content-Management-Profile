<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// ========== 读取一次性 Flash 消息 ==========
$success = $_SESSION['flash_success'] ?? null;
$warning = $_SESSION['flash_warning'] ?? null;
$error = $_SESSION['flash_error'] ?? null;

// 读完立刻清除，保证只显示一次
unset($_SESSION['flash_success'], $_SESSION['flash_warning'], $_SESSION['flash_error']);

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
        // CREATE new profile
        $result = supabaseRequest('POST', 'profile', $updateData, true);
        if ($result['http_code'] === 201 || $result['http_code'] === 200) {
            // 提交成功后重试获取最新数据（避免数据延迟）
            $maxRetries = 3;
            $retryDelay = 500000; // 0.5 seconds
            $latestProfiles = [];
            for ($i = 0; $i < $maxRetries; $i++) {
                if ($i > 0)
                    usleep($retryDelay);
                $latestProfiles = getSupabaseData('profile', [], 'id.asc');
                if (!empty($latestProfiles))
                    break;
            }

            $_SESSION['flash_success'] = 'Profile created successfully!';
            if (empty($latestProfiles)) {
                $_SESSION['flash_warning'] = 'Your profile was saved successfully, but the data is taking a moment to appear. Please refresh the page in a few seconds.';
            }
            header('Location: edit_profile.php');
            exit;
        } else {
            $error = 'Failed to create profile. Please try again.';
            error_log("Profile creation failed: " . print_r($result, true));
        }
    } else {
        // UPDATE existing profile
        $profileId = $profiles[0]['id'];
        $result = supabaseRequest('PATCH', 'profile?id=eq.' . $profileId, $updateData, true);
        if ($result['http_code'] === 204 || $result['http_code'] === 200) {
            $maxRetries = 3;
            $retryDelay = 500000;
            $latestProfiles = [];
            for ($i = 0; $i < $maxRetries; $i++) {
                if ($i > 0)
                    usleep($retryDelay);
                $latestProfiles = getSupabaseData('profile', [], 'id.asc');
                if (!empty($latestProfiles))
                    break;
            }

            $_SESSION['flash_success'] = 'Profile updated successfully!';
            if (empty($latestProfiles)) {
                $_SESSION['flash_warning'] = 'Your profile was saved successfully, but the data is taking a moment to appear. Please refresh the page in a few seconds.';
            }
            header('Location: edit_profile.php');
            exit;
        } else {
            $error = 'Failed to update profile. Please try again.';
            error_log("Profile update failed: " . print_r($result, true));
        }
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-page: #f7f8fc;
            --card-bg: #ffffff;
            --text-primary: #1a1a1a;
            --text-secondary: #4a4a4a;
            --border-light: #eaeef2;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 8px 28px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 16px 40px rgba(0, 0, 0, 0.08);
            --radius-sm: 14px;
            --radius-md: 20px;
            --radius-lg: 28px;
            --radius-full: 50px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

            --hud-bg: #0a0f0a;
            --hud-border: #00ff41;
            --hud-text: #00ff41;
            --hud-glow: 0 0 8px rgba(0, 255, 65, 0.4);
            --hud-font: 'Courier New', 'Fira Code', monospace;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-page);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 0, 0, 0.02) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: -1;
        }

        .edit-container {
            max-width: 1000px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 10;
            animation: fadeUp 0.7s ease forwards;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .btn-back {
            position: fixed;
            top: 24px;
            left: 24px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(6px);
            color: var(--text-primary);
            padding: 0.7rem 1.8rem;
            border-radius: 40px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            z-index: 1000;
            border: 1px solid var(--border-light);
        }

        .btn-back:hover {
            transform: translateX(-4px);
            box-shadow: var(--shadow-lg);
            background: #1a1a1a;
            color: white;
            border-color: #1a1a1a;
        }

        .btn-back i {
            font-size: 1.1rem;
            transition: transform 0.3s;
        }

        .btn-back:hover i {
            transform: translateX(-3px);
        }

        .edit-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .edit-card:hover {
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.08);
        }

        .edit-card h2 {
            color: #1e1e1e;
            font-size: 2.4rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 2.8rem;
            position: relative;
            padding-bottom: 1.2rem;
        }

        .edit-card h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            background: #1a1a1a;
            border-radius: 4px;
            transition: width 0.3s;
        }

        .edit-card:hover h2::after {
            width: 100px;
        }

        .alert {
            background: white;
            border: none;
            border-radius: var(--radius-md);
            padding: 1.2rem 1.6rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid;
            font-weight: 500;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-15px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-success {
            border-left-color: #10b981;
            color: #065f46;
            background: #f0fdf6;
        }

        .alert-warning {
            border-left-color: #f59e0b;
            color: #92400e;
            background: #fffbeb;
        }

        .alert-danger {
            border-left-color: #ef4444;
            color: #991b1b;
            background: #fef2f2;
        }

        /* ========== 绿色高科技仪表板 ========== */
        .profile-data {
            background: var(--hud-bg);
            border: 1px solid var(--hud-border);
            border-radius: var(--radius-md);
            padding: 2rem;
            margin-bottom: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 0 30px rgba(0, 255, 65, 0.05), var(--hud-glow);
            font-family: var(--hud-font);
            color: var(--hud-text);
        }

        .profile-data::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(0deg,
                    transparent,
                    transparent 2px,
                    rgba(0, 255, 65, 0.03) 2px,
                    rgba(0, 255, 65, 0.03) 4px);
            pointer-events: none;
            z-index: 2;
            animation: scanLines 8s linear infinite;
        }

        @keyframes scanLines {
            0% {
                background-position: 0 0;
            }

            100% {
                background-position: 0 20px;
            }
        }

        .profile-data::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 16px;
            height: 16px;
            border-top: 2px solid var(--hud-border);
            border-right: 2px solid var(--hud-border);
            opacity: 0.6;
        }

        .profile-data h4 {
            font-family: var(--hud-font);
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--hud-text);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1.8rem;
            position: relative;
            z-index: 3;
            text-shadow: 0 0 5px rgba(0, 255, 65, 0.7);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .profile-data h4::before {
            content: '▸';
            font-size: 1.2rem;
        }

        .profile-data p {
            font-family: var(--hud-font);
            font-size: 0.95rem;
            color: var(--hud-text);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: baseline;
            gap: 0.6rem;
            position: relative;
            z-index: 3;
            text-shadow: 0 0 3px rgba(0, 255, 65, 0.4);
            border-bottom: 1px dotted rgba(0, 255, 65, 0.15);
            padding-bottom: 0.5rem;
            flex-wrap: nowrap;
        }

        .profile-data strong {
            color: #ffffff;
            font-weight: 600;
            min-width: 160px;
            white-space: nowrap;
            flex-shrink: 0;
            text-shadow: 0 0 4px rgba(255, 255, 255, 0.5);
            letter-spacing: 0.5px;
        }

        .empty-state {
            background: var(--hud-bg);
            border: 1px dashed var(--hud-border);
            border-radius: var(--radius-md);
            padding: 3rem 2rem;
            text-align: center;
            margin-bottom: 2.5rem;
            font-family: var(--hud-font);
            color: var(--hud-text);
            position: relative;
            z-index: 3;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--hud-text);
            margin-bottom: 1rem;
            text-shadow: var(--hud-glow);
        }

        .empty-state h5 {
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            opacity: 0.85;
            font-size: 0.95rem;
        }

        /* ========== 表单控件（高级感输入框） ========== */
        .form-label {
            color: #1e1e1e;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            background:
                linear-gradient(to right, #1a1a1a, #1a1a1a) bottom center / 0% 2px no-repeat,
                #f9fafc;
            border: 1.5px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 0.9rem 1.2rem;
            color: var(--text-primary);
            font-size: 1rem;
            transition: background-size 0.35s ease, border-color 0.3s, box-shadow 0.3s, transform 0.2s;
            width: 100%;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            background-size: 100% 2px, auto;
            border-color: #1a1a1a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04), 0 0 0 4px rgba(0, 0, 0, 0.02);
            outline: none;
            background-color: #fff;
            /* 覆盖原有背景色，避免渐变影响 */
        }

        .form-control::placeholder {
            color: #9ca3af;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .form-control:focus::placeholder {
            opacity: 0.5;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            background:
                linear-gradient(to right, #1a1a1a, #1a1a1a) bottom center / 0% 2px no-repeat,
                #f9fafc;
        }

        textarea.form-control:focus {
            background-size: 100% 2px, auto;
        }

        /* 带图标输入框 */
        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 1.1rem;
            transition: color 0.2s, transform 0.2s;
            z-index: 2;
        }

        .input-icon-wrapper .form-control {
            padding-left: 45px;
        }

        .input-icon-wrapper .form-control:focus~i,
        .input-icon-wrapper .form-control:focus+i {
            color: #1a1a1a;
            transform: translateY(-50%) scale(1.1);
        }

        /* ========== 按钮组 ========== */
        .action-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1.2rem;
            margin-top: 2.8rem;
        }

        /* 取消按钮 */
        .btn-cancel-modern {
            padding: 0.95rem 2.5rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            background: white;
            color: #1e1e1e;
            border: 2px solid #d1d5db;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-cancel-modern:hover {
            background: #1e1e1e;
            color: white;
            border-color: #1e1e1e;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .btn-cancel-modern:active {
            transform: translateY(-1px) scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.1s ease;
        }

        .btn-cancel-modern i,
        .btn-cancel-modern span {
            position: relative;
            z-index: 2;
            transition: transform 0.3s;
        }

        .btn-cancel-modern:hover i {
            transform: translateX(-3px);
        }

        /* 保存按钮（高科技感 + 扫光特效） */
        .btn-save-modern {
            padding: 1rem 3rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.3px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            background: linear-gradient(135deg, #0f1f0f, #1a3a1a);
            color: white;
            box-shadow: 0 8px 22px rgba(0, 80, 20, 0.25);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        /* 扫光动画层（悬停播放） */
        .btn-save-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.25),
                    transparent);
            transition: left 0.6s ease;
            z-index: 1;
        }

        /* 光晕扩散层 */
        .btn-save-modern::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0, 255, 65, 0.2) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s;
            z-index: 0;
        }

        .btn-save-modern i,
        .btn-save-modern span {
            position: relative;
            z-index: 2;
        }

        .btn-save-modern:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(0, 80, 20, 0.4);
        }

        .btn-save-modern:hover::before {
            left: 100%;
        }

        .btn-save-modern:hover::after {
            opacity: 1;
        }

        .btn-save-modern:active {
            transform: translateY(-1px) scale(0.97);
            box-shadow: 0 6px 18px rgba(0, 80, 20, 0.3);
            transition: all 0.1s ease;
        }

        .btn-save-modern i {
            transition: transform 0.3s;
        }

        .btn-save-modern:hover i {
            transform: scale(1.15);
        }

        /* 加载状态 */
        .btn-save-modern.loading {
            pointer-events: none;
            opacity: 0.9;
        }

        .btn-save-modern.loading i {
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* 小清理按钮 */
        .btn-cleanup {
            background: white;
            border: 1px solid #d1d5db;
            color: #1a1a1a;
            padding: 0.5rem 1.4rem;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-cleanup:hover {
            background: #1a1a1a;
            color: white;
            border-color: #1a1a1a;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .btn-cleanup:active {
            transform: translateY(0) scale(0.98);
        }

        /* ========== 响应式 ========== */
        @media (max-width: 768px) {
            .edit-card {
                padding: 2rem 1.5rem;
                border-radius: var(--radius-md);
            }

            .edit-card h2 {
                font-size: 2rem;
                margin-bottom: 2rem;
            }

            .btn-back {
                top: 16px;
                left: 16px;
                padding: 0.6rem 1.2rem;
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-cancel-modern,
            .btn-save-modern {
                width: 100%;
                padding: 0.9rem 2rem;
            }

            .profile-data p {
                flex-direction: column;
                align-items: flex-start;
            }

            .profile-data strong {
                min-width: auto;
                white-space: normal;
                margin-bottom: 0.2rem;
            }
        }

        @media (max-width: 480px) {
            .edit-card {
                padding: 1.5rem 1rem;
            }

            .edit-card h2 {
                font-size: 1.7rem;
            }

            .profile-data {
                padding: 1.5rem;
            }
        }

        /* ========== 高科技仪表板成功提示 ========== */
        .hud-success-toast {
            background: rgba(10, 15, 10, 0.95);
            border: 1px solid #00ff41;
            border-radius: 12px;
            padding: 1rem 1.8rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--hud-font);
            box-shadow: 0 0 18px rgba(0, 255, 65, 0.3), inset 0 0 18px rgba(0, 255, 65, 0.05);
            position: relative;
            overflow: hidden;
            animation: hudFadeIn 0.5s ease forwards;
            backdrop-filter: blur(4px);
        }

        .hud-success-toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(0deg,
                    transparent,
                    transparent 2px,
                    rgba(0, 255, 65, 0.03) 2px,
                    rgba(0, 255, 65, 0.03) 4px);
            pointer-events: none;
            z-index: 2;
            animation: scanLines 4s linear infinite;
        }

        .hud-success-content {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 3;
            color: #00ff41;
            text-shadow: 0 0 6px rgba(0, 255, 65, 0.6);
        }

        .hud-success-icon {
            font-size: 1.6rem;
            animation: iconPulse 1.5s ease infinite;
        }

        .hud-success-text {
            letter-spacing: 1.5px;
            font-weight: 600;
            font-size: 1rem;
        }

        .hud-success-blink {
            font-size: 1.4rem;
            animation: blink 1s step-end infinite;
            margin-left: 0.2rem;
        }

        @keyframes hudFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes iconPulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }
        }

        /* ========== 高科技终端警告提示 ========== */
        .hud-warning-toast {
            background: rgba(10, 15, 10, 0.95);
            border: 1px solid #ffb300;
            /* 琥珀色边框 */
            border-radius: 12px;
            padding: 1.2rem 1.8rem;
            margin-bottom: 2rem;
            font-family: var(--hud-font);
            box-shadow: 0 0 18px rgba(255, 179, 0, 0.25), inset 0 0 18px rgba(255, 179, 0, 0.05);
            position: relative;
            overflow: hidden;
            animation: hudFadeIn 0.5s ease forwards;
            backdrop-filter: blur(4px);
        }

        .hud-warning-toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(0deg,
                    transparent,
                    transparent 2px,
                    rgba(255, 179, 0, 0.04) 2px,
                    rgba(255, 179, 0, 0.04) 4px);
            pointer-events: none;
            z-index: 2;
            animation: scanLines 4s linear infinite;
        }

        .hud-warning-content {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            z-index: 3;
            color: #ffb300;
            /* 琥珀色文字 */
            text-shadow: 0 0 6px rgba(255, 179, 0, 0.6);
        }

        .hud-warning-icon {
            font-size: 1.6rem;
            animation: iconPulseWarning 1.5s ease infinite;
            color: #ffb300;
        }

        .hud-warning-text {
            letter-spacing: 1.5px;
            font-weight: 600;
            font-size: 1rem;
        }

        .hud-warning-blink {
            font-size: 1.4rem;
            animation: blink 1s step-end infinite;
            margin-left: 0.2rem;
            color: #ffb300;
        }

        @keyframes iconPulseWarning {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.2);
                opacity: 0.7;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* 高级感优雅退场 */
        .elegant-fadeout {
            animation: elegantFadeOut 0.7s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            pointer-events: none;
        }

        @keyframes elegantFadeOut {
            0% {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
                box-shadow: 0 0 18px rgba(0, 255, 65, 0.3);
            }

            100% {
                opacity: 0;
                transform: translateY(-12px) scale(0.96);
                filter: blur(4px);
                box-shadow: 0 0 30px rgba(0, 255, 65, 0);
                margin-bottom: -1rem;
            }
        }

        @media (max-width: 768px) {
            /* ... 其他样式保持不变 ... */

            .btn-cancel-modern,
            .btn-save-modern {
                width: 100%;
                padding: 0.7rem 1.5rem;
                /* 原来是 0.9rem 2rem */
                font-size: 0.9rem;
                /* 新增：略微缩小字体 */
            }

            /* 可选：按钮间距也微调 */
            .action-buttons {
                gap: 0.8rem;
            }
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
                <div class="hud-success-toast" id="successToast">
                    <div class="hud-success-content">
                        <i class="fas fa-check-circle hud-success-icon"></i>
                        <span class="hud-success-text">[ SYSTEM ] PROFILE UPDATE COMPLETE</span>
                        <span class="hud-success-blink">_</span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($warning)): ?>
                <div class="hud-warning-toast" id="warningToast">
                    <div class="hud-warning-content">
                        <i class="bi bi-exclamation-triangle-fill hud-warning-icon"></i>
                        <span class="hud-warning-text">[ SYSTEM ] <?php echo $warning; ?></span>
                        <span class="hud-warning-blink">_</span>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="edit_profile.php" class="btn-cleanup" style="position: relative; z-index: 3;">
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
                            <p><strong><i class="bi bi-person-circle me-2"></i>Full Name: </strong>
                                <?php echo htmlspecialchars($profile['full_name'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                            <p><strong><i class="bi bi-briefcase me-2"></i>Designation: </strong>
                                <?php echo htmlspecialchars($profile['designation'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                            <p><strong><i class="bi bi-building me-2"></i>Department: </strong>
                                <?php echo htmlspecialchars($profile['department'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                            <p><strong><i class="bi bi-shield me-2"></i>Institution: </strong>
                                <?php echo htmlspecialchars($profile['institution'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><i class="bi bi-envelope me-2"></i>Email: </strong>
                                <?php echo htmlspecialchars($profile['email'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                            <p><strong><i class="bi bi-telephone me-2"></i>Contact: </strong>
                                <?php echo htmlspecialchars($profile['contact'] ?? '<span class="text-muted">Not set</span>'); ?>
                            </p>
                            <p><strong><i class="bi bi-clock me-2"></i>Last Updated: </strong>
                                <?php echo isset($profile['updated_at']) ? date('d M Y, H:i', strtotime($profile['updated_at'])) : '<span class="text-muted">Never</span>'; ?>
                            </p>
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
                            <input type="text" class="form-control" name="full_name"
                                value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>"
                                placeholder="Dr. John Smith">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-briefcase me-2"></i>Designation</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-tag"></i>
                            <input type="text" class="form-control" name="designation"
                                value="<?php echo htmlspecialchars($profile['designation'] ?? ''); ?>"
                                placeholder="Professor of Computer Science">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-building me-2"></i>Department</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-columns"></i>
                            <input type="text" class="form-control" name="department"
                                value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>"
                                placeholder="Department of Computer Science">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-shield me-2"></i>Institution</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-bank"></i>
                            <input type="text" class="form-control" name="institution"
                                value="<?php echo htmlspecialchars($profile['institution'] ?? ''); ?>"
                                placeholder="University of Technology">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-envelope me-2"></i>Email</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope"></i>
                            <input type="email" class="form-control" name="email"
                                value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>"
                                placeholder="john.smith@university.edu">
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label"><i class="bi bi-telephone me-2"></i>Contact</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-phone"></i>
                            <input type="text" class="form-control" name="contact"
                                value="<?php echo htmlspecialchars($profile['contact'] ?? ''); ?>"
                                placeholder="+60 12-345 6789">
                        </div>
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label"><i class="bi bi-journal-text me-2"></i>Biography</label>
                        <textarea class="form-control" name="bio" rows="6"
                            placeholder="Tell us about your research interests, academic achievements, publications, and professional experience..."><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
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

        document.getElementById('profileForm').addEventListener('submit', function (e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <i class="bi bi-arrow-repeat me-2"></i>
                <span>Saving...</span>
                <div class="btn-glow"></div>
            `;
        });

        // 成功提示：3秒后优雅退场
        setTimeout(() => {
            const successToast = document.getElementById('successToast');
            if (successToast) {
                successToast.classList.add('elegant-fadeout');
                successToast.addEventListener('animationend', () => {
                    if (successToast.parentNode) successToast.remove();
                });
            }
        }, 3000);

        // 警告提示：8秒后优雅退场
        setTimeout(() => {
            const warningToast = document.getElementById('warningToast');
            if (warningToast) {
                warningToast.classList.add('elegant-fadeout');
                warningToast.addEventListener('animationend', () => {
                    if (warningToast.parentNode) warningToast.remove();
                });
            }
        }, 8000);


    </script>
</body>

</html>

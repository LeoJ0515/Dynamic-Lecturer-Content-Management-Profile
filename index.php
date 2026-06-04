<?php
session_start();
require_once 'database.php';

// Debug: Check what getSupabaseData returns
error_log("=== Starting index.php ===");
$testProfile = getSupabaseData('profile', [], 'id.asc');
error_log("getSupabaseData('profile') returned: " . print_r($testProfile, true));

// Direct API call for comparison
$directTest = supabaseRequest('GET', 'profile?select=*', null, true);
error_log("Direct API call returned: " . print_r($directTest, true));

// Now use the data
$profile = !empty($testProfile) ? $testProfile[0] : [];
if (empty($profile) && !empty($directTest['data'])) {
    error_log("Using direct API data as fallback");
    $profile = $directTest['data'][0] ?? [];
}

// Fetch data for all sections with proper error handling
$sections = [
    'supervision' => getSupabaseData('supervision', [], 'display_order.asc') ?: [],
    'teaching' => getSupabaseData('teaching', [], 'display_order.asc') ?: [],
    'research_projects' => getSupabaseData('research_projects', [], 'display_order.asc') ?: [],
    'publications' => getSupabaseData('publications', [], 'year.desc') ?: [],
    'awards' => getSupabaseData('awards', [], 'display_order.asc') ?: [],
    'appointments' => getSupabaseData('appointments', [], 'display_order.asc') ?: [],
    'invited_talks' => getSupabaseData('invited_talks', [], 'display_order.asc') ?: []
];

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $profile['full_name'] ?? '';
$profilePic = $profile['profile_picture'] ?? '';
$backgroundPic = $profile['background_picture'] ?? '';

// 提取 Teaching 中所有唯一的学期（用于下拉筛选）
$semesters = [];
if (!empty($sections['teaching'])) {
    foreach ($sections['teaching'] as $item) {
        if (!empty($item['semester'])) {
            $semesters[] = $item['semester'];
        }
    }
}
$semesters = array_unique($semesters);
sort($semesters); // 按字母排序

// 提取 Teaching 中所有唯一的机构（用于筛选）
$teachingInstitutions = [];
if (!empty($sections['teaching'])) {
    foreach ($sections['teaching'] as $item) {
        if (!empty($item['institution'])) {
            $teachingInstitutions[] = $item['institution'];
        }
    }
}
$teachingInstitutions = array_unique($teachingInstitutions);
sort($teachingInstitutions);

// 提取 Supervision 中所有唯一的学位、状态、项目（用于筛选）
$degrees = [];
$statuses = [];
$programs = [];
if (!empty($sections['supervision'])) {
    foreach ($sections['supervision'] as $item) {
        if (!empty($item['degree']))
            $degrees[] = $item['degree'];
        if (!empty($item['status']))
            $statuses[] = trim($item['status']);
        if (!empty($item['program']))
            $programs[] = $item['program'];
    }
}
$degrees = array_unique($degrees);
sort($degrees);
$statuses = array_unique($statuses);
sort($statuses);
$programs = array_unique($programs);
sort($programs);

// ---- Supervision stats ----
$supervisionStats = [
    'total' => 0,
    'completed' => 0,
    'not_completed' => 0,
    'other' => 0       // for any other statuses
];

if (!empty($sections['supervision'])) {
    $supervisionStats['total'] = count($sections['supervision']);
    foreach ($sections['supervision'] as $item) {
        $status = strtolower(trim($item['status'] ?? ''));
        if ($status === 'completed') {
            $supervisionStats['completed']++;
        } elseif ($status === 'not completed' || $status === 'not_completed') {
            $supervisionStats['not_completed']++;
        } else {
            $supervisionStats['other']++;
        }
    }
}

// ---- Degree counts ----
$degreeCounts = [];
if (!empty($sections['supervision'])) {
    foreach ($sections['supervision'] as $item) {
        $deg = trim($item['degree'] ?? 'N/A');
        if (!isset($degreeCounts[$deg])) {
            $degreeCounts[$deg] = 0;
        }
        $degreeCounts[$deg]++;
    }
}
// Sort by name so it's consistent
ksort($degreeCounts);

// 提取 Awards, Appointments, Invited Talks 中所有唯一的年份（用于筛选）
$awardYears = [];
if (!empty($sections['awards'])) {
    foreach ($sections['awards'] as $award)
        if (!empty($award['year']))
            $awardYears[] = $award['year'];
    $awardYears = array_unique($awardYears);
    sort($awardYears);
}
$apptStartYears = [];
if (!empty($sections['appointments'])) {
    foreach ($sections['appointments'] as $appt)
        if (!empty($appt['start_year']))
            $apptStartYears[] = $appt['start_year'];
    $apptStartYears = array_unique($apptStartYears);
    sort($apptStartYears);
}
$talkYears = [];
if (!empty($sections['invited_talks'])) {
    foreach ($sections['invited_talks'] as $talk)
        if (!empty($talk['year']))
            $talkYears[] = $talk['year'];
    $talkYears = array_unique($talkYears);
    sort($talkYears);
}

// 提取 Research Projects 中所有唯一的类型与状态（用于筛选）
$grantTypes = [];
$researchStatuses = [];
if (!empty($sections['research_projects'])) {
    foreach ($sections['research_projects'] as $item) {
        if (!empty($item['type_of_grant']))
            $grantTypes[] = $item['type_of_grant'];
        if (!empty($item['status']))
            $researchStatuses[] = $item['status'];
    }
}
$grantTypes = array_unique($grantTypes);
sort($grantTypes);
$researchStatuses = array_unique($researchStatuses);
sort($researchStatuses);


// 提取 Publications 中所有唯一的类型、年份、作者（用于筛选）
$pubTypes = [];
$pubYears = [];
$pubAuthors = [];
if (!empty($sections['publications'])) {
    foreach ($sections['publications'] as $item) {
        if (!empty($item['type']))
            $pubTypes[] = $item['type'];
        if (!empty($item['year']))
            $pubYears[] = $item['year'];
        if (!empty($item['authors'])) {
            // Split authors string by common separators and trim
            $authorsList = preg_split('/[,;&]+/', $item['authors']);
            foreach ($authorsList as $author) {
                $author = trim($author);
                if (!empty($author))
                    $pubAuthors[] = $author;
            }
        }
    }
}
$pubTypes = array_unique($pubTypes);
sort($pubTypes);
$pubYears = array_unique($pubYears);
sort($pubYears, SORT_NUMERIC);
$pubAuthors = array_unique($pubAuthors);
sort($pubAuthors);


// Fetch research areas
$researchAreas = getSupabaseData('research_areas', [], 'display_order.asc') ?: [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Portfolio - <?php echo htmlspecialchars($userName ?: 'Researcher'); ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/light.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Cropper.js 样式 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <!-- Cropper.js 脚本 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
</head>

<body>
    <div id="intro-animation">
        <div class="intro-content">
            <h1><?php echo htmlspecialchars($userName ?: 'Academic Portfolio'); ?></h1>
            <p>Welcome to my professional journey</p>
        </div>
    </div>

    <header id="main-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-name">
                    <?php echo htmlspecialchars($userName ?: 'Academic Portfolio'); ?>
                </h1>
                <?php if (!empty($profile['designation']) || !empty($profile['institution']) || !empty($profile['department']) || !empty($profile['email'])): ?>
                    <div class="header-meta">
                        <?php if (!empty($profile['designation'])): ?>
                            <span class="header-designation"><?php echo htmlspecialchars($profile['designation']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profile['institution'])): ?>
                            <span class="header-institution"><?php echo htmlspecialchars($profile['institution']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profile['department'])): ?>
                            <span class="header-department"><?php echo htmlspecialchars($profile['department']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($profile['email'])): ?>
                            <span class="header-email"><?php echo htmlspecialchars($profile['email']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="header-tagline">Welcome to my academic journey</p>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <?php include 'footer.php'; ?>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Portfolio</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i
                            class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group-modern">
                                <span class="input-icon"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" class="form-control-modern" id="email"
                                    placeholder="your.email@example.com" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group-modern">
                                <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control-modern" id="password" placeholder="••••••••"
                                    required>
                            </div>
                        </div>
                        <div id="loginMessage" class="alert d-none"></div>
                        <button type="submit" class="login-btn-modern" id="loginSubmitBtn">
                            <i class="bi bi-box-arrow-in-right me-2"></i><span>Login to Portfolio</span>
                        </button>
                        <div class="login-footer">
                            <p class="text-muted small mt-3 mb-0"><i class="bi bi-shield-lock me-1"></i>Secure login for
                                admin access only</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Upload Modal -->
    <div class="modal fade" id="imageUploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cloud-upload me-2"></i>Upload Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="imageUploadForm" enctype="multipart/form-data">
                        <input type="hidden" id="imageType" name="image_type">
                        <div class="mb-3">
                            <label for="imageFile" class="form-label">Choose Image</label>
                            <input type="file" class="form-control" id="imageFile" name="image" accept="image/*"
                                required>
                        </div>
                        <!-- 裁剪区域，初始隐藏 -->
                        <div id="cropSection" style="display: none;">
                            <div class="alert alert-info small">
                                <i class="bi bi-info-circle"></i> Drag & resize the box to select the visible area for
                                background.
                            </div>
                            <div class="img-container" style="max-height: 400px; overflow: hidden;">
                                <img id="cropImage" src="" alt="Crop preview" style="max-width: 100%; display: block;">
                            </div>
                        </div>
                        <!-- 简单预览（保留，但只在没有裁剪时显示） -->
                        <div id="simplePreview" style="display: none;">
                            <div id="imagePreview" class="text-center">
                                <img src="" alt="Preview"
                                    style="max-width: 100%; max-height: 200px; border-radius: 10px;">
                            </div>
                        </div>
                        <button type="submit" class="upload-submit-btn" id="uploadBtn">Upload</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Modal -->
    <div class="modal fade" id="contentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-pencil-square me-2"></i>Add/Edit Content
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contentForm">
                        <input type="hidden" id="contentId" name="id">
                        <input type="hidden" id="contentTable" name="table">
                        <div id="formFields"></div>
                        <div class="modal-action-buttons">
                            <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal"><i
                                    class="bi bi-x-lg me-2"></i>Cancel</button>
                            <button type="submit" class="modal-btn modal-btn-save" id="modalSubmitBtn"><i
                                    class="bi bi-check-lg me-2"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <main>
        <!-- Hero Section -->
        <section id="home">
            <div class="hero-wrapper">
                <div class="hero-background-section">
                    <?php if ($backgroundPic): ?>
                        <div class="hero-background"
                            style="background-image: url('<?php echo htmlspecialchars($backgroundPic); ?>')"></div>
                    <?php else: ?>
                        <div class="hero-background" style="background: #1e1e1e;"></div>
                    <?php endif; ?>
                    <?php if ($isLoggedIn): ?>
                        <div class="profile-edit-controls">
                            <button class="bg-upload-btn" onclick="uploadImage('background')" title="Change Background"><i
                                    class="bi bi-image"></i></button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-section">
                    <div class="profile-content">
                        <div class="profile-image-wrapper">
                            <div class="image-upload-container">
                                <?php if (!empty($profilePic)): ?>
                                    <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile"
                                        class="profile-image" id="profile-image">
                                <?php else: ?>
                                    <div class="profile-default-avatar"><i class="bi bi-person-circle"></i></div>
                                <?php endif; ?>
                                <?php if ($isLoggedIn): ?>
                                    <div class="upload-overlay" onclick="uploadImage('profile')"
                                        title="Change Profile Picture"><i class="bi bi-camera"></i></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <h1 class="profile-name" id="profile-name">
                            <span><?php echo htmlspecialchars($profile['full_name'] ?? ''); ?></span>
                            <?php if ($isLoggedIn): ?>
                                <button class="inline-edit-btn" onclick="editProfile()" title="Edit Profile"><i
                                        class="bi bi-pencil-fill"></i></button>
                            <?php endif; ?>
                        </h1>

                        <div class="profile-institution" id="profile-institution">
                            <?php echo htmlspecialchars($profile['institution'] ?? ''); ?>
                        </div>
                        <div class="profile-designation" id="profile-designation">
                            <?php echo htmlspecialchars($profile['designation'] ?? ''); ?>
                        </div>
                        <div class="profile-bio" id="profile-bio">
                            <?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?>
                        </div>
                        <div class="contact-info">
                            <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($profile['email'] ?? ''); ?>
                            </p>
                            <p><i class="bi bi-telephone"></i>
                                <?php echo htmlspecialchars($profile['contact'] ?? ''); ?></p>
                            <?php if (!empty($profile['department'])): ?>
                                <p><i class="bi bi-building"></i>
                                    <?php echo htmlspecialchars($profile['department'] ?? ''); ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- Research Areas Tags -->
                        <div class="research-areas-container">
                            <div class="research-areas-tags" id="research-areas-tags">
                                <?php if (empty($researchAreas)): ?>
                                    <span class="no-tags-message">No research areas yet.</span>
                                <?php else: ?>
                                    <?php foreach ($researchAreas as $area): ?>
                                        <?php
                                        $areaName = $area['area_name'] ?? '';
                                        $desc = $area['description'] ?? '';
                                        $hash = abs(crc32($areaName));
                                        $hue = $hash % 360;
                                        $color = "hsl({$hue}, 65%, 65%)";
                                        ?>
                                        <div class="tag-wrapper" data-id="<?php echo $area['id']; ?>
                                            data-name=" <?php echo htmlspecialchars($areaName); ?>">
                                            <span class="research-tag" style="background-color: <?php echo $color; ?>"
                                                data-description="<?php echo htmlspecialchars($desc); ?>">
                                                <?php echo htmlspecialchars($areaName); ?>
                                            </span>
                                            <?php if ($isLoggedIn): ?>
                                                <div class="tag-edit-controls">
                                                    <button class="control-btn edit"
                                                        onclick="editContent('research_areas', <?php echo $area['id']; ?>)"><i
                                                            class="bi bi-pencil"></i></button>
                                                    <button class="control-btn delete"
                                                        onclick="deleteContent('research_areas', <?php echo $area['id']; ?>)"><i
                                                            class="bi bi-trash"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <button class="add-tag-btn" onclick="addContent('research_areas')">
                                    <i class="bi bi-plus-circle"></i> Add Tag
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <!-- Teaching -->
        <section id="teaching" class="section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="bi bi-book-fill me-2"></i>Teaching</h2>
                    <div class="supervision-toolbar">
                        <?php if ($isLoggedIn): ?>
                            <button class="add-btn" onclick="addContent('teaching')"><i class="bi bi-plus-circle"></i> Add
                                Course</button>
                        <?php endif; ?>
                        <?php if (!empty($semesters) || !empty($teachingInstitutions)): ?>
                            <div class="dropdown filter-dropdown" id="teachingFilterDropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    id="teachingFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="bi bi-funnel-fill me-1"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if (!empty($semesters)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Semester</div>
                                            <ul class="filter-options" id="teaching-semester-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="semester"
                                                        data-value="all">All Semesters</a></li>
                                                <?php foreach ($semesters as $sem): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="semester"
                                                            data-value="<?php echo htmlspecialchars($sem); ?>"><?php echo htmlspecialchars($sem); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($teachingInstitutions)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Institution</div>
                                            <ul class="filter-options" id="teaching-institution-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="institution"
                                                        data-value="all">All Institutions</a></li>
                                                <?php foreach ($teachingInstitutions as $inst): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="institution"
                                                            data-value="<?php echo htmlspecialchars($inst); ?>"><?php echo htmlspecialchars($inst); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-container scrollable-card-grid" id="teaching-container">
                    <?php if (empty($sections['teaching'])): ?>
                        <div class="empty-state-card"><i class="bi bi-book"></i>
                            <p>No teaching records yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections['teaching'] as $item): ?>
                            <div class="card" id="teaching-<?php echo $item['id']; ?>"
                                data-semester="<?php echo htmlspecialchars($item['semester'] ?? ''); ?>"
                                data-institution="<?php echo htmlspecialchars($item['institution'] ?? ''); ?>">
                                <?php if ($isLoggedIn): ?>
                                    <div class="edit-controls">
                                        <button class="control-btn edit"
                                            onclick="editContent('teaching', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-pencil"></i></button>
                                        <button class="control-btn delete"
                                            onclick="deleteContent('teaching', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars(($item['course_code'] ?? '') . ' - ' . ($item['course_name'] ?? '')); ?>
                                    </h5>
                                    <p class="card-text">
                                        <strong>Institution:</strong>
                                        <?php echo htmlspecialchars($item['institution'] ?? ''); ?><br>
                                        <strong>Semester:</strong> <?php echo htmlspecialchars($item['semester'] ?? ''); ?>
                                        <?php echo $item['year'] ?? ''; ?><br>
                                        <?php if (!empty($item['description'])): ?><small><?php echo htmlspecialchars($item['description']); ?></small><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Research Projects -->
        <section id="research" class="section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="bi bi-search-heart-fill me-2"></i>Research Projects</h2>
                    <div class="supervision-toolbar">
                        <?php if ($isLoggedIn): ?>
                            <button class="add-btn" onclick="addContent('research_projects')">
                                <i class="bi bi-plus-circle"></i> Add Research Project
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($grantTypes) || !empty($researchStatuses)): ?>
                            <div class="dropdown filter-dropdown" id="researchFilterDropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    id="researchFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="bi bi-funnel-fill me-1"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if (!empty($grantTypes)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Grant Type</div>
                                            <ul class="filter-options" id="research-grant-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="type_of_grant"
                                                        data-value="all">All Types</a></li>
                                                <?php foreach ($grantTypes as $gt): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="type_of_grant"
                                                            data-value="<?php echo htmlspecialchars($gt); ?>"><?php echo htmlspecialchars($gt); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($researchStatuses)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Status</div>
                                            <ul class="filter-options" id="research-status-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="status"
                                                        data-value="all">All Statuses</a></li>
                                                <?php foreach ($researchStatuses as $rs): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="status"
                                                            data-value="<?php echo htmlspecialchars($rs); ?>"><?php echo htmlspecialchars($rs); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-container scrollable-card-grid" id="research_projects-container">
                    <!-- cards generated by PHP (with data attributes) -->
                    <?php if (empty($sections['research_projects'])): ?>
                        <div class="empty-state-card"><i class="bi bi-clipboard-data"></i>
                            <p>No research projects yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections['research_projects'] as $item): ?>
                            <div class="card" id="research-<?php echo $item['id']; ?>"
                                data-type-of-grant="<?php echo htmlspecialchars($item['type_of_grant'] ?? ''); ?>"
                                data-status="<?php echo htmlspecialchars($item['status'] ?? ''); ?>">
                                <!-- card content as before -->
                                <?php if ($isLoggedIn): ?>
                                    <div class="edit-controls">
                                        <button class="control-btn edit"
                                            onclick="editContent('research_projects', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-pencil"></i></button>
                                        <button class="control-btn delete"
                                            onclick="deleteContent('research_projects', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($item['project_title'] ?? 'Untitled Project'); ?>
                                    </h5>
                                    <p class="card-text">
                                        <?php if (!empty($item['type_of_grant'])): ?><strong><i class="bi bi-tag me-1"></i>Grant
                                                Type:</strong>
                                            <?php echo htmlspecialchars($item['type_of_grant']); ?><br><?php endif; ?>
                                        <?php if (!empty($item['funding_body'])): ?><strong><i
                                                    class="bi bi-building me-1"></i>Funded by:</strong>
                                            <?php echo htmlspecialchars($item['funding_body']); ?><br><?php endif; ?>
                                        <?php if (!empty($item['start_year']) || !empty($item['end_year'])): ?><strong><i
                                                    class="bi bi-calendar me-1"></i>Period:</strong>
                                            <?php echo $item['start_year'] ?? '?'; ?> -
                                            <?php echo $item['end_year'] ?? 'Present'; ?><br><?php endif; ?>
                                        <?php if (!empty($item['status'])): ?><strong><i
                                                    class="bi bi-check-circle me-1"></i>Status:</strong>
                                            <?php echo htmlspecialchars($item['status']); ?><br><?php endif; ?>
                                        <?php if (!empty($item['description'])): ?><small
                                                class="d-block mt-2"><?php echo nl2br(htmlspecialchars($item['description'])); ?></small><?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Supervision (原生横向滚动) -->
        <section id="supervision" class="section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="bi bi-people-fill me-2"></i>Supervision</h2>
                    <div class="supervision-toolbar">
                        <?php if ($isLoggedIn): ?>
                            <button class="add-btn" onclick="addContent('supervision')"><i class="bi bi-plus-circle"></i>
                                Add Supervision</button>
                        <?php endif; ?>
                        <?php if (!empty($degrees) || !empty($statuses) || !empty($programs)): ?>
                            <div class="dropdown filter-dropdown" id="combinedFilterDropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    id="filterDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="bi bi-funnel-fill me-1"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if (!empty($degrees)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Degree</div>
                                            <ul class="filter-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="degree"
                                                        data-value="all">All Degrees</a></li>
                                                <?php foreach ($degrees as $deg): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="degree"
                                                            data-value="<?php echo htmlspecialchars($deg); ?>"><?php echo htmlspecialchars($deg); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!empty($statuses)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Status</div>
                                            <ul class="filter-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="status"
                                                        data-value="all">All Status</a></li>
                                                <?php foreach ($statuses as $stat): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="status"
                                                            data-value="<?php echo htmlspecialchars($stat); ?>"><?php echo htmlspecialchars($stat); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (!empty($programs)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Program</div>
                                            <ul class="filter-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="program"
                                                        data-value="all">All Programs</a></li>
                                                <?php foreach ($programs as $prog): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="program"
                                                            data-value="<?php echo htmlspecialchars($prog); ?>"><?php echo htmlspecialchars($prog); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div><!-- end .supervision-toolbar -->

                    <!-- ===== Supervision Overview Stats + Degree Breakdown ===== -->
                    <div class="supervision-overview" id="supervision-overview"
                        style="<?php echo ($supervisionStats['total'] == 0) ? 'display:none' : ''; ?>">
                        <div class="stat-item">
                            <span class="stat-number" id="stat-total"><?php echo $supervisionStats['total']; ?></span>
                            <span class="stat-label">Total Students</span>
                        </div>
                        <div class="stat-item completed">
                            <span class="stat-number"
                                id="stat-completed"><?php echo $supervisionStats['completed']; ?></span>
                            <span class="stat-label">Completed</span>
                        </div>
                        <div class="stat-item not-completed">
                            <span class="stat-number"
                                id="stat-not-completed"><?php echo $supervisionStats['not_completed']; ?></span>
                            <span class="stat-label">Not Completed</span>
                        </div>
                        <?php if ($supervisionStats['other'] > 0): ?>
                            <div class="stat-item other" id="stat-other-wrapper">
                                <span class="stat-number" id="stat-other"><?php echo $supervisionStats['other']; ?></span>
                                <span class="stat-label">Other</span>
                            </div>
                        <?php else: ?>
                            <!-- Hidden placeholder so JS can still find it -->
                            <div class="stat-item other" id="stat-other-wrapper" style="display:none">
                                <span class="stat-number" id="stat-other">0</span>
                                <span class="stat-label">Other</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="degree-breakdown" id="degree-breakdown"
                        style="<?php echo ($supervisionStats['total'] == 0) ? 'display:none' : ''; ?>">
                        <?php foreach ($degreeCounts as $deg => $count): ?>
                            <span class="degree-badge" data-degree="<?php echo htmlspecialchars($deg); ?>">
                                <strong><?php echo $count; ?></strong> <?php echo htmlspecialchars($deg); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <!-- ===== End Stats ===== -->

                </div><!-- end .section-title -->

                <div class="supervision-native-scroll" id="supervision-scroll-container">
                    <div class="supervision-cards <?php echo empty($sections['supervision']) ? 'centered-empty' : ''; ?>"
                        id="supervision-cards">
                        <?php if (empty($sections['supervision'])): ?>
                            <div class="empty-state-card">
                                <i class="bi bi-people"></i>
                                <p>No supervision records yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sections['supervision'] as $item): ?>
                                <div class="supervision-card" id="supervision-<?php echo $item['id']; ?>"
                                    data-degree="<?php echo htmlspecialchars($item['degree'] ?? ''); ?>"
                                    data-status="<?php echo htmlspecialchars(trim($item['status'] ?? '')); ?>"
                                    data-program="<?php echo htmlspecialchars($item['program'] ?? ''); ?>">
                                    <div class="supervision-avatar-box">
                                        <?php
                                        $studentName = $item['student_name'] ?? 'Student';
                                        $initials = '';
                                        $words = explode(' ', $studentName);
                                        foreach ($words as $word)
                                            if (!empty($word))
                                                $initials .= strtoupper(substr($word, 0, 1));
                                        $initials = substr($initials, 0, 2) ?: 'ST';
                                        $colors = ['#28396C', '#B5E18B', '#F0FFC2', '#8C7A6B', '#4A3B32'];
                                        $avatarColor = $colors[abs(crc32($studentName)) % count($colors)];
                                        ?>
                                        <div class="supervision-avatar" style="background-color: <?php echo $avatarColor; ?>;">
                                            <?php if (!empty($item['student_avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['student_avatar']); ?>"
                                                    alt="<?php echo htmlspecialchars($studentName); ?>"
                                                    class="supervision-avatar-img">
                                            <?php else: ?>
                                                <span class="supervision-avatar-initials"><?php echo $initials; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                        $statusClass = str_replace(' ', '-', strtolower(trim($item['status'] ?? 'current')));
                                        ?>
                                        <div class="supervision-status-badge <?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(trim($item['status'] ?? 'Current')); ?>
                                        </div>
                                    </div>
                                    <div class="supervision-content">
                                        <h4 class="supervision-student-name">
                                            <?php echo htmlspecialchars($item['student_name'] ?? ''); ?>
                                        </h4>
                                        <div class="supervision-detail">
                                            <i class="bi bi-journal-bookmark-fill"></i>
                                            <span class="supervision-label">Thesis:</span>
                                            <span
                                                class="supervision-value"><?php echo htmlspecialchars($item['thesis_title'] ?? 'Not specified'); ?></span>
                                        </div>
                                        <div class="supervision-detail">
                                            <i class="bi bi-mortarboard-fill"></i>
                                            <span class="supervision-label">Degree:</span>
                                            <span
                                                class="supervision-value"><?php echo htmlspecialchars($item['degree'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="supervision-detail">
                                            <i class="bi bi-mortarboard-fill"></i>
                                            <span class="supervision-label">Program:</span>
                                            <span
                                                class="supervision-value"><?php echo htmlspecialchars($item['program'] ?? 'Not specified'); ?></span>
                                        </div>
                                        <div class="supervision-detail">
                                            <i class="bi bi-calendar-check-fill"></i>
                                            <span class="supervision-label">Period:</span>
                                            <span class="supervision-value"><?php echo $item['start_year'] ?? '??'; ?> -
                                                <?php echo $item['completion_year'] ?? 'Present'; ?></span>
                                        </div>
                                        <?php if (!empty($item['research_area'])): ?>
                                            <div class="supervision-tag">
                                                <i class="bi bi-tag-fill"></i>
                                                <span><?php echo htmlspecialchars($item['research_area']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isLoggedIn): ?>
                                        <div class="supervision-edit-controls">
                                            <button class="control-btn edit"
                                                onclick="editContent('supervision', <?php echo $item['id']; ?>)"><i
                                                    class="bi bi-pencil"></i></button>
                                            <button class="control-btn delete"
                                                onclick="deleteContent('supervision', <?php echo $item['id']; ?>)"><i
                                                    class="bi bi-trash"></i></button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>



        <!-- Publications -->
        <section id="publications" class="section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="bi bi-journal-text me-2"></i>Publications</h2>
                    <div class="supervision-toolbar">
                        <?php if ($isLoggedIn): ?>
                            <button class="add-btn" onclick="addContent('publications')"><i class="bi bi-plus-circle"></i>
                                Add Publication</button>
                        <?php endif; ?>
                        <?php if (!empty($pubTypes) || !empty($pubYears) || !empty($pubAuthors)): ?>
                            <div class="dropdown filter-dropdown" id="publicationFilterDropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                    id="publicationFilterBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                    aria-expanded="false">
                                    <i class="bi bi-funnel-fill me-1"></i> Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <?php if (!empty($pubTypes)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Type</div>
                                            <ul class="filter-options" id="pub-type-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="type"
                                                        data-value="all">All Types</a></li>
                                                <?php foreach ($pubTypes as $pt): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="type"
                                                            data-value="<?php echo htmlspecialchars($pt); ?>"><?php echo htmlspecialchars($pt); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($pubYears)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Year</div>
                                            <ul class="filter-options" id="pub-year-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="year"
                                                        data-value="all">All Years</a></li>
                                                <?php foreach ($pubYears as $yr): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="year"
                                                            data-value="<?php echo $yr; ?>"><?php echo $yr; ?></a></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (!empty($pubAuthors)): ?>
                                        <li class="filter-group">
                                            <div class="dropdown-header">Author</div>
                                            <ul class="filter-options" id="pub-author-options">
                                                <li><a class="dropdown-item active" href="#" data-filter="author"
                                                        data-value="all">All Authors</a></li>
                                                <?php foreach ($pubAuthors as $auth): ?>
                                                    <li><a class="dropdown-item" href="#" data-filter="author"
                                                            data-value="<?php echo htmlspecialchars($auth); ?>"><?php echo htmlspecialchars($auth); ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-container scrollable-card-grid" id="publications-container">
                    <?php if (empty($sections['publications'])): ?>
                        <div class="empty-state-card"><i class="bi bi-journal"></i>
                            <p>No publications yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sections['publications'] as $item): ?>
                            <div class="card" id="publication-<?php echo $item['id']; ?>"
                                data-type="<?php echo htmlspecialchars($item['type'] ?? ''); ?>"
                                data-year="<?php echo htmlspecialchars($item['year'] ?? ''); ?>"
                                data-authors="<?php echo htmlspecialchars($item['authors'] ?? ''); ?>">
                                <div class="card-body">
                                    <div class="publication-header">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h5>
                                        <?php if (!empty($item['type'])): ?>
                                            <span class="publication-type"><?php echo htmlspecialchars($item['type']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text">
                                        <strong><i class="bi bi-pencil-fill"></i> Authors:</strong>
                                        <?php echo htmlspecialchars($item['authors'] ?? ''); ?><br>
                                        <?php if (!empty($item['journal'])): ?><strong><i class="bi bi-journal-text"></i>
                                                Journal:</strong>
                                            <?php echo htmlspecialchars($item['journal']); ?><br><?php endif; ?>
                                        <?php
                                        $details = [];
                                        if (!empty($item['volume']))
                                            $details[] = 'Vol. ' . $item['volume'];
                                        if (!empty($item['issue']))
                                            $details[] = 'Issue ' . $item['issue'];
                                        if (!empty($item['pages']))
                                            $details[] = 'pp. ' . $item['pages'];
                                        if (!empty($item['year']))
                                            $details[] = $item['year'];
                                        ?>
                                        <?php if (!empty($details)): ?><strong><i class="bi bi-info-circle-fill"></i>
                                                Details:</strong>
                                            <?php echo htmlspecialchars(implode(' • ', $details)); ?><br><?php endif; ?>
                                        <?php if (!empty($item['doi'])): ?>
                                            <strong><i class="bi bi-link-45deg"></i> DOI:</strong> <a
                                                href="https://doi.org/<?php echo htmlspecialchars($item['doi']); ?>" target="_blank"
                                                class="doi-link"><?php echo htmlspecialchars($item['doi']); ?></a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if ($isLoggedIn): ?>
                                    <div class="edit-controls">
                                        <button class="control-btn edit"
                                            onclick="editContent('publications', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-pencil"></i></button>
                                        <button class="control-btn delete"
                                            onclick="deleteContent('publications', <?php echo $item['id']; ?>)"><i
                                                class="bi bi-trash"></i></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Others -->
        <section id="others" class="section">
            <div class="container">
                <div class="section-title">
                    <h2><i class="bi bi-stars me-2"></i>Other Achievements</h2>
                </div>

                <!-- Twitter‑style tab navigation with sliding indicator -->
                <div class="tab-bar">
                    <div class="tab-indicator"></div>
                    <button class="tab-btn active" data-tab="awards">
                        <span class="tab-text"><i class="bi bi-trophy-fill tab-icon"></i> Awards</span>
                    </button>
                    <button class="tab-btn" data-tab="appointments">
                        <span class="tab-text"><i class="bi bi-briefcase-fill tab-icon"></i> Appointments</span>
                    </button>
                    <button class="tab-btn" data-tab="invited_talks">
                        <span class="tab-text"><i class="bi bi-mic-fill tab-icon"></i> Invited Talks</span>
                    </button>
                </div>

                <!-- Tab Content Container -->
                <div class="tab-content-wrapper">
                    <!-- Awards Tab -->
                    <div class="tab-pane active" id="tab-awards">
                        <div class="tab-control-row">
                            <h3 class="tab-panel-title">
                                <i class="bi bi-trophy-fill me-2" style="color: #ffd700;"></i> Awards &amp; Recognition
                            </h3>
                            <div class="tab-actions">
                                <?php if ($isLoggedIn): ?>
                                    <button class="add-btn btn-sm" onclick="addContent('awards')">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($awardYears)): ?>
                                    <div class="dropdown filter-dropdown" id="awards-year-filter-dropdown">
                                        <button class="btn filter-btn dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            <i class="bi bi-funnel-fill"></i> Year
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li class="filter-group">
                                                <div class="dropdown-header">Year</div>
                                                <ul class="filter-options" id="awards-year-options">
                                                    <li><a class="dropdown-item active" href="#" data-filter="year"
                                                            data-value="all">All Years</a></li>
                                                    <?php foreach ($awardYears as $yr): ?>
                                                        <li><a class="dropdown-item" href="#" data-filter="year"
                                                                data-value="<?php echo $yr; ?>"><?php echo $yr; ?></a></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tab-scrollable" id="awards-container">
                            <?php if (empty($sections['awards'])): ?>
                                <p class="text-muted text-center py-4">No awards yet.</p>
                            <?php else: ?>
                                <?php foreach ($sections['awards'] as $item): ?>
                                    <div class="name-card" id="award-<?php echo $item['id']; ?>"
                                        data-year="<?php echo htmlspecialchars($item['year'] ?? ''); ?>">
                                        <?php if ($isLoggedIn): ?>
                                            <div class="edit-controls">
                                                <button class="control-btn edit"
                                                    onclick="editContent('awards', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-pencil"></i></button>
                                                <button class="control-btn delete"
                                                    onclick="deleteContent('awards', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                                        <p><?php echo htmlspecialchars($item['organization'] ?? ''); ?>,
                                            <?php echo $item['year'] ?? ''; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Appointments Tab -->
                    <div class="tab-pane" id="tab-appointments">
                        <div class="tab-control-row">
                            <h3 class="tab-panel-title">
                                <i class="bi bi-briefcase-fill me-2" style="color: #3498db;"></i> Appointments
                            </h3>
                            <div class="tab-actions">
                                <?php if ($isLoggedIn): ?>
                                    <button class="add-btn btn-sm" onclick="addContent('appointments')">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($apptStartYears)): ?>
                                    <div class="dropdown filter-dropdown" id="appointments-year-filter-dropdown">
                                        <button class="btn filter-btn dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            <i class="bi bi-funnel-fill"></i> Start Year
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li class="filter-group">
                                                <div class="dropdown-header">Start Year</div>
                                                <ul class="filter-options" id="appointments-year-options">
                                                    <li><a class="dropdown-item active" href="#" data-filter="start_year"
                                                            data-value="all">All Years</a></li>
                                                    <?php foreach ($apptStartYears as $yr): ?>
                                                        <li><a class="dropdown-item" href="#" data-filter="start_year"
                                                                data-value="<?php echo $yr; ?>"><?php echo $yr; ?></a></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tab-scrollable" id="appointments-container">
                            <?php if (empty($sections['appointments'])): ?>
                                <p class="text-muted text-center py-4">No appointments yet.</p>
                            <?php else: ?>
                                <?php foreach ($sections['appointments'] as $item): ?>
                                    <div class="name-card" id="appointment-<?php echo $item['id']; ?>"
                                        data-start-year="<?php echo htmlspecialchars($item['start_year'] ?? ''); ?>">
                                        <?php if ($isLoggedIn): ?>
                                            <div class="edit-controls">
                                                <button class="control-btn edit"
                                                    onclick="editContent('appointments', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-pencil"></i></button>
                                                <button class="control-btn delete"
                                                    onclick="deleteContent('appointments', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($item['position'] ?? ''); ?></h4>
                                        <p><?php echo htmlspecialchars($item['organization'] ?? ''); ?></p>
                                        <p><?php echo $item['start_year'] ?? ''; ?> -
                                            <?php echo !empty($item['is_current']) ? 'Present' : ($item['end_year'] ?? ''); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Invited Talks Tab -->
                    <div class="tab-pane" id="tab-invited_talks">
                        <div class="tab-control-row">
                            <h3 class="tab-panel-title">
                                <i class="bi bi-mic-fill me-2" style="color: #e74c3c;"></i> Invited Talks
                            </h3>
                            <div class="tab-actions">
                                <?php if ($isLoggedIn): ?>
                                    <button class="add-btn btn-sm" onclick="addContent('invited_talks')">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($talkYears)): ?>
                                    <div class="dropdown filter-dropdown" id="talks-year-filter-dropdown">
                                        <button class="btn filter-btn dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            <i class="bi bi-funnel-fill"></i> Year
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li class="filter-group">
                                                <div class="dropdown-header">Year</div>
                                                <ul class="filter-options" id="talks-year-options">
                                                    <li><a class="dropdown-item active" href="#" data-filter="year"
                                                            data-value="all">All Years</a></li>
                                                    <?php foreach ($talkYears as $yr): ?>
                                                        <li><a class="dropdown-item" href="#" data-filter="year"
                                                                data-value="<?php echo $yr; ?>"><?php echo $yr; ?></a></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="tab-scrollable" id="invited_talks-container">
                            <?php if (empty($sections['invited_talks'])): ?>
                                <p class="text-muted text-center py-4">No invited talks yet.</p>
                            <?php else: ?>
                                <?php foreach ($sections['invited_talks'] as $item): ?>
                                    <div class="name-card" id="talk-<?php echo $item['id']; ?>"
                                        data-year="<?php echo htmlspecialchars($item['year'] ?? ''); ?>">
                                        <?php if ($isLoggedIn): ?>
                                            <div class="edit-controls">
                                                <button class="control-btn edit"
                                                    onclick="editContent('invited_talks', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-pencil"></i></button>
                                                <button class="control-btn delete"
                                                    onclick="deleteContent('invited_talks', <?php echo $item['id']; ?>)"><i
                                                        class="bi bi-trash"></i></button>
                                            </div>
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($item['talk_title'] ?? ''); ?></h4>
                                        <p><strong>Event:</strong> <?php echo htmlspecialchars($item['event_name'] ?? ''); ?>
                                        </p>
                                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($item['venue'] ?? ''); ?>,
                                            <?php echo $item['year'] ?? ''; ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="social-links">
                    <?php
                    // 定义社交平台数组（键名、显示名、图标类）
                    $socialPlatforms = [
                        'twitter' => ['name' => 'Twitter', 'icon' => 'bi-twitter-x', 'default_url' => ''],
                        'linkedin' => ['name' => 'LinkedIn', 'icon' => 'bi-linkedin', 'default_url' => ''],
                        'github' => ['name' => 'GitHub', 'icon' => 'bi-github', 'default_url' => ''],
                        'google_scholar' => ['name' => 'Google Scholar', 'icon' => 'bi-google', 'default_url' => '']
                    ];
                    ?>
                    <?php foreach ($socialPlatforms as $key => $platform): ?>
                        <div class="social-link-wrapper" data-platform="<?php echo $key; ?>">
                            <a href="javascript:void(0);" class="social-link" data-platform="<?php echo $key; ?>"
                                data-default-url="<?php echo htmlspecialchars($platform['default_url']); ?>" target="_blank"
                                title="<?php echo htmlspecialchars($platform['name']); ?>">
                                <i class="bi <?php echo $platform['icon']; ?>"></i>
                            </a>
                            <?php if ($isLoggedIn): ?>
                                <button class="edit-social-link" data-platform="<?php echo $key; ?>"
                                    title="Edit <?php echo htmlspecialchars($platform['name']); ?> link">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p>&copy; <?php echo date('Y'); ?> Academic Portfolio. All rights reserved.</p>
                <p class="small opacity-50">Designed with <i class="bi bi-heart-fill text-danger"></i> for academia</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        const currentUser = '<?php echo $userName; ?>';

        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('profile_updated')) {
                showNotification('Profile updated successfully!', 'success');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            if (urlParams.has('profile_created')) {
                showNotification('Profile created successfully!', 'success');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            initIntroAnimation();
            initScrollEffects();
            initForms();
            // ← 初始化筛选监听
        });

        function initIntroAnimation() {
            setTimeout(() => {
                const intro = document.getElementById('intro-animation');
                if (intro) intro.style.display = 'none';
            }, 4000); // 与CSS动画时长一致
        }

        function initScrollEffects() {
            window.addEventListener('scroll', () => {
                const header = document.getElementById('main-header');
                if (window.scrollY > 100) header.classList.add('visible');
                else header.classList.remove('visible');
            });
        }

        function initForms() {
            const loginForm = document.getElementById('loginForm');
            if (loginForm) loginForm.addEventListener('submit', handleLogin);
            const imageUploadForm = document.getElementById('imageUploadForm');
            if (imageUploadForm) {
                imageUploadForm.addEventListener('submit', handleImageUpload);
                document.getElementById('imageFile').addEventListener('change', previewImage);
            }
            const contentForm = document.getElementById('contentForm');
            if (contentForm) contentForm.addEventListener('submit', handleContentSubmit);
        }

        async function handleLogin(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('loginSubmitBtn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i><span>Logging in...</span>`;

            const formData = new FormData();
            formData.append('email', document.getElementById('email').value);
            formData.append('password', document.getElementById('password').value);

            try {
                const response = await fetch('login.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    // 1. Close the login modal gracefully
                    const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
                    if (modal) modal.hide();

                    // 2. Show a beautiful success toast
                    showLoginSuccessToast('Login successful! Redirecting to dashboard...');

                    // 3. Redirect after a short delay (so user sees the toast)
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    // Error case – keep modal open and show error inside modal
                    const messageDiv = document.getElementById('loginMessage');
                    messageDiv.classList.remove('d-none', 'alert-success');
                    messageDiv.classList.add('alert-danger');
                    messageDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${data.message}`;
                    submitBtn.classList.remove('loading');
                    submitBtn.innerHTML = originalContent;
                }
            } catch (error) {
                const messageDiv = document.getElementById('loginMessage');
                messageDiv.classList.remove('d-none', 'alert-success');
                messageDiv.classList.add('alert-danger');
                messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i> Connection error. Please try again.';
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = originalContent;
            }
        }

        // New helper: elegant login success toast
        function showLoginSuccessToast(message) {
            // Remove any existing toast to avoid stacking
            const oldToast = document.querySelector('.login-success-toast');
            if (oldToast) oldToast.remove();

            const toast = document.createElement('div');
            toast.className = 'login-success-toast';
            toast.innerHTML = `
        <div class="toast-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">Welcome back!</div>
            <div class="toast-message">${escapeHtml(message)}</div>
            <div class="toast-progress"></div>
        </div>
        <button class="toast-close" onclick="this.closest('.login-success-toast').remove()">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
            document.body.appendChild(toast);

            // Animate in
            setTimeout(() => toast.classList.add('show'), 10);

            // Auto remove after 2 seconds (before redirect)
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 2000);
        }

        function uploadImage(type) {
            document.getElementById('imageType').value = type;
            new bootstrap.Modal(document.getElementById('imageUploadModal')).show();
        }

        let cropper = null;
        let currentImageFile = null;

        // 当文件选择变化时
        document.getElementById('imageFile').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            currentImageFile = file;

            // 只对背景图片类型启用裁剪（也可对头像启用，按需）
            const imageType = document.getElementById('imageType').value;
            if (imageType !== 'background') {
                // 头像等其他类型：仅显示简单预览，不裁剪
                const reader = new FileReader();
                reader.onload = function (ev) {
                    const previewImg = document.querySelector('#simplePreview img');
                    previewImg.src = ev.target.result;
                    document.getElementById('simplePreview').style.display = 'block';
                    document.getElementById('cropSection').style.display = 'none';
                };
                reader.readAsDataURL(file);
                return;
            }

            // 背景图片：显示裁剪器
            const reader = new FileReader();
            reader.onload = function (ev) {
                const imgURL = ev.target.result;
                const cropImg = document.getElementById('cropImage');
                cropImg.src = imgURL;

                // 隐藏简单预览，显示裁剪区
                document.getElementById('simplePreview').style.display = 'none';
                const cropSection = document.getElementById('cropSection');
                cropSection.style.display = 'block';

                // 销毁已有的 cropper
                if (cropper) cropper.destroy();

                // 获取英雄区背景容器的宽高比，用于锁定裁剪框比例
                const heroSection = document.querySelector('.hero-background-section');
                let aspectRatio = 16 / 9; // 默认
                if (heroSection) {
                    const rect = heroSection.getBoundingClientRect();
                    if (rect.width && rect.height) {
                        aspectRatio = rect.width / rect.height;
                    }
                }

                cropper = new Cropper(cropImg, {
                    aspectRatio: aspectRatio,    // 锁定比例与背景容器一致
                    viewMode: 1,                 // 限制裁剪框不超出图片
                    dragMode: 'move',
                    autoCropArea: 0.8,           // 默认显示图片的 80% 区域
                    responsive: true,
                    restore: false,
                    guides: true,
                    center: true,
                    highlight: false,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        });

        // 修改原有的 handleImageUpload 函数
        async function handleImageUpload(e) {
            e.preventDefault();
            const imageType = document.getElementById('imageType').value;
            const fileInput = document.getElementById('imageFile');

            if (!currentImageFile && !fileInput.files[0]) {
                showNotification('Please select an image', 'error');
                return;
            }

            let finalFile = null;

            // 如果是背景图片且 cropper 实例存在，则裁剪后上传
            if (imageType === 'background' && cropper) {
                // 获取裁剪后的 Canvas，指定输出尺寸（可根据需要调整）
                const canvas = cropper.getCroppedCanvas({
                    width: 1920,          // 输出宽度（桌面背景常见）
                    height: 1080,         // 输出高度，按比例自动适应裁剪框比例
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });

                if (!canvas) {
                    showNotification('Unable to crop image. Please try again.', 'error');
                    return;
                }

                // 将 Canvas 转为 Blob（JPEG 格式，质量 0.9）
                finalFile = await new Promise((resolve) => {
                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', 0.9);
                });
                // 构造一个 File 对象（保持原名或新名）
                finalFile = new File([finalFile], 'cropped_background.jpg', { type: 'image/jpeg' });
            } else {
                // 非背景图或没有裁剪器时，使用原始文件
                finalFile = fileInput.files[0];
            }

            const formData = new FormData();
            formData.append('image', finalFile);
            formData.append('type', imageType);

            try {
                showLoading('Uploading image...');
                const response = await fetch('upload_image.php', { method: 'POST', body: formData });
                const data = await response.json();
                hideLoading();

                if (data.success) {
                    const timestamp = new Date().getTime();
                    if (imageType === 'profile') {
                        // 更新头像逻辑（保持不变）
                        const profileImg = document.getElementById('profile-image');
                        const profileDefault = document.querySelector('.profile-default-avatar');
                        if (profileImg) profileImg.src = data.url + '?t=' + timestamp;
                        else if (profileDefault) {
                            const newImg = document.createElement('img');
                            newImg.src = data.url + '?t=' + timestamp;
                            newImg.alt = 'Profile';
                            newImg.className = 'profile-image';
                            newImg.id = 'profile-image';
                            profileDefault.replaceWith(newImg);
                        }
                        const headerAvatar = document.getElementById('header-avatar');
                        const headerDefault = document.querySelector('.header-default-avatar');
                        if (headerAvatar) headerAvatar.src = data.url + '?t=' + timestamp;
                        else if (headerDefault) {
                            const newImg = document.createElement('img');
                            newImg.src = data.url + '?t=' + timestamp;
                            newImg.alt = 'Profile';
                            newImg.className = 'header-avatar';
                            newImg.id = 'header-avatar';
                            headerDefault.replaceWith(newImg);
                        }
                    } else if (imageType === 'background') {
                        document.querySelector('.hero-background').style.backgroundImage = `url('${data.url}?t=${timestamp}')`;
                    }
                    // 关闭模态框，重置表单和 cropper
                    bootstrap.Modal.getInstance(document.getElementById('imageUploadModal')).hide();
                    document.getElementById('imageUploadForm').reset();
                    document.getElementById('cropSection').style.display = 'none';
                    document.getElementById('simplePreview').style.display = 'none';
                    if (cropper) { cropper.destroy(); cropper = null; }
                    currentImageFile = null;
                    showNotification('Image uploaded successfully!', 'success');
                } else {
                    showNotification('Upload failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                hideLoading();
                showNotification('Upload error: ' + error.message, 'error');
            }
        }

        // 模态框关闭时清理 cropper 和预览
        document.getElementById('imageUploadModal').addEventListener('hidden.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            document.getElementById('cropSection').style.display = 'none';
            document.getElementById('simplePreview').style.display = 'none';
            document.getElementById('imageUploadForm').reset();
            currentImageFile = null;
        });

        async function addContent(table) {
            if (!isLoggedIn) return;
            document.getElementById('modalTitle').innerHTML = `<i class="bi bi-plus-circle me-2"></i>Add ${formatTableName(table)}`;
            document.getElementById('contentTable').value = table;
            document.getElementById('contentId').value = '';
            document.getElementById('formFields').innerHTML = generateFormFields(table);
            new bootstrap.Modal(document.getElementById('contentModal')).show();
            if (table === 'publications') initPublicationType();
        }

        async function editContent(table, id) {
            if (!isLoggedIn) return;
            document.getElementById('modalTitle').innerHTML = `<i class="bi bi-pencil-square me-2"></i>Edit ${formatTableName(table)}`;
            document.getElementById('contentTable').value = table;
            document.getElementById('contentId').value = id;
            try {
                showLoading('Loading content...');
                const response = await fetch(`get_content.php?table=${table}&id=${id}`);
                const data = await response.json();
                hideLoading();
                if (data.success === false) { showNotification(data.message || 'Error loading content', 'error'); return; }
                document.getElementById('formFields').innerHTML = generateFormFields(table, data);
                new bootstrap.Modal(document.getElementById('contentModal')).show();
                if (table === 'publications') initPublicationType();
            } catch (error) {
                hideLoading();
                showNotification('Error loading content: ' + error.message, 'error');
            }
        }

        async function deleteContent(table, id) {
            if (!isLoggedIn || !confirm('Are you sure?')) return;
            try {
                showLoading('Deleting...');
                const response = await fetch('delete_content.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table, id })
                });
                const data = await response.json();
                hideLoading();
                if (data.success) {
                    showNotification('Item deleted successfully', 'success');
                    await refreshTableData(table);
                } else {
                    showNotification('Error deleting item', 'error');
                }
            } catch (error) {
                hideLoading();
                showNotification('Error deleting item', 'error');
            }
        }

        async function handleContentSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const table = formData.get('table');
            const id = formData.get('id');
            const data = {};
            formData.forEach((value, key) => {
                if (key !== 'table' && key !== 'id') {
                    if (['year', 'start_year', 'end_year', 'completion_year', 'display_order'].includes(key))
                        data[key] = value === '' ? null : parseInt(value, 10);
                    else if (key === 'is_current')
                        data[key] = value === 'on';
                    else
                        data[key] = value;
                }
            });
            try {
                showLoading('Saving...');
                const response = await fetch(id ? 'update_content.php' : 'add_content.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ table, id, data })
                });
                const result = await response.json();
                hideLoading();
                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('contentModal')).hide();
                    showNotification(result.message || 'Content saved successfully', 'success');
                    document.getElementById('contentForm').reset();
                    await refreshTableData(table);
                } else {
                    showNotification('Error saving content: ' + (result.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                hideLoading();
                showNotification('Error saving content: ' + error.message, 'error');
            }
        }

        async function refreshTableData(table) {
            try {
                const response = await fetch(`get_all_data.php?table=${table}`);
                const result = await response.json();
                if (result.success) {
                    if (table === 'supervision') {
                        await refreshSupervisionCarousel(result.data);
                    } else if (table === 'research_areas') {
                        // Use the dedicated tag renderer
                        renderResearchAreas(result.data);
                    } else {
                        updateTableUI(table, result.data);
                        if (table === 'teaching') {
                            updateTeachingFiltersDropdown(result.data);
                        }
                    }
                }
            } catch (error) {
                console.error('Error refreshing table data:', error);
            }
        }

        async function refreshSupervisionCarousel(data) {
            const container = document.getElementById('supervision-cards');
            if (!container) return;
            container.innerHTML = '';
            if (!data || data.length === 0) {
                container.innerHTML = `<div class="empty-state-card"><i class="bi bi-people"></i><p>No supervision records yet.</p></div>`;
                container.classList.add('centered-empty');   // 添加
                return;
            } else {
                container.classList.remove('centered-empty'); // 正常情况移除
            }

            if (data[0] && data[0].display_order !== undefined) {
                data.sort((a, b) => (a.display_order || 0) - (b.display_order || 0));
            }
            data.forEach((item, index) => {
                const studentName = item.student_name || 'Student';
                let initials = '';
                studentName.split(' ').forEach(w => { if (w) initials += w[0].toUpperCase(); });
                initials = initials.substring(0, 2) || 'ST';
                const colors = ['#28396C', '#B5E18B', '#F0FFC2', '#8C7A6B', '#4A3B32'];
                const avatarColor = colors[hashCode(studentName) % colors.length];
                const statusClass = (item.status || 'current').toLowerCase().trim().replace(/\s+/g, '-');
                const statusText = item.status || 'Current';
                const cardHtml = `
                    <div class="supervision-card" id="supervision-${item.id}"
                         data-degree="${escapeHtml(item.degree || '')}"
                         data-status="${escapeHtml(statusText)}"
                         data-program="${escapeHtml(item.program || '')}">
                        <div class="supervision-avatar-box">
                            <div class="supervision-avatar" style="background-color: ${avatarColor};">
                                ${item.student_avatar ? `<img src="${escapeHtml(item.student_avatar)}" alt="${escapeHtml(studentName)}" class="supervision-avatar-img">` : `<span class="supervision-avatar-initials">${initials}</span>`}
                            </div>
                            <div class="supervision-status-badge ${statusClass}">${statusText}</div>
                        </div>
                        <div class="supervision-content">
                            <h4 class="supervision-student-name">${escapeHtml(item.student_name || '')}</h4>
                            <div class="supervision-detail">
                                <i class="bi bi-journal-bookmark-fill"></i>
                                <span class="supervision-label">Thesis:</span>
                                <span class="supervision-value">${escapeHtml(item.thesis_title || 'Not specified')}</span>
                            </div>
                            <div class="supervision-detail">
                                <i class="bi bi-mortarboard-fill"></i>
                                <span class="supervision-label">Degree:</span>
                                <span class="supervision-value">${escapeHtml(item.degree || 'N/A')}</span>
                            </div>
                            <div class="supervision-detail">
                                <i class="bi bi-mortarboard-fill"></i>
                                <span class="supervision-label">Program:</span>
                                <span class="supervision-value">${escapeHtml(item.program || 'Not specified')}</span>
                            </div>
                            <div class="supervision-detail">
                                <i class="bi bi-calendar-check-fill"></i>
                                <span class="supervision-label">Period:</span>
                                <span class="supervision-value">${item.start_year || '??'} - ${item.completion_year || 'Present'}</span>
                            </div>
                            ${item.research_area ? `<div class="supervision-tag"><i class="bi bi-tag-fill"></i><span>${escapeHtml(item.research_area)}</span></div>` : ''}
                        </div>
                        ${isLoggedIn ? `<div class="supervision-edit-controls"><button class="control-btn edit" onclick="editContent('supervision', ${item.id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('supervision', ${item.id})"><i class="bi bi-trash"></i></button></div>` : ''}
                    </div>`;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });
            supervisionCards = document.querySelectorAll('.supervision-card');
            supervisionCurrentIndex = 0;
            if (supervisionCards.length > 0) {
                supervisionCardWidth = supervisionCards[0].offsetWidth + 24;
                container.style.transform = 'translateX(0)';
            }
            updateSupervisionFiltersDropdown(data);
            updateSupervisionStats();
        }


        function hashCode(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) { hash = ((hash << 5) - hash) + str.charCodeAt(i); hash |= 0; }
            return Math.abs(hash);
        }

        function updateTableUI(table, data) {
            const container = document.getElementById(`${table}-container`);
            if (!container) return;
            container.innerHTML = '';
            if (!data || data.length === 0) {
                container.innerHTML = `<div class="empty-state-card"><i class="bi bi-${getIconForTable(table)}"></i><p>No ${formatTableName(table)} records yet.</p></div>`;
                return;
            }
            data.forEach(item => {
                let html = '';
                const id = item.id;
                switch (table) {
                    case 'teaching': html = generateTeachingHTML(item, id); break;
                    case 'research_projects': html = generateResearchHTML(item, id); break;
                    case 'publications': html = generatePublicationHTML(item, id); break;
                    case 'awards': html = generateAwardHTML(item, id); break;
                    case 'appointments': html = generateAppointmentHTML(item, id); break;
                    case 'invited_talks': html = generateTalkHTML(item, id); break;
                }
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        function getIconForTable(table) {
            const icons = { teaching: 'book', research_projects: 'clipboard-data', publications: 'journal', awards: 'trophy', appointments: 'briefcase', invited_talks: 'mic' };
            return icons[table] || 'info-circle';
        }

        // Check if a field name is a year field
        function isYearField(name) {
            const lower = name.toLowerCase();
            return lower === 'year' || lower.endsWith('_year');
        }

        // Generate <option> list for a year dropdown
        function generateYearOptions(selectedValue) {
            const currentYear = new Date().getFullYear();
            const startYear = 1950;           // you can adjust this
            const endYear = currentYear + 5;  // show 5 years into the future
            let options = '<option value="">Select Year</option>';
            for (let y = endYear; y >= startYear; y--) {
                const yStr = y.toString();
                const selected = (selectedValue == yStr) ? ' selected' : '';
                options += `<option value="${yStr}"${selected}>${yStr}</option>`;
            }
            return options;
        }

        function generateFormFields(table, data = {}) {
            const fields = {
                teaching: [
                    { name: 'course_code', label: 'Course Code', type: 'text' },
                    { name: 'course_name', label: 'Course Name', type: 'text', required: true },
                    { name: 'institution', label: 'Institution', type: 'text' },
                    { name: 'semester', label: 'Semester', type: 'text' },
                    { name: 'year', label: 'Year', type: 'number' },
                    { name: 'description', label: 'Description', type: 'textarea' },
                ],
                publications: [
                    { name: 'title', label: 'Title', type: 'text', required: true },
                    { name: 'authors', label: 'Authors', type: 'text', required: true },
                    { name: 'type', label: 'Type', type: 'select', options: ['Journal Article', 'Conference Paper', 'Book Chapter', 'Book'] },
                    { name: 'journal', label: '', type: 'text' },
                    { name: 'year', label: 'Year', type: 'number' },
                    { name: 'volume', label: 'Volume', type: 'text' },
                    { name: 'issue', label: 'Issue', type: 'text' },
                    { name: 'pages', label: 'Pages', type: 'text' },
                    { name: 'doi', label: 'DOI', type: 'text' },
                ],
                awards: [
                    { name: 'title', label: 'Award Name', type: 'text', required: true },
                    { name: 'organization', label: 'Organization', type: 'text' },
                    { name: 'year', label: 'Year', type: 'number' },
                    { name: 'description', label: 'Description', type: 'textarea' },
                ],
                appointments: [
                    { name: 'position', label: 'Position', type: 'text', required: true },
                    { name: 'organization', label: 'Organization', type: 'text' },
                    { name: 'start_year', label: 'Start Year', type: 'number' },
                    { name: 'end_year', label: 'End Year', type: 'number' },
                    { name: 'is_current', label: 'Current Position', type: 'checkbox' },
                ],
                invited_talks: [
                    { name: 'event_name', label: 'Event Name', type: 'text', required: true },
                    { name: 'talk_title', label: 'Talk Title', type: 'text', required: true },
                    { name: 'venue', label: 'Venue', type: 'text' },
                    { name: 'year', label: 'Year', type: 'number' },
                ],
                supervision: [
                    { name: 'student_name', label: 'Student Name', type: 'text', required: true },
                    { name: 'degree', label: 'Degree', type: 'select', options: ['Master', 'PhD', 'Undergraduate', 'Other'] },
                    { name: 'thesis_title', label: 'Thesis Title', type: 'text' },
                    { name: 'program', label: 'Program', type: 'text' },
                    { name: 'start_year', label: 'Start Year', type: 'number' },
                    { name: 'completion_year', label: 'Completion Year', type: 'number' },
                    { name: 'status', label: 'Status', type: 'select', options: ['Completed', 'Not Completed'] },
                ],
                research_projects: [
                    { name: 'project_title', label: 'Project Title', type: 'text', required: true },
                    { name: 'type_of_grant', label: 'Type of Grant', type: 'text' },
                    { name: 'funding_body', label: 'Funding Body', type: 'text' },
                    { name: 'start_year', label: 'Start Year', type: 'number' },
                    { name: 'end_year', label: 'End Year', type: 'number' },
                    { name: 'status', label: 'Status', type: 'select', options: ['Ongoing', 'Completed'] },
                    { name: 'description', label: 'Description', type: 'textarea' },
                ],
                research_areas: [
                    { name: 'area_name', label: 'Area Name', type: 'text', required: true },
                ]
            };
            let html = '';
            (fields[table] || []).forEach(field => {
                const value = data[field.name] || '';
                const required = field.required ? 'required' : '';
                let currentLabel = field.label;
                if (field.name === 'journal') {
                    const selectedType = data.type || 'Journal Article';
                    currentLabel = getJournalLabel(selectedType);
                }

                if (field.type === 'textarea')
                    html += `<div class="mb-3"><label class="form-label">${currentLabel}</label><textarea class="form-control" name="${field.name}" rows="3" ${required}>${value}</textarea></div>`;
                else if (field.type === 'checkbox')
                    html += `<div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="${field.name}" ${value ? 'checked' : ''}><label class="form-check-label">${currentLabel}</label></div>`;
                else if (field.type === 'select') {
                    let opts = '';
                    field.options.forEach(opt => opts += `<option value="${opt}" ${value === opt ? 'selected' : ''}>${opt}</option>`);
                    html += `<div class="mb-3"><label class="form-label">${currentLabel}</label><select class="form-select" name="${field.name}" ${required}><option value="">Select</option>${opts}</select></div>`;
                }
                else {
                    // NEW: if the field is a year, render a dropdown
                    if (isYearField(field.name)) {
                        html += `<div class="mb-3">
                        <label class="form-label">${currentLabel}</label>
                        <select class="form-select" name="${field.name}" ${required}>
                            ${generateYearOptions(value)}
                        </select>
                     </div>`;
                    } else {
                        // fallback for other number or text fields
                        html += `<div class="mb-3"><label class="form-label">${currentLabel}</label><input type="${field.type}" class="form-control" name="${field.name}" value="${escapeHtml(value)}" ${required}></div>`;
                    }
                }
            });
            return html;
        }

        function generateTeachingHTML(data, id) {
            return `<div class="card" id="teaching-${id}"
             data-semester="${escapeHtml(data.semester || '')}"
             data-institution="${escapeHtml(data.institution || '')}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('teaching', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('teaching', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <div class="card-body"><h5 class="card-title">${escapeHtml(data.course_code || '')} - ${escapeHtml(data.course_name || '')}</h5><p class="card-text"><strong>Institution:</strong> ${escapeHtml(data.institution || '')}<br><strong>Semester:</strong> ${escapeHtml(data.semester || '')} ${data.year || ''}<br>${data.description ? '<small>' + escapeHtml(data.description) + '</small>' : ''}</p></div>
    </div>`;
        }

        function generateResearchHTML(data, id) {
            const grantType = data.type_of_grant || '';
            const funding = data.funding_body || '';
            const period = (data.start_year || '') + ' - ' + (data.end_year || 'Present');
            const status = data.status || '';
            return `<div class="card" id="research-${id}" data-type-of-grant="${escapeHtml(grantType)}" data-status="${escapeHtml(status)}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('research_projects', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('research_projects', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <div class="card-body">
            <h5 class="card-title">${escapeHtml(data.project_title || 'Untitled Project')}</h5>
            <p class="card-text">
                ${grantType ? `<strong><i class="bi bi-tag me-1"></i>Grant Type:</strong> ${escapeHtml(grantType)}<br>` : ''}
                ${funding ? `<strong><i class="bi bi-building me-1"></i>Funded by:</strong> ${escapeHtml(funding)}<br>` : ''}
                <strong><i class="bi bi-calendar me-1"></i>Period:</strong> ${period}<br>
                ${status ? `<strong><i class="bi bi-check-circle me-1"></i>Status:</strong> ${escapeHtml(status)}<br>` : ''}
                ${data.description ? '<small class="d-block mt-2">' + escapeHtml(data.description) + '</small>' : ''}
            </p>
        </div>
    </div>`;
        }

        function generatePublicationHTML(data, id) {
            const type = data.type || '';
            const authors = data.authors || '';
            const year = data.year || '';
            let journalLabel = 'Publisher';
            if (type === 'Journal Article') journalLabel = 'Journal';
            else if (type === 'Conference Paper') journalLabel = 'Conference';
            else if (type === 'Book Chapter') journalLabel = 'Book Title';
            else if (type === 'Book') journalLabel = 'Publisher';
            let details = [];
            if (data.journal) details.push(`<strong>${journalLabel}:</strong> ${escapeHtml(data.journal)}`);
            if (data.volume) details.push(`Vol. ${escapeHtml(data.volume)}`);
            if (data.issue) details.push(`Issue ${escapeHtml(data.issue)}`);
            if (data.pages) details.push(`pp. ${escapeHtml(data.pages)}`);
            if (data.year) details.push(`Year: ${data.year}`);
            return `<div class="card" id="publication-${id}"
             data-type="${escapeHtml(type)}"
             data-year="${escapeHtml(year)}"
             data-authors="${escapeHtml(authors)}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('publications', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('publications', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <div class="card-body">
            <div class="publication-header">
                <h5 class="card-title">${escapeHtml(data.title || '')}</h5>
                ${data.type ? `<span class="publication-type">${escapeHtml(data.type)}</span>` : ''}
            </div>
            <p class="card-text">
                <strong><i class="bi bi-pencil-fill me-1"></i> Authors:</strong> ${escapeHtml(data.authors || '')}<br>
                ${details.length ? details.join('<br>') + '<br>' : ''}
                ${data.doi ? `<strong>DOI:</strong> <a href="https://doi.org/${escapeHtml(data.doi)}" target="_blank" class="doi-link">${escapeHtml(data.doi)}</a>` : ''}
            </p>
        </div>
    </div>`;
        }

        // ===== Updated HTML generators with data attributes =====
        function generateAwardHTML(data, id) {
            return `<div class="name-card" id="award-${id}" data-year="${escapeHtml(data.year || '')}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('awards', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('awards', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <h4>${escapeHtml(data.title || '')}</h4><p>${escapeHtml(data.organization || '')}, ${data.year || ''}</p>
    </div>`;
        }

        function generateAppointmentHTML(data, id) {
            const endYear = data.is_current ? 'Present' : (data.end_year || '');
            return `<div class="name-card" id="appointment-${id}" data-start-year="${escapeHtml(data.start_year || '')}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('appointments', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('appointments', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <h4>${escapeHtml(data.position || '')}</h4><p>${escapeHtml(data.organization || '')}</p><p>${data.start_year || ''} - ${endYear}</p>
    </div>`;
        }

        function generateTalkHTML(data, id) {
            return `<div class="name-card" id="talk-${id}" data-year="${escapeHtml(data.year || '')}">
        ${isLoggedIn ? `<div class="edit-controls"><button class="control-btn edit" onclick="editContent('invited_talks', ${id})"><i class="bi bi-pencil"></i></button><button class="control-btn delete" onclick="deleteContent('invited_talks', ${id})"><i class="bi bi-trash"></i></button></div>` : ''}
        <h4>${escapeHtml(data.talk_title || '')}</h4><p><strong>Event:</strong> ${escapeHtml(data.event_name || '')}</p><p><strong>Venue:</strong> ${escapeHtml(data.venue || '')}, ${data.year || ''}</p>
    </div>`;
        }

        // ===== Filtering functions =====
        function filterAwardsByYear(year) {
            const cards = document.querySelectorAll('#awards-container .name-card');
            let visibleCount = 0;
            cards.forEach(card => {
                const cardYear = card.dataset.year || '';
                const show = year === 'all' || cardYear == year;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            const container = document.getElementById('awards-container');
            let emptyMsg = container.querySelector('.filter-empty-message');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('p');
                    emptyMsg.className = 'text-muted text-center py-3 filter-empty-message';
                    emptyMsg.textContent = 'No awards found for this year.';
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function filterAppointmentsByStartYear(year) {
            const cards = document.querySelectorAll('#appointments-container .name-card');
            let visibleCount = 0;
            cards.forEach(card => {
                const startYear = card.dataset.startYear || '';
                const show = year === 'all' || startYear == year;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            const container = document.getElementById('appointments-container');
            let emptyMsg = container.querySelector('.filter-empty-message');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('p');
                    emptyMsg.className = 'text-muted text-center py-3 filter-empty-message';
                    emptyMsg.textContent = 'No appointments with this start year.';
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function filterTalksByYear(year) {
            const cards = document.querySelectorAll('#invited_talks-container .name-card');
            let visibleCount = 0;
            cards.forEach(card => {
                const cardYear = card.dataset.year || '';
                const show = year === 'all' || cardYear == year;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            const container = document.getElementById('invited_talks-container');
            let emptyMsg = container.querySelector('.filter-empty-message');
            if (visibleCount === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('p');
                    emptyMsg.className = 'text-muted text-center py-3 filter-empty-message';
                    emptyMsg.textContent = 'No talks found for this year.';
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function rebuildAwardsFilter(data) {
            const ul = document.getElementById('awards-year-options');
            if (!ul) return;
            const years = [...new Set(data.map(item => item.year).filter(Boolean))].sort();
            ul.innerHTML = '<li><a class="dropdown-item active" href="#" data-filter="year" data-value="all">All Years</a></li>';
            years.forEach(y => {
                ul.insertAdjacentHTML('beforeend', `<li><a class="dropdown-item" href="#" data-filter="year" data-value="${escapeHtml(y)}">${escapeHtml(y)}</a></li>`);
            });
            filterAwardsByYear('all');
        }

        function rebuildAppointmentsFilter(data) {
            const ul = document.getElementById('appointments-year-options');
            if (!ul) return;
            const startYears = [...new Set(data.map(item => item.start_year).filter(Boolean))].sort();
            ul.innerHTML = '<li><a class="dropdown-item active" href="#" data-filter="start_year" data-value="all">All Years</a></li>';
            startYears.forEach(y => {
                ul.insertAdjacentHTML('beforeend', `<li><a class="dropdown-item" href="#" data-filter="start_year" data-value="${escapeHtml(y)}">${escapeHtml(y)}</a></li>`);
            });
            filterAppointmentsByStartYear('all');
        }

        function rebuildTalksFilter(data) {
            const ul = document.getElementById('talks-year-options');
            if (!ul) return;
            const years = [...new Set(data.map(item => item.year).filter(Boolean))].sort();
            ul.innerHTML = '<li><a class="dropdown-item active" href="#" data-filter="year" data-value="all">All Years</a></li>';
            years.forEach(y => {
                ul.insertAdjacentHTML('beforeend', `<li><a class="dropdown-item" href="#" data-filter="year" data-value="${escapeHtml(y)}">${escapeHtml(y)}</a></li>`);
            });
            filterTalksByYear('all');
        }

        // ===== Attach filter change listeners =====
        document.addEventListener('DOMContentLoaded', function () {
            const awardsFilter = document.getElementById('awards-year-filter');
            if (awardsFilter) {
                awardsFilter.addEventListener('change', function () {
                    filterAwardsByYear(this.value);
                });
            }

            const appointmentsFilter = document.getElementById('appointments-year-filter');
            if (appointmentsFilter) {
                appointmentsFilter.addEventListener('change', function () {
                    filterAppointmentsByStartYear(this.value);
                });
            }

            const talksFilter = document.getElementById('talks-year-filter');
            if (talksFilter) {
                talksFilter.addEventListener('change', function () {
                    filterTalksByYear(this.value);
                });
            }
        });

        const originalUpdateTableUI = updateTableUI;
        updateTableUI = function (table, data) {
            originalUpdateTableUI(table, data);
            if (table === 'awards') {
                rebuildAwardsFilter(data);
            } else if (table === 'appointments') {
                rebuildAppointmentsFilter(data);
            } else if (table === 'invited_talks') {
                rebuildTalksFilter(data);
            } else if (table === 'research_projects') {
                updateResearchFiltersDropdown(data);   // ← ADD THIS
            } else if (table === 'publications') {
                updatePublicationFiltersDropdown(data);
            } else if (table === 'teaching') {
                updateTeachingFiltersDropdown(data);
            }
        };



        function formatTableName(table) {
            const names = { supervision: 'Supervision', teaching: 'Teaching', research_projects: 'Research Project', publications: 'Publications', awards: 'Awards', appointments: 'Appointments', invited_talks: 'Invited Talks' };
            return names[table] || table;
        }

        function escapeHtml(unsafe) {
            if (unsafe == null) return '';
            return String(unsafe).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function showLoading(m = 'Loading...') {
            const ov = document.createElement('div'); ov.className = 'loading-overlay'; ov.id = 'loading-overlay';
            ov.innerHTML = `<div class="loading-spinner"></div><p>${m}</p>`; document.body.appendChild(ov);
        }

        function hideLoading() { document.getElementById('loading-overlay')?.remove(); }

        function showNotification(msg, type = 'info') {
            const n = document.createElement('div');
            n.className = `notification ${type}`;
            n.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle-fill' :
                type === 'error' ? 'exclamation-circle-fill' :
                    'info-circle-fill'
                }"></i><span>${msg}</span>`;
            document.body.appendChild(n);

            // Automatic dismissal with smooth exit
            setTimeout(() => {
                n.classList.add('hide');
                setTimeout(() => n.remove(), 300);
            }, 3000);
        }

        function editProfile() { window.location.href = 'edit_profile.php'; }

        function logout() { window.location.href = 'logout.php'; }

        // 让 Supervision 容器支持鼠标滚轮横向滚动
        function enableHorizontalScrollWithWheel(containerSelector) {
            const container = document.querySelector(containerSelector);
            if (!container) return;
            container.addEventListener('wheel', (e) => {
                if (container.scrollWidth <= container.clientWidth) return;
                e.preventDefault();
                container.scrollLeft += e.deltaY || e.deltaX || 0;
            }, { passive: false });
        }

        document.addEventListener('DOMContentLoaded', function () {
            enableHorizontalScrollWithWheel('.supervision-native-scroll');
        });

        // 按钮涟漪效果
        document.addEventListener('DOMContentLoaded', function () {
            const buttons = document.querySelectorAll('.add-btn, .control-btn, .inline-edit-btn, .login-btn-modern');
            buttons.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    const ripple = document.createElement('span');
                    ripple.style.cssText = 'position:absolute;border-radius:50%;background:rgba(255,255,255,0.5);width:20px;height:20px;left:' + (e.offsetX - 10) + 'px;top:' + (e.offsetY - 10) + 'px;transform:scale(0);transition:transform 0.4s,opacity 0.4s;opacity:1;pointer-events:none';
                    this.style.position = 'relative'; this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    requestAnimationFrame(() => { ripple.style.transform = 'scale(20)'; ripple.style.opacity = '0'; });
                    setTimeout(() => ripple.remove(), 400);
                });
            });
        });

        // 滚动时元素淡入
        document.addEventListener('DOMContentLoaded', function () {
            const fadeElements = document.querySelectorAll('.card, .supervision-card, .others-card, .name-card');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => { if (entry.isIntersecting) { entry.target.style.opacity = '1'; entry.target.style.transform = 'translateY(0)'; } });
            }, { threshold: 0.1 });
            fadeElements.forEach(el => { el.style.opacity = '0'; el.style.transform = 'translateY(20px)'; el.style.transition = 'opacity 0.5s, transform 0.5s'; observer.observe(el); });
        });

        // 教学筛选
        function filterTeachingBySemester(semester) {
            const cards = document.querySelectorAll('#teaching-container .card');
            let visibleCount = 0;
            cards.forEach(card => {
                const cardText = card.querySelector('.card-text')?.innerText || '';
                const match = cardText.match(/Semester:\s*([A-Za-z0-9\s]+?)(?:\s+\d{4}|$)/);
                let cardSemester = match ? match[1].trim() : '';
                cardSemester = cardSemester.replace(/\s*\d{4}$/, '').trim();
                card.style.display = (semester === 'all' || cardSemester === semester) ? '' : 'none';
                if (semester === 'all' || cardSemester === semester) visibleCount++;
            });
            const container = document.getElementById('teaching-container');
            const existing = container.querySelector('.filter-empty-message');
            if (visibleCount === 0) {
                if (!existing) {
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state-card filter-empty-message';
                    emptyMsg.innerHTML = `<i class="bi bi-funnel"></i><p>No courses found for this semester.</p>`;
                    container.appendChild(emptyMsg);
                }
            } else {
                if (existing) existing.remove();
            }
            document.querySelectorAll('.semester-dropdown .dropdown-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.semester === semester) item.classList.add('active');
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.semester-dropdown .dropdown-item').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    filterTeachingBySemester(this.dataset.semester);
                });
            });
        });

        function updateSemesterDropdown(teachingData) {
            const menu = document.querySelector('.semester-dropdown .dropdown-menu');
            if (!menu) return;
            const semesters = [...new Set(teachingData.map(item => item.semester).filter(Boolean))].sort();
            let html = '<li><a class="dropdown-item active" href="#" data-semester="all">All Semesters</a></li>';
            semesters.forEach(sem => html += `<li><a class="dropdown-item" href="#" data-semester="${escapeHtml(sem)}">${escapeHtml(sem)}</a></li>`);
            menu.innerHTML = html;
            menu.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    filterTeachingBySemester(this.dataset.semester);
                    menu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        }

        // ===== Supervision 多条件筛选（合并下拉菜单版） =====
        let supervisionFilters = { degree: 'all', status: 'all', program: 'all' };

        // 应用筛选
        function applySupervisionFilters() {
            const cards = document.querySelectorAll('#supervision-cards .supervision-card');
            let visibleCount = 0;
            cards.forEach(card => {
                const matchDegree = supervisionFilters.degree === 'all' || card.dataset.degree === supervisionFilters.degree;
                const matchStatus = supervisionFilters.status === 'all' || card.dataset.status === supervisionFilters.status;
                const matchProgram = supervisionFilters.program === 'all' || card.dataset.program === supervisionFilters.program;
                card.style.display = (matchDegree && matchStatus && matchProgram) ? '' : 'none';
                if (matchDegree && matchStatus && matchProgram) visibleCount++;
            });

            const container = document.getElementById('supervision-cards');
            const existing = container.querySelector('.filter-empty-message');
            if (visibleCount === 0) {
                container.classList.add('centered-empty');
                if (!existing) {
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state-card filter-empty-message';
                    emptyMsg.innerHTML = `<i class="bi bi-funnel"></i><p>No supervision records match the selected filters.</p>`;
                    container.appendChild(emptyMsg);
                }
            } else {
                container.classList.remove('centered-empty');
                if (existing) existing.remove();
            }
        }



        // 重建合并下拉菜单（用于数据刷新后）
        function rebuildCombinedFilterDropdown(degrees, statuses, programs) {
            const dropdownMenu = document.querySelector('#combinedFilterDropdown .dropdown-menu');
            if (!dropdownMenu) return;

            let html = '';
            if (degrees.length > 0) {
                html += `<li class="filter-group">
            <div class="dropdown-header">Degree</div>
            <ul class="filter-options">
                <li><a class="dropdown-item active" href="#" data-filter="degree" data-value="all">All Degrees</a></li>`;
                degrees.forEach(d => html += `<li><a class="dropdown-item" href="#" data-filter="degree" data-value="${escapeHtml(d)}">${escapeHtml(d)}</a></li>`);
                html += `</ul></li>`;
            }
            if (statuses.length > 0) {
                html += `<li class="filter-group">
            <div class="dropdown-header">Status</div>
            <ul class="filter-options">
                <li><a class="dropdown-item active" href="#" data-filter="status" data-value="all">All Status</a></li>`;
                statuses.forEach(s => html += `<li><a class="dropdown-item" href="#" data-filter="status" data-value="${escapeHtml(s)}">${escapeHtml(s)}</a></li>`);
                html += `</ul></li>`;
            }
            if (programs.length > 0) {
                html += `<li class="filter-group">
            <div class="dropdown-header">Program</div>
            <ul class="filter-options">
                <li><a class="dropdown-item active" href="#" data-filter="program" data-value="all">All Programs</a></li>`;
                programs.forEach(p => html += `<li><a class="dropdown-item" href="#" data-filter="program" data-value="${escapeHtml(p)}">${escapeHtml(p)}</a></li>`);
                html += `</ul></li>`;
            }

            dropdownMenu.innerHTML = html;
        }

        // 更新筛选下拉选项并重置
        function updateSupervisionFiltersDropdown(data) {
            const degrees = [...new Set(data.map(item => item.degree).filter(Boolean))].sort();
            const statuses = [...new Set(data.map(item => item.status).filter(Boolean))].map(s => s.trim()).sort();
            const programs = [...new Set(data.map(item => item.program).filter(Boolean))].sort();

            rebuildCombinedFilterDropdown(degrees, statuses, programs);
            supervisionFilters = { degree: 'all', status: 'all', program: 'all' };
            applySupervisionFilters();

        }

        // 出版物动态标签
        function initPublicationType() {
            const typeSelect = document.querySelector('#contentForm select[name="type"]');
            const journalInput = document.querySelector('#contentForm input[name="journal"]');
            if (!typeSelect || !journalInput) return;
            const updateLabel = () => {
                const label = getJournalLabel(typeSelect.value || 'Journal Article');
                const labelEl = journalInput.closest('.mb-3')?.querySelector('.form-label');
                if (labelEl) labelEl.textContent = label;
            };
            updateLabel();
            typeSelect.addEventListener('change', updateLabel);
        }

        function getJournalLabel(type) {
            const mapping = {
                'Journal Article': 'Journal Name',
                'Conference Paper': 'Conference Name',
                'Book Chapter': 'Book Title',
                'Book': 'Publisher'
            };
            return mapping[type] || 'Journal/Publisher';
        }

        // 全局委托处理合并筛选菜单点击（不会干扰 Bootstrap 关闭）
        document.addEventListener('click', function (e) {
            const item = e.target.closest('#combinedFilterDropdown .filter-options .dropdown-item');
            if (!item) return;
            e.preventDefault(); // 阻止链接跳转，不影响菜单开关
            const filter = item.dataset.filter;
            const value = item.dataset.value;
            if (filter && supervisionFilters.hasOwnProperty(filter)) {
                supervisionFilters[filter] = value;
            }
            // 更新同一筛选组内的 active 样式
            const parentUl = item.closest('.filter-options');
            parentUl.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            applySupervisionFilters();
        });

        // ===== Helpers to get the filter value from active dropdown item =====
        function getActiveFilterValue(listId) {
            const active = document.querySelector(`#${listId} .dropdown-item.active`);
            return active ? active.dataset.value : 'all';
        }

        // ===== Filter functions (using data attributes) =====
        function filterAwardsByYear(year) {
            document.querySelectorAll('#awards-container .name-card').forEach(card => {
                card.style.display = (year === 'all' || card.dataset.year == year) ? '' : 'none';
            });
            handleFilterEmptyState('awards-container', 'No awards found for this year.', year !== 'all');
        }

        function filterAppointmentsByStartYear(year) {
            document.querySelectorAll('#appointments-container .name-card').forEach(card => {
                card.style.display = (year === 'all' || card.dataset.startYear == year) ? '' : 'none';
            });
            handleFilterEmptyState('appointments-container', 'No appointments with this start year.', year !== 'all');
        }

        function filterTalksByYear(year) {
            document.querySelectorAll('#invited_talks-container .name-card').forEach(card => {
                card.style.display = (year === 'all' || card.dataset.year == year) ? '' : 'none';
            });
            handleFilterEmptyState('invited_talks-container', 'No talks found for this year.', year !== 'all');
        }

        // Show/hide empty message (same pattern as Supervision)
        function handleFilterEmptyState(containerId, message, isFiltered) {
            const container = document.getElementById(containerId);
            let emptyMsg = container.querySelector('.filter-empty-message');
            const visible = container.querySelectorAll('.name-card[style*="display: none"]').length < container.querySelectorAll('.name-card').length;

            if (!visible) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('p');
                    emptyMsg.className = 'text-muted text-center py-3 filter-empty-message';
                    emptyMsg.textContent = message;
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        // ===== Global click handler for Others filter dropdowns (similar to supervision) =====
        document.addEventListener('click', function (e) {
            const item = e.target.closest('.filter-options .dropdown-item');
            if (!item) return;
            if (item.closest('#combinedFilterDropdown')) return; // skip supervision (already handled)

            e.preventDefault();
            const filter = item.dataset.filter;
            const value = item.dataset.value;
            const parentUl = item.closest('.filter-options');

            // Update active class
            parentUl.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            // Call the appropriate filter function
            if (filter === 'year') {
                // Determine which section based on the parent dropdown id
                if (item.closest('#awards-year-options')) filterAwardsByYear(value);
                else if (item.closest('#talks-year-options')) filterTalksByYear(value);
            } else if (filter === 'start_year') {
                filterAppointmentsByStartYear(value);
            }
        });

        // ---- Premium tab switching with animated sliding indicator ----
        document.addEventListener('DOMContentLoaded', function () {
            const tabBar = document.querySelector('.tab-bar');
            if (!tabBar) return;

            const tabButtons = tabBar.querySelectorAll('.tab-btn');
            const indicator = tabBar.querySelector('.tab-indicator');

            // Function to update the indicator position / size based on a given button
            function moveIndicator(btn) {
                if (!indicator || !btn) return;
                const barRect = tabBar.getBoundingClientRect();
                const btnRect = btn.getBoundingClientRect();
                const left = btnRect.left - barRect.left;
                const width = btnRect.width;
                indicator.style.left = left + 'px';
                indicator.style.width = width + 'px';
            }

            // Set initial indicator position to the active tab (or first)
            const activeTab = tabBar.querySelector('.tab-btn.active') || tabButtons[0];
            // Delay to allow layout to settle
            setTimeout(() => moveIndicator(activeTab), 50);

            // Recalculate on window resize
            window.addEventListener('resize', () => {
                const currentActive = tabBar.querySelector('.tab-btn.active');
                if (currentActive) moveIndicator(currentActive);
            });

            // Tab click event
            tabButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    // Update active class
                    tabButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    // Animate the indicator
                    moveIndicator(this);

                    // Show corresponding pane (same logic as before)
                    const targetId = 'tab-' + this.dataset.tab;
                    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
                    const targetPane = document.getElementById(targetId);
                    if (targetPane) {
                        targetPane.classList.add('active');
                        // Refresh Bootstrap dropdowns inside the newly shown pane
                        setTimeout(() => {
                            targetPane.querySelectorAll('.dropdown-toggle[aria-expanded="true"]').forEach(btn => {
                                const dropdown = bootstrap.Dropdown.getInstance(btn);
                                if (dropdown) dropdown.update();
                            });
                        }, 10);
                    }
                });

                // Optional: light indicator preview on hover
                btn.addEventListener('mouseenter', function () {
                    if (!this.classList.contains('active')) {
                        indicator.style.opacity = '0.4';
                        moveIndicator(this);
                    }
                });
                btn.addEventListener('mouseleave', function () {
                    indicator.style.opacity = '1';
                    const currentActive = tabBar.querySelector('.tab-btn.active');
                    if (currentActive) moveIndicator(currentActive);
                });
            });
        });

        // ===== Research Projects multi-filter =====
        let researchFilters = { type_of_grant: 'all', status: 'all' };

        function applyResearchFilters() {
            const cards = document.querySelectorAll('#research_projects-container .card');
            let visibleCount = 0;
            cards.forEach(card => {
                // Only filter actual data cards (skip empty-state)
                if (!card.dataset.typeOfGrant && !card.dataset.status) return;
                const matchGrant = researchFilters.type_of_grant === 'all' ||
                    card.dataset.typeOfGrant === researchFilters.type_of_grant;
                const matchStatus = researchFilters.status === 'all' ||
                    card.dataset.status === researchFilters.status;
                const show = matchGrant && matchStatus;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            const container = document.getElementById('research_projects-container');
            let emptyMsg = container.querySelector('.filter-empty-message');
            if (visibleCount === 0 && container.querySelectorAll('.card:not(.empty-state-card)').length > 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state-card filter-empty-message';
                    emptyMsg.innerHTML = `<i class="bi bi-funnel"></i><p>No research projects match the selected filters.</p>`;
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function updateResearchFiltersDropdown(data) {
            const grantTypes = [...new Set(data.map(item => item.type_of_grant).filter(Boolean))].sort();
            const statuses = [...new Set(data.map(item => item.status).filter(Boolean))].sort();

            // Rebuild grant type options
            const grantUl = document.getElementById('research-grant-options');
            if (grantUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="type_of_grant" data-value="all">All Types</a></li>';
                grantTypes.forEach(gt => html += `<li><a class="dropdown-item" href="#" data-filter="type_of_grant" data-value="${escapeHtml(gt)}">${escapeHtml(gt)}</a></li>`);
                grantUl.innerHTML = html;
            }

            // Rebuild status options
            const statusUl = document.getElementById('research-status-options');
            if (statusUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="status" data-value="all">All Statuses</a></li>';
                statuses.forEach(st => html += `<li><a class="dropdown-item" href="#" data-filter="status" data-value="${escapeHtml(st)}">${escapeHtml(st)}</a></li>`);
                statusUl.innerHTML = html;
            }

            // Reset filters
            researchFilters = { type_of_grant: 'all', status: 'all' };
            applyResearchFilters();
        }

        // Attach click handler for research filter dropdown (global delegation)
        document.addEventListener('click', function (e) {
            const item = e.target.closest('#researchFilterDropdown .filter-options .dropdown-item');
            if (!item) return;
            e.preventDefault();
            const filter = item.dataset.filter;
            const value = item.dataset.value;
            if (filter && researchFilters.hasOwnProperty(filter)) {
                researchFilters[filter] = value;
            }
            // Update active class in the same group
            const parentUl = item.closest('.filter-options');
            parentUl.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            applyResearchFilters();
        });

        // ===== Publications multi-filter =====
        let publicationFilters = { type: 'all', year: 'all', author: 'all' };

        function applyPublicationFilters() {
            const cards = document.querySelectorAll('#publications-container .card');
            let visibleCount = 0;
            cards.forEach(card => {
                // Skip empty-state or cards without publication data attributes
                if (!card.dataset.type && !card.dataset.year && !card.dataset.authors) return;

                const matchType = publicationFilters.type === 'all' ||
                    card.dataset.type === publicationFilters.type;
                const matchYear = publicationFilters.year === 'all' ||
                    card.dataset.year == publicationFilters.year;
                // Author filter: check if the selected author appears anywhere in the authors string
                let matchAuthor = true;
                if (publicationFilters.author !== 'all') {
                    const cardAuthors = card.dataset.authors || '';
                    // Split by common separators and trim, then check if selected author is in list
                    const authorsArray = cardAuthors.split(/[,;&]+/).map(a => a.trim());
                    matchAuthor = authorsArray.includes(publicationFilters.author);
                }
                const show = matchType && matchYear && matchAuthor;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            const container = document.getElementById('publications-container');
            let emptyMsg = container?.querySelector('.filter-empty-message');
            if (visibleCount === 0 && container?.querySelectorAll('.card:not(.empty-state-card)').length > 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state-card filter-empty-message';
                    emptyMsg.innerHTML = `<i class="bi bi-funnel"></i><p>No publications match the selected filters.</p>`;
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function updatePublicationFiltersDropdown(data) {
            // Rebuild type options
            const types = [...new Set(data.map(item => item.type).filter(Boolean))].sort();
            const typeUl = document.getElementById('pub-type-options');
            if (typeUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="type" data-value="all">All Types</a></li>';
                types.forEach(t => html += `<li><a class="dropdown-item" href="#" data-filter="type" data-value="${escapeHtml(t)}">${escapeHtml(t)}</a></li>`);
                typeUl.innerHTML = html;
            }

            // Rebuild year options
            const years = [...new Set(data.map(item => item.year).filter(Boolean))].sort((a, b) => b - a); // descending
            const yearUl = document.getElementById('pub-year-options');
            if (yearUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="year" data-value="all">All Years</a></li>';
                years.forEach(y => html += `<li><a class="dropdown-item" href="#" data-filter="year" data-value="${escapeHtml(y)}">${escapeHtml(y)}</a></li>`);
                yearUl.innerHTML = html;
            }

            // Rebuild author options
            const authors = [...new Set(
                data.map(item => item.authors).filter(Boolean)
                    .flatMap(a => a.split(/[,;&]+/).map(s => s.trim()))
                    .filter(Boolean)
            )].sort();
            const authorUl = document.getElementById('pub-author-options');
            if (authorUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="author" data-value="all">All Authors</a></li>';
                authors.forEach(a => html += `<li><a class="dropdown-item" href="#" data-filter="author" data-value="${escapeHtml(a)}">${escapeHtml(a)}</a></li>`);
                authorUl.innerHTML = html;
            }

            // Reset filters
            publicationFilters = { type: 'all', year: 'all', author: 'all' };
            applyPublicationFilters();
        }

        // Click handler for publication filter dropdown
        document.addEventListener('click', function (e) {
            const item = e.target.closest('#publicationFilterDropdown .filter-options .dropdown-item');
            if (!item) return;
            e.preventDefault();
            const filter = item.dataset.filter;
            const value = item.dataset.value;
            if (filter && publicationFilters.hasOwnProperty(filter)) {
                publicationFilters[filter] = value;
            }
            const parentUl = item.closest('.filter-options');
            parentUl.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            applyPublicationFilters();
        });

        // ===== Teaching multi-filter =====
        let teachingFilters = { semester: 'all', institution: 'all' };

        function applyTeachingFilters() {
            const cards = document.querySelectorAll('#teaching-container .card');
            let visibleCount = 0;
            cards.forEach(card => {
                // Skip non-data cards (empty-state, etc.)
                if (!card.dataset.semester && !card.dataset.institution) return;
                const matchSemester = teachingFilters.semester === 'all' ||
                    card.dataset.semester === teachingFilters.semester;
                const matchInstitution = teachingFilters.institution === 'all' ||
                    card.dataset.institution === teachingFilters.institution;
                const show = matchSemester && matchInstitution;
                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            const container = document.getElementById('teaching-container');
            let emptyMsg = container.querySelector('.filter-empty-message');
            if (visibleCount === 0 && container.querySelectorAll('.card:not(.empty-state-card)').length > 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state-card filter-empty-message';
                    emptyMsg.innerHTML = `<i class="bi bi-funnel"></i><p>No courses match the selected filters.</p>`;
                    container.appendChild(emptyMsg);
                }
            } else {
                if (emptyMsg) emptyMsg.remove();
            }
        }

        function updateTeachingFiltersDropdown(data) {
            // Rebuild semester options
            const semesters = [...new Set(data.map(item => item.semester).filter(Boolean))].sort();
            const semUl = document.getElementById('teaching-semester-options');
            if (semUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="semester" data-value="all">All Semesters</a></li>';
                semesters.forEach(s => html += `<li><a class="dropdown-item" href="#" data-filter="semester" data-value="${escapeHtml(s)}">${escapeHtml(s)}</a></li>`);
                semUl.innerHTML = html;
            }

            // Rebuild institution options
            const institutions = [...new Set(data.map(item => item.institution).filter(Boolean))].sort();
            const instUl = document.getElementById('teaching-institution-options');
            if (instUl) {
                let html = '<li><a class="dropdown-item active" href="#" data-filter="institution" data-value="all">All Institutions</a></li>';
                institutions.forEach(i => html += `<li><a class="dropdown-item" href="#" data-filter="institution" data-value="${escapeHtml(i)}">${escapeHtml(i)}</a></li>`);
                instUl.innerHTML = html;
            }

            // Reset filters
            teachingFilters = { semester: 'all', institution: 'all' };
            applyTeachingFilters();
        }

        // Click handler for teaching filter dropdown
        document.addEventListener('click', function (e) {
            const item = e.target.closest('#teachingFilterDropdown .filter-options .dropdown-item');
            if (!item) return;
            e.preventDefault();
            const filter = item.dataset.filter;
            const value = item.dataset.value;
            if (filter && teachingFilters.hasOwnProperty(filter)) {
                teachingFilters[filter] = value;
            }
            const parentUl = item.closest('.filter-options');
            parentUl.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            applyTeachingFilters();
        });

        function refreshResearchAreas() {
            fetch('get_all_data.php?table=research_areas')
                .then(res => res.json())
                .then(result => {
                    if (!result.success) return;
                    renderResearchAreas(result.data);
                });
        }

        function renderResearchAreas(areas) {
            const container = document.getElementById('research-areas-tags');
            if (!container) return;
            container.innerHTML = '';
            if (!areas || areas.length === 0) {
                container.innerHTML = '<span class="no-tags-message">No research areas yet.</span>';
                return;
            }
            areas.forEach(area => {
                const name = area.area_name || '';
                const desc = area.description || '';
                const hash = Math.abs(escapeHtml(name).split('').reduce((acc, c) => acc + c.charCodeAt(0), 0));
                const hue = hash % 360;
                const color = `hsl(${hue}, 65%, 65%)`;
                const wrapper = document.createElement('div');
                wrapper.className = 'tag-wrapper';
                wrapper.setAttribute('data-id', area.id);

                wrapper.setAttribute('data-name', name);

                const tag = document.createElement('span');
                tag.className = 'research-tag';
                tag.style.backgroundColor = color;
                tag.textContent = name;


                wrapper.appendChild(tag);

                if (isLoggedIn) {
                    const controls = document.createElement('div');
                    controls.className = 'tag-edit-controls';
                    controls.innerHTML = `
                <button class="control-btn edit" onclick="editContent('research_areas', ${area.id})"><i class="bi bi-pencil"></i></button>
                <button class="control-btn delete" onclick="deleteContent('research_areas', ${area.id})"><i class="bi bi-trash"></i></button>
            `;
                    wrapper.appendChild(controls);
                }

                container.appendChild(wrapper);
            });
        }

        function updateSupervisionStats() {
            const cards = document.querySelectorAll('#supervision-cards .supervision-card');
            let total = 0, completed = 0, notCompleted = 0, other = 0;
            const degreeMap = {};

            cards.forEach(card => {
                total++;
                const status = (card.dataset.status || '').toLowerCase().trim();
                if (status === 'completed') completed++;
                else if (status === 'not completed' || status === 'not_completed') notCompleted++;
                else other++;

                const degree = (card.dataset.degree || 'N/A').trim();
                degreeMap[degree] = (degreeMap[degree] || 0) + 1;
            });

            // Update overview numbers
            const overview = document.getElementById('supervision-overview');
            const statTotal = document.getElementById('stat-total');
            const statCompleted = document.getElementById('stat-completed');
            const statNotCompleted = document.getElementById('stat-not-completed');
            const statOtherWrapper = document.getElementById('stat-other-wrapper');
            const statOther = document.getElementById('stat-other');

            if (total === 0) {
                if (overview) overview.style.display = 'none';
            } else {
                if (overview) overview.style.display = '';
                if (statTotal) statTotal.textContent = total;
                if (statCompleted) statCompleted.textContent = completed;
                if (statNotCompleted) statNotCompleted.textContent = notCompleted;
                if (statOtherWrapper && statOther) {
                    if (other === 0) {
                        statOtherWrapper.style.display = 'none';
                    } else {
                        statOtherWrapper.style.display = '';
                        statOther.textContent = other;
                    }
                }
            }

            // Update degree breakdown
            const breakdown = document.getElementById('degree-breakdown');
            if (breakdown) {
                if (total === 0) {
                    breakdown.style.display = 'none';
                } else {
                    breakdown.style.display = '';
                    let html = '';
                    const sortedDegrees = Object.keys(degreeMap).sort();
                    sortedDegrees.forEach(deg => {
                        const count = degreeMap[deg];
                        html += `<span class="degree-badge" data-degree="${escapeHtml(deg)}"><strong>${count}</strong> ${escapeHtml(deg)}</span>`;
                    });
                    breakdown.innerHTML = html;
                }
            }
        }

        // 存储键名前缀
        const STORAGE_PREFIX = 'social_link_';

        // 初始化：从 localStorage 加载链接并更新页面
        function loadSocialLinks() {
            document.querySelectorAll('.social-link-wrapper').forEach(wrapper => {
                const platform = wrapper.dataset.platform;
                const storedUrl = localStorage.getItem(STORAGE_PREFIX + platform);
                const linkEl = wrapper.querySelector('.social-link');
                if (storedUrl && storedUrl.trim() !== '') {
                    linkEl.href = storedUrl;
                    linkEl.setAttribute('data-custom-url', storedUrl);
                } else {
                    // 使用默认空链接
                    linkEl.href = 'javascript:void(0)';
                    linkEl.removeAttribute('data-custom-url');
                }
            });
        }

        // 保存链接到 localStorage
        function saveSocialLink(platform, url) {
            if (url && url.trim() !== '') {
                // 简单验证 URL 格式
                let finalUrl = url.trim();
                if (!finalUrl.startsWith('http://') && !finalUrl.startsWith('https://')) {
                    finalUrl = 'https://' + finalUrl;
                }
                localStorage.setItem(STORAGE_PREFIX + platform, finalUrl);
            } else {
                localStorage.removeItem(STORAGE_PREFIX + platform);
            }
            loadSocialLinks(); // 刷新显示
        }

        // 编辑链接弹窗
        function editSocialLink(platform) {
            const currentUrl = localStorage.getItem(STORAGE_PREFIX + platform) || '';
            const newUrl = prompt('Enter your ' + platform + ' profile URL (full link including https://):', currentUrl);
            if (newUrl !== null) {
                saveSocialLink(platform, newUrl);
                showNotification(platform + ' link updated!', 'success');
            }
        }

        // 处理点击事件（访客点击无链接时提示）
        function handleSocialClick(e) {
            const link = e.currentTarget;
            const href = link.getAttribute('href');
            if (!href || href === 'javascript:void(0)' || href === '#') {
                e.preventDefault();
                const platform = link.closest('.social-link-wrapper').dataset.platform;
                const platformNames = {
                    twitter: 'Twitter',
                    linkedin: 'LinkedIn',
                    github: 'GitHub',
                    google_scholar: 'Google Scholar'
                };
                showNotification(platformNames[platform] + ' link not set yet.', 'info');
                return false;
            }
            // 如果链接有效，正常跳转
            return true;
        }

        // 页面加载完成后初始化
        document.addEventListener('DOMContentLoaded', function () {
            loadSocialLinks();

            // 绑定点击事件
            document.querySelectorAll('.social-link').forEach(link => {
                link.addEventListener('click', handleSocialClick);
            });

            // 绑定编辑按钮事件（仅登录用户可见）
            document.querySelectorAll('.edit-social-link').forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const platform = this.dataset.platform;
                    editSocialLink(platform);
                });
            });
        });

        // 确保 showNotification 函数存在（如果页面全局没有，定义备用）
        if (typeof showNotification !== 'function') {
            window.showNotification = function (msg, type) {
                alert(msg);
            };
        }



    </script>
</body>

</html>
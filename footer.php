<?php
// footer.php - Navigation bar component (Updated Color Palette)
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>

<div class="floating-nav-wrapper">
    <nav class="nav-dock">
        <a class="nav-link" href="#home" onclick="scrollToSection('home')">
            <i class="bi bi-house-door-fill"></i>
            <span>Home</span>
        </a>
        <a class="nav-link" href="#teaching" onclick="scrollToSection('teaching')">
            <i class="bi bi-book-fill"></i>
            <span>Teaching</span>
        </a>        
        <a class="nav-link" href="#research" onclick="scrollToSection('research')">
            <i class="bi bi-search-heart-fill"></i>
            <span>Research</span>
        </a>        

        <?php if (!$isLoggedIn): ?>
        <a class="nav-link nav-login-btn" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Login</span>
        </a>
        <?php else: ?>
        <a class="nav-link nav-logout-btn" href="#" onclick="showLogoutConfirm()">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
        <?php endif; ?>              
        <a class="nav-link" href="#supervision" onclick="scrollToSection('supervision')">
            <i class="bi bi-people-fill"></i>
            <span>Supervise</span>
        </a>        
        <a class="nav-link" href="#publications" onclick="scrollToSection('publications')">
            <i class="bi bi-journal-text"></i>
            <span>Publish</span>
        </a>
        <a class="nav-link" href="#others" onclick="scrollToSection('others')">
            <i class="bi bi-stars"></i>
            <span>Others</span>
        </a>
    </nav>
</div>

<div class="modal fade" id="logoutConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content logout-modal-content">
            <div class="modal-header logout-modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-shaded me-2"></i>
                    Confirm Logout
                </h5>
                <button type="button" class="logout-close-btn" data-bs-dismiss="modal" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="modal-body logout-modal-body">
                <div class="logout-icon-wrapper">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <h4 class="logout-title">Ready to leave?</h4>
                <p class="logout-message">You'll need to login again to access admin features</p>
            </div>
            <div class="modal-footer logout-modal-footer">
                <button type="button" class="logout-btn logout-btn-cancel" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>
                    Cancel
                </button>
                <button type="button" class="logout-btn logout-btn-confirm" onclick="logout()">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Logout
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== Color Variables (Updated Palette: #B5E18B, #F0FFC2, #EAE6BC, #28396C) ===== */
:root {
    --deep-blue: #28396C;
    --soft-green: #B5E18B;
    --light-mint: #F0FFC2;
    --warm-beige: #EAE6BC;
    --text-dark: #1e2a3a;
    --text-light: #3a4a5a;
    --shadow-sm: 0 2px 8px rgba(40, 57, 108, 0.08);
    --shadow-md: 0 8px 20px rgba(40, 57, 108, 0.12);
    --shadow-lg: 0 16px 32px rgba(40, 57, 108, 0.16);
    --primary-gradient: linear-gradient(135deg, var(--deep-blue) 0%, var(--soft-green) 100%);
    --accent-gradient: linear-gradient(135deg, var(--soft-green) 0%, var(--light-mint) 100%);
}

/* Base Wrapper - Fixed Bottom */
.floating-nav-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    z-index: 1030;
    pointer-events: none;
}

/* The Dock Container */
.nav-dock {
    pointer-events: auto;
    display: flex;
    justify-content: space-between;
    align-items: stretch;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-lg);
    width: 100%;
    padding: 0.3rem 0.2rem;
    padding-bottom: calc(0.3rem + env(safe-area-inset-bottom));
    gap: 2px;
}

/* Navigation Links */
.nav-link {
    flex: 1 1 0px;
    min-width: 0;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.4rem 0;
    color: var(--text-dark) !important;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    text-decoration: none;
}

.nav-link i {
    font-size: 1.1rem;
    margin-bottom: 0.15rem;
    transition: transform 0.3s ease;
}

.nav-link span {
    font-size: 0.55rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: -0.2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: clip;
    max-width: 100%;
}

/* ----- 各 Section 按钮专属颜色（hover + active）----- */
/* Home - 浅绿色 */
.nav-link[href="#home"]:hover,
.nav-link[href="#home"].active {
    background: var(--soft-green) !important;
    color: var(--deep-blue) !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 57, 108, 0.2);
}

/* Teaching - 蓝色 */
.nav-link[href="#teaching"]:hover,
.nav-link[href="#teaching"].active {
    background: #3498db !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

/* Research - 橙色 */
.nav-link[href="#research"]:hover,
.nav-link[href="#research"].active {
    background: #e67e22 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(230, 126, 34, 0.3);
}

/* Supervision - 绿色 */
.nav-link[href="#supervision"]:hover,
.nav-link[href="#supervision"].active {
    background: #27ae60 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
}

/* Publications - 紫色 */
.nav-link[href="#publications"]:hover,
.nav-link[href="#publications"].active {
    background: #9b59b6 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(155, 89, 182, 0.3);
}

/* Others - 红色 */
.nav-link[href="#others"]:hover,
.nav-link[href="#others"].active {
    background: #c0392b !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(192, 57, 43, 0.3);
}

/* 图标悬停放大效果 */
.nav-link:hover i,
.nav-link.active i {
    transform: scale(1.1);
}

/* ----- Login 按钮 - 深色 #1e1e1e，白色文字 ----- */
.nav-link.nav-login-btn {
    background: #1e1e1e !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.nav-link.nav-login-btn:hover,
.nav-link.nav-login-btn.active {
    background: #333333 !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
}

/* ----- Logout 按钮 - 保持红色渐变 ----- */
.nav-link.nav-logout-btn {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white !important;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
}

.nav-link.nav-logout-btn:hover,
.nav-link.nav-logout-btn.active {
    background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(217, 104, 104, 0.5);
}

/* --- PC / Desktop Compatibility --- */
@media (min-width: 768px) {
    .floating-nav-wrapper {
        bottom: 24px;
        display: flex;
        justify-content: center;
    }

    .nav-dock {
        width: auto;
        border-radius: 50px;
        box-shadow: var(--shadow-lg);
        padding: 0.4rem 0.8rem;
        gap: 0.4rem;
    }

    .nav-link {
        flex: 0 0 auto;
        padding: 0.6rem 1rem;
        min-width: 70px;
        max-width: 90px;
        border-radius: 30px;
    }

    .nav-link i { font-size: 1.25rem; }
    .nav-link span { font-size: 0.65rem; letter-spacing: 0.2px; text-overflow: ellipsis; }
}

/* Ultra-small mobile */
@media (max-width: 350px) {
    .nav-link i { font-size: 1rem; }
    .nav-link span { font-size: 0.45rem; }
}

/* ===== LOGOUT MODAL (Updated Palette) ===== */
.logout-modal-content {
    border-radius: 32px !important;
    overflow: hidden !important;
    border: none !important;
    box-shadow: var(--shadow-lg) !important;
}

.logout-modal-header {
    background: #1e1e1e !important;
    padding: 1.5rem 2rem !important;
    border-bottom: none !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

/* 可选：保持关闭按钮背景半透明，但文字白色 */
.logout-close-btn {
    /* 已有样式基本符合，无需修改，因为背景是 rgba(255,255,255,0.2) 在深色上依然可见 */
}

.logout-modal-header .modal-title {
    color: white !important;
    font-size: 1.3rem !important;
    font-weight: 600 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    margin: 0 !important;
}

.logout-modal-header .modal-title i {
    font-size: 1.4rem !important;
}

.logout-close-btn {
    width: 38px !important;
    height: 38px !important;
    border-radius: 50% !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    padding: 0 !important;
    margin: 0 !important;
}

.logout-close-btn i {
    font-size: 1.1rem !important;
}

.logout-close-btn:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    transform: rotate(90deg) scale(1.1) !important;
}

.logout-modal-body {
    padding: 2.5rem 2rem !important;
    background: white !important;
    text-align: center !important;
}

.logout-icon-wrapper {
    width: 90px !important;
    height: 90px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, rgba(40, 57, 108, 0.1), rgba(181, 225, 139, 0.1)) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 auto 1.5rem !important;
    animation: pulseIcon 2s ease-in-out infinite !important;
}

@keyframes pulseIcon {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(40, 57, 108, 0.3); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(40, 57, 108, 0); }
}

.logout-icon-wrapper i {
    font-size: 3.5rem !important;
    color: #c0392b !important;
}

.logout-title {
    color: var(--deep-blue) !important;
    font-size: 1.8rem !important;
    font-weight: 700 !important;
    margin-bottom: 0.75rem !important;
}

.logout-message {
    color: var(--text-light) !important;
    font-size: 1rem !important;
    line-height: 1.6 !important;
    max-width: 280px !important;
    margin: 0 auto !important;
}

.logout-modal-footer {
    padding: 1.5rem 2rem !important;
    border-top: 1px solid rgba(40, 57, 108, 0.1) !important;
    background: white !important;
    display: flex !important;
    gap: 1rem !important;
    justify-content: center !important;
}

.logout-btn {
    padding: 0.9rem 2rem !important;
    border-radius: 50px !important;
    font-weight: 600 !important;
    font-size: 0.95rem !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    min-width: 130px !important;
}

.logout-btn-cancel {
    background: transparent !important;
    color: var(--deep-blue) !important;
    border: 2px solid var(--soft-green) !important;
}

.logout-btn-cancel:hover {
    border-color: var(--deep-blue) !important;
    transform: translateY(-2px) !important;
    box-shadow: var(--shadow-sm) !important;
}

.logout-btn-confirm {
    background: linear-gradient(135deg, #c0392b, #d96868) !important;
    color: white !important;
    box-shadow: 0 8px 20px rgba(192, 57, 43, 0.25) !important;
}

.logout-btn-confirm:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 15px 30px rgba(192, 57, 43, 0.35) !important;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(40, 57, 108, 0.95);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 1055;
    color: white;
    backdrop-filter: blur(5px);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.loading-overlay.show {
    opacity: 1;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top: 4px solid #fff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@media (max-width: 576px) {
    .logout-modal-header { padding: 1.2rem 1.5rem !important; }
    .logout-modal-body { padding: 2rem 1.5rem !important; }
    .logout-modal-footer { padding: 1.2rem 1.5rem !important; flex-direction: column !important; }
    .logout-btn { width: 100% !important; }
}

/* ===== 导航栏激活按钮跟随 Section 主题色 ===== */
/* 默认激活样式（Home 及未匹配项） */
.nav-link.active {
    background: var(--soft-green) !important;
    color: var(--deep-blue) !important;
}

/* 各 Section 专属激活色（优先级更高） */
.nav-link[href="#teaching"].active {
    background: #3498db !important;
    color: white !important;
}
.nav-link[href="#research"].active {
    background: #e67e22 !important;
    color: white !important;
}
.nav-link[href="#supervision"].active {
    background: #27ae60 !important;
    color: white !important;
}
.nav-link[href="#publications"].active {
    background: #9b59b6 !important;
    color: white !important;
}
.nav-link[href="#others"].active {
    background: #c0392b !important;
    color: white !important;
}

/* 登录/登出按钮保持原有渐变，不覆盖（如果有 active 状态） */
.nav-link.nav-login-btn.active,
.nav-link.nav-logout-btn.active {
    background: var(--primary-gradient) !important; /* 登录按钮渐变 */
    color: white !important;
}
.nav-link.nav-logout-btn.active {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
}



</style>

<script>
// Prevent default anchor behavior and scroll smoothly
function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        const y = element.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
}

// Modal handling
function showLogoutConfirm() {
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutConfirmModal'));
    logoutModal.show();
}

function logout() {
    const confirmBtn = document.querySelector('.logout-btn-confirm');
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<i class="bi bi-arrow-repeat me-2"></i> Logging out...`;
    
    const logoutModal = bootstrap.Modal.getInstance(document.getElementById('logoutConfirmModal'));
    if (logoutModal) {
        logoutModal.hide();
    }
    
    showLoading();
    
    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 600);
}

function showLoading() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.id = 'loading-overlay';
    overlay.innerHTML = `
        <div class="loading-spinner"></div>
        <h5 class="text-white fw-bold">Logging out...</h5>
    `;
    document.body.appendChild(overlay);
    
    setTimeout(() => overlay.classList.add('show'), 10);
}

// Intersection Observer for active link highlighting
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-dock .nav-link');

    const observerOptions = {
        root: null,
        rootMargin: '-20% 0px -60% 0px',
        threshold: 0
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                navLinks.forEach(link => link.classList.remove('active'));
                const activeId = entry.target.getAttribute('id');
                const activeLink = document.querySelector(`.nav-dock .nav-link[href="#${activeId}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
        });
    }, observerOptions);

    sections.forEach(sec => observer.observe(sec));
});
</script>
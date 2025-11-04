
const API_BASE = "http://localhost:8000/api/";
const headerPath = '../components/header.html';
async function loadHeader() {
    try {
        const currentPath = window.location.pathname;
        const headerPath = currentPath.includes('/page/') ? '../components/header.html' : 'components/header.html';
        
        const response = await fetch(headerPath);
        const headerHTML = await response.text();
        
        const headerContainer = document.getElementById('header-container');
        if (headerContainer) {
            headerContainer.innerHTML = headerHTML;
            
            normalizeHeaderLinks();
            initializeHeader();
        }
    } catch (error) {
        console.error('Error loading header:', error);
    }
}

function initializeHeader() {
    const token = localStorage.getItem('auth_token');
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    
    if (token && userData) {
        // User is logged in
        showUserAccount(userData);
    } else {
        // User is not logged in
        showAuthButtons();
    }
    
    // Setup event listeners
    setupHeaderEventListeners();
}

function normalizeHeaderLinks() {
    const isInPage = window.location.pathname.includes('/page/');
    const select = (sel) => document.querySelector(sel);
    const setHref = (el, href) => { if (el) el.setAttribute('href', href); };

    // desktop
    setHref(select('.logo a'), isInPage ? '../index.html' : 'index.html');
    setHref(select('.nav-item a[href="index.html"]'), isInPage ? '../index.html' : 'index.html');
    setHref(document.getElementById('profile-btn'), isInPage ? 'profile.html' : 'page/profile.html');
    // auth buttons
    setHref(select('.auth-buttons .btn-login'), isInPage ? 'login.html' : 'page/login.html');
    setHref(select('.auth-buttons .btn-register'), isInPage ? 'register.html' : 'page/register.html');

    // mobile
    const mobileHome = Array.from(document.querySelectorAll('.mobile-nav-item a'))
        .find(a => a.getAttribute('href') === 'index.html');
    setHref(mobileHome, isInPage ? '../index.html' : 'index.html');
    const mobileProfile = document.querySelector('.mobile-profile-btn');
    setHref(mobileProfile, isInPage ? 'profile.html' : 'page/profile.html');
    const mobileLogin = document.querySelector('.mobile-auth-buttons .mobile-btn-login');
    const mobileRegister = document.querySelector('.mobile-auth-buttons .mobile-btn-register');
    setHref(mobileLogin, isInPage ? 'login.html' : 'page/login.html');
    setHref(mobileRegister, isInPage ? 'register.html' : 'page/register.html');
}

function showUserAccount(userData) {
    const userAccount = document.getElementById('user-account');
    const authButtons = document.querySelector('.auth-buttons');
    const mobileUserAccount = document.getElementById('mobile-user-account');
    const mobileAuthButtons = document.getElementById('mobile-auth-buttons');
    
    if (userAccount) {
        userAccount.style.display = 'block';
        document.getElementById('user-name').textContent = userData.fullname || userData.email || 'Tài khoản';
        renderAvatar(
            document.getElementById('user-avatar'),
            document.getElementById('user-avatar-initial'),
            userData
        );
    }
    
    if (authButtons) {
        authButtons.style.display = 'none';
    }
    
    if (mobileUserAccount) {
        mobileUserAccount.style.display = 'block';
        document.getElementById('mobile-user-name').textContent = userData.fullname || userData.email || 'Tài khoản';
        renderAvatar(
            document.getElementById('mobile-user-avatar'),
            document.getElementById('mobile-user-avatar-initial'),
            userData,
            true
        );
    }
    
    if (mobileAuthButtons) {
        mobileAuthButtons.style.display = 'none';
    }
}

function fixAvatarUrl(src) {
    if (!src) return '';
    if (src.startsWith('http')) return src;
    // Laravel storage URL format: /storage/avatars/xxx.jpg
    if (src.startsWith('/storage/')) {
        return 'http://localhost:8000' + src;
    } else if (src.startsWith('storage/')) {
        return 'http://localhost:8000/' + src;
    }
    return src;
}

function renderAvatar(imgEl, initialEl, userData, isMobile){
    const avatar = userData && userData.avatar;
    if (avatar) {
        if (imgEl){ imgEl.src = fixAvatarUrl(avatar); imgEl.style.display = 'block'; }
        if (initialEl){ initialEl.style.display = 'none'; }
        return;
    }
    const name = (userData && userData.fullname) || (userData && userData.email) || 'U';
    const firstName = String(name).trim().split(/\s+/).slice(-1)[0];
    const initial = firstName ? firstName.charAt(0).toUpperCase() : 'U';
    const colors = ['#0d47a1','#1565c0','#2e7d32','#ef6c00','#6a1b9a','#00838f','#ad1457'];
    const idx = (initial.charCodeAt(0) + (isMobile?1:0)) % colors.length;
    if (initialEl){
        initialEl.textContent = initial;
        initialEl.style.background = colors[idx];
        initialEl.style.display = 'inline-flex';
    }
    if (imgEl){ imgEl.style.display = 'none'; }
}

function showAuthButtons() {
    const userAccount = document.getElementById('user-account');
    const authButtons = document.querySelector('.auth-buttons');
    const mobileUserAccount = document.getElementById('mobile-user-account');
    const mobileAuthButtons = document.getElementById('mobile-auth-buttons');
    
    if (userAccount) {
        userAccount.style.display = 'none';
    }
    
    if (authButtons) {
        authButtons.style.display = 'flex';
    }
    
    if (mobileUserAccount) {
        mobileUserAccount.style.display = 'none';
    }
    
    if (mobileAuthButtons) {
        mobileAuthButtons.style.display = 'flex';
    }
}

function setupHeaderEventListeners() {
    const userAccount = document.getElementById('user-account');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userAccount && userDropdown) {
        userAccount.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!userAccount.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
    
    const logoutBtn = document.getElementById('logout-btn');
    const mobileLogoutBtn = document.getElementById('mobile-logout-btn');
    
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
    
    if (mobileLogoutBtn) {
        mobileLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    }
    
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenuBtn = document.getElementById('close-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.add('active');
        });
    }
    
    if (closeMenuBtn && mobileMenu) {
        closeMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
        });
    }
    
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === mobileMenu) {
                mobileMenu.classList.remove('active');
            }
        });
    }
}

async function logout() {
    const token = localStorage.getItem('auth_token');
    const isInPage = window.location.pathname.includes('/page/');
    const homeUrl = isInPage ? '../index.html' : 'index.html';
    
    if (token) {
        try {
            const response = await fetch(API_BASE + 'logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('userData');
                
                window.location.href = homeUrl;
            } else {
                throw new Error('Logout failed');
            }
        } catch (error) {
            console.error('Logout error:', error);
            localStorage.removeItem('auth_token');
            localStorage.removeItem('userData');
            window.location.href = homeUrl;
        }
    } else {
        window.location.href = homeUrl;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadHeader();
});


const url = "http://localhost:8000/";
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

function showUserAccount(userData) {
    const userAccount = document.getElementById('user-account');
    const authButtons = document.querySelector('.auth-buttons');
    const mobileUserAccount = document.getElementById('mobile-user-account');
    const mobileAuthButtons = document.getElementById('mobile-auth-buttons');
    
    if (userAccount) {
        userAccount.style.display = 'block';
        document.getElementById('user-name').textContent = userData.fullname || userData.email || 'Tài khoản';
    }
    
    if (authButtons) {
        authButtons.style.display = 'none';
    }
    
    if (mobileUserAccount) {
        mobileUserAccount.style.display = 'block';
        document.getElementById('mobile-user-name').textContent = userData.fullname || userData.email || 'Tài khoản';
    }
    
    if (mobileAuthButtons) {
        mobileAuthButtons.style.display = 'none';
    }
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
    
    if (token) {
        try {
            const response = await fetch(url + 'api/logout', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                localStorage.removeItem('auth_token');
                localStorage.removeItem('userData');
                
                window.location.href = 'login.html';
            } else {
                throw new Error('Logout failed');
            }
        } catch (error) {
            console.error('Logout error:', error);
            localStorage.removeItem('auth_token');
            localStorage.removeItem('userData');
            window.location.href = 'login.html';
        }
    } else {
        window.location.href = 'login.html';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadHeader();
});

const url="http://localhost:8000/";
document.addEventListener('DOMContentLoaded', function() {
    
    const token = localStorage.getItem('auth_token');
    if (!token) {
        alert('Vui lòng đăng nhập để xem thông tin cá nhân');
        window.location.href = 'login.html';
        return;
    }
    
    loadUserProfile();
    
    updateHeaderUserInfo();
});

async function loadUserProfile() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;
    
    try {
        const response = await fetch(url+'api/profile', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const result = await response.json();
            const userData = result.data;
            populateProfileInfo(userData);
        } else if (response.status === 401) {
            localStorage.removeItem('authToken');
            localStorage.removeItem('userData');
            alert('Phiên đăng nhập đã hết hạn');
            window.location.href = 'login.html';
        } else {
            throw new Error('Không thể tải thông tin cá nhân');
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        alert('Lỗi khi tải thông tin cá nhân: ' + error.message);
    }
}

function populateProfileInfo(userData) {
    document.getElementById('fullname').textContent = userData.fullname || 'Chưa cập nhật';
    document.getElementById('email').textContent = userData.email || 'Chưa cập nhật';
    document.getElementById('phone').textContent = userData.phone || 'Chưa cập nhật';
    
    let genderText = 'Chưa cập nhật';
    if (userData.gender === 'male') genderText = 'Nam';
    else if (userData.gender === 'female') genderText = 'Nữ';
    else if (userData.gender === 'other') genderText = 'Khác';
    document.getElementById('gender').textContent = genderText;
    
    document.getElementById('address').textContent = userData.address || 'Chưa cập nhật';
}

function updateHeaderUserInfo() {
    const userData = JSON.parse(localStorage.getItem('userData') || '{}');
    const userName = userData.fullname || userData.email || 'Tài khoản';
    
    const userAccount = document.getElementById('user-account');
    if (userAccount) {
        userAccount.style.display = 'block';
        document.getElementById('user-name').textContent = userName;
    }
    
    const mobileUserAccount = document.getElementById('mobile-user-account');
    if (mobileUserAccount) {
        mobileUserAccount.style.display = 'block';
        document.getElementById('mobile-user-name').textContent = userName;
    }
    
    const authButtons = document.querySelector('.auth-buttons');
    if (authButtons) {
        authButtons.style.display = 'none';
    }
    
    const mobileAuthButtons = document.getElementById('mobile-auth-buttons');
    if (mobileAuthButtons) {
        mobileAuthButtons.style.display = 'none';
    }
}

if (typeof API_BASE === 'undefined') {
    window.API_BASE = 'http://localhost:8000/api/';
}

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
        const response = await fetch(API_BASE+'profile', {
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
            renderProfileAvatar(userData);
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
    if (userData.gender === 0 || userData.gender === '0') genderText = 'Nam';
    else if (userData.gender === 1 || userData.gender === '1') genderText = 'Nữ';
    else genderText = 'Khác';
    document.getElementById('gender').textContent = genderText;
    
    document.getElementById('address').textContent = userData.address || 'Chưa cập nhật';
}

function renderProfileAvatar(userData) {
    const avatarImg = document.getElementById('profile-avatar');
    const avatarInitial = document.getElementById('profile-avatar-initial');
    
    if (userData && userData.avatar) {
        let avatarUrl = userData.avatar;
        if (!avatarUrl.startsWith('http')) {
            // Laravel storage URL format: /storage/avatars/xxx.jpg
            if (avatarUrl.startsWith('/storage/')) {
                avatarUrl = 'http://localhost:8000' + avatarUrl;
            } else if (avatarUrl.startsWith('storage/')) {
                avatarUrl = 'http://localhost:8000/' + avatarUrl;
            }
        }
        if (avatarImg) {
            avatarImg.src = avatarUrl;
            avatarImg.style.display = 'block';
        }
        if (avatarInitial) {
            avatarInitial.style.display = 'none';
        }
    } else {
        const name = (userData && userData.fullname) || (userData && userData.email) || 'U';
        const firstName = String(name).trim().split(/\s+/).slice(-1)[0];
        const initial = firstName ? firstName.charAt(0).toUpperCase() : 'U';
        const colors = ['#0d47a1', '#1565c0', '#2e7d32', '#ef6c00', '#6a1b9a', '#00838f', '#ad1457'];
        const idx = initial.charCodeAt(0) % colors.length;
        
        if (avatarInitial) {
            avatarInitial.textContent = initial;
            avatarInitial.style.background = colors[idx];
            avatarInitial.style.display = 'inline-flex';
        }
        if (avatarImg) {
            avatarImg.style.display = 'none';
        }
    }
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

document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('avatar-file');
    const labelFile = document.getElementById('label-file');
    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');
    const btnRemove = document.getElementById('btn-remove-preview');
    const uploadBtn = document.getElementById('btn-upload-avatar');
    
    // Khi chọn file
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.style.display = 'block';
                    labelFile.style.display = 'none';
                    uploadBtn.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Xóa preview
    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            if (fileInput) fileInput.value = '';
            previewContainer.style.display = 'none';
            labelFile.style.display = 'inline-block';
            uploadBtn.style.display = 'none';
        });
    }
    
    // Upload avatar
    if (uploadBtn) {
        uploadBtn.addEventListener('click', async function() {
            const file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) { 
                alert('Vui lòng chọn ảnh.'); 
                return; 
            }
            const token = localStorage.getItem('auth_token');
            if (!token) return;
            
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Đang tải...';
            
            try {
                const form = new FormData();
                form.append('avatar', file);
                
                const res = await fetch(API_BASE+'profile/avatar', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`
                    },
                    body: form
                });
                
                const data = await res.json();
                if (res.ok) {
                    localStorage.setItem('userData', JSON.stringify(data.data));
                    renderProfileAvatar(data.data);
                    // Reset preview
                    if (fileInput) fileInput.value = '';
                    previewContainer.style.display = 'none';
                    labelFile.style.display = 'inline-block';
                    uploadBtn.style.display = 'none';
                    alert('Cập nhật ảnh thành công');
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Cập nhật thất bại');
                    alert(errorMsg);
                }
            } catch (e) {
                console.error('Upload error:', e);
                alert('Cập nhật ảnh thất bại: ' + e.message);
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Cập nhật ảnh';
            }
        });
    }
});

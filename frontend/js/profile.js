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

let currentUserData = null;

function populateProfileInfo(userData) {
    currentUserData = userData;
    

    document.getElementById('fullname').textContent = userData.fullname || 'Chưa cập nhật';
    document.getElementById('email').textContent = userData.email || 'Chưa cập nhật';
    document.getElementById('phone').textContent = userData.phone || 'Chưa cập nhật';
    
    let genderText = 'Chưa cập nhật';
    if (userData.gender === 0 || userData.gender === '0') genderText = 'Nam';
    else if (userData.gender === 1 || userData.gender === '1') genderText = 'Nữ';
    else genderText = 'Khác';
    document.getElementById('gender').textContent = genderText;
    
    document.getElementById('address').textContent = userData.address || 'Chưa cập nhật';

    document.getElementById('fullname-input').value = userData.fullname || '';
    document.getElementById('phone-input').value = userData.phone || '';
    document.getElementById('gender-input').value = userData.gender !== null && userData.gender !== undefined ? String(userData.gender) : '';
    document.getElementById('address-input').value = userData.address || '';}

function renderProfileAvatar(userData) {
    const avatarImg = document.getElementById('profile-avatar');
    const avatarInitial = document.getElementById('profile-avatar-initial');
    
    if (userData && userData.avatar) {
        let avatarUrl = userData.avatar;
        if (!avatarUrl.startsWith('http')) {
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


function toggleEditMode(isEdit) {
    const displayElements = document.querySelectorAll('.info-display');
    const inputElements = document.querySelectorAll('.info-input');
    const editBtn = document.getElementById('btn-edit-profile');
    const actionsDiv = document.getElementById('profile-actions');
    
    if (isEdit) {

        displayElements.forEach(el => {
            if (el.id !== 'email') el.style.display = 'none';
        });
        inputElements.forEach(el => el.style.display = 'block');
        editBtn.style.display = 'none';
        actionsDiv.style.display = 'flex';
    } else {

        displayElements.forEach(el => el.style.display = 'inline');
        inputElements.forEach(el => el.style.display = 'none');
        editBtn.style.display = 'inline-block';
        actionsDiv.style.display = 'none';
    }
}

function cancelEdit() {
  
    if (currentUserData) {
        populateProfileInfo(currentUserData);
    }
    toggleEditMode(false);
}

async function saveProfile() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        alert('Vui lòng đăng nhập lại');
        window.location.href = 'login.html';
        return;
    }
    
    const fullname = document.getElementById('fullname-input').value.trim();
    const phone = document.getElementById('phone-input').value.trim();
    const gender = document.getElementById('gender-input').value;
    const address = document.getElementById('address-input').value.trim();
    

    if (!fullname) {
        alert('Vui lòng nhập họ và tên');
        return;
    }
    
    if (fullname.length > 120) {
        alert('Họ và tên không được vượt quá 120 ký tự');
        return;
    }
    
   
    if (phone) {
        if (phone.length < 10) {
            alert('Số điện thoại phải có ít nhất 10 ký tự');
            return;
        }
        if (phone.length > 14) {
            alert('Số điện thoại không được vượt quá 14 ký tự');
            return;
        }
    }
    
    if (address && address.length > 255) {
        alert('Địa chỉ không được vượt quá 255 ký tự');
        return;
    }
    
    const saveBtn = document.getElementById('btn-save-profile');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    
    try {
        const payload = {
            fullname: fullname,
            phone: phone || null, 
            gender: (gender && gender !== '') ? parseInt(gender) : null,
            address: address || null
        };
        
        const response = await fetch(API_BASE + 'profile', {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        
        let result;
        try {
            result = await response.json();
        } catch (parseError) {
            console.error('Error parsing response:', parseError);
            alert('Lỗi khi xử lý phản hồi từ server');
            return;
        }
        
        if (response.ok) {  
            currentUserData = result.data;
            populateProfileInfo(result.data);
            toggleEditMode(false);
            
            localStorage.setItem('userData', JSON.stringify(result.data));
            
            updateHeaderUserInfo();
            
            alert('Cập nhật thông tin thành công');
        } else {
            let errorMsg = 'Cập nhật thất bại';
            
            console.log('Error response:', result);
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (response.status === 422 && result.errors) {
                const errorMessages = [];
                Object.keys(result.errors).forEach(field => {
                    const fieldErrors = result.errors[field];
                    if (Array.isArray(fieldErrors)) {
                        fieldErrors.forEach(err => {
                            errorMessages.push(err);
                        });
                    } else if (typeof fieldErrors === 'string') {
                        errorMessages.push(fieldErrors);
                    }
                });
                
                if (errorMessages.length > 0) {
                    errorMsg = errorMessages.join('\n');
                } else {
                    errorMsg = result.message || 'Cập nhật thất bại';
                }
            } else if (result.message) {
                // Ưu tiên hiển thị message từ backend (đã được format)
                errorMsg = result.message;
            } else if (result.error) {
                // Nếu có error field, kiểm tra xem có phải lỗi database không
                const errorDetail = result.error;
                if (errorDetail.includes('Data too long for column') && errorDetail.includes('phone')) {
                    errorMsg = 'Số điện thoại quá dài. Vui lòng nhập số điện thoại từ 10 đến 14 ký tự';
                } else if (errorDetail.includes('Data too long for column') && errorDetail.includes('fullname')) {
                    errorMsg = 'Họ và tên quá dài. Vui lòng nhập tên từ 1 đến 120 ký tự';
                } else if (errorDetail.includes('Data too long for column') && errorDetail.includes('address')) {
                    errorMsg = 'Địa chỉ quá dài. Vui lòng nhập địa chỉ tối đa 255 ký tự';
                } else {
                    // Hiển thị message nếu có, không thì hiển thị error
                    errorMsg = result.message || errorDetail;
                }
            } else {    
                errorMsg = `Cập nhật thất bại (Status: ${response.status})`;
            }
            
            alert(errorMsg);
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        alert('Lỗi khi cập nhật thông tin: ' + error.message);
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('btn-edit-profile');
    const saveBtn = document.getElementById('btn-save-profile');
    const cancelBtn = document.getElementById('btn-cancel-edit');
    
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            toggleEditMode(true);
        });
    }
    
    if (saveBtn) {
        saveBtn.addEventListener('click', saveProfile);
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', cancelEdit);
    }
    
    const fileInput = document.getElementById('avatar-file');
    const labelFile = document.getElementById('label-file');
    const previewContainer = document.getElementById('preview-container');
    const previewImg = document.getElementById('preview-img');
    const btnRemove = document.getElementById('btn-remove-preview');
    const uploadBtn = document.getElementById('btn-upload-avatar');
    
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
    
    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            if (fileInput) fileInput.value = '';
            previewContainer.style.display = 'none';
            labelFile.style.display = 'inline-block';
            uploadBtn.style.display = 'none';
        });
    }
    
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
                    currentUserData = data.data;
                    localStorage.setItem('userData', JSON.stringify(data.data));
                    renderProfileAvatar(data.data);
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

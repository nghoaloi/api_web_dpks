const API_BASE = "http://localhost:8000/api/";
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    if (!passwordInput) return;
    
    const toggleBtn = passwordInput.parentNode.querySelector('.toggle-btn i');
    if (!toggleBtn) return;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.classList.remove('fa-eye');
        toggleBtn.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleBtn.classList.remove('fa-eye-slash');
        toggleBtn.classList.add('fa-eye');
    }
}


function showMessage(message, type) {
    const errorDiv = document.getElementById('errorMessage');
    const successDiv = document.getElementById('successMessage');
    
    if (!errorDiv || !successDiv) return;
    
    if (type === 'error') {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        successDiv.style.display = 'none';
    } else {
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        errorDiv.style.display = 'none';
    }
}


function validateForm() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    const phone = document.getElementById('phone');
    const agreeTerms = document.getElementById('agreeTerms');
    
    if (!password || !confirmPassword || !phone || !agreeTerms) return false;

    if (password.value !== confirmPassword.value) {
        showMessage('Mật khẩu không trùng khớp', 'error');
        return false;
    }

    if (password.value.length < 6) {
        showMessage('Mật khẩu phải có ít nhất 6 ký tự', 'error');
        return false;
    }

    if (phone.value.length < 10) {
        showMessage('Số điện thoại phải có ít nhất 10 số', 'error');
        return false;
    }

    if (!agreeTerms.checked) {
        showMessage('Vui lòng đồng ý với điều khoản sử dụng', 'error');
        return false;
    }

    return true;
}


document.addEventListener('DOMContentLoaded', function() {

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const registerBtn = document.getElementById('registerBtn');
            if (!registerBtn) return;
            
            const btnText = registerBtn.querySelector('.btn-text');
            const loading = registerBtn.querySelector('.loading');
            
            if (!btnText || !loading) return;
            
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            registerBtn.disabled = true;
            
            const formData = new FormData(this);
            const data = {
                fullname: formData.get('fullname'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                gender: formData.get('gender'),
                address: formData.get('address'),
                password: formData.get('password'),
                status: 'active'
            };
            
            try {
                const response = await fetch(API_BASE+'auth/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    showMessage('Đăng ký thành công', 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 1000);
                } else {
                    if (result.errors) {
                        const errorMessages = Object.values(result.errors).flat();
                        showMessage(errorMessages.join(', '), 'error');
                    } else {
                        showMessage(result.message || 'Đăng ký thất bại', 'error');
                    }
                }
            } catch (error) {
                showMessage('Lỗi kết nối! Vui lòng thử lại.', 'error');
            } finally {
                btnText.style.display = 'inline';
                loading.style.display = 'none';
                registerBtn.disabled = false;
            }
        });
    }
    
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            if (!loginBtn) return;
            
            const btnText = loginBtn.querySelector('.btn-text');
            const loading = loginBtn.querySelector('.loading');
            
            if (!btnText || !loading) return;
            
            btnText.style.display = 'none';
            loading.style.display = 'inline-block';
            loginBtn.disabled = true;
            
            const formData = new FormData(this);
            const data = {
                email: formData.get('email'),
                password: formData.get('password')
            };
            
            try {   
                const response = await fetch(API_BASE+'auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    localStorage.setItem('auth_token', result.token);

                    try {
                        const profileRes = await fetch(API_BASE+'profile', {
                            method: 'GET',
                            headers: {
                                'Authorization': `Bearer ${result.token}`,
                                'Accept': 'application/json'
                            }
                        });
                        if (profileRes.ok) {
                            const profileJson = await profileRes.json();
                            if (profileJson && profileJson.data) {
                                localStorage.setItem('userData', JSON.stringify(profileJson.data));
                            }
                        }
                    } catch (_) {
                        // ignore profile fetch failure for now 
                    }

                    window.location.href = '../index.html';
                } else {
                    showMessage(result.message || 'Đăng nhập thất bại!', 'error');
                }
            } catch (error) {
                showMessage('Lỗi kết nối! Vui lòng thử lại.', 'error');
            } finally {
                btnText.style.display = 'inline';
                loading.style.display = 'none';
                loginBtn.disabled = false;
            }
        });
    }


    const termsModal = document.getElementById('termsModal');
    if (termsModal) {
        termsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTermsModal();
            }
        });
    }
    

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTermsModal();
        }
    });
    

    if (localStorage.getItem('auth_token')) {
        window.location.href = 'index.html';
    }
});

function showTerms() {
    const modal = document.getElementById('termsModal');
    if (!modal) return;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; 
}

function closeTermsModal() {
    const modal = document.getElementById('termsModal');
    if (!modal) return;
    
    modal.classList.remove('active');
    document.body.style.overflow = 'auto'; 
}

function acceptTerms() {
    const checkbox = document.getElementById('agreeTerms');
    if (!checkbox) return;
    
    checkbox.checked = true;
    closeTermsModal();
}


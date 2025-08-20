document.addEventListener("DOMContentLoaded", function() {
    // Apply darkmode if set in localStorage
    if (localStorage.getItem("darkmode") === "on") {
        document.body.classList.add("darkmode");
    }

    // Add darkmode button event if it exists
    const btn = document.getElementById("darkmodeBtn");
    if (btn) {
        btn.addEventListener("click", function() {
            document.body.classList.toggle("darkmode");
            if (document.body.classList.contains("darkmode")) {
                localStorage.setItem("darkmode", "on");
            } else {
                localStorage.setItem("darkmode", "off");
            }
        });
    }

    // Theme Management
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme from localStorage
        const currentTheme = localStorage.getItem('theme') || 'light';
        document.body.classList.toggle('darkmode', currentTheme === 'dark');
        document.getElementById('themeSelect').value = currentTheme;

        // Theme change handler
        document.getElementById('themeSelect').addEventListener('change', function() {
            const theme = this.value;
            document.body.classList.toggle('darkmode', theme === 'dark');
            localStorage.setItem('theme', theme);
        });

        // Profile Picture Preview
        const profileInput = document.querySelector('input[name="profile_picture"]');
        const profilePreview = document.getElementById('profilePreview');

        if (profileInput && profilePreview) {
            profileInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Password Validation
        const passwordForm = document.querySelector('form[name="change_password"]');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const newPass = this.querySelector('input[name="new_password"]').value;
                const confirmPass = this.querySelector('input[name="confirm_password"]').value;

                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }

        // Form Validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!this.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                this.classList.add('was-validated');
            });
        });

        // Tab Management
        const tabLinks = document.querySelectorAll('.nav-link');
        const currentTab = localStorage.getItem('activeSettingsTab') || '#profile';

        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                localStorage.setItem('activeSettingsTab', this.getAttribute('href'));
            });

            // Set active tab from localStorage
            if (link.getAttribute('href') === currentTab) {
                const tab = new bootstrap.Tab(link);
                tab.show();
            }
        });

        // 2FA Toggle
        const twoFAButton = document.querySelector('.btn-secondary');
        if (twoFAButton) {
            twoFAButton.addEventListener('click', function() {
                const isEnabled = this.textContent === 'Disable 2FA';
                this.textContent = isEnabled ? 'Enable 2FA' : 'Disable 2FA';
                this.classList.toggle('btn-danger', !isEnabled);
                this.classList.toggle('btn-secondary', isEnabled);
            });
        }

        // Session Management
        const logoutAllButton = document.querySelector('.btn-danger');
        if (logoutAllButton) {
            logoutAllButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to log out from all devices?')) {
                    // Add AJAX call to server to invalidate all sessions
                    alert('Logged out from all devices');
                }
            });
        }

        // Settings Save Handler
        const saveSettingsBtn = document.querySelector('#system .btn-primary');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', function() {
                const settings = {
                    theme: document.getElementById('themeSelect').value,
                    refreshInterval: document.querySelector('select:nth-of-type(2)').value,
                    timezone: document.querySelector('select:nth-of-type(3)').value
                };

                // Save to localStorage
                localStorage.setItem('userSettings', JSON.stringify(settings));
                
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success mt-3';
                alert.textContent = 'Settings saved successfully!';
                this.parentNode.appendChild(alert);

                // Remove alert after 3 seconds
                setTimeout(() => alert.remove(), 3000);
            });
        }
    });

    // Unsaved Changes Warning
    window.addEventListener('beforeunload', function(e) {
        const forms = document.querySelectorAll('form');
        let hasChanges = false;

        forms.forEach(form => {
            const elements = form.elements;
            for (let element of elements) {
                if (element.type !== 'submit' && element.value !== element.defaultValue) {
                    hasChanges = true;
                    break;
                }
            }
        });

        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Initialize form handlers
    initializeProfileForm();
    initializePasswordForm();
    initializeSystemSettings();
    initializeTheme();
    initializeClearData();

    // Initialize all toasts
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000
        });
    });
});

function initializeProfileForm() {
    const profileForm = document.querySelector('#profile form');
    if (!profileForm) return;

    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_profile');

        fetch('backend/handle_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Profile updated successfully', 'success');
            } else {
                showAlert(data.message || 'Failed to update profile', 'danger');
            }
        })
        .catch(error => showAlert('An error occurred', 'danger'));
    });
}

function initializePasswordForm() {
    const passwordForm = document.querySelector('#security form');
    if (!passwordForm) return;

    passwordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (!validatePasswords()) return;

        const formData = new FormData(this);
        formData.append('action', 'change_password');

        fetch('backend/handle_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Password changed successfully', 'success');
                this.reset();
            } else {
                showAlert(data.message || 'Failed to change password', 'danger');
            }
        })
        .catch(error => showAlert('An error occurred', 'danger'));
    });
}

function initializeSystemSettings() {
    const systemForm = document.querySelector('#system form');
    if (!systemForm) return;

    systemForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'system_settings');

        fetch('backend/handle_settings.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Settings saved successfully', 'success');
                if (formData.get('theme')) {
                    document.body.classList.toggle('darkmode', 
                        formData.get('theme') === 'dark');
                }
            } else {
                showAlert(data.message || 'Failed to save settings', 'danger');
            }
        })
        .catch(error => showAlert('An error occurred', 'danger'));
    });
}

function initializeTheme() {
    const theme = localStorage.getItem('theme') || 'light';
    document.body.classList.toggle('darkmode', theme === 'dark');
    if (document.getElementById('themeSelect')) {
        document.getElementById('themeSelect').value = theme;
    }
}

function validatePasswords() {
    const newPass = document.querySelector('input[name="new_password"]').value;
    const confirmPass = document.querySelector('input[name="confirm_password"]').value;
    
    if (newPass !== confirmPass) {
        showAlert('Passwords do not match', 'danger');
        return false;
    }
    return true;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.settings-container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => alertDiv.remove(), 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Theme switcher
    const themeSelect = document.getElementById('themeSelect');
    themeSelect.addEventListener('change', function() {
        document.body.classList.toggle('darkmode', this.value === 'dark');
        localStorage.setItem('theme', this.value);
    });

    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    themeSelect.value = savedTheme;
    document.body.classList.toggle('darkmode', savedTheme === 'dark');

    // Profile picture preview
    const profileInput = document.querySelector('input[name="profile_picture"]');
    const profilePreview = document.getElementById('profilePreview');
    
    profileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                profilePreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Password strength meter
    const newPassword = document.querySelector('input[name="new_password"]');
    if (newPassword) {
        newPassword.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });
    }

    // Session timeout warning
    let timeoutWarning = setTimeout(showTimeoutWarning, 25 * 60 * 1000); // 25 minutes
    document.addEventListener('mousemove', function() {
        clearTimeout(timeoutWarning);
        timeoutWarning = setTimeout(showTimeoutWarning, 25 * 60 * 1000);
    });
});

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    const indicator = document.createElement('div');
    indicator.className = 'progress mb-2';
    indicator.innerHTML = `
        <div class="progress-bar ${getStrengthClass(strength)}" 
             role="progressbar" 
             style="width: ${strength * 20}%" 
             aria-valuenow="${strength}" 
             aria-valuemin="0" 
             aria-valuemax="5">
            ${getStrengthText(strength)}
        </div>
    `;
    
    const existingIndicator = document.querySelector('.progress');
    if (existingIndicator) {
        existingIndicator.replaceWith(indicator);
    } else {
        document.querySelector('input[name="new_password"]').after(indicator);
    }
}

function getStrengthClass(strength) {
    if (strength <= 2) return 'bg-danger';
    if (strength <= 3) return 'bg-warning';
    return 'bg-success';
}

function getStrengthText(strength) {
    if (strength <= 2) return 'Weak';
    if (strength <= 3) return 'Medium';
    return 'Strong';
}

function showTimeoutWarning() {
    const toast = document.createElement('div');
    toast.className = 'toast show position-fixed bottom-0 end-0 m-3';
    toast.innerHTML = `
        <div class="toast-header">
            <strong class="me-auto">Session Warning</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Your session will expire soon. Please save your changes.
        </div>
    `;
    document.body.appendChild(toast);
}

/* PHP code removed: session_start(), $_SESSION, and related logic do not belong in a .js file. */
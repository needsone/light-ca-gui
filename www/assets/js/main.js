/**
 * SSL Certificate Manager - Main JavaScript
 */

(function() {
    'use strict';
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });
    
    /**
     * Initialize application
     */
    function initializeApp() {
        initFormValidation();
        initPasswordToggle();
        initConfirmDialogs();
        initTooltips();
        initAutoLogout();
        initCopyButtons();
    }
    
    /**
     * Form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const passwordField = form.querySelector('input[name="password"]');
                const confirmField = form.querySelector('input[name="password_confirm"]');
                
                if (passwordField && confirmField) {
                    if (passwordField.value !== confirmField.value) {
                        e.preventDefault();
                        showAlert('Les mots de passe ne correspondent pas', 'error');
                        confirmField.focus();
                        return false;
                    }
                }
                
                // Show loading state for submit buttons
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('loading')) {
                    submitBtn.classList.add('loading');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
                    submitBtn.disabled = true;
                    
                    // Re-enable after 5 seconds as failsafe
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('loading');
                    }, 5000);
                }
            });
        });
        
        // Real-time password confirmation validation
        const passwordConfirm = document.querySelector('input[name="password_confirm"]');
        if (passwordConfirm) {
            passwordConfirm.addEventListener('input', function() {
                const password = document.querySelector('input[name="password"]');
                if (password && this.value) {
                    if (password.value === this.value) {
                        this.setCustomValidity('');
                        this.style.borderColor = 'var(--success-color)';
                    } else {
                        this.setCustomValidity('Les mots de passe ne correspondent pas');
                        this.style.borderColor = 'var(--error-color)';
                    }
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '';
                }
            });
        }
    }
    
    /**
     * Password visibility toggle
     */
    function initPasswordToggle() {
        const passwordFields = document.querySelectorAll('input[type="password"]');
        
        passwordFields.forEach(field => {
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            
            field.parentNode.insertBefore(wrapper, field);
            wrapper.appendChild(field);
            
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.className = 'password-toggle';
            toggleBtn.style.cssText = `
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                cursor: pointer;
                color: var(--text-light);
                padding: 5px;
            `;
            
            wrapper.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function() {
                if (field.type === 'password') {
                    field.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    field.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        });
    }
    
    /**
     * Confirm dialogs
     */
    function initConfirmDialogs() {
        const deleteButtons = document.querySelectorAll('form[onsubmit*="confirm"]');
        
        deleteButtons.forEach(form => {
            const originalOnSubmit = form.getAttribute('onsubmit');
            form.removeAttribute('onsubmit');
            
            form.addEventListener('submit', function(e) {
                const confirmMsg = originalOnSubmit.match(/confirm\(['"](.+?)['"]\)/);
                if (confirmMsg) {
                    if (!confirm(confirmMsg[1])) {
                        e.preventDefault();
                        return false;
                    }
                }
            });
        });
    }
    
    /**
     * Simple tooltips
     */
    function initTooltips() {
        const elements = document.querySelectorAll('[title]');
        
        elements.forEach(el => {
            const title = el.getAttribute('title');
            if (!title) return;
            
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = title;
                tooltip.style.cssText = `
                    position: absolute;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    z-index: 10000;
                    pointer-events: none;
                `;
                
                document.body.appendChild(tooltip);
                
                const rect = el.getBoundingClientRect();
                tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                
                el.setAttribute('data-tooltip-id', Date.now());
                tooltip.setAttribute('data-tooltip-for', el.getAttribute('data-tooltip-id'));
            });
            
            el.addEventListener('mouseleave', function() {
                const tooltipId = this.getAttribute('data-tooltip-id');
                const tooltip = document.querySelector(`[data-tooltip-for="${tooltipId}"]`);
                if (tooltip) {
                    tooltip.remove();
                }
            });
        });
    }
    
    /**
     * Auto logout on inactivity
     */
    function initAutoLogout() {
        let inactivityTimer;
        const timeout = 30 * 60 * 1000; // 30 minutes
        
        function resetTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                if (confirm('Votre session va expirer pour cause d\'inactivité. Voulez-vous continuer ?')) {
                    resetTimer();
                } else {
                    window.location.href = 'logout.php';
                }
            }, timeout);
        }
        
        // Don't run on login page
        if (!document.body.classList.contains('login-page')) {
            ['mousedown', 'keydown', 'scroll', 'touchstart'].forEach(event => {
                document.addEventListener(event, resetTimer, true);
            });
            
            resetTimer();
        }
    }
    
    /**
     * Copy to clipboard buttons
     */
    function initCopyButtons() {
        // Already handled in ca_download.php inline script
        // This is for additional copy functionality
    }
    
    /**
     * Show alert message
     */
    function showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        `;
        
        const icon = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        }[type] || 'fa-info-circle';
        
        alert.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    }
    
    // Expose utility functions globally
    window.SSLManager = {
        showAlert: showAlert
    };
    
})();

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

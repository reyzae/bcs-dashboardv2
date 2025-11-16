<?php
// Load bootstrap configuration
require_once __DIR__ . '/bootstrap.php';

// Handle legacy login form submission (kept for compatibility; prefer using API router)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once __DIR__ . '/../app/config/database.php';
    require_once __DIR__ . '/../app/controllers/AuthController.php';
    
    // Use correct constructor and controller method that reads request body
    $authController = new AuthController($pdo);
    $authController->login();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bytebalok Dashboard</title>
    <meta name="description" content="Login to Bytebalok Business Management System">
    <meta name="theme-color" content="#4f46e5">
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <style>
        /* Additional login page enhancements */
        .login-card {
            animation: fadeInUp 0.5s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Shake animation for errors */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        
        .login-card.shake {
            animation: shake 0.5s ease-in-out;
        }
        
        /* Better focus states */
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        
        /* Better button hover */
        .login-btn:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .login-btn:active:not(:disabled) {
            transform: translateY(0);
        }
        
        /* Toast Notification - Modern & Clean */
        .toast-container {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            pointer-events: none;
        }
        
        .toast {
            background: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 320px;
            max-width: 500px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: all;
            font-family: 'Inter', sans-serif;
        }
        
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
        
        .toast-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .toast-message {
            font-size: 13px;
            opacity: 0.8;
            line-height: 1.4;
        }
        
        .toast-close {
            background: none;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0.5;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        
        .toast-close:hover {
            opacity: 1;
            background: rgba(0, 0, 0, 0.05);
        }
        
        /* Toast variants */
        .toast.toast-success {
            border-left: 4px solid #10b981;
        }
        
        .toast.toast-success .toast-icon {
            background: #d1fae5;
            color: #10b981;
        }
        
        .toast.toast-success .toast-title {
            color: #065f46;
        }
        
        .toast.toast-error {
            border-left: 4px solid #ef4444;
        }
        
        .toast.toast-error .toast-icon {
            background: #fee2e2;
            color: #ef4444;
        }
        
        .toast.toast-error .toast-title {
            color: #991b1b;
        }
        
        .toast.toast-warning {
            border-left: 4px solid #f59e0b;
        }
        
        .toast.toast-warning .toast-icon {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .toast.toast-warning .toast-title {
            color: #92400e;
        }
        
        .toast.toast-info {
            border-left: 4px solid #3b82f6;
        }
        
        .toast.toast-info .toast-icon {
            background: #dbeafe;
            color: #3b82f6;
        }
        
        .toast.toast-info .toast-title {
            color: #1e40af;
        }
        
        /* Smooth transitions */
        * {
            transition: all 0.2s ease;
        }
        
        /* Responsive toast */
        @media (max-width: 640px) {
            .toast-container {
                top: 16px;
                left: 16px;
                right: 16px;
                transform: none;
            }
            
            .toast {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-chart-line"></i>
                    <h1>Bytebalok</h1>
                </div>
                <p class="login-subtitle">Business Management System</p>
            </div>
            
            <form id="loginForm" class="login-form" autocomplete="on">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? SecurityMiddleware::generateCsrfToken(); ?>">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Enter your username or email"
                        autocomplete="username"
                        autofocus
                        required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required>
                        <button 
                            type="button" 
                            class="password-toggle" 
                            onclick="togglePassword()"
                            aria-label="Toggle password visibility">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="login-btn">
                    <span class="btn-text">Sign In</span>
                    <div class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </button>
            </form>
            
            <div class="login-footer">
                <p>&copy; 2024 Bytebalok. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Decorative Background Elements -->
        <div class="login-bg">
            <div class="bg-pattern"></div>
        </div>
    </div>
    
    <!-- Toast Notifications (ARIA) -->
    <div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="true"></div>
    
    <!-- Scripts -->
    <script src="assets/js/app.js"></script>
    <script>
        // Login form handling
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const loginBtn = document.querySelector('.login-btn');
            const btnText = document.querySelector('.btn-text');
            const btnLoader = document.querySelector('.btn-loader');
            
            // Show loading state
            loginBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoader.style.display = 'flex';
            
            try {
                const response = await fetch('api.php?controller=auth&action=login', {
                    method: 'POST',
                    body: formData
                });
                
                // Prefer JSON; fallback to text if content-type is not JSON
                const contentType = response.headers.get('content-type') || '';
                let result;
                if (contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    console.error('Login response (non-JSON):', text.slice(0, 300));
                    // Attempt to extract JSON inside text if possible
                    try {
                        result = JSON.parse(text);
                    } catch (_) {
                        // Build a friendly error when server returned HTML or plaintext
                        showToast('Server Error', 'Response bukan JSON. Detail: ' + (text.slice(0, 120) || 'unknown'), 'error');
                        shakeLoginCard();
                        return;
                    }
                }
                
                if (result.success) {
                    showToast('Login Successful!', 'Redirecting to dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = 'dashboard/';
                    }, 1500);
                } else {
                    showToast('Login Failed', result.error || result.message || 'Invalid username or password', 'error');
                    shakeLoginCard();
                }
            } catch (error) {
                // Network or fetch error
                console.error('Login error:', error);
                showToast('Connection Error', 'Network error. Please check your connection and try again.', 'error');
                shakeLoginCard();
            } finally {
                // Reset button state
                loginBtn.disabled = false;
                btnText.style.display = 'block';
                btnLoader.style.display = 'none';
            }
        });
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle i');
            
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
        
        function showToast(title, message, type = 'info') {
            const toast = document.getElementById('toast');
            
            // Icon based on type
            const icons = {
                success: 'fa-check',
                error: 'fa-times',
                warning: 'fa-exclamation',
                info: 'fa-info'
            };
            
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${icons[type]}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    ${message ? `<div class="toast-message">${message}</div>` : ''}
                </div>
                <button class="toast-close" onclick="closeToast()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            toast.className = `toast toast-${type} show`;
            
            // Auto dismiss after 4 seconds
            setTimeout(() => {
                closeToast();
            }, 4000);
        }
        
        function closeToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('show');
        }
        
        function shakeLoginCard() {
            const loginCard = document.querySelector('.login-card');
            loginCard.classList.add('shake');
            setTimeout(() => {
                loginCard.classList.remove('shake');
            }, 500);
        }
    </script>
</body>
</html>
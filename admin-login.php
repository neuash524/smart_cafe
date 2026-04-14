<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Café</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #1a0f0a 0%, #2c1810 50%, #1a0f0a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .admin-login-container {
            width: 100%;
            max-width: 460px;
        }
        
        .admin-login-box {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.5s ease;
        }
        
        .admin-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .admin-logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #8B4513, #654321);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .admin-logo-icon span {
            font-size: 2.5rem;
        }
        
        .admin-login-box h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: #8B4513;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        
        .admin-login-box .subtitle {
            text-align: center;
            color: #9b8070;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0e6dc;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a3520;
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 2px solid #e8d5c0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.2s;
            font-family: 'Lato', sans-serif;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #8B4513;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon .icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.1rem;
        }
        
        .input-icon input {
            padding-left: 45px;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: #8B4513;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 0.5rem;
        }
        
        .btn-login:hover {
            background: #654321;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }
        
        .btn-login:disabled {
            background: #c4a882;
            cursor: not-allowed;
            transform: none;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #f0e6dc;
            font-size: 0.8rem;
            color: #9b8070;
        }
        
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .back-link a {
            color: #8B4513;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.85rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ef4444;
            font-size: 0.9rem;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 0.85rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #10b981;
            font-size: 0.9rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .security-note {
            background: #fef3c7;
            border-radius: 10px;
            padding: 0.75rem;
            margin-top: 1rem;
            font-size: 0.75rem;
            color: #92400e;
            text-align: center;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-logo">
                <div class="admin-logo-icon">
                    <span>👑</span>
                </div>
                <h1>Admin Portal</h1>
                <div class="subtitle">Smart Café Management System</div>
            </div>
            
            <div id="messageContainer"></div>
            
            <form id="adminLoginForm" onsubmit="handleAdminLogin(event)">
                <div class="form-group">
                    <label for="admin-email">📧 Admin Email</label>
                    <div class="input-icon">
                        <span class="icon">📧</span>
                        <input type="email" id="admin-email" name="email" required 
                               placeholder="admin@smartcafe.com" autocomplete="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="admin-password">🔒 Password</label>
                    <div class="input-icon">
                        <span class="icon">🔑</span>
                        <input type="password" id="admin-password" name="password" required 
                               placeholder="Enter your admin password" autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    🔐 Login to Dashboard
                </button>
                
                <div class="security-badge">
                    <span>🔒</span>
                    <span>Secure Admin Access</span>
                    <span>🛡️</span>
                </div>
                
                <div class="security-note">
                    ⚡ This area is restricted to authorized personnel only.<br>
                    All access attempts are logged for security purposes.
                </div>
            </form>
            
            <div class="back-link">
                <a href="customer_dashboard.php">
                    ← Back to Customer Website
                </a>
            </div>
        </div>
    </div>

    <script>
        // Simple localStorage functions for session management
        function saveSession(sessionData) {
            try {
                localStorage.setItem('sc_session', JSON.stringify(sessionData));
                console.log('Session saved:', sessionData);
            } catch(e) {
                console.error('Failed to save session:', e);
            }
        }
        
        function getSession() {
            try {
                const session = localStorage.getItem('sc_session');
                return session ? JSON.parse(session) : null;
            } catch(e) {
                console.error('Failed to get session:', e);
                return null;
            }
        }
        
        function clearSession() {
            localStorage.removeItem('sc_session');
        }
        
        // Show message function
        function showMessage(message, type) {
            const container = document.getElementById('messageContainer');
            if (!container) return;
            
            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'error' ? 'error-message' : 'success-message';
            messageDiv.innerHTML = (type === 'error' ? '⚠️ ' : '✅ ') + message;
            
            // Clear previous messages
            container.innerHTML = '';
            container.appendChild(messageDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
        
        // Handle admin login
        async function handleAdminLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('admin-email').value.trim();
            const password = document.getElementById('admin-password').value;
            const loginBtn = document.getElementById('loginBtn');
            
            if (!email || !password) {
                showMessage('Please fill in both email and password.', 'error');
                return;
            }
            
            // Disable button and show loading
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner"></span> Authenticating...';
            
            try {
                // Try MySQL authentication via API
                console.log('Attempting login for:', email);
                
                const response = await fetch('login_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: email, password: password })
                });
                
                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                console.log('Login API response:', result);
                
                if (result.success && result.data?.user) {
                    const dbUser = result.data.user;
                    
                    // Check if user is admin
                    if (dbUser.user_type !== 'admin') {
                        showMessage('Access denied. This account does not have administrator privileges.', 'error');
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = '🔐 Login to Dashboard';
                        return;
                    }
                    
                    // Store admin session
                    const sessionData = {
                        user_id: dbUser.user_id,
                        email: dbUser.email,
                        full_name: dbUser.full_name,
                        phone: dbUser.phone || '',
                        user_type: 'admin',
                        is_admin: true,
                        login_time: new Date().toISOString()
                    };
                    
                    saveSession(sessionData);
                    
                    // Show success and redirect
                    showMessage('Welcome, ' + dbUser.full_name + '! Redirecting to dashboard...', 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php';
                    }, 1500);
                    
                } else {
                    // Try fallback for demo/admin@smartcafe.com / admin123
                    if (email === 'admin@smartcafe.com' && password === 'admin123') {
                        // Demo admin login
                        const sessionData = {
                            user_id: 1,
                            email: 'admin@smartcafe.com',
                            full_name: 'Admin User',
                            phone: '',
                            user_type: 'admin',
                            is_admin: true,
                            login_time: new Date().toISOString()
                        };
                        
                        saveSession(sessionData);
                        showMessage('Welcome, Admin User! Redirecting to dashboard...', 'success');
                        
                        setTimeout(() => {
                            window.location.href = 'admin_dashboard.php';
                        }, 1500);
                    } else {
                        showMessage(result.message || 'Invalid email or password. Please try again.', 'error');
                        loginBtn.disabled = false;
                        loginBtn.innerHTML = '🔐 Login to Dashboard';
                    }
                }
                
            } catch (error) {
                console.error('Login error:', error);
                
                // Fallback for demo credentials when API is not available
                if (email === 'admin@smartcafe.com' && password === 'admin123') {
                    const sessionData = {
                        user_id: 1,
                        email: 'admin@smartcafe.com',
                        full_name: 'Admin User',
                        phone: '',
                        user_type: 'admin',
                        is_admin: true,
                        login_time: new Date().toISOString()
                    };
                    
                    saveSession(sessionData);
                    showMessage('Welcome, Admin User! Redirecting to dashboard...', 'success');
                    
                    setTimeout(() => {
                        window.location.href = 'admin_dashboard.php';
                    }, 1500);
                } else {
                    showMessage('Network error or invalid credentials. Please check your connection and try again.', 'error');
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '🔐 Login to Dashboard';
                }
            }
        }
        
        // Check if already logged in as admin on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin login page loaded');
            const session = getSession();
            if (session && session.user_type === 'admin') {
                console.log('Already logged in, redirecting...');
                window.location.href = 'admin_dashboard.php';
            }
        });
    </script>
</body>
</html>
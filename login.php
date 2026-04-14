<?php
session_start();

// If already logged in as customer, redirect to customer dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer') {
    header('Location: customer_dashboard.php');
    exit;
}

// If admin is logged in, redirect to admin dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - Smart Cafe</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Lato', sans-serif; background: linear-gradient(135deg,#fdf6ee,#fff8f2,#fdf6ee); min-height:100vh; }
        .login-container { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }
        .login-box { background:#fff; padding:2.5rem 2.25rem; border-radius:16px; box-shadow:0 20px 60px rgba(139,69,19,.13); width:100%; max-width:460px; animation:fadeInUp .4s ease; }
        .login-header { text-align:center; margin-bottom:1.75rem; }
        .login-logo { display:flex; justify-content:center; margin-bottom:1rem; }
        h1.login-title { font-family:'Playfair Display',serif; font-size:1.85rem; color:#8B4513; margin-bottom:.3rem; }
        p.login-subtitle { color:#999; font-size:.92rem; }
        .login-tabs { display:flex; gap:.35rem; margin-bottom:2rem; background:#f7f0e8; padding:.35rem; border-radius:10px; }
        .login-tab { flex:1; padding:.6rem .4rem; border:none; background:transparent; border-radius:8px; cursor:pointer; font-weight:700; font-size:.85rem; color:#a08060; transition:all .2s; font-family:'Lato',sans-serif; }
        .login-tab.active { background:#fff; color:#8B4513; box-shadow:0 2px 8px rgba(139,69,19,.12); }
        .login-tab:hover:not(.active) { color:#8B4513; }
        .login-form { display:none; }
        .login-form.active { display:block; animation:fadeIn .25s ease; }
        .form-group { margin-bottom:1.1rem; }
        .form-group label { display:block; margin-bottom:.35rem; font-weight:700; color:#4a3520; font-size:.88rem; }
        .form-group input { width:100%; padding:.75rem 1rem; border:2px solid #e8d5c0; border-radius:8px; font-size:.95rem; font-family:'Lato',sans-serif; transition:border-color .2s; color:#2d1f0e; background:#fff; }
        .form-group input:focus { outline:none; border-color:#8B4513; box-shadow:0 0 0 3px rgba(139,69,19,.1); }
        .form-group input::placeholder { color:#c4a882; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
        .btn-login { width:100%; padding:.85rem; background:#8B4513; color:#fff; border:none; border-radius:8px; font-size:1rem; font-weight:700; font-family:'Lato',sans-serif; cursor:pointer; transition:background .2s; margin-top:.25rem; }
        .btn-login:hover { background:#a0522d; }
        .btn-login:disabled { background:#c4a882; cursor:not-allowed; }
        .forgot-link { text-align:right; margin-bottom:1rem; }
        .forgot-link a { color:#8B4513; text-decoration:none; font-size:.82rem; }
        .switch-link { text-align:center; margin-top:1.1rem; color:#999; font-size:.88rem; }
        .switch-link button { background:none; border:none; color:#8B4513; font-weight:700; cursor:pointer; font-size:.88rem; font-family:'Lato',sans-serif; padding:0; }
        .switch-link button:hover { text-decoration:underline; }
        .demo-hint { margin-top:1.1rem; padding:.75rem 1rem; background:#fdf6ee; border-left:3px solid #D4A574; border-radius:0 8px 8px 0; font-size:.81rem; color:#7a5c3a; line-height:1.8; }
        .demo-hint strong { color:#8B4513; }
        .strength-bar-wrap { height:4px; background:#ede0d0; border-radius:2px; margin-top:.4rem; overflow:hidden; }
        .strength-bar { height:100%; border-radius:2px; transition:all .3s; width:0; }
        .strength-text { font-size:.74rem; margin-top:.2rem; display:block; }
        .email-status { font-size:.78rem; margin-top:.25rem; display:block; min-height:1rem; }
        .terms-row { display:flex; align-items:flex-start; gap:.6rem; margin-bottom:1.1rem; }
        .terms-row input { margin-top:3px; accent-color:#8B4513; flex-shrink:0; width:15px; height:15px; }
        .terms-row label { font-size:.83rem; color:#7a5c3a; line-height:1.5; }
        .terms-row a { color:#8B4513; }
        .back-home { text-align:center; margin-top:1.25rem; }
        .back-home a { color:#a08060; text-decoration:none; font-size:.88rem; }
        .back-home a:hover { color:#8B4513; }
        .toast { position:fixed; top:20px; right:20px; z-index:99999; min-width:280px; max-width:380px; padding:1rem 1.25rem; border-radius:10px; font-weight:600; font-size:.92rem; box-shadow:0 8px 24px rgba(0,0,0,.15); animation:slideIn .3s ease; font-family:'Lato',sans-serif; }
        .toast.success { background:#d1fae5; color:#065f46; border-left:4px solid #10b981; }
        .toast.error { background:#fee2e2; color:#991b1b; border-left:4px solid #ef4444; }
        .toast.warning { background:#fef3c7; color:#92400e; border-left:4px solid #f59e0b; }
        @keyframes fadeInUp { from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn { from{opacity:0}to{opacity:1} }
        @keyframes slideIn { from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)} }
    </style>
</head>
<body>

<div class="login-container">
  <div class="login-box">

    <div class="login-header">
      <div class="login-logo">
        <svg width="54" height="54" viewBox="0 0 40 40" fill="none">
          <circle cx="20" cy="20" r="19" fill="#D4A574"/>
          <path d="M20 10C14.477 10 10 14.477 10 20s4.477 10 10 10 10-4.477 10-10S25.523 10 20 10z" fill="#8B4513"/>
          <circle cx="20" cy="20" r="6" fill="#D4A574"/>
        </svg>
      </div>
      <h1 class="login-title" id="pageTitle">Customer Login</h1>
      <p class="login-subtitle" id="pageSubtitle">Login to reserve tables, order food and more</p>
    </div>

    <div class="login-tabs">
      <button class="login-tab active" id="tab-customer" onclick="switchTab('customer')">Login</button>
      <button class="login-tab" id="tab-signup" onclick="switchTab('signup')">Sign Up</button>
    </div>

    <!-- CUSTOMER LOGIN -->
    <form id="customerLogin" class="login-form active" onsubmit="handleCustomerLogin(event)">
      <div class="form-group">
        <label for="cust-email">Email Address</label>
        <input type="email" id="cust-email" required placeholder="your.email@example.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label for="cust-pass">Password</label>
        <input type="password" id="cust-pass" required placeholder="Enter your password" autocomplete="current-password">
      </div>
      <div class="forgot-link"><a href="#">Forgot Password?</a></div>
      <button type="submit" class="btn-login" id="custLoginBtn">Login to My Account</button>
      <div class="switch-link">
        Don't have an account?
        <button type="button" onclick="switchTab('signup')">Sign up free</button>
      </div>
    </form>

    <!-- SIGN UP -->
    <form id="signupForm" class="login-form" onsubmit="handleSignup(event)">
      <div class="form-row">
        <div class="form-group">
          <label for="su-first">First Name</label>
          <input type="text" id="su-first" required placeholder="Aashish" autocomplete="given-name">
        </div>
        <div class="form-group">
          <label for="su-last">Last Name</label>
          <input type="text" id="su-last" required placeholder="Neupane" autocomplete="family-name">
        </div>
      </div>
      <div class="form-group">
        <label for="su-email">Email Address</label>
        <input type="email" id="su-email" required placeholder="your.email@example.com"
               autocomplete="email" oninput="liveEmailCheck(this.value)">
        <span class="email-status" id="emailStatus"></span>
      </div>
      <div class="form-group">
        <label for="su-phone">Phone Number</label>
        <input type="tel" id="su-phone" required placeholder="+1 234 567 8900" autocomplete="tel">
      </div>
      <div class="form-group">
        <label for="su-pass">Password</label>
        <input type="password" id="su-pass" required placeholder="Minimum 6 characters"
               oninput="updateStrength(this.value)" autocomplete="new-password">
        <div class="strength-bar-wrap"><div class="strength-bar" id="strengthBar"></div></div>
        <span class="strength-text" id="strengthText"></span>
      </div>
      <div class="form-group">
        <label for="su-confirm">Confirm Password</label>
        <input type="password" id="su-confirm" required placeholder="Repeat password" autocomplete="new-password">
      </div>
      <div class="terms-row">
        <input type="checkbox" id="su-terms" required>
        <label for="su-terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
      </div>
      <button type="submit" class="btn-login" id="signupBtn">Create My Account</button>
      <div class="switch-link">
        Already have an account?
        <button type="button" onclick="switchTab('customer')">Login here</button>
      </div>
    </form>

    <div class="back-home"><a href="customer_dashboard.php">&#8592; Back to Home</a></div>
  </div>
</div>

<!-- cafe-data.js MUST load first -->
<script src="cafe-data.js"></script>
<script>

// Wait for full page load so cafe-data.js is 100% executed before any code runs
document.addEventListener('DOMContentLoaded', function () {

    // Auto-redirect if already logged in as customer
    var s = getSession();
    if (s && s.user_type === 'customer') {
        window.location.href = 'customer_dashboard.php';
        return;
    }
    
    // If admin is logged in, redirect them to admin page (they shouldn't be here)
    if (s && s.user_type === 'admin') {
        window.location.href = 'admin-login.php';
        return;
    }

    // Open signup tab if URL has ?tab=signup
    if (new URLSearchParams(location.search).get('tab') === 'signup') {
        switchTab('signup');
    }
});

// ── Tab switcher ─────────────────────────────────────────────
var TABS = {
    customer: { title: 'Customer Login', sub: 'Login to reserve tables, order food and more' },
    signup:   { title: 'Create Account', sub: 'Join Smart Cafe and start ordering today' }
};

function switchTab(tab) {
    document.querySelectorAll('.login-tab').forEach(function (t) { t.classList.remove('active'); });
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelectorAll('.login-form').forEach(function (f) { f.classList.remove('active'); });
    var ids = { customer: 'customerLogin', signup: 'signupForm' };
    document.getElementById(ids[tab]).classList.add('active');
    document.getElementById('pageTitle').textContent    = TABS[tab].title;
    document.getElementById('pageSubtitle').textContent = TABS[tab].sub;
}

// ── Toast notification ───────────────────────────────────────
function showToast(msg, type) {
    document.querySelectorAll('.toast').forEach(function (t) { t.remove(); });
    var t      = document.createElement('div');
    t.className   = 'toast ' + (type || 'info');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () {
        t.style.transition = 'opacity 0.4s';
        t.style.opacity    = '0';
        setTimeout(function () { t.remove(); }, 400);
    }, 3500);
}

// ── Button loading state ─────────────────────────────────────
function setBtn(id, loading, label) {
    var b = document.getElementById(id);
    if (!b) return;
    b.disabled    = loading;
    b.textContent = loading ? 'Please wait...' : label;
}

// ── CUSTOMER LOGIN - BLOCKS ADMIN LOGIN ──────────────────────
function handleCustomerLogin(e) {
    e.preventDefault();

    var email = document.getElementById('cust-email').value.trim();
    var pass  = document.getElementById('cust-pass').value;

    if (!email || !pass) { showToast('Please fill in all fields.', 'error'); return; }

    setBtn('custLoginBtn', true, 'Login to My Account');
    
    // First try MySQL login to get the database user_id
    fetch('login_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, password: pass })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        setBtn('custLoginBtn', false, 'Login to My Account');
        
        if (data && data.success && data.data.user) {
            const dbUser = data.data.user;
            
            // 🔴 BLOCK ADMIN LOGIN - Show error and prevent login
            if (dbUser.user_type === 'admin') {
                showToast('❌ Admin accounts cannot login here. Please use the admin portal.', 'warning');
                return;
            }
            
            // Sync to localStorage
            const users = getUsers();
            const existingUser = users.find(u => u.email === email);
            if (existingUser) {
                existingUser.user_id = dbUser.user_id;
                saveUsers(users);
            }
            
            // Store session with database user_id
            saveSession({
                user_id: dbUser.user_id,
                email: dbUser.email,
                full_name: dbUser.full_name,
                phone: dbUser.phone || '',
                user_type: 'customer'
            });
            
            showToast('Welcome back, ' + dbUser.full_name + '!', 'success');
            setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1200);
        } else {
            // Fallback to localStorage only
            var user = authenticateUser(email, pass);
            if (!user) {
                showToast('Incorrect email or password. Please try again.', 'error');
                return;
            }
            
            // 🔴 BLOCK ADMIN LOGIN from localStorage as well
            if (user.user_type === 'admin') {
                showToast('❌ Admin accounts cannot login here. Please use the admin portal.', 'warning');
                return;
            }
            
            saveSession({
                user_id: user.user_id,
                email: user.email,
                full_name: user.full_name,
                phone: user.phone || '',
                user_type: user.user_type
            });
            showToast('Welcome back, ' + user.full_name + '!', 'success');
            setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1200);
        }
    })
    .catch(function(err) {
        setBtn('custLoginBtn', false, 'Login to My Account');
        console.error('Login error:', err);
        // Fallback to localStorage
        var user = authenticateUser(email, pass);
        if (user) {
            // 🔴 BLOCK ADMIN LOGIN
            if (user.user_type === 'admin') {
                showToast('❌ Admin accounts cannot login here. Please use the admin portal.', 'warning');
                return;
            }
            saveSession({
                user_id: user.user_id,
                email: user.email,
                full_name: user.full_name,
                phone: user.phone || '',
                user_type: user.user_type
            });
            showToast('Welcome back, ' + user.full_name + '!', 'success');
            setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1200);
        } else {
            showToast('Incorrect email or password. Please try again.', 'error');
        }
    });
}

// SIGN UP - Only creates customer accounts
function handleSignup(e) {
    e.preventDefault();

    var firstName = document.getElementById('su-first').value.trim();
    var lastName  = document.getElementById('su-last').value.trim();
    var email     = document.getElementById('su-email').value.trim();
    var phone     = document.getElementById('su-phone').value.trim();
    var pass      = document.getElementById('su-pass').value;
    var confirm   = document.getElementById('su-confirm').value;
    var fullName  = (firstName + ' ' + lastName).trim();

    if (!firstName || !lastName) { showToast('Please enter your full name.', 'error'); return; }
    if (!email)                  { showToast('Please enter your email address.', 'error'); return; }
    if (!phone)                  { showToast('Please enter your phone number.', 'error'); return; }
    if (pass.length < 6)         { showToast('Password must be at least 6 characters.', 'error'); return; }
    if (pass !== confirm)        { showToast('Passwords do not match.', 'error'); return; }

    setBtn('signupBtn', true, 'Create My Account');

    // Register in MySQL - Always creates customer account
    fetch('register.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({
            full_name: fullName,
            email:     email,
            phone:     phone,
            password:  pass
        })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data && data.success && data.data.user) {
            const dbUser = data.data.user;
            
            // Save to localStorage with the correct user_id
            const users = getUsers();
            const existing = users.find(u => u.email === email);
            if (!existing) {
                users.push({
                    user_id: dbUser.user_id,
                    email: dbUser.email,
                    full_name: dbUser.full_name,
                    phone: dbUser.phone || '',
                    user_type: 'customer',
                    password: pass,
                    created_at: new Date().toISOString().split('T')[0],
                    is_active: true
                });
                saveUsers(users);
            }
            
            // Store session with database user_id
            saveSession({
                user_id: dbUser.user_id,
                email: dbUser.email,
                full_name: dbUser.full_name,
                phone: dbUser.phone || '',
                user_type: 'customer'
            });
            
            showToast('Account created! Welcome, ' + dbUser.full_name + '!', 'success');
            setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1400);
        } else {
            // Fallback to localStorage
            var result = registerUser(fullName, email, phone, pass);
            setBtn('signupBtn', false, 'Create My Account');
            
            if (result.success) {
                saveSession({
                    user_id: result.user.user_id,
                    email: result.user.email,
                    full_name: result.user.full_name,
                    phone: result.user.phone || '',
                    user_type: 'customer'
                });
                showToast('Account created! Welcome, ' + result.user.full_name + '!', 'success');
                setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1400);
            } else {
                showToast(result.message, 'error');
            }
        }
        setBtn('signupBtn', false, 'Create My Account');
    })
    .catch(function(err) {
        console.error('Signup error:', err);
        setBtn('signupBtn', false, 'Create My Account');
        // Offline fallback
        var result = registerUser(fullName, email, phone, pass);
        if (result.success) {
            saveSession({
                user_id: result.user.user_id,
                email: result.user.email,
                full_name: result.user.full_name,
                phone: result.user.phone || '',
                user_type: 'customer'
            });
            showToast('Account created! Welcome, ' + result.user.full_name + '!', 'success');
            setTimeout(function () { window.location.href = 'customer_dashboard.php'; }, 1400);
        } else {
            showToast(result.message, 'error');
        }
    });
}

// ── LIVE EMAIL CHECK ─────────────────────────────────────────
var emailTimer;
function liveEmailCheck(val) {
    clearTimeout(emailTimer);
    var el = document.getElementById('emailStatus');
    if (!val || val.indexOf('@') === -1) { el.textContent = ''; return; }
    emailTimer = setTimeout(function () {
        var users  = getUsers();
        var exists = false;
        for (var i = 0; i < users.length; i++) {
            if (users[i].email.toLowerCase() === val.toLowerCase()) { exists = true; break; }
        }
        el.style.color = exists ? '#dc2626' : '#059669';
        el.textContent = exists ? 'This email is already registered.' : 'Email is available.';
    }, 400);
}

// ── PASSWORD STRENGTH METER ──────────────────────────────────
function updateStrength(pass) {
    var bar  = document.getElementById('strengthBar');
    var text = document.getElementById('strengthText');
    if (!bar || !text) return;
    if (!pass) { bar.style.width = '0'; text.textContent = ''; return; }
    var score = 0;
    if (pass.length >= 6)           score++;
    if (pass.length >= 10)          score++;
    if (/[A-Z]/.test(pass))         score++;
    if (/[0-9]/.test(pass))         score++;
    if (/[^A-Za-z0-9]/.test(pass))  score++;
    var levels = [
        { w: '40%',  c: '#f97316', t: 'Weak'        },
        { w: '60%',  c: '#eab308', t: 'Fair'        },
        { w: '80%',  c: '#22c55e', t: 'Strong'      },
        { w: '100%', c: '#16a34a', t: 'Very strong' }
    ];
    var lvl = levels[Math.min(score - 1, 4)] || levels[0];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.c;
    text.style.color     = lvl.c;
    text.textContent     = lvl.t;
}

</script>
</body>
</html>
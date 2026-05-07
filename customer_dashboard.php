<?php
session_start();
// No redirect here - customers can browse without login
// But we need session to check login status
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Café - Table Reservations & Queue Management</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/x-icon" href="img/logo/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <style>
        /* ── Section login banner (reservation / queue) ── */
        .section-login-banner {
            display: flex; align-items: center; gap: 0.75rem;
            background: #fff3cd; border: 2px solid #f59e0b;
            border-radius: var(--radius-sm); padding: 0.85rem 1.25rem;
            margin-bottom: 1rem; font-size: 0.95rem; color: #92400e;
        }
        .section-login-banner a { color: var(--primary); font-weight: 700; }
        .section-login-banner span:first-child { font-size: 1.4rem; }

        /* ── Login Required Overlay ── */
        #loginRequiredOverlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            z-index: 9000; display: flex; align-items: center; justify-content: center;
        }
        .login-required-box {
            background: var(--surface); border-radius: var(--radius-lg);
            padding: 2.5rem; max-width: 420px; width: 90%; text-align: center;
            box-shadow: var(--shadow-lg); animation: popIn 0.3s ease;
        }
        .login-required-icon { font-size: 3rem; margin-bottom: 1rem; }
        .login-required-box h3 { font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem; }
        .login-required-box p  { color: var(--text-secondary); margin-bottom: 1.25rem; }
        .login-required-actions { display: flex; gap: 1rem; justify-content: center; margin-bottom: 1rem; }
        .login-hint { font-size: 0.85rem; color: var(--text-light); background: var(--background);
                      padding: 0.5rem 1rem; border-radius: var(--radius-sm); margin-top: 0.5rem !important; }

        /* ── Payment Modal ── */
        #paymentModal {
            position: fixed; inset: 0; background: rgba(0,0,0,0.65);
            z-index: 9000; display: flex; align-items: center; justify-content: center; padding: 1rem;
        }
        .payment-modal-box {
            background: var(--surface); border-radius: var(--radius-lg);
            padding: 2rem; max-width: 480px; width: 100%;
            max-height: 90vh; overflow-y: auto;
            box-shadow: var(--shadow-lg); animation: popIn 0.3s ease;
        }
        .payment-modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #E5D4C1;
        }
        .payment-modal-header h2 { color: var(--primary); font-size: 1.4rem; }
        .payment-modal-header button {
            background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-secondary);
        }
        .payment-order-summary {
            background: var(--background); border-radius: var(--radius-sm);
            padding: 1rem; margin-bottom: 1.25rem;
        }
        .payment-order-summary h4 { color: var(--primary); margin-bottom: 0.75rem; font-size: 0.95rem; }
        .payment-item-row {
            display: flex; justify-content: space-between;
            font-size: 0.9rem; color: var(--text-secondary); padding: 0.2rem 0;
        }
        .payment-total-row {
            display: flex; justify-content: space-between;
            border-top: 1px solid #E5D4C1; margin-top: 0.5rem; padding-top: 0.5rem;
            color: var(--primary); font-size: 1rem;
        }
        .payment-customer-info {
            background: rgba(139,69,19,0.07); border-radius: var(--radius-sm);
            padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.9rem; color: var(--text-secondary);
        }
        .payment-method-section h4 { color: var(--primary); margin-bottom: 0.75rem; }

        /* ── Payment / Reservation / Queue Success overlays ── */
        #paymentSuccess, #reservationSuccess, #queueSuccess {
            position: fixed; inset: 0; background: rgba(0,0,0,0.65);
            z-index: 9500; display: flex; align-items: center; justify-content: center; padding: 1rem;
        }
        /* ── User nav item (logged-in state) ── */
        #nav-user-item {
            gap: 0.6rem;
        }
        #nav-user-item span {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.92rem;
            white-space: nowrap;
        }
        #nav-logout-btn:hover { background: var(--primary-dark, #6b2f0a) !important; }
        .payment-success-box {
            background: var(--surface); border-radius: var(--radius-lg);
            padding: 2.5rem; max-width: 440px; width: 100%; text-align: center;
            box-shadow: var(--shadow-lg); animation: popIn 0.4s ease;
        }
        .success-icon   { font-size: 3.5rem; margin-bottom: 1rem; }
        .payment-success-box h2 { color: var(--primary); margin-bottom: 0.5rem; }
        .success-ref    { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 0.75rem; }
        .success-details {
            background: var(--background); border-radius: var(--radius-sm);
            padding: 1rem; margin: 1rem 0; text-align: left; font-size: 0.9rem; color: var(--text-secondary);
        }
        .success-details p { margin-bottom: 0.3rem; }
        .success-note   { font-size: 0.85rem; color: var(--text-light); margin: 0.75rem 0 1.25rem; }

        /* ── Locked cart button ── */
        .add-to-cart-locked {
            background: var(--background) !important; color: var(--text-secondary) !important;
            border: 2px dashed var(--accent) !important; cursor: pointer;
        }
        .add-to-cart-locked:hover { background: var(--accent-light) !important; }

        /* Payment method box - Pay at Counter only */
        .payment-method-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .payment-method-box .method-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
        .info-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            color: #92400e;
        }

        @keyframes popIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <img src="img/logo/logo.png" alt="Smart Café Logo" width="40" height="40" onerror="this.style.display='none'; this.nextElementSibling.style.marginLeft='0';">
                <span class="brand-text">Smart Café</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#home" class="nav-link active">Home</a></li>
                <li><a href="#reserve" class="nav-link">Reserve</a></li>
                <li><a href="#menu" class="nav-link">Menu</a></li>
                <li><a href="#queue" class="nav-link">Queue</a></li>
                <!-- Guest: show Login button - FIXED to login.php -->
                <li id="nav-login-item"><a href="login.php" class="nav-link btn-primary">Login</a></li>
                <!-- Logged-in: show user name + logout (hidden by default, shown via JS) -->
                <li id="nav-user-item" style="display:none; align-items:center; gap:0.5rem; list-style:none;">
                    <span id="nav-user-name" style="font-weight:700; color:var(--primary); font-size:0.95rem;">👤 User</span>
                    <a href="logout.php" id="nav-logout-btn"
                       style="background:var(--primary); color:#fff; padding:0.4rem 1rem; border-radius:20px;
                              font-size:0.85rem; font-weight:700; text-decoration:none; transition:background 0.2s;">
                        Logout
                    </a>
                </li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-bg" style="background-image: url('img/hero/cafe-interior.jpg');"></div>
        <div class="container hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="title-line">Skip the Wait,</span>
                    <span class="title-line">Savor the Moment</span>
                </h1>
                <p class="hero-subtitle">Reserve your table, join the queue virtually, and pre-order your favorites—all from your device.</p>
                <div class="hero-cta">
                    <a href="#reserve" class="btn btn-large btn-primary">Reserve Now</a>
                    <a href="#queue" class="btn btn-large btn-secondary">Join Queue</a>
                </div>
            </div>
            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-number" data-target="5">0</div>
                    <div class="stat-label">Tables Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="12">0</div>
                    <div class="stat-label">In Queue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" data-target="15">0</div>
                    <div class="stat-label">Min Wait</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="img/features/reservation-icon.png" alt="Reserve" onerror="this.parentElement.innerHTML='📅';">
                    </div>
                    <h3>Reserve Your Table</h3>
                    <p>Book your preferred time slot and table size in advance. Get instant confirmation.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="img/features/queue-icon.png" alt="Queue" onerror="this.parentElement.innerHTML='⏱️';">
                    </div>
                    <h3>Virtual Queue</h3>
                    <p>Join the waiting list from anywhere. Track your position in real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="img/features/menu-icon.png" alt="Menu" onerror="this.parentElement.innerHTML='🍽️';">
                    </div>
                    <h3>Pre-Order Meals</h3>
                    <p>Browse our menu and order ahead. Your food will be ready when you arrive.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="img/features/payment-icon.png" alt="Payment" onerror="this.parentElement.innerHTML='💳';">
                    </div>
                    <h3>Secure Payment</h3>
                    <p>Pay online with confidence. Multiple payment methods supported.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Reservation Section -->
    <section id="reserve" class="reservation-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Make a Reservation</h2>
                <p class="section-subtitle">Reserve your table and enjoy a seamless dining experience</p>
            </div>
            <div class="reservation-container">
                <form id="reservationForm" class="reservation-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res-name">Full Name</label>
                            <input type="text" id="res-name" name="name" required placeholder="Aashish Neupane">
                        </div>
                        <div class="form-group">
                            <label for="res-email">Email Address</label>
                            <input type="email" id="res-email" name="email" required placeholder="aashish@example.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res-phone">Phone Number</label>
                            <input type="tel" id="res-phone" name="phone" required placeholder="+1 234 567 8900">
                        </div>
                        <div class="form-group">
                            <label for="res-guests">Number of Guests</label>
                            <select id="res-guests" name="guests" required>
                                <option value="">Select</option>
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5 Guests</option>
                                <option value="6">6+ Guests</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="res-date">Date</label>
                            <input type="date" id="res-date" name="date" required>
                        </div>
                        <div class="form-group">
                            <label for="res-time">Time</label>
                            <input type="time" id="res-time" name="time" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="res-notes">Special Requests (Optional)</label>
                        <textarea id="res-notes" name="notes" rows="3" placeholder="Any dietary requirements or special occasions?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-large btn-primary btn-full">Complete Reservation</button>
                </form>
                <div class="available-tables">
                    <h3>Available Tables</h3>
                    <div class="tables-grid" id="tablesGrid">
                        <!-- Tables will be populated dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Section -->
    <section id="menu" class="menu-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Menu</h2>
                <p class="section-subtitle">Pre-order your favorites and skip the wait</p>
            </div>
            <div class="menu-categories">
                <button class="category-btn active" data-category="all">All Items</button>
                <button class="category-btn" data-category="breakfast">Breakfast</button>
                <button class="category-btn" data-category="lunch">Lunch</button>
                <button class="category-btn" data-category="beverages">Beverages</button>
                <button class="category-btn" data-category="desserts">Desserts</button>
            </div>
            <div class="menu-grid" id="menuGrid">
                <!-- Menu items will be populated dynamically -->
            </div>
            <div class="cart-summary" id="cartSummary" style="display: none;">
                <div class="cart-header">
                    <h3>Your Order</h3>
                    <button class="close-cart">×</button>
                </div>
                <div class="cart-items" id="cartItems"></div>
                <div class="cart-total">
                    <span>Total:</span>
                    <span id="cartTotal">$0.00</span>
                </div>
                <button class="btn btn-primary btn-full" id="checkoutBtn">Proceed to Payment</button>
            </div>
        </div>
    </section>

    <!-- Queue Section -->
    <section id="queue" class="queue-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Virtual Queue</h2>
                <p class="section-subtitle">Join the waitlist and get notified when your table is ready</p>
            </div>
            <div class="queue-container">
                <div class="queue-form-wrapper">
                    <form id="queueForm" class="queue-form">
                        <div class="form-group">
                            <label for="queue-name">Full Name</label>
                            <input type="text" id="queue-name" name="name" required placeholder="Your name">
                        </div>
                        <div class="form-group">
                            <label for="queue-phone">Phone Number</label>
                            <input type="tel" id="queue-phone" name="phone" required placeholder="+1 234 567 8900">
                        </div>
                        <div class="form-group">
                            <label for="queue-party">Party Size</label>
                            <select id="queue-party" name="party" required>
                                <option value="">Select</option>
                                <option value="1">1 Person</option>
                                <option value="2">2 People</option>
                                <option value="3">3 People</option>
                                <option value="4">4 People</option>
                                <option value="5">5 People</option>
                                <option value="6">6+ People</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-large btn-primary btn-full">Join Queue</button>
                    </form>
                </div>
                <div class="queue-status">
                    <h3>Current Queue Status</h3>
                    <div class="queue-stats">
                        <div class="queue-stat">
                            <span class="queue-number">12</span>
                            <span class="queue-label">People Waiting</span>
                        </div>
                        <div class="queue-stat">
                            <span class="queue-number">~15</span>
                            <span class="queue-label">Min Wait Time</span>
                        </div>
                    </div>
                    <div class="queue-list" id="queueList">
                        <!-- Queue entries will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Smart Café</h3>
                    <p>Revolutionizing café dining with smart reservations and queue management.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#reserve">Reserve</a></li>
                        <li><a href="#menu">Menu</a></li>
                        <li><a href="#queue">Queue</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>📍 123 Café Street, City</p>
                    <p>📞 +1 234 567 8900</p>
                    <p>✉️ <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="c0a9aea6af80b3ada1b2b4a3a1a6a5eea3afad">[email&#160;protected]</a></p>
                </div>
                <div class="footer-section">
                    <h4>Hours</h4>
                    <p>Mon-Fri: 7:00 AM - 10:00 PM</p>
                    <p>Sat-Sun: 8:00 AM - 11:00 PM</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Smart Café. Project by Aashish Neupane - DMU BSc Computer Science</p>
            </div>
        </div>
    </footer>

    <script src="cafe-data.js"></script>
    <script src="script.js"></script>
</body>
</html>
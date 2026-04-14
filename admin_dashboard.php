<?php
session_start();
// No PHP session check - we'll use localStorage via JavaScript
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Café</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="admin-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="img/logo/logo.png" alt="Smart Café" width="40" height="40"
                 onerror="this.style.display='none';">
            <h2>Smart Café Admin</h2>
        </div>

        <nav class="sidebar-nav">
            <a href="#" class="sidebar-link active" data-section="dashboard">
                <span class="sidebar-icon-emoji">📊</span>
                Dashboard
            </a>
            <a href="#" class="sidebar-link" data-section="tables">
                <span class="sidebar-icon-emoji">🪑</span>
                Table Management
            </a>
            <a href="#" class="sidebar-link" data-section="reservations">
                <span class="sidebar-icon-emoji">📅</span>
                Reservations
            </a>
            <a href="#" class="sidebar-link" data-section="queue">
                <span class="sidebar-icon-emoji">⏱️</span>
                Queue Management
            </a>
            <a href="#" class="sidebar-link" data-section="orders">
                <span class="sidebar-icon-emoji">🍽️</span>
                Pre-Orders
            </a>
            <a href="#" class="sidebar-link" data-section="menu">
                <span class="sidebar-icon-emoji">📋</span>
                Menu Management
            </a>
            <a href="#" class="sidebar-link" data-section="analytics">
                <span class="sidebar-icon-emoji">📈</span>
                Analytics
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="customer_dashboard.php" class="btn btn-secondary btn-full">Back to Website</a>
            <button class="btn btn-outline btn-full" onclick="logout()">Logout</button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">

        <!-- Header -->
        <header class="admin-header">
            <div class="header-left">
                <h1 id="pageTitle">Dashboard</h1>
                <p id="pageSubtitle">Welcome back, <span id="adminName">Admin</span></p>
            </div>
            <div class="header-right">
                <div class="notification-bell">
                    <span>🔔</span>
                    <span class="badge">3</span>
                </div>
                <div class="admin-profile">
                    <div class="profile-avatar" id="adminAvatar">AD</div>
                    <div class="profile-info">
                        <span class="profile-name" id="adminProfileName">Admin User</span>
                        <span class="profile-role">Manager</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- DASHBOARD SECTION -->
        <section id="dashboard-section" class="admin-section active">
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-icon">🪑</div>
                    <div class="stat-content">
                        <h3>Available Tables</h3>
                        <div class="stat-value">5<span>/8</span></div>
                        <p class="stat-change positive">+2 from yesterday</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3>Today's Reservations</h3>
                        <div class="stat-value">12</div>
                        <p class="stat-change positive">+15% from last week</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <h3>Current Queue</h3>
                        <div class="stat-value">8</div>
                        <p class="stat-change">~15 min wait</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3>Today's Revenue</h3>
                        <div class="stat-value">$1,234</div>
                        <p class="stat-change positive">+8% from yesterday</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Recent Reservations</h3>
                    <div class="recent-list" id="recentReservations"></div>
                </div>
                <div class="dashboard-card">
                    <h3>Active Queue</h3>
                    <div class="recent-list" id="activeQueue"></div>
                </div>
            </div>
            <div style="margin-bottom: 1rem; text-align: right;">
                <button class="btn btn-small btn-secondary" onclick="forceCustomerRefresh()">
                    🔄 Force Customer Page Refresh
                </button>
            </div>
        </section>

        <!-- TABLES SECTION -->
        <section id="tables-section" class="admin-section">
            <div class="section-actions">
                <button class="btn btn-primary" onclick="openTableModal()">+ Add New Table</button>
                <div class="search-box">
                    <input type="text" placeholder="Search tables..." id="tableSearch"
                           oninput="searchTables(this.value)">
                </div>
            </div>
            <div class="table-layout-grid" id="tableLayoutGrid"></div>
        </section>

        <!-- RESERVATIONS SECTION -->
        <section id="reservations-section" class="admin-section">
            <div class="section-actions">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all">All</button>
                    <button class="filter-tab" data-filter="today">Today</button>
                    <button class="filter-tab" data-filter="upcoming">Upcoming</button>
                    <button class="filter-tab" data-filter="past">Past</button>
                </div>
                <button class="btn btn-primary" onclick="openReservationModal()">+ New Reservation</button>
            </div>
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Date & Time</th>
                            <th>Guests</th>
                            <th>Table</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reservationsTable"></tbody>
                </table>
            </div>
        </section>

        <!-- QUEUE SECTION -->
        <section id="queue-section" class="admin-section">
            <div class="section-actions">
                <div class="queue-controls">
                    <button class="btn btn-primary" onclick="callNextInQueue()">Call Next Customer</button>
                    <button class="btn btn-secondary" onclick="refreshQueue()">Refresh Queue</button>
                </div>
            </div>
            <div class="queue-management-grid">
                <div class="queue-list-card">
                    <h3>Current Queue <span id="queueCount">(8 people)</span></h3>
                    <div class="queue-entries" id="queueManagement"></div>
                </div>
                <div class="queue-settings-card">
                    <h3>Queue Settings</h3>
                    <div class="settings-form">
                        <div class="form-group">
                            <label>Average Wait Time per Party</label>
                            <input type="number" value="15" min="5" max="60">
                            <small>minutes</small>
                        </div>
                        <div class="form-group">
                            <label>Max Queue Size</label>
                            <input type="number" value="20" min="5" max="50">
                        </div>
                        <div class="form-group">
                            <label>Enable SMS Notifications</label>
                            <label class="toggle">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button class="btn btn-primary btn-full"
                                onclick="showAdminNotification('Settings saved!', 'success')">
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ORDERS SECTION -->
        <section id="orders-section" class="admin-section">
            <div class="section-actions">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" onclick="filterOrders('all', this)">All</button>
                    <button class="filter-tab" data-filter="pending" onclick="filterOrders('pending', this)">Pending</button>
                    <button class="filter-tab" data-filter="preparing" onclick="filterOrders('preparing', this)">Preparing</button>
                    <button class="filter-tab" data-filter="ready" onclick="filterOrders('ready', this)">Ready</button>
                    <button class="filter-tab" data-filter="completed" onclick="filterOrders('completed', this)">Completed</button>
                </div>
            </div>
            <div class="orders-grid" id="ordersGrid"></div>
        </section>

        <!-- MENU MANAGEMENT SECTION -->
        <section id="menu-section" class="admin-section">
            <div class="section-actions">
                <button class="btn btn-primary" onclick="openMenuItemModal()">+ Add Menu Item</button>
                <div class="search-box">
                    <input type="text" placeholder="Search menu items..."
                           oninput="searchMenu(this.value)">
                </div>
            </div>
            <div class="menu-management-grid" id="menuManagement"></div>
        </section>

        <!-- ANALYTICS SECTION -->
        <section id="analytics-section" class="admin-section">
            <div class="analytics-grid">
                <div class="chart-card">
                    <h3>Reservation Trends (Last 7 Days)</h3>
                    <div class="chart-placeholder">
                        <canvas id="reservationChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Popular Time Slots</h3>
                    <div class="chart-placeholder">
                        <canvas id="timeSlotChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Revenue Overview</h3>
                    <div class="chart-placeholder">
                        <canvas id="revenueChart" width="400" height="200"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Top Menu Items</h3>
                    <div class="top-items-list" id="topItems"></div>
                </div>
            </div>
        </section>

    </main>

    <!-- Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Modal Title</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script src="cafe-data.js"></script>
    <script src="admin-script.js"></script>
    <script>
        // Check authentication from localStorage
        (function checkAuth() {
            const session = getSession();
            if (!session || session.user_type !== 'admin') {
                window.location.href = 'admin-login.php';
                return;
            }
            
            // Display admin name
            const adminName = session.full_name || 'Admin';
            document.getElementById('adminName').textContent = adminName;
            document.getElementById('adminProfileName').textContent = adminName;
            document.getElementById('adminAvatar').textContent = adminName.substring(0, 2).toUpperCase();
        })();
        
        function logout() {
            if (confirm('Logout?')) {
                clearSession();
                window.location.href = 'admin-login.php';
            }
        }
    </script>
</body>
</html>
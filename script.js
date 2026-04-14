// ============================================================
// Smart Café — script.js (v23 - Fixed Login Links)
// Customers only get notified when admin approves actions
// Queue seating only to reserved tables
// ============================================================

// ── Global Variables ─────────────────────────────────────────
let cart = [];
let selectedTableId = null;
let lastTableState = '';
let unreadNotifications = 0;
let notificationPollingInterval = null;

// ── Initialization ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    console.log('[Customer] Page loaded, initializing...');
    
    // First, ensure session has correct database user_id
    syncSessionWithDatabase().then(() => {
        updateNavForSession();
        renderTablesGrid();
        displayMenuItems('all');
        renderQueueStatus();
        renderLoginBanners();
        initializeEventListeners();
        setMinDate();
        prefillFormsFromSession();
        
        startTablePolling();
        setupStorageListener();
        loadDataFromDatabase();
        
        updateHeroStats();
        
        // Initialize notification system
        initNotifications();
        
        // Load customer's pending reservations
        loadCustomerReservationsWithStatus();
    });
});

// ══════════════════════════════════════════════════════════════
// NOTIFICATION SYSTEM - Only shows admin-approved actions
// ══════════════════════════════════════════════════════════════

// Add notification bell to navigation bar
function addNotificationBell() {
    const navUserItem = document.getElementById('nav-user-item');
    if (!navUserItem) {
        console.log('[Notifications] Nav user item not found');
        return;
    }
    
    // Check if bell already exists
    if (document.getElementById('notification-bell')) {
        console.log('[Notifications] Bell already exists');
        return;
    }
    
    const bellHtml = `
        <div id="notification-bell" class="notification-bell-wrapper" style="position: relative; margin-right: 0.5rem; cursor: pointer;" onclick="toggleNotificationDropdown()">
            <span style="font-size: 1.3rem;">🔔</span>
            <span id="notification-badge" style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; font-weight: bold; min-width: 18px; text-align: center; display: none;">
                0
            </span>
        </div>
    `;
    
    navUserItem.insertAdjacentHTML('afterbegin', bellHtml);
    console.log('[Notifications] ✅ Bell added to navbar');
}

// Fetch notifications from database
async function fetchNotifications() {
    const session = getSession();
    
    if (!session || !session.user_id) {
        console.log('[Notifications] No user logged in, skipping fetch');
        return [];
    }
    
    console.log('[Notifications] Fetching for user_id:', session.user_id);
    
    try {
        const response = await fetch(`notifications_api.php?user_id=${session.user_id}`);
        const result = await response.json();
        
        console.log('[Notifications] API Response:', result);
        
        if (result.success && result.data?.notifications) {
            const notifications = result.data.notifications;
            console.log(`[Notifications] Found ${notifications.length} notifications`);
            
            // Count unread (is_read can be 0 or 1)
            unreadNotifications = notifications.filter(n => n.is_read === 0 || n.is_read === '0').length;
            console.log('[Notifications] Unread count:', unreadNotifications);
            
            // Update badge
            updateNotificationBadge();
            
            // Store in localStorage for offline display
            localStorage.setItem('sc_notifications', JSON.stringify(notifications));
            localStorage.setItem('sc_notifications_last_fetch', Date.now().toString());
            
            return notifications;
        } else {
            console.log('[Notifications] API error or no notifications:', result.message);
        }
    } catch (error) {
        console.error('[Notifications] Error fetching:', error);
        // Fallback to localStorage
        const cached = localStorage.getItem('sc_notifications');
        if (cached) {
            try {
                const notifications = JSON.parse(cached);
                console.log('[Notifications] Using cached notifications:', notifications.length);
                return notifications;
            } catch(e) {}
        }
    }
    return [];
}

// Update notification badge
function updateNotificationBadge() {
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (unreadNotifications > 0) {
            badge.textContent = unreadNotifications > 99 ? '99+' : unreadNotifications;
            badge.style.display = 'block';
            console.log('[Notifications] Badge updated:', unreadNotifications);
        } else {
            badge.style.display = 'none';
            console.log('[Notifications] Badge hidden');
        }
    }
}

// Create notification dropdown
function createNotificationDropdown() {
    // Remove existing dropdown
    const existing = document.getElementById('notification-dropdown');
    if (existing) existing.remove();
    
    const dropdown = document.createElement('div');
    dropdown.id = 'notification-dropdown';
    dropdown.style.cssText = `
        position: fixed;
        top: 70px;
        right: 20px;
        width: 380px;
        max-height: 500px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        z-index: 10000;
        overflow: hidden;
        animation: slideInDown 0.2s ease;
        display: flex;
        flex-direction: column;
    `;
    
    dropdown.innerHTML = `
        <div style="padding: 1rem; border-bottom: 2px solid #E5D4C1; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: #8B4513; margin: 0;">Notifications</h3>
            <button onclick="markAllNotificationsRead()" style="background: none; border: none; color: #8B4513; cursor: pointer; font-size: 0.85rem;">
                Mark all read
            </button>
        </div>
        <div id="notification-list" style="overflow-y: auto; max-height: 400px;">
            <div style="padding: 2rem; text-align: center; color: #9b8070;">
                <div class="spinner" style="width: 30px; height: 30px; border: 3px solid #E5D4C1; border-top-color: #8B4513; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                Loading notifications...
            </div>
        </div>
    `;
    
    document.body.appendChild(dropdown);
    
    // Load notifications into dropdown
    loadNotificationsIntoDropdown();
    
    // Close when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown.contains(e.target) && !e.target.closest('.notification-bell-wrapper')) {
                dropdown.remove();
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}

// Load notifications into dropdown
async function loadNotificationsIntoDropdown() {
    const container = document.getElementById('notification-list');
    if (!container) return;
    
    const notifications = await fetchNotifications();
    
    if (!notifications || notifications.length === 0) {
        container.innerHTML = `
            <div style="padding: 2rem; text-align: center; color: #9b8070;">
                <span style="font-size: 2rem;">🔔</span>
                <p style="margin-top: 0.5rem;">No notifications yet</p>
                <p style="font-size: 0.8rem; margin-top: 0.5rem;">When admin approves your reservations or updates your order status, you'll see updates here.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = notifications.map(n => {
        const isUnread = (n.is_read === 0 || n.is_read === '0');
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''}" 
                 data-id="${n.notification_id}"
                 style="padding: 1rem; border-bottom: 1px solid #f0f0f0; cursor: pointer; background: ${isUnread ? '#fef3e8' : 'white'}; transition: background 0.2s;"
                 onclick="markNotificationRead(${n.notification_id})">
                <div style="display: flex; gap: 0.75rem;">
                    <div style="font-size: 1.3rem;">
                        ${getNotificationIcon(n.notification_type)}
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #2C1810; margin-bottom: 0.25rem;">${escapeHtml(n.title)}</div>
                        <div style="font-size: 0.85rem; color: #6B4E3D;">${escapeHtml(n.message)}</div>
                        <div style="font-size: 0.7rem; color: #9B8070; margin-top: 0.5rem;">${formatNotificationTime(n.created_at)}</div>
                    </div>
                    ${isUnread ? '<div style="width: 8px; height: 8px; background: #8B4513; border-radius: 50%; margin-top: 8px;"></div>' : ''}
                </div>
            </div>
        `;
    }).join('');
    
    console.log('[Notifications] Dropdown populated with', notifications.length, 'items');
}

// Get icon for notification type
function getNotificationIcon(type) {
    const icons = {
        'order': '🍽️',
        'reservation': '📅',
        'queue': '⏱️',
        'payment': '💳',
        'general': '📢'
    };
    return icons[type] || '🔔';
}

// Format notification time
function formatNotificationTime(timestamp) {
    if (!timestamp) return 'Just now';
    
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return date.toLocaleDateString();
}

// Toggle notification dropdown
function toggleNotificationDropdown() {
    console.log('[Notifications] Toggling dropdown');
    const existing = document.getElementById('notification-dropdown');
    if (existing) {
        existing.remove();
    } else {
        createNotificationDropdown();
    }
}

// Mark single notification as read
async function markNotificationRead(notificationId) {
    console.log('[Notifications] Marking as read:', notificationId);
    
    try {
        const response = await fetch(`notifications_api.php?id=${notificationId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ is_read: 1 })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Update local count
            unreadNotifications = Math.max(0, unreadNotifications - 1);
            updateNotificationBadge();
            
            // Update local storage
            const cached = localStorage.getItem('sc_notifications');
            if (cached) {
                try {
                    const notifications = JSON.parse(cached);
                    const updated = notifications.map(n => {
                        if (n.notification_id === notificationId) {
                            n.is_read = 1;
                        }
                        return n;
                    });
                    localStorage.setItem('sc_notifications', JSON.stringify(updated));
                } catch(e) {}
            }
            
            // Refresh dropdown
            loadNotificationsIntoDropdown();
            console.log('[Notifications] Marked as read successfully');
        } else {
            console.error('[Notifications] Failed to mark as read:', result.message);
        }
    } catch (error) {
        console.error('[Notifications] Error marking read:', error);
    }
}

// Mark all notifications as read
async function markAllNotificationsRead() {
    const session = getSession();
    if (!session || !session.user_id) return;
    
    console.log('[Notifications] Marking all as read for user:', session.user_id);
    
    try {
        const response = await fetch(`notifications_api.php?user_id=${session.user_id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mark_all_read: true })
        });
        
        const result = await response.json();
        
        if (result.success) {
            unreadNotifications = 0;
            updateNotificationBadge();
            
            // Update local storage
            const cached = localStorage.getItem('sc_notifications');
            if (cached) {
                try {
                    const notifications = JSON.parse(cached);
                    const updated = notifications.map(n => {
                        n.is_read = 1;
                        return n;
                    });
                    localStorage.setItem('sc_notifications', JSON.stringify(updated));
                } catch(e) {}
            }
            
            loadNotificationsIntoDropdown();
            console.log('[Notifications] All marked as read');
        }
    } catch (error) {
        console.error('[Notifications] Error marking all read:', error);
    }
}

// Start notification polling
function startNotificationPolling() {
    // Clear existing interval
    if (notificationPollingInterval) {
        clearInterval(notificationPollingInterval);
    }
    
    // Poll every 15 seconds
    notificationPollingInterval = setInterval(async () => {
        const session = getSession();
        if (session && session.user_id) {
            const oldCount = unreadNotifications;
            await fetchNotifications();
            
            // Show toast for new notifications (admin-approved actions)
            if (unreadNotifications > oldCount && unreadNotifications > 0) {
                const newCount = unreadNotifications - oldCount;
                showNotificationToast(`🔔 You have ${newCount} new notification${newCount > 1 ? 's' : ''} from admin`, 'info');
            }
        }
    }, 15000);
    
    console.log('[Notifications] Polling started (every 15 seconds)');
}

// Show notification toast
function showNotificationToast(message, type) {
    // Remove existing toast
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = 'notification-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'info' ? '#8B4513' : '#10b981'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 10001;
        animation: slideInRight 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-weight: 500;
        cursor: pointer;
    `;
    toast.innerHTML = message;
    toast.onclick = () => toggleNotificationDropdown();
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

// Force refresh notifications (call this after login or page load)
async function refreshNotifications() {
    console.log('[Notifications] Manual refresh triggered');
    await fetchNotifications();
    const dropdown = document.getElementById('notification-dropdown');
    if (dropdown) {
        loadNotificationsIntoDropdown();
    }
}

// Add spinner animation CSS if not exists
function addSpinnerAnimation() {
    if (!document.getElementById('notification-spinner-style')) {
        const style = document.createElement('style');
        style.id = 'notification-spinner-style';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            @keyframes slideInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .notification-item {
                transition: background 0.2s ease;
            }
            .notification-item:hover {
                background: #faf3eb !important;
            }
            .notification-item.unread {
                background: #fef3e8;
                border-left: 3px solid #8B4513;
            }
            .notification-bell-wrapper {
                transition: transform 0.2s ease;
            }
            .notification-bell-wrapper:hover {
                transform: scale(1.1);
            }
            #notification-badge {
                animation: bounce 0.5s ease;
            }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize notifications on page load
function initNotifications() {
    console.log('[Notifications] Initializing...');
    addSpinnerAnimation();
    addNotificationBell();
    startNotificationPolling();
    
    // Initial fetch after a short delay to ensure session is loaded
    setTimeout(() => {
        fetchNotifications();
    }, 1000);
}

// Load customer's pending reservations status
async function loadCustomerReservationsWithStatus() {
    const session = getSession();
    if (!session || !session.user_id) return;
    
    try {
        const response = await fetch(`reservations.php?user_id=${session.user_id}`);
        const result = await response.json();
        
        if (result.success && result.data.reservations) {
            const reservations = result.data.reservations;
            const pendingCount = reservations.filter(r => r.status === 'pending').length;
            const confirmedCount = reservations.filter(r => r.status === 'confirmed').length;
            
            if (pendingCount > 0) {
                showNotification(`📋 You have ${pendingCount} reservation${pendingCount > 1 ? 's' : ''} pending admin approval. You'll be notified when confirmed.`, 'info');
            }
            if (confirmedCount > 0) {
                console.log(`[Customer] ${confirmedCount} confirmed reservation(s)`);
            }
        }
    } catch (error) {
        console.error('[Customer] Error loading reservations:', error);
    }
}

// ══════════════════════════════════════════════════════════════
// SESSION SYNC - Ensure we have correct database user_id
// ══════════════════════════════════════════════════════════════

async function syncSessionWithDatabase() {
    const session = getSession();
    if (!session) return;
    
    try {
        // Verify if the user exists in database and get correct ID
        const response = await fetch('login_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                email: session.email, 
                password: session.password || 'dummy' 
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data?.user) {
            const dbUser = result.data.user;
            
            // Update session with database user_id if different
            if (session.user_id !== dbUser.user_id) {
                console.log('[Session] Updating session with database user_id:', dbUser.user_id);
                saveSession({
                    user_id: dbUser.user_id,
                    email: dbUser.email,
                    full_name: dbUser.full_name,
                    phone: dbUser.phone || '',
                    user_type: dbUser.user_type,
                    password: session.password // Keep password for offline fallback
                });
            }
            
            // Also update localStorage users array
            const users = getUsers();
            const localUser = users.find(u => u.email === dbUser.email);
            if (localUser) {
                localUser.user_id = dbUser.user_id;
                saveUsers(users);
            }
            
            return dbUser.user_id;
        }
    } catch (error) {
        console.log('[Session] Could not sync with database, using local session');
    }
    
    return session.user_id;
}

// ══════════════════════════════════════════════════════════════
// DATABASE LOAD FUNCTIONS
// ══════════════════════════════════════════════════════════════

async function loadDataFromDatabase() {
    console.log('[API] Loading data from database...');
    await Promise.all([
        loadReservationsFromDB(),
        loadOrdersFromDB(),
        loadQueueFromDB()
    ]);
    renderTablesGrid();
    renderQueueStatus();
    updateHeroStats();
}

async function loadReservationsFromDB() {
    try {
        const response = await fetch('reservations.php');
        const result = await response.json();
        if (result.success && result.data.reservations) {
            saveReservations(result.data.reservations);
            console.log('[API] Loaded', result.data.reservations.length, 'reservations');
            return result.data.reservations;
        }
    } catch (error) {
        console.error('[API] Error loading reservations:', error);
    }
    return getReservations();
}

async function loadOrdersFromDB() {
    try {
        const response = await fetch('orders.php');
        const result = await response.json();
        if (result.success && result.data.orders) {
            saveOrders(result.data.orders);
            console.log('[API] Loaded', result.data.orders.length, 'orders');
            return result.data.orders;
        }
    } catch (error) {
        console.error('[API] Error loading orders:', error);
    }
    return getOrders();
}

async function loadQueueFromDB() {
    try {
        const response = await fetch('queue.php');
        const result = await response.json();
        if (result.success && result.data.queue) {
            saveQueue(result.data.queue);
            console.log('[API] Loaded', result.data.queue.length, 'queue entries');
            return result.data.queue;
        }
    } catch (error) {
        console.error('[API] Error loading queue:', error);
    }
    return getQueue();
}

// ══════════════════════════════════════════════════════════════
// REAL-TIME SYNC FUNCTIONS
// ══════════════════════════════════════════════════════════════

function startTablePolling() {
    updateTableStateHash();
    
    setInterval(function() {
        const currentState = getTableStateHash();
        if (currentState !== lastTableState) {
            console.log('[Sync] Table state changed! Refreshing...');
            renderTablesGrid();
            updateHeroStats();
            updateTableStateHash();
            
            if (selectedTableId) {
                const tables = getTables();
                const selected = tables.find(t => t.table_id === selectedTableId);
                if (selected && selected.status !== 'available') {
                    selectedTableId = null;
                    document.getElementById('selectedTableBadge')?.remove();
                    showNotification('Your selected table is no longer available.', 'warning');
                }
            }
        }
    }, 2000);
}

function getTableStateHash() {
    const tables = getTables();
    return tables.map(t => `${t.table_id}:${t.status}`).join('|');
}

function updateTableStateHash() {
    lastTableState = getTableStateHash();
}

function setupStorageListener() {
    window.addEventListener('storage', function (e) {
        if (e.key === 'sc_tables') {
            renderTablesGrid();
            updateHeroStats();
            updateTableStateHash();
            showNotification('Table availability has been updated!', 'success');
        }
        
        if (e.key === 'sc_customer_ping') {
            try {
                const ping = JSON.parse(e.newValue || '{}');
                if (ping.type === 'sc_tables') {
                    renderTablesGrid();
                    updateHeroStats();
                }
            } catch(err) {}
        }
        
        if (e.key === 'sc_table_update' || e.key === 'sc_table_counter') {
            renderTablesGrid();
            updateHeroStats();
        }
    });
    
    window.addEventListener('tablesUpdated', function(e) {
        renderTablesGrid();
        updateHeroStats();
    });
}

// ══════════════════════════════════════════════════════════════
// NAVIGATION & AUTH
// ══════════════════════════════════════════════════════════════

function updateNavForSession() {
    const session = getSession();
    const loginItem = document.getElementById('nav-login-item');
    const userItem = document.getElementById('nav-user-item');
    const userNameEl = document.getElementById('nav-user-name');

    if (session) {
        if (loginItem) loginItem.style.display = 'none';
        if (userItem) {
            userItem.style.display = 'flex';
            userItem.style.alignItems = 'center';
            userItem.style.gap = '0.5rem';
        }
        if (userNameEl) userNameEl.textContent = '👤 ' + session.full_name.split(' ')[0];
    } else {
        if (loginItem) loginItem.style.display = '';
        if (userItem) userItem.style.display = 'none';
    }
}

function handleNavLogout(e) {
    e.preventDefault();
    if (confirm('Log out of Smart Café?')) {
        clearSession();
        window.location.href = 'login.php';
    }
}

function prefillFormsFromSession() {
    const session = getSession();
    if (!session) return;

    const name = document.getElementById('res-name');
    const email = document.getElementById('res-email');
    const phone = document.getElementById('res-phone');
    if (name && !name.value) name.value = session.full_name;
    if (email && !email.value) email.value = session.email;
    if (phone && !phone.value) phone.value = session.phone || '';

    const qname = document.getElementById('queue-name');
    const qphone = document.getElementById('queue-phone');
    if (qname && !qname.value) qname.value = session.full_name;
    if (qphone && !qphone.value) qphone.value = session.phone || '';
}

function setMinDate() {
    const d = document.getElementById('res-date');
    if (d) d.setAttribute('min', new Date().toISOString().split('T')[0]);
}

// ══════════════════════════════════════════════════════════════
// LOGIN BANNERS & REQUIRED LOGIN - FIXED to login.php
// ══════════════════════════════════════════════════════════════

function renderLoginBanners() {
    if (isLoggedIn()) return;

    const bannerHTML = (action) => `
        <div class="section-login-banner" style="background:#fff3cd;border:2px solid #f59e0b;border-radius:8px;padding:0.85rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.75rem;">
            <span style="font-size:1.4rem;">🔐</span>
            <span>You must <strong><a href="login.php" style="color:#8B4513;">log in</a></strong> to ${action}.</span>
        </div>
    `;

    const resForm = document.getElementById('reservationForm');
    if (resForm && !resForm.previousElementSibling?.classList.contains('section-login-banner')) {
        resForm.insertAdjacentHTML('beforebegin', bannerHTML('make a reservation'));
        resForm.style.opacity = '0.45';
        resForm.style.pointerEvents = 'none';
        
        const submitBtn = resForm.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'auto';
            submitBtn.style.opacity = '1';
            submitBtn.onclick = (e) => { e.preventDefault(); requireLogin('make a reservation'); };
        }
    }

    const queueForm = document.getElementById('queueForm');
    if (queueForm && !queueForm.previousElementSibling?.classList.contains('section-login-banner')) {
        queueForm.insertAdjacentHTML('beforebegin', bannerHTML('join the queue'));
        queueForm.style.opacity = '0.45';
        queueForm.style.pointerEvents = 'none';
        
        const submitBtn = queueForm.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.style.pointerEvents = 'auto';
            submitBtn.style.opacity = '1';
            submitBtn.onclick = (e) => { e.preventDefault(); requireLogin('join the queue'); };
        }
    }
}

function requireLogin(actionLabel) {
    showLoginRequired(actionLabel);
    return false;
}

function showLoginRequired(action) {
    document.getElementById('loginRequiredOverlay')?.remove();

    const overlay = document.createElement('div');
    overlay.id = 'loginRequiredOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9000;display:flex;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2.5rem;max-width:420px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
            <div style="font-size:3rem;margin-bottom:1rem;">🔐</div>
            <h3 style="font-size:1.5rem;color:#8B4513;margin-bottom:0.5rem;">Login Required</h3>
            <p style="color:#6B4E3D;margin-bottom:1.25rem;">You need to be logged in to <strong>${action}</strong>.</p>
            <div style="display:flex;gap:1rem;justify-content:center;margin-bottom:1rem;">
                <a href="login.php" class="btn btn-primary" style="background:#8B4513;color:#fff;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;">Login Now</a>
                <button class="btn btn-secondary" onclick="document.getElementById('loginRequiredOverlay').remove()" style="background:transparent;border:2px solid #8B4513;color:#8B4513;padding:0.75rem 1.5rem;border-radius:8px;cursor:pointer;">Cancel</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}

// ══════════════════════════════════════════════════════════════
// TABLES MANAGEMENT
// ══════════════════════════════════════════════════════════════

function renderTablesGrid() {
    const grid = document.getElementById('tablesGrid');
    if (!grid) return;
    const tablelist = getTables();
    
    if (!tablelist || tablelist.length === 0) {
        grid.innerHTML = '<p style="text-align:center;color:#9b8070;padding:2rem;">No tables available.</p>';
        return;
    }
    
    grid.innerHTML = tablelist.map(t => {
        const isAvailable = t.status === 'available';
        const isSelected = selectedTableId === t.table_id;
        
        let statusClass = '';
        let statusText = '';
        let statusIcon = '';
        
        switch(t.status) {
            case 'available':
                statusClass = 'available';
                statusText = 'Available';
                statusIcon = '🟢';
                break;
            case 'occupied':
                statusClass = 'occupied';
                statusText = 'Occupied';
                statusIcon = '🔴';
                break;
            case 'reserved':
                statusClass = 'reserved';
                statusText = 'Reserved';
                statusIcon = '🔵';
                break;
            default:
                statusClass = 'unavailable';
                statusText = t.status;
                statusIcon = '⚫';
        }
        
        return `
            <div class="table-item ${!isAvailable ? 'unavailable' : ''} ${isSelected ? 'selected' : ''}"
                 data-table-id="${t.table_id}" 
                 onclick="${isAvailable ? `selectTable(${t.table_id})` : 'return false;'}"
                 style="${!isAvailable ? 'cursor: not-allowed; opacity: 0.7; background: #f5f5f5;' : 'cursor: pointer;'}">
                <div class="table-info">
                    <span style="font-weight:bold;font-size:1.1rem;">${escapeHtml(t.table_number)}</span>
                    <small style="display:block;color:#6B4E3D;">👥 Capacity: ${t.capacity} ${t.capacity === 1 ? 'person' : 'people'}</small>
                </div>
                <span class="table-status ${statusClass}" style="padding:0.25rem 0.75rem;border-radius:20px;font-size:0.8rem;font-weight:600;">${statusIcon} ${statusText}</span>
            </div>
        `;
    }).join('');
    
    updateHeroStats();
}

function selectTable(tableId) {
    if (!isLoggedIn()) { requireLogin('select a table'); return; }
    
    const tables = getTables();
    const t = tables.find(t => t.table_id === tableId);
    
    if (!t) {
        showNotification('Table not found.', 'error');
        return;
    }
    
    if (t.status !== 'available') {
        let msg = t.status === 'occupied' ? 'This table is currently occupied.' : 
                  t.status === 'reserved' ? 'This table is already reserved.' : 
                  `This table is ${t.status}.`;
        showNotification(msg, 'error');
        return;
    }
    
    selectedTableId = tableId;
    renderTablesGrid();
    
    const old = document.getElementById('selectedTableBadge');
    if (old) old.remove();
    
    const form = document.getElementById('reservationForm');
    if (form) {
        const badge = document.createElement('div');
        badge.id = 'selectedTableBadge';
        badge.style.cssText = 'background:linear-gradient(135deg,#d1fae5,#ecfdf5);border:2px solid #10b981;border-radius:10px;padding:0.85rem 1.2rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.75rem;';
        badge.innerHTML = `
            <span style="font-size:1.5rem;">✅</span>
            <div>
                <div style="font-weight:700;color:#065f46;">${escapeHtml(t.table_number)} Selected</div>
                <small style="color:#059669;">Capacity: ${t.capacity} people</small>
            </div>
        `;
        form.insertBefore(badge, form.firstChild);
    }
    
    const guestsSelect = document.getElementById('res-guests');
    if (guestsSelect) {
        guestsSelect.onchange = function() {
            const selectedGuests = parseInt(this.value);
            if (selectedGuests > t.capacity) {
                showNotification(`⚠️ This table only seats ${t.capacity} guests.`, 'warning');
            }
        };
    }
}

function updateHeroStats() {
    const availableTables = getTables().filter(t => t.status === 'available').length;
    const queueLength = getQueue().filter(q => q.status === 'waiting').length;
    const avgWaitTime = queueLength * 15;
    
    const availableStat = document.querySelector('.stat-card:first-child .stat-number');
    const queueStat = document.querySelector('.stat-card:nth-child(2) .stat-number');
    const waitStat = document.querySelector('.stat-card:last-child .stat-number');
    
    if (availableStat) availableStat.textContent = availableTables;
    if (queueStat) queueStat.textContent = queueLength;
    if (waitStat) waitStat.textContent = `~${avgWaitTime}`;
}

// ══════════════════════════════════════════════════════════════
// MENU & CART
// ══════════════════════════════════════════════════════════════

function displayMenuItems(category) {
    const grid = document.getElementById('menuGrid');
    if (!grid) return;
    const items = getMenuItems().filter(i =>
        i.is_available && (category === 'all' || i.category === category)
    );

    if (items.length === 0) {
        grid.innerHTML = '<p style="text-align:center;color:#9b8070;padding:3rem;">No items in this category.</p>';
        return;
    }

    if (!isLoggedIn()) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;background:linear-gradient(135deg,#fff8f0,#fdf3e7);border:2px dashed #D4A574;border-radius:20px;padding:2rem;text-align:center;margin-bottom:1rem;">
                <div style="font-size:2.5rem;margin-bottom:0.75rem;">🔐</div>
                <h3 style="color:#8B4513;font-size:1.3rem;margin-bottom:0.5rem;">Login to Order</h3>
                <p style="color:#6B4E3D;margin-bottom:1.25rem;">You need to <strong>log in</strong> to add items to your cart.</p>
                <a href="login.php" class="btn btn-primary" style="background:#8B4513;color:#fff;padding:0.75rem 1.5rem;border-radius:8px;text-decoration:none;">Login to Start Ordering</a>
            </div>
            ${items.map(item => `
                <div class="menu-item" style="opacity:0.75;pointer-events:none;">
                    <div class="menu-item-image" style="height:150px;background:#f5f0ea;display:flex;align-items:center;justify-content:center;">
                        <img src="${item.image}" alt="${item.item_name}" style="max-width:100%;max-height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=&quot;font-size:4rem;&quot;>${item.emoji}</span>';">
                    </div>
                    <div class="menu-item-content" style="padding:1rem;">
                        <h3 style="color:#8B4513;">${escapeHtml(item.item_name)}</h3>
                        <p style="color:#D4A574;font-weight:700;">$${item.price.toFixed(2)}</p>
                        <button class="add-to-cart add-to-cart-locked" style="background:#f0f0f0;border:2px dashed #D4A574;color:#9b8070;padding:0.5rem;border-radius:8px;width:100%;">🔐 Login to Order</button>
                    </div>
                </div>
            `).join('')}
        `;
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="menu-item" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">
            <div class="menu-item-image" style="height:150px;background:#f5f0ea;display:flex;align-items:center;justify-content:center;">
                <img src="${item.image}" alt="${item.item_name}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=&quot;font-size:4rem;&quot;>${item.emoji}</span>';">
            </div>
            <div class="menu-item-content" style="padding:1rem;">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.5rem;">
                    <h3 style="color:#8B4513;font-size:1.1rem;">${escapeHtml(item.item_name)}</h3>
                    <span style="color:#D4A574;font-weight:700;">$${item.price.toFixed(2)}</span>
                </div>
                <p style="color:#9b8070;font-size:0.85rem;margin-bottom:1rem;">${escapeHtml(item.description)}</p>
                <div style="display:flex;gap:0.5rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;border:1px solid #E5D4C1;border-radius:8px;padding:0.25rem;">
                        <button class="quantity-btn" onclick="changeQty(${item.item_id}, -1)" style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;font-size:1.2rem;">-</button>
                        <span class="quantity-display" id="qty-${item.item_id}" style="min-width:30px;text-align:center;">1</span>
                        <button class="quantity-btn" onclick="changeQty(${item.item_id}, 1)" style="width:28px;height:28px;border:none;background:transparent;cursor:pointer;font-size:1.2rem;">+</button>
                    </div>
                    <button class="add-to-cart" onclick="addToCart(${item.item_id})" style="flex:1;background:#8B4513;color:#fff;border:none;border-radius:8px;padding:0.5rem;cursor:pointer;">Add to Cart</button>
                </div>
            </div>
        </div>
    `).join('');
}

function changeQty(itemId, delta) {
    const el = document.getElementById(`qty-${itemId}`);
    if (el) el.textContent = Math.max(1, parseInt(el.textContent) + delta);
}

function addToCart(itemId) {
    if (!isLoggedIn()) { requireLogin('add items to cart'); return; }
    const item = getMenuItems().find(i => i.item_id === itemId);
    if (!item) return;
    const qty = parseInt(document.getElementById(`qty-${itemId}`).textContent);
    const existing = cart.find(i => i.item_id === itemId);
    if (existing) {
        existing.quantity += qty;
    } else {
        cart.push({ ...item, quantity: qty });
    }
    updateCartUI();
    showNotification(`${escapeHtml(item.item_name)} added to cart! 🛒`, 'success');
}

function updateCartUI() {
    const summary = document.getElementById('cartSummary');
    if (!summary) return;
    
    if (cart.length === 0) {
        summary.style.display = 'none';
        return;
    }
    
    summary.style.display = 'flex';
    summary.style.flexDirection = 'column';
    summary.style.position = 'fixed';
    summary.style.right = '20px';
    summary.style.bottom = '20px';
    summary.style.width = '350px';
    summary.style.maxHeight = '500px';
    summary.style.background = '#fff';
    summary.style.borderRadius = '12px';
    summary.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
    summary.style.zIndex = '999';
    
    const itemsEl = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');
    const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    
    itemsEl.innerHTML = cart.map(item => `
        <div style="display:flex;justify-content:space-between;padding:0.75rem 0;border-bottom:1px solid #f0f0f0;">
            <div>
                <strong>${escapeHtml(item.item_name)}</strong>
                <small> x${item.quantity}</small>
            </div>
            <div>
                <span>$${(item.price * item.quantity).toFixed(2)}</span>
                <button onclick="removeFromCart(${item.item_id})" style="background:none;border:none;color:#dc2626;cursor:pointer;margin-left:0.5rem;">✕</button>
            </div>
        </div>
    `).join('');
    totalEl.textContent = `$${total.toFixed(2)}`;
    
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.onclick = handleCheckout;
        checkoutBtn.textContent = `Proceed to Payment — $${total.toFixed(2)}`;
    }
}

function removeFromCart(itemId) {
    cart = cart.filter(i => i.item_id !== itemId);
    updateCartUI();
}

// ══════════════════════════════════════════════════════════════
// RESERVATION - API INTEGRATED (Pending until admin approval)
// ══════════════════════════════════════════════════════════════

async function handleReservation(e) {
    e.preventDefault();
    if (!isLoggedIn()) { requireLogin('make a reservation'); return; }
    if (!selectedTableId) {
        showNotification('Please select a table first.', 'error');
        return;
    }

    const fd = new FormData(e.target);
    const tables = getTables();
    const tbl = tables.find(t => t.table_id === selectedTableId);
    const session = getSession();

    const name = fd.get('name')?.trim();
    const email = fd.get('email')?.trim();
    const phone = fd.get('phone')?.trim();
    const date = fd.get('date');
    const time = fd.get('time');
    const guests = parseInt(fd.get('guests'));

    if (!name || !email || !date || !time || !guests) {
        showNotification('Please fill all fields.', 'error');
        return;
    }

    if (!tbl || tbl.status !== 'available') {
        showNotification('Table no longer available.', 'error');
        selectedTableId = null;
        renderTablesGrid();
        return;
    }

    if (guests > tbl.capacity) {
        showNotification(`Table only seats ${tbl.capacity} guests.`, 'error');
        return;
    }

    const userId = session.user_id;
    console.log('[Reservation] Using user_id from session:', userId);

    const reservationData = {
        user_id: userId,
        customer_name: name,
        customer_email: email,
        customer_phone: phone,
        table_id: selectedTableId,
        reservation_date: date,
        reservation_time: time,
        number_of_guests: guests,
        special_requests: fd.get('notes') || ''
    };

    const submitBtn = e.target.querySelector('[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Submitting...';
    }

    try {
        const response = await fetch('reservations.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(reservationData)
        });
        
        const result = await response.json();
        
        if (result.success && result.data?.reservation) {
            const savedReservation = result.data.reservation;
            console.log('[API] Reservation saved to database:', savedReservation);
            
            const reservations = getReservations();
            reservations.push(savedReservation);
            saveReservations(reservations);
            
            const freshTables = getTables();
            const table = freshTables.find(t => t.table_id === selectedTableId);
            if (table) {
                table.status = 'reserved';
                saveTables(freshTables);
            }
            
            document.getElementById('selectedTableBadge')?.remove();
            e.target.reset();
            prefillFormsFromSession();
            selectedTableId = null;
            renderTablesGrid();
            
            // Show message that reservation is pending admin approval
            showNotification(`✅ Reservation request submitted! Waiting for admin approval. You'll be notified when confirmed.`, 'success');
        } else {
            showNotification(result.message || 'Failed to create reservation', 'error');
        }
    } catch (error) {
        console.error('[API] Error saving reservation:', error);
        showNotification('Network error. Please check your connection.', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
}

// ══════════════════════════════════════════════════════════════
// QUEUE - API INTEGRATED (Waiting until admin seats)
// ══════════════════════════════════════════════════════════════

function renderQueueStatus() {
    const el = document.getElementById('queueList');
    if (!el) return;
    const queue = getQueue().filter(q => q.status === 'waiting');
    
    const countEl = document.querySelector('.queue-stat:first-child .queue-number');
    const waitEl = document.querySelector('.queue-stat:last-child .queue-number');
    if (countEl) countEl.textContent = queue.length;
    if (waitEl) waitEl.textContent = `~${queue.length * 15}`;
    
    if (queue.length === 0) {
        el.innerHTML = '<p style="text-align:center;color:#9b8070;padding:2rem;">No one waiting — walk right in! 🎉</p>';
        return;
    }
    
    el.innerHTML = queue.map((entry, i) => `
        <div style="display:flex;align-items:center;gap:1rem;padding:1rem;background:#faf3eb;border-radius:8px;margin-bottom:0.75rem;">
            <div style="width:40px;height:40px;background:#8B4513;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;">${i + 1}</div>
            <div style="flex:1;">
                <strong style="color:#8B4513;">${escapeHtml(entry.customer_name)}</strong>
                <small style="display:block;color:#9b8070;">Party of ${entry.party_size}</small>
            </div>
            <div style="color:#D4A574;font-weight:600;">~${entry.estimated_wait_time} min</div>
        </div>
    `).join('');
}

async function handleQueueJoin(e) {
    e.preventDefault();
    if (!isLoggedIn()) { requireLogin('join the queue'); return; }

    const fd = new FormData(e.target);
    const name = fd.get('name')?.trim();
    const phone = fd.get('phone')?.trim();
    const party = parseInt(fd.get('party'));
    const session = getSession();

    if (!name || !party) {
        showNotification('Please enter name and party size.', 'error');
        return;
    }

    const userId = session.user_id;
    console.log('[Queue] Using user_id from session:', userId);

    const queueData = {
        customer_name: name,
        customer_phone: phone,
        party_size: party,
        user_id: userId
    };

    const submitBtn = e.target.querySelector('[type="submit"]');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Joining...';
    }

    try {
        const response = await fetch('queue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(queueData)
        });
        
        const result = await response.json();
        
        if (result.success && result.data?.entry) {
            const savedEntry = result.data.entry;
            console.log('[API] Queue entry saved to database:', savedEntry);
            
            const queue = getQueue();
            queue.push(savedEntry);
            saveQueue(queue);
            
            renderQueueStatus();
            e.target.reset();
            prefillFormsFromSession();
            
            // Show message that they're in queue and will be notified when admin seats them
            showNotification(`✅ You are #${savedEntry.position} in queue! We'll notify you when your table is ready.`, 'success');
        } else {
            showNotification(result.message || 'Failed to join queue', 'error');
        }
    } catch (error) {
        console.error('[API] Error saving queue entry:', error);
        showNotification('Network error. Please check your connection.', 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
}

// ══════════════════════════════════════════════════════════════
// PAYMENT & ORDERS - API INTEGRATED (Pending until admin updates)
// ══════════════════════════════════════════════════════════════

function handleCheckout() {
    if (!isLoggedIn()) { requireLogin('checkout'); return; }
    if (cart.length === 0) {
        showNotification('Your cart is empty.', 'error');
        return;
    }
    openPaymentModal();
}

function openPaymentModal() {
    const session = getSession();
    if (!session) return;
    
    const total = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    
    const modal = document.createElement('div');
    modal.id = 'paymentModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9000;display:flex;align-items:center;justify-content:center;padding:1rem;';
    modal.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2rem;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:2px solid #E5D4C1;">
                <h2 style="color:#8B4513;">💳 Payment</h2>
                <button onclick="document.getElementById('paymentModal').remove()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">✕</button>
            </div>
            
            <div style="background:#faf3eb;border-radius:8px;padding:1rem;margin-bottom:1.25rem;">
                <h4 style="color:#8B4513;margin-bottom:0.75rem;">Order Summary</h4>
                ${cart.map(i => `
                    <div style="display:flex;justify-content:space-between;font-size:0.9rem;color:#6B4E3D;padding:0.2rem 0;">
                        <span>${i.quantity}× ${escapeHtml(i.item_name)}</span>
                        <span>$${(i.price * i.quantity).toFixed(2)}</span>
                    </div>
                `).join('')}
                <div style="display:flex;justify-content:space-between;border-top:1px solid #E5D4C1;margin-top:0.5rem;padding-top:0.5rem;color:#8B4513;font-weight:700;">
                    <strong>Total</strong>
                    <strong>$${total.toFixed(2)}</strong>
                </div>
            </div>
            
            <div style="background:rgba(139,69,19,0.07);border-radius:8px;padding:0.75rem 1rem;margin-bottom:1.25rem;">
                <p>👤 <strong>${escapeHtml(session.full_name)}</strong> | ${escapeHtml(session.email)}</p>
                <p style="font-size:0.85rem;color:#6B4E3D;">User ID: ${session.user_id}</p>
            </div>
            
            <div class="payment-method-section">
                <h4 style="color:#8B4513;margin-bottom:0.75rem;">Payment Method</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1rem;">
                    <label style="display:flex;align-items:center;gap:0.5rem;border:2px solid #E5D4C1;border-radius:8px;padding:0.6rem;cursor:pointer;">
                        <input type="radio" name="payMethod" value="credit_card" checked> 💳 Credit Card
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;border:2px solid #E5D4C1;border-radius:8px;padding:0.6rem;cursor:pointer;">
                        <input type="radio" name="payMethod" value="cash"> 💵 Pay at Counter
                    </label>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="processPayment(${total})" style="width:100%;background:#8B4513;color:#fff;border:none;padding:0.85rem;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer;">
                Pay $${total.toFixed(2)} Now
            </button>
            <p style="font-size:0.75rem;color:#9b8070;text-align:center;margin-top:0.75rem;">You'll be notified when admin updates your order status.</p>
        </div>
    `;
    document.body.appendChild(modal);
}

async function processPayment(total) {
    const method = document.querySelector('input[name="payMethod"]:checked')?.value || 'credit_card';
    const session = getSession();
    
    if (!session) {
        showNotification('Session expired. Please login again.', 'error');
        return;
    }
    
    const userId = session.user_id;
    console.log('[Payment] Using user_id from session:', userId);
    
    const orderData = {
        user_id: userId,
        customer_name: session.full_name,
        customer_email: session.email,
        customer_phone: session.phone || '',
        order_type: 'pre_order',
        total_amount: parseFloat(total),
        payment_method: method,
        items: cart.map(i => ({
            item_id: i.item_id,
            item_name: i.item_name,
            quantity: i.quantity,
            unit_price: i.price,
            subtotal: i.price * i.quantity
        }))
    };
    
    const payBtn = document.querySelector('#paymentModal .btn-primary');
    const originalText = payBtn?.textContent;
    if (payBtn) {
        payBtn.disabled = true;
        payBtn.textContent = '⏳ Processing...';
    }
    
    try {
        const response = await fetch('orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        
        const result = await response.json();
        
        if (result.success && result.data?.order) {
            const savedOrder = result.data.order;
            console.log('[API] Order saved to database:', savedOrder);
            
            const orders = getOrders();
            orders.push(savedOrder);
            saveOrders(orders);
            
            document.getElementById('paymentModal')?.remove();
            cart = [];
            updateCartUI();
            
            showPaymentSuccess(savedOrder);
            
            // Refresh data to get updated notifications
            setTimeout(() => {
                loadDataFromDatabase();
                refreshNotifications();
            }, 1000);
        } else {
            showNotification(result.message || 'Failed to process order', 'error');
            if (payBtn) {
                payBtn.disabled = false;
                payBtn.textContent = originalText;
            }
        }
    } catch (error) {
        console.error('[API] Error saving order:', error);
        showNotification('Network error. Please check your connection.', 'error');
        if (payBtn) {
            payBtn.disabled = false;
            payBtn.textContent = originalText;
        }
    }
}

function showPaymentSuccess(order) {
    const total = order.items.reduce((s, i) => s + i.unit_price * i.quantity, 0);
    
    const modal = document.createElement('div');
    modal.id = 'paymentSuccess';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9500;display:flex;align-items:center;justify-content:center;padding:1rem;';
    modal.innerHTML = `
        <div style="background:#fff;border-radius:16px;padding:2.5rem;max-width:440px;width:100%;text-align:center;">
            <div style="font-size:3.5rem;margin-bottom:1rem;">✅</div>
            <h2 style="color:#8B4513;margin-bottom:0.5rem;">Payment Successful!</h2>
            <div style="background:#d1fae5;border:1px solid #10b981;border-radius:8px;padding:0.75rem 1rem;margin:0.75rem 0;">
                <p style="font-weight:700;color:#065f46;">Order Reference: ${order.order_ref}</p>
                <p>Amount: <strong>$${total.toFixed(2)}</strong></p>
                <p style="font-size:0.8rem;margin-top:0.5rem;">You will be notified when admin updates your order status!</p>
            </div>
            <p style="font-size:0.85rem;color:#9b8070;margin:0.75rem 0;">🍳 Your order has been sent to the kitchen!</p>
            <button class="btn btn-primary" onclick="document.getElementById('paymentSuccess').remove()" style="background:#8B4513;color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:8px;cursor:pointer;">Done</button>
        </div>
    `;
    document.body.appendChild(modal);
}

// ══════════════════════════════════════════════════════════════
// EVENT LISTENERS & HELPERS
// ══════════════════════════════════════════════════════════════

function initializeEventListeners() {
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', handleReservation);
    }
    
    const queueForm = document.getElementById('queueForm');
    if (queueForm) {
        queueForm.addEventListener('submit', handleQueueJoin);
    }
    
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            displayMenuItems(this.dataset.category);
        });
    });
    
    const closeCart = document.querySelector('.close-cart');
    if (closeCart) {
        closeCart.addEventListener('click', () => {
            document.getElementById('cartSummary').style.display = 'none';
        });
    }
    
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
    
    document.addEventListener('click', function(e) {
        if (e.target.id === 'loginRequiredOverlay') e.target.remove();
        if (e.target.id === 'paymentModal') e.target.remove();
        if (e.target.id === 'paymentSuccess') e.target.remove();
    });
}

function showNotification(message, type) {
    const existing = document.querySelectorAll('.notification-toast');
    existing.forEach(n => n.remove());
    
    const n = document.createElement('div');
    n.className = 'notification-toast';
    n.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        max-width: 400px;
        background: ${type === 'success' ? '#d1fae5' : type === 'error' ? '#fee2e2' : '#fef3c7'};
        color: ${type === 'success' ? '#065f46' : type === 'error' ? '#991b1b' : '#92400e'};
        padding: 1rem 1.25rem;
        border-radius: 8px;
        border-left: 4px solid ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-weight: 500;
    `;
    n.textContent = message;
    document.body.appendChild(n);
    
    setTimeout(() => {
        n.style.transition = 'opacity 0.3s';
        n.style.opacity = '0';
        setTimeout(() => n.remove(), 300);
    }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    if (nav) {
        nav.style.boxShadow = window.pageYOffset > 100
            ? '0 4px 12px rgba(139,69,19,0.15)'
            : '0 1px 3px rgba(139,69,19,0.08)';
    }
});
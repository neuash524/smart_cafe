// ============================================================
// Smart Café — api.js
// Every function here calls a PHP endpoint that writes to MySQL.
// localStorage is kept as a read-cache only — the database is
// always the authoritative source.
//
// HOW IT WORKS:
//   1. JS calls apiRegister() / apiLogin() / apiSaveReservation() etc.
//   2. That PHP file runs an INSERT/UPDATE against MySQL
//   3. The PHP response is returned as JSON
//   4. We also update localStorage so the page doesn't need a reload
//
// Set API_BASE to the folder where your PHP files live on the server.
// Default: same domain, /api/ subfolder (e.g. http://localhost/smart-cafe/api)
// ============================================================

const API_BASE = '.';   // PHP files are in the same root folder as index.html

// ── Low-level fetch wrapper ───────────────────────────────────
async function _api(endpoint, method, body) {
    const opts = {
        method:  method || 'GET',
        headers: { 'Content-Type': 'application/json' }
    };
    if (body) opts.body = JSON.stringify(body);

    try {
        const res  = await fetch(`${API_BASE}/${endpoint}`, opts);
        const data = await res.json();
        return data;                      // { success, message, data: {...} }
    } catch (err) {
        // Network error or PHP not running — fall back to localStorage only
        console.warn(`[API] ${endpoint} failed (offline?):`, err.message);
        return { success: false, message: 'offline', offline: true };
    }
}

// ══════════════════════════════════════════════════════════════
// AUTH
// ══════════════════════════════════════════════════════════════

/**
 * Register a new customer.
 * → PHP INSERT INTO users
 * → Updates localStorage users array
 * Returns { success, message, data: { user } }
 */
async function apiRegister(fullName, email, phone, password) {
    const res = await _api('register.php', 'POST', { full_name: fullName, email, phone, password });

    if (res.success && res.data?.user) {
        // Sync into localStorage users list
        const users  = getUsers();
        const exists = users.find(u => u.email.toLowerCase() === email.toLowerCase());
        if (!exists) {
            users.push({
                user_id:    res.data.user.user_id,
                email:      res.data.user.email,
                full_name:  res.data.user.full_name,
                phone:      res.data.user.phone,
                user_type:  'customer',
                password:   password,      // keep plain-text copy for offline fallback
                created_at: res.data.user.created_at,
                is_active:  true
            });
            saveUsers(users);
        }
    } else if (res.offline) {
        // Offline fallback — write to localStorage only
        return registerUser(fullName, email, phone, password);
    }

    return res;
}

/**
 * Login — authenticate against MySQL.
 * Falls back to localStorage if API is offline.
 * Returns { success, message, data: { user } }
 */
async function apiLogin(email, password) {
    const res = await _api('login.php', 'POST', { email, password });

    if (res.success && res.data?.user) {
        return res;
    }

    if (res.offline) {
        // Offline fallback
        const user = authenticateUser(email, password);
        if (user) {
            return { success: true, message: 'OK', data: { user } };
        }
        return { success: false, message: 'Invalid email or password.' };
    }

    return res;
}

// ══════════════════════════════════════════════════════════════
// RESERVATIONS
// ══════════════════════════════════════════════════════════════

/**
 * Save a reservation.
 * → PHP INSERT INTO reservations + UPDATE cafe_tables
 * → Syncs localStorage reservations
 */
async function apiSaveReservation(obj) {
    const res = await _api('reservations.php', 'POST', obj);

    if (res.success && res.data?.reservation) {
        // Sync into localStorage
        const list = getReservations();
        list.push(res.data.reservation);
        saveReservations(list);

        // Mark table reserved in localStorage too
        const tables = getTables();
        const tbl    = tables.find(t => t.table_id === obj.table_id);
        if (tbl) { tbl.status = 'reserved'; saveTables(tables); }

        return { success: true, reservation: res.data.reservation };
    }

    if (res.offline) {
        // Offline fallback — localStorage only
        const entry = addReservation(obj);
        return { success: true, reservation: entry, offline: true };
    }

    return { success: false, message: res.message };
}

/**
 * Update reservation status (admin).
 * → PHP UPDATE reservations SET status
 */
async function apiUpdateReservation(id, status) {
    const res = await _api(`reservations.php?id=${id}`, 'PATCH', { status });

    // Always update localStorage too
    const list = getReservations();
    const r    = list.find(r => r.reservation_id == id);
    if (r) {
        r.status = status;
        saveReservations(list);
        if (status === 'cancelled') {
            const tables = getTables();
            const tbl    = tables.find(t => t.table_id === r.table_id);
            if (tbl) { tbl.status = 'available'; saveTables(tables); }
        }
    }

    return res;
}

/**
 * Load all reservations from MySQL into localStorage.
 * Called on admin panel load to keep cache fresh.
 */
async function apiLoadReservations() {
    const res = await _api('reservations.php', 'GET');
    if (res.success && res.data?.reservations) {
        saveReservations(res.data.reservations);
    }
    return getReservations();
}

// ══════════════════════════════════════════════════════════════
// ORDERS
// ══════════════════════════════════════════════════════════════

/**
 * Place a paid order.
 * → PHP INSERT INTO orders + order_items + payments
 * → Syncs localStorage orders
 */
async function apiPlaceOrder(obj) {
    const res = await _api('orders.php', 'POST', obj);

    if (res.success && res.data?.order) {
        const orders = getOrders();
        orders.push(res.data.order);
        saveOrders(orders);
        return { success: true, order: res.data.order };
    }

    if (res.offline) {
        const entry = addOrder(obj);
        return { success: true, order: entry, offline: true };
    }

    return { success: false, message: res.message };
}

/**
 * Update order kitchen status (admin).
 */
async function apiUpdateOrder(id, status) {
    const res = await _api(`orders.php?id=${id}`, 'PATCH', { status });

    const orders = getOrders();
    const o      = orders.find(o => o.order_id == id);
    if (o) { o.status = status; saveOrders(orders); }

    return res;
}

/**
 * Load all orders from MySQL into localStorage.
 */
async function apiLoadOrders() {
    const res = await _api('orders.php', 'GET');
    if (res.success && res.data?.orders) {
        saveOrders(res.data.orders);
    }
    return getOrders();
}

// ══════════════════════════════════════════════════════════════
// QUEUE
// ══════════════════════════════════════════════════════════════

/**
 * Join queue.
 * → PHP INSERT INTO queue
 */
async function apiJoinQueue(obj) {
    const res = await _api('queue.php', 'POST', obj);

    if (res.success && res.data?.entry) {
        const queue = getQueue();
        queue.push(res.data.entry);
        saveQueue(queue);
        return { success: true, entry: res.data.entry };
    }

    if (res.offline) {
        const entry = addToQueue(obj);
        return { success: true, entry, offline: true };
    }

    return { success: false, message: res.message };
}

/**
 * Update queue entry status (admin).
 */
async function apiUpdateQueue(id, status) {
    const res = await _api(`queue.php?id=${id}`, 'PATCH', { status });

    const queue = getQueue();
    const e     = queue.find(q => q.queue_id == id);
    if (e) { e.status = status; saveQueue(queue); }

    return res;
}

/**
 * Load live queue from MySQL.
 */
async function apiLoadQueue() {
    const res = await _api('queue.php', 'GET');
    if (res.success && res.data?.queue) {
        saveQueue(res.data.queue);
    }
    return getQueue();
}

// ══════════════════════════════════════════════════════════════
// USERS  (admin panel)
// ══════════════════════════════════════════════════════════════

/**
 * Load all customers from MySQL — used by admin Users section.
 */
async function apiLoadUsers(search) {
    const qs  = search ? `?search=${encodeURIComponent(search)}` : '';
    const res = await _api(`users.php${qs}`, 'GET');

    if (res.success && res.data?.users) {
        // Merge into localStorage users (keep passwords for offline auth)
        const stored = getUsers();
        res.data.users.forEach(u => {
            const existing = stored.find(s => s.user_id == u.user_id);
            if (existing) {
                // Update stats fields
                existing.reservation_count = u.reservation_count;
                existing.order_count       = u.order_count;
                existing.total_spent       = u.total_spent;
                existing.is_active         = u.is_active;
                existing.last_login        = u.last_login;
            } else {
                stored.push(u);
            }
        });
        saveUsers(stored);
        return res.data.users;
    }

    if (res.offline) {
        return getUsers().filter(u => u.user_type !== 'admin');
    }

    return [];
}

/**
 * Toggle user active/inactive status (admin).
 */
async function apiToggleUser(id, isActive) {
    const res = await _api(`users.php?id=${id}`, 'PATCH', { is_active: isActive });

    const users = getUsers();
    const u     = users.find(u => u.user_id == id);
    if (u) { u.is_active = isActive; saveUsers(users); }

    return res;
}

// ══════════════════════════════════════════════════════════════
// CHECK DUPLICATE EMAIL  (used on signup form live check)
// ══════════════════════════════════════════════════════════════
async function apiCheckEmail(email) {
    // First check localStorage (instant)
    const localExists = getUsers().find(u => u.email.toLowerCase() === email.toLowerCase());
    if (localExists) return { exists: true };

    // Then check MySQL
    const res = await _api(`register.php`, 'POST', {
        email, full_name: '__check__', password: '__check__', _check_only: true
    });

    // If error is "already exists" → email taken
    if (!res.success && res.message && res.message.includes('already exists')) {
        return { exists: true };
    }
    return { exists: false };
}

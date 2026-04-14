// ============================================================
// Smart Café — cafe-data.js (v14 - Enhanced Session Sync)
// Now properly syncs with database user_id for notifications
// ============================================================

const STORAGE_KEYS = {
    USERS: 'sc_users',
    RESERVATIONS: 'sc_reservations',
    QUEUE: 'sc_queue',
    ORDERS: 'sc_orders',
    MENU_ITEMS: 'sc_menu_items',
    TABLES: 'sc_tables',
    SESSION: 'sc_session',
    SEED_VER: 'sc_seed_ver',
    TABLE_UPDATE_FLAG: 'sc_table_update_flag',
    SESSION_SYNCED: 'sc_session_synced'  // Track if session has been synced
};

const CURRENT_SEED_VERSION = '15';

function _load(key, seed) {
    try {
        const r = localStorage.getItem(key);
        if (!r) return seed ? JSON.parse(JSON.stringify(seed)) : null;
        return JSON.parse(r);
    } catch (e) { 
        console.warn(`[Storage] Load error for ${key}:`, e);
        return seed ? JSON.parse(JSON.stringify(seed)) : null; 
    }
}

function _save(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
        console.log(`[Storage] Saved ${key}:`, data);
    } catch (e) { console.warn('Storage save failed:', e); }
}

// Force notify all tabs about table changes
function _forceNotifyTableChange(tableId, newStatus, tableNumber) {
    const updateData = {
        type: 'table_update',
        tableId: tableId,
        tableNumber: tableNumber,
        newStatus: newStatus,
        timestamp: Date.now()
    };
    
    // Store in multiple keys to ensure all tabs receive it
    localStorage.setItem('sc_table_update', JSON.stringify(updateData));
    localStorage.setItem('sc_customer_ping', JSON.stringify({ type: 'sc_tables', ...updateData }));
    localStorage.setItem('sc_admin_ping', JSON.stringify({ type: 'sc_tables', ...updateData }));
    
    // Increment a counter to force storage event (value changes every time)
    const counter = parseInt(localStorage.getItem('sc_table_counter') || '0') + 1;
    localStorage.setItem('sc_table_counter', counter.toString());
    
    console.log('[Storage] Force notified all tabs about table change:', updateData);
}

function _today() {
    return new Date().toISOString().split('T')[0];
}

function _nowTime() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Seed Data
const SEED_USERS = [
    { user_id: 1, email: 'admin@smartcafe.com', full_name: 'Admin User', phone: '+1234567890', user_type: 'admin', password: 'admin123', created_at: '2026-01-01', is_active: true },
    { user_id: 2, email: 'aashish@example.com', full_name: 'Aashish Neupane', phone: '+1234567891', user_type: 'customer', password: 'customer123', created_at: '2026-01-15', is_active: true }
];

const DB_MENU_ITEMS_SEED = [
    { item_id: 1, category: 'breakfast', item_name: 'Classic Pancakes', price: 8.99, is_available: true, emoji: '🥞', image: 'img/menu/pancakes.jpg', description: 'Fluffy buttermilk pancakes' },
    { item_id: 2, category: 'breakfast', item_name: 'Eggs Benedict', price: 12.99, is_available: true, emoji: '🍳', image: 'img/menu/eggs-benedict.jpg', description: 'Poached eggs with hollandaise' },
    { item_id: 3, category: 'breakfast', item_name: 'French Toast', price: 9.99, is_available: true, emoji: '🍞', image: 'img/menu/french-toast.jpg', description: 'Thick-cut brioche' },
    { item_id: 4, category: 'breakfast', item_name: 'Avocado Toast', price: 10.99, is_available: true, emoji: '🥑', image: 'img/menu/avocado-toast.jpg', description: 'Smashed avocado on sourdough' },
    { item_id: 5, category: 'lunch', item_name: 'Caesar Salad', price: 11.99, is_available: true, emoji: '🥗', image: 'img/menu/caesar-salad.jpg', description: 'Crisp romaine with parmesan' },
    { item_id: 6, category: 'lunch', item_name: 'Club Sandwich', price: 13.99, is_available: true, emoji: '🥪', image: 'img/menu/club-sandwich.jpg', description: 'Triple-decker sandwich' },
    { item_id: 7, category: 'lunch', item_name: 'Beef Burger', price: 14.99, is_available: true, emoji: '🍔', image: 'img/menu/beef-burger.jpg', description: 'Angus beef patty' },
    { item_id: 8, category: 'lunch', item_name: 'Grilled Chicken', price: 15.99, is_available: true, emoji: '🍗', image: 'img/menu/grilled-chicken.jpg', description: 'Herb-marinated chicken breast' },
    { item_id: 9, category: 'beverages', item_name: 'Espresso', price: 3.50, is_available: true, emoji: '☕', image: 'img/menu/espresso.jpg', description: 'Rich Italian espresso' },
    { item_id: 10, category: 'beverages', item_name: 'Cappuccino', price: 4.50, is_available: true, emoji: '☕', image: 'img/menu/cappuccino.jpg', description: 'Espresso with steamed milk' },
    { item_id: 11, category: 'beverages', item_name: 'Iced Latte', price: 5.00, is_available: true, emoji: '🧊', image: 'img/menu/iced-latte.jpg', description: 'Cold espresso with milk' },
    { item_id: 12, category: 'beverages', item_name: 'Fresh Orange Juice', price: 4.00, is_available: true, emoji: '🍊', image: 'img/menu/orange-juice.jpg', description: 'Freshly squeezed' },
    { item_id: 13, category: 'desserts', item_name: 'Chocolate Cake', price: 6.99, is_available: true, emoji: '🍰', image: 'img/menu/chocolate-cake.jpg', description: 'Rich chocolate layers' },
    { item_id: 14, category: 'desserts', item_name: 'Tiramisu', price: 7.99, is_available: true, emoji: '🍮', image: 'img/menu/tiramisu.jpg', description: 'Classic Italian dessert' },
    { item_id: 15, category: 'desserts', item_name: 'Apple Pie', price: 6.50, is_available: true, emoji: '🥧', image: 'img/menu/apple-pie.jpg', description: 'Warm apple pie' },
    { item_id: 16, category: 'desserts', item_name: 'Cheesecake', price: 7.50, is_available: true, emoji: '🍰', image: 'img/menu/cheesecake.jpg', description: 'New York style' }
];

const DB_CAFE_TABLES_SEED = [
    { table_id: 1, table_number: 'Table 1', capacity: 2, status: 'available' },
    { table_id: 2, table_number: 'Table 2', capacity: 2, status: 'available' },
    { table_id: 3, table_number: 'Table 3', capacity: 4, status: 'available' },
    { table_id: 4, table_number: 'Table 4', capacity: 4, status: 'available' },
    { table_id: 5, table_number: 'Table 5', capacity: 6, status: 'available' },
    { table_id: 6, table_number: 'Table 6', capacity: 6, status: 'available' },
    { table_id: 7, table_number: 'Table 7', capacity: 8, status: 'available' },
    { table_id: 8, table_number: 'Table 8', capacity: 4, status: 'available' }
];

const DB_TOP_ITEMS = [
    { rank: 1, name: 'Cappuccino', sales: 245 },
    { rank: 2, name: 'Avocado Toast', sales: 198 },
    { rank: 3, name: 'Beef Burger', sales: 187 }
];

// Force seed on first load
(function _forceSeed() {
    const storedVer = localStorage.getItem(STORAGE_KEYS.SEED_VER);
    if (storedVer !== CURRENT_SEED_VERSION) {
        console.log('[SmartCafe] Seeding data v' + CURRENT_SEED_VERSION);
        
        if (!localStorage.getItem(STORAGE_KEYS.USERS)) {
            _save(STORAGE_KEYS.USERS, SEED_USERS);
        }
        if (!localStorage.getItem(STORAGE_KEYS.MENU_ITEMS)) {
            _save(STORAGE_KEYS.MENU_ITEMS, DB_MENU_ITEMS_SEED);
        }
        if (!localStorage.getItem(STORAGE_KEYS.TABLES)) {
            _save(STORAGE_KEYS.TABLES, DB_CAFE_TABLES_SEED);
        }
        if (!localStorage.getItem(STORAGE_KEYS.RESERVATIONS)) {
            _save(STORAGE_KEYS.RESERVATIONS, []);
        }
        if (!localStorage.getItem(STORAGE_KEYS.QUEUE)) {
            _save(STORAGE_KEYS.QUEUE, []);
        }
        if (!localStorage.getItem(STORAGE_KEYS.ORDERS)) {
            _save(STORAGE_KEYS.ORDERS, []);
        }
        
        _save(STORAGE_KEYS.SEED_VER, CURRENT_SEED_VERSION);
    }
})();

// Public API
function getUsers() { return _load(STORAGE_KEYS.USERS, SEED_USERS) || []; }
function saveUsers(d) { _save(STORAGE_KEYS.USERS, d); }
function getReservations() { return _load(STORAGE_KEYS.RESERVATIONS, []) || []; }
function saveReservations(d) { _save(STORAGE_KEYS.RESERVATIONS, d); }
function getQueue() { return _load(STORAGE_KEYS.QUEUE, []) || []; }
function saveQueue(d) { _save(STORAGE_KEYS.QUEUE, d); }
function getOrders() { return _load(STORAGE_KEYS.ORDERS, []) || []; }
function saveOrders(d) { _save(STORAGE_KEYS.ORDERS, d); }
function getMenuItems() { return _load(STORAGE_KEYS.MENU_ITEMS, DB_MENU_ITEMS_SEED) || []; }
function saveMenuItems(d) { _save(STORAGE_KEYS.MENU_ITEMS, d); }
function getTables() { return _load(STORAGE_KEYS.TABLES, DB_CAFE_TABLES_SEED) || []; }

function saveTables(d) {
    _save(STORAGE_KEYS.TABLES, d);
    // Find what changed and force notify
    const oldTables = _load(STORAGE_KEYS.TABLES + '_old', []);
    if (oldTables.length > 0) {
        for (let i = 0; i < d.length; i++) {
            const old = oldTables.find(t => t.table_id === d[i].table_id);
            if (old && old.status !== d[i].status) {
                _forceNotifyTableChange(d[i].table_id, d[i].status, d[i].table_number);
            }
        }
    }
    _save(STORAGE_KEYS.TABLES + '_old', JSON.parse(JSON.stringify(d)));
}

// Auth functions
function getSession() { 
    const session = _load(STORAGE_KEYS.SESSION, null);
    if (session) {
        // Ensure session has all required fields
        if (!session.user_id && session.email) {
            console.warn('[Session] Session missing user_id, attempting to fix...');
            const user = getUsers().find(u => u.email === session.email);
            if (user) {
                session.user_id = user.user_id;
                saveSession(session);
                console.log('[Session] Fixed session with user_id:', session.user_id);
            }
        }
    }
    return session;
}

function saveSession(u) { 
    // Ensure we save with all required fields
    if (u && !u.user_id && u.email) {
        const user = getUsers().find(u2 => u2.email === u.email);
        if (user) {
            u.user_id = user.user_id;
        }
    }
    _save(STORAGE_KEYS.SESSION, u); 
}

function clearSession() { 
    localStorage.removeItem(STORAGE_KEYS.SESSION);
    localStorage.removeItem(STORAGE_KEYS.SESSION_SYNCED);
}

function isLoggedIn() { 
    const session = getSession();
    return session !== null && session.user_id !== undefined;
}

function isAdmin() { 
    const s = getSession(); 
    return s && s.user_type === 'admin'; 
}

function authenticateUser(email, password) {
    return getUsers().find(u =>
        u.email.toLowerCase() === email.toLowerCase() &&
        u.password === password &&
        u.is_active !== false
    ) || null;
}

function registerUser(fullName, email, phone, password) {
    const users = getUsers();
    if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) {
        return { success: false, message: 'Email already exists.' };
    }
    const newId = users.length > 0 ? Math.max(...users.map(u => u.user_id)) + 1 : 1;
    const newUser = {
        user_id: newId, 
        email: email.trim(), 
        full_name: fullName.trim(),
        phone: phone.trim(), 
        user_type: 'customer', 
        password,
        created_at: _today(), 
        is_active: true
    };
    users.push(newUser);
    saveUsers(users);
    return { success: true, user: newUser };
}

// Enhanced function to sync session with database
async function syncSessionWithDatabase() {
    const session = getSession();
    if (!session) return null;
    
    // Check if already synced recently
    const lastSync = localStorage.getItem(STORAGE_KEYS.SESSION_SYNCED);
    const now = Date.now();
    if (lastSync && (now - parseInt(lastSync)) < 60000) { // Sync every minute max
        console.log('[Session] Using cached session sync');
        return session.user_id;
    }
    
    try {
        console.log('[Session] Syncing session with database...');
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
            
            // Update local user if needed
            const users = getUsers();
            const localUser = users.find(u => u.email === dbUser.email);
            if (localUser) {
                if (localUser.user_id !== dbUser.user_id) {
                    console.log('[Session] Updating local user_id from', localUser.user_id, 'to', dbUser.user_id);
                    localUser.user_id = dbUser.user_id;
                    saveUsers(users);
                }
            }
            
            // Update session with database user_id
            if (session.user_id !== dbUser.user_id) {
                console.log('[Session] Updating session user_id from', session.user_id, 'to', dbUser.user_id);
                const updatedSession = {
                    ...session,
                    user_id: dbUser.user_id,
                    email: dbUser.email,
                    full_name: dbUser.full_name,
                    phone: dbUser.phone || session.phone
                };
                saveSession(updatedSession);
                localStorage.setItem(STORAGE_KEYS.SESSION_SYNCED, now.toString());
                return dbUser.user_id;
            }
            
            localStorage.setItem(STORAGE_KEYS.SESSION_SYNCED, now.toString());
            return dbUser.user_id;
        }
    } catch (error) {
        console.log('[Session] Could not sync with database, using local session');
    }
    
    return session.user_id;
}

// Mutation functions
function addReservation(obj) {
    const list = getReservations();
    const tables = getTables();
    const table = tables.find(t => t.table_id === obj.table_id);
    
    if (!table || table.status !== 'available') {
        throw new Error(`Table ${table?.table_number} is not available.`);
    }
    
    if (obj.number_of_guests > table.capacity) {
        throw new Error(`This table only seats ${table.capacity} guests.`);
    }
    
    const newId = list.length > 0 ? Math.max(...list.map(r => r.reservation_id)) + 1 : 1;
    const entry = {
        reservation_id: newId,
        user_id: obj.user_id || null,
        customer_name: obj.customer_name,
        customer_email: obj.customer_email,
        customer_phone: obj.customer_phone || '',
        table_id: obj.table_id,
        table_number: obj.table_number,
        reservation_date: obj.reservation_date,
        reservation_time: obj.reservation_time,
        number_of_guests: obj.number_of_guests,
        special_requests: obj.special_requests || '',
        status: 'confirmed',
        created_at: _today()
    };
    list.push(entry);
    saveReservations(list);
    
    // Mark table as reserved
    table.status = 'reserved';
    saveTables(tables);
    
    return entry;
}

function addToQueue(obj) {
    const queue = getQueue();
    const waiting = queue.filter(q => q.status === 'waiting');
    const nextPos = waiting.length + 1;
    const newId = queue.length > 0 ? Math.max(...queue.map(q => q.queue_id)) + 1 : 1;
    const entry = {
        queue_id: newId,
        user_id: obj.user_id || null,
        customer_name: obj.customer_name,
        customer_phone: obj.customer_phone || '',
        party_size: obj.party_size,
        status: 'waiting',
        position: nextPos,
        estimated_wait_time: nextPos * 15,
        joined_at: _nowTime()
    };
    queue.push(entry);
    saveQueue(queue);
    return entry;
}

function addOrder(obj) {
    const orders = getOrders();
    const newId = orders.length > 0 ? Math.max(...orders.map(o => o.order_id)) + 1 : 1;
    const ref = 'ORD-' + String(newId).padStart(3, '0');
    const entry = {
        order_id: newId,
        order_ref: ref,
        user_id: obj.user_id || null,
        customer_name: obj.customer_name,
        customer_email: obj.customer_email || '',
        customer_phone: obj.customer_phone || '',
        order_type: obj.order_type || 'pre_order',
        status: 'pending',
        total_amount: obj.total_amount,
        payment_status: 'paid',
        payment_method: obj.payment_method,
        created_at: _nowTime(),
        updated_at: _nowTime(),
        items: obj.items || []
    };
    orders.push(entry);
    saveOrders(orders);
    return entry;
}

// Utility function to get current user ID (ensuring it's from database)
async function getCurrentUserId() {
    const session = getSession();
    if (!session) return null;
    
    // If session has user_id, try to sync with database
    if (session.user_id) {
        const dbUserId = await syncSessionWithDatabase();
        return dbUserId;
    }
    
    // Try to find user by email
    const user = getUsers().find(u => u.email === session.email);
    if (user) {
        return user.user_id;
    }
    
    return null;
}


// Global exports
window.getTables = getTables;
window.saveTables = saveTables;
window.getReservations = getReservations;
window.saveReservations = saveReservations;
window.getQueue = getQueue;
window.saveQueue = saveQueue;
window.getOrders = getOrders;
window.saveOrders = saveOrders;
window.getMenuItems = getMenuItems;
window.saveMenuItems = saveMenuItems;
window.getUsers = getUsers;
window.saveUsers = saveUsers;
window.getSession = getSession;
window.saveSession = saveSession;
window.clearSession = clearSession;
window.isLoggedIn = isLoggedIn;
window.isAdmin = isAdmin;
window.authenticateUser = authenticateUser;
window.registerUser = registerUser;
window.addReservation = addReservation;
window.addToQueue = addToQueue;
window.addOrder = addOrder;
window.syncSessionWithDatabase = syncSessionWithDatabase;
window.getCurrentUserId = getCurrentUserId;

console.log('[cafe-data.js] v14 loaded - Enhanced session sync enabled');
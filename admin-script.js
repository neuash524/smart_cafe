// ============================================================
// Smart Café — admin-script.js (v13 - Fixed Queue Table Assignment)
// Fixed: Seating customers now assigns to their reserved table
// Fixed: Email triggers for all admin actions
// ============================================================

// ── Auth guard ────────────────────────────────────────────────
(function () {
    const s = getSession();
    if (!s || s.user_type !== 'admin') {
        window.location.href = 'admin-logout.php';
    }
})();

// ── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const session = getSession();
    if (session) {
        const nameEl = document.querySelector('.profile-name');
        const avatarEl = document.querySelector('.profile-avatar');
        if (nameEl) nameEl.textContent = session.full_name;
        if (avatarEl) avatarEl.textContent = session.full_name.split(' ').map(w => w[0]).join('').toUpperCase();
    }

    initializeSidebar();
    loadDashboard();
    loadTables();
    loadReservations();
    loadQueue();
    loadOrders();
    loadMenu();
    loadAnalytics();

    const modal = document.getElementById('modal');
    if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    
    // Start orders polling for real-time updates
    startOrdersPolling();
    
    // Listen for customer changes
    window.addEventListener('storage', function(e) {
        if (e.key === 'sc_admin_ping') {
            try {
                const ping = JSON.parse(e.newValue || '{}');
                if (ping.type === 'sc_reservations') loadReservations();
                if (ping.type === 'sc_orders') refreshOrdersData();
                if (ping.type === 'sc_queue') loadQueue();
                loadDashboard();
            } catch(err) {}
        }
    });
});

function initializeSidebar() {
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
            const section = document.getElementById(this.dataset.section + '-section');
            if (section) section.classList.add('active');
        });
    });
}

// Dashboard
function loadDashboard() {
    const today = new Date().toISOString().split('T')[0];
    const tables = getTables();
    const resAll = getReservations();
    const queue = getQueue().filter(q => q.status === 'waiting');
    const orders = getOrders();
    const todayRes = resAll.filter(r => r.reservation_date === today);

    const statBoxes = document.querySelectorAll('.stat-box .stat-value');
    if (statBoxes[0]) {
        const avail = tables.filter(t => t.status === 'available').length;
        statBoxes[0].innerHTML = avail + '<span>/' + tables.length + '</span>';
    }
    if (statBoxes[1]) statBoxes[1].textContent = todayRes.length;
    if (statBoxes[2]) statBoxes[2].textContent = queue.length;
    if (statBoxes[3]) {
        const rev = orders.filter(o => o.payment_status === 'paid').reduce((s, o) => s + (parseFloat(o.total_amount) || 0), 0);
        statBoxes[3].textContent = '$' + rev.toFixed(2);
    }

    // Recent Reservations widget
    const recentEl = document.getElementById('recentReservations');
    if (recentEl) {
        if (todayRes.length === 0) {
            recentEl.innerHTML = '<p style="padding:1.5rem;color:#9ca3af;text-align:center;">No reservations today yet.</p>';
        } else {
            recentEl.innerHTML = todayRes.slice(0, 5).map(r => `
                <div class="recent-item">
                    <div class="recent-item-info">
                        <h4>${escapeHtml(r.customer_name)}</h4>
                        <p>${r.reservation_time} &middot; ${r.number_of_guests} guests &middot; ${r.table_number || 'TBD'}</p>
                    </div>
                    <span class="status-badge ${r.status}">${r.status}</span>
                </div>
            `).join('');
        }
    }

    // Active Queue widget
    const queueEl = document.getElementById('activeQueue');
    if (queueEl) {
        if (queue.length === 0) {
            queueEl.innerHTML = '<p style="padding:1.5rem;color:#9ca3af;text-align:center;">Queue is empty.</p>';
        } else {
            queueEl.innerHTML = queue.slice(0, 5).map((entry, i) => `
                <div class="recent-item">
                    <div class="recent-item-info">
                        <h4>#${i + 1} &mdash; ${escapeHtml(entry.customer_name)}</h4>
                        <p>Party of ${entry.party_size} &middot; joined ${entry.joined_at}</p>
                    </div>
                    <button class="btn btn-small btn-primary" onclick="callCustomer(${entry.queue_id})">Call</button>
                </div>
            `).join('');
        }
    }

    const qCountEl = document.getElementById('queueCount');
    if (qCountEl) qCountEl.textContent = '(' + queue.length + ' people)';
}

// Helper function to escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Helper function to trigger email notifications
function triggerEmail(type, id, status, extraData = {}) {
    // Silent trigger - doesn't block the main action
    const url = `email_trigger.php?type=${type}&id=${id}&status=${status}`;
    
    fetch(url, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
    }).catch(err => {
        console.log('[Email] Trigger failed (non-critical):', err);
    });
    
    console.log(`[Email] Triggered: ${type} #${id} -> ${status}`);
}

// ══════════════════════════════════════════════════════════════
// TABLE MANAGEMENT - API INTEGRATED
// ══════════════════════════════════════════════════════════════

function loadTables(data) {
    const grid = document.getElementById('tableLayoutGrid');
    if (!grid) return;
    const list = data || getTables();

    if (list.length === 0) {
        grid.innerHTML = '<p style="padding:2rem;color:#999;text-align:center;">No tables found.</p>';
        return;
    }

    grid.innerHTML = list.map(t => `
        <div class="table-card ${t.status}">
            <div class="table-number">${escapeHtml(t.table_number)}</div>
            <div class="table-capacity">👥 Capacity: ${t.capacity} people</div>
            <div class="status-badge ${t.status}">${t.status}</div>
            <div class="table-actions">
                <button class="btn btn-small ${t.status === 'available' ? 'btn-primary' : 'btn-secondary'}"
                        onclick="toggleTableStatus(${t.table_id})">
                    ${t.status === 'available' ? 'Mark Occupied' : 'Mark Available'}
                </button>
            </div>
        </div>
    `).join('');
}

async function toggleTableStatus(tableId) {
    const tables = getTables();
    const t = tables.find(t => t.table_id === tableId);
    if (!t) return;
    
    const oldStatus = t.status;
    const newStatus = (t.status === 'available') ? 'occupied' : 'available';
    
    // Disable button to prevent double clicks
    const buttons = document.querySelectorAll('.table-actions button');
    buttons.forEach(btn => btn.disabled = true);
    
    try {
        // Call the correct API endpoint for tables
        const response = await fetch(`table.php?id=${tableId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[API] Table status updated in database:', result);
            
            // Update localStorage
            t.status = newStatus;
            saveTables(tables);
            
            // Notify customer page
            _notifyCustomer('sc_tables');
            
            // Refresh admin views
            loadTables();
            loadDashboard();
            
            showAdminNotification(`${t.table_number} marked as ${newStatus}`, 'success');
        } else {
            showAdminNotification(result.message || 'Failed to update table', 'error');
        }
    } catch (error) {
        console.error('[API] Error updating table:', error);
        showAdminNotification('Network error. Please try again.', 'error');
    } finally {
        // Re-enable buttons
        setTimeout(() => {
            const btns = document.querySelectorAll('.table-actions button');
            btns.forEach(btn => btn.disabled = false);
        }, 500);
    }
}

function searchTables(query) {
    const q = (query || '').toLowerCase();
    loadTables(getTables().filter(t =>
        t.table_number.toLowerCase().includes(q) || t.status.toLowerCase().includes(q)
    ));
}

function openTableModal() {
    openModal('Add New Table', `
        <div class="form-group"><label>Table Name</label>
            <input type="text" id="new-table-name" placeholder="e.g., Table 9"></div>
        <div class="form-group"><label>Capacity (people)</label>
            <input type="number" id="new-table-cap" min="1" max="20" placeholder="4"></div>
        <button class="btn btn-primary btn-full" onclick="saveNewTable()">Add Table</button>
    `);
}

function saveNewTable() {
    const name = document.getElementById('new-table-name')?.value?.trim();
    const cap = parseInt(document.getElementById('new-table-cap')?.value);
    if (!name || !cap) { showAdminNotification('Please fill in all fields', 'error'); return; }
    const tables = getTables();
    const newId = tables.length > 0 ? Math.max(...tables.map(t => t.table_id)) + 1 : 1;
    tables.push({ table_id: newId, table_number: name, capacity: cap, status: 'available' });
    saveTables(tables);
    _notifyCustomer('sc_tables');
    loadTables();
    closeModal();
    showAdminNotification(name + ' added!', 'success');
}

// ══════════════════════════════════════════════════════════════
// RESERVATIONS - API INTEGRATED (WITH EMAIL TRIGGERS)
// ══════════════════════════════════════════════════════════════

function loadReservations(data) {
    const tbody = document.getElementById('reservationsTable');
    if (!tbody) return;
    const list = data !== undefined ? data : getReservations();

    if (!list || list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;padding:3rem;">No reservations yet</td></tr>`;
    } else {
        tbody.innerHTML = list.map(r => `
            <tr>
                <td>#${r.reservation_id}</td>
                <td><strong>${escapeHtml(r.customer_name)}</strong></td>
                <td>${escapeHtml(r.customer_email || '—')}<br><small>${escapeHtml(r.customer_phone || '')}</small></td>
                <td>${r.reservation_date} · ${r.reservation_time}</td>
                <td>${r.number_of_guests}</td>
                <td>${r.table_number || 'TBD'}</td>
                <td><span class="status-badge ${r.status}">${r.status}</span></td>
                <td>
                    <button class="btn btn-small btn-primary" onclick="viewReservation(${r.reservation_id})">View</button>
                    <button class="btn btn-small btn-secondary" onclick="editReservation(${r.reservation_id})">Edit</button>
                </td>
            </tr>
        `).join('');
    }

    // Filter tab wiring
    document.querySelectorAll('#reservations-section .filter-tab').forEach(tab => {
        tab.removeEventListener('click', filterHandler);
        tab.addEventListener('click', filterHandler);
    });
}

function filterHandler(e) {
    document.querySelectorAll('#reservations-section .filter-tab').forEach(t => t.classList.remove('active'));
    this.classList.add('active');
    filterReservations(this.dataset.filter);
}

function filterReservations(filter) {
    const today = new Date().toISOString().split('T')[0];
    const all = getReservations();
    let out = all;
    if (filter === 'today') out = all.filter(r => r.reservation_date === today);
    if (filter === 'upcoming') out = all.filter(r => r.reservation_date > today);
    if (filter === 'past') out = all.filter(r => r.reservation_date < today);
    loadReservations(out);
}

function viewReservation(id) {
    const r = getReservations().find(r => r.reservation_id === id);
    if (!r) return;
    openModal('Reservation #' + id, `
        <div class="detail-grid">
            <p><strong>Customer:</strong> ${escapeHtml(r.customer_name)}</p>
            <p><strong>Email:</strong> ${escapeHtml(r.customer_email || '—')}</p>
            <p><strong>Phone:</strong> ${escapeHtml(r.customer_phone || '—')}</p>
            <p><strong>Date:</strong> ${r.reservation_date}</p>
            <p><strong>Time:</strong> ${r.reservation_time}</p>
            <p><strong>Guests:</strong> ${r.number_of_guests}</p>
            <p><strong>Table:</strong> ${r.table_number || 'TBD'}</p>
            <p><strong>Status:</strong> <span class="status-badge ${r.status}">${r.status}</span></p>
        </div>
        <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
            <button class="btn btn-primary" onclick="updateReservationStatus(${r.reservation_id},'confirmed');closeModal();">Confirm</button>
            <button class="btn btn-outline" onclick="updateReservationStatus(${r.reservation_id},'cancelled');closeModal();">Cancel</button>
        </div>
    `);
}

function editReservation(id) {
    const r = getReservations().find(r => r.reservation_id === id);
    if (!r) return;
    openModal('Edit Reservation #' + id, `
        <div class="form-group"><label>Customer Name</label>
            <input type="text" id="edit-name" value="${escapeHtml(r.customer_name)}"></div>
        <div class="form-group"><label>Date</label>
            <input type="date" id="edit-date" value="${r.reservation_date}"></div>
        <div class="form-group"><label>Time</label>
            <input type="time" id="edit-time" value="${r.reservation_time}"></div>
        <div class="form-group"><label>Guests</label>
            <input type="number" id="edit-guests" value="${r.number_of_guests}" min="1"></div>
        <div class="form-group"><label>Status</label>
            <select id="edit-status">
                <option value="pending" ${r.status === 'pending' ? 'selected' : ''}>Pending</option>
                <option value="confirmed" ${r.status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                <option value="cancelled" ${r.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                <option value="completed" ${r.status === 'completed' ? 'selected' : ''}>Completed</option>
            </select></div>
        <button class="btn btn-primary btn-full" onclick="saveEditReservation(${id})">Save Changes</button>
    `);
}

function saveEditReservation(id) {
    const list = getReservations();
    const r = list.find(r => r.reservation_id === id);
    if (!r) return;
    r.customer_name = document.getElementById('edit-name').value;
    r.reservation_date = document.getElementById('edit-date').value;
    r.reservation_time = document.getElementById('edit-time').value;
    r.number_of_guests = parseInt(document.getElementById('edit-guests').value);
    r.status = document.getElementById('edit-status').value;
    saveReservations(list);
    loadReservations();
    closeModal();
    showAdminNotification('Reservation #' + id + ' updated!', 'success');
    
    // Trigger email for status change if needed
    if (r.status === 'confirmed' || r.status === 'cancelled') {
        triggerEmail('reservation', id, r.status);
    }
}

async function updateReservationStatus(id, status) {
    try {
        const response = await fetch(`reservations.php?id=${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: status })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[API] Reservation status updated in database');
            
            // Update localStorage
            const list = getReservations();
            const r = list.find(r => r.reservation_id === id);
            if (r) {
                r.status = status;
                saveReservations(list);
                
                if (status === 'cancelled') {
                    const tables = getTables();
                    const t = tables.find(t => t.table_id === r.table_id);
                    if (t && t.status === 'reserved') {
                        t.status = 'available';
                        saveTables(tables);
                        _notifyCustomer('sc_tables');
                    }
                }
            }
            
            // ========== TRIGGER EMAIL NOTIFICATION ==========
            // Send email to customer about reservation status change
            triggerEmail('reservation', id, status);
            // =================================================
            
            loadReservations();
            loadDashboard();
            showAdminNotification(`Reservation #${id} → ${status}`, 'success');
        } else {
            showAdminNotification(result.message || 'Failed to update reservation', 'error');
        }
    } catch (error) {
        console.error('[API] Error updating reservation:', error);
        showAdminNotification('Network error. Please try again.', 'error');
    }
}

function openReservationModal() {
    const tableOptions = getTables()
        .filter(t => t.status === 'available')
        .map(t => `<option value="${t.table_id}">${escapeHtml(t.table_number)} (cap. ${t.capacity})</option>`)
        .join('');
    openModal('New Reservation', `
        <div class="form-group"><label>Customer Name</label>
            <input type="text" id="nr-name" placeholder="Full name"></div>
        <div class="form-group"><label>Email</label>
            <input type="email" id="nr-email" placeholder="email@example.com"></div>
        <div class="form-group"><label>Phone</label>
            <input type="tel" id="nr-phone" placeholder="+1 234 567 8900"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group"><label>Date</label>
                <input type="date" id="nr-date" min="${new Date().toISOString().split('T')[0]}"></div>
            <div class="form-group"><label>Time</label>
                <input type="time" id="nr-time"></div>
        </div>
        <div class="form-group"><label>Guests</label>
            <input type="number" id="nr-guests" min="1" max="20" placeholder="2"></div>
        <div class="form-group"><label>Table</label>
            <select id="nr-table">
                <option value="">-- Select Table --</option>
                ${tableOptions}
            </select></div>
        <div class="form-group"><label>Special Requests</label>
            <textarea id="nr-requests" rows="2" placeholder="Optional..."></textarea></div>
        <button class="btn btn-primary btn-full" onclick="adminSaveReservation()">Create Reservation</button>
    `);
}

async function adminSaveReservation() {
    const name = document.getElementById('nr-name')?.value?.trim();
    const email = document.getElementById('nr-email')?.value?.trim();
    const phone = document.getElementById('nr-phone')?.value?.trim();
    const date = document.getElementById('nr-date')?.value;
    const time = document.getElementById('nr-time')?.value;
    const guests = parseInt(document.getElementById('nr-guests')?.value);
    const tableId = parseInt(document.getElementById('nr-table')?.value);
    const reqs = document.getElementById('nr-requests')?.value?.trim() || '';

    if (!name || !email || !date || !time || !guests || !tableId) {
        showAdminNotification('Please fill all required fields', 'error');
        return;
    }

    const tbl = getTables().find(t => t.table_id === tableId);
    if (!tbl || tbl.status !== 'available') {
        showAdminNotification('Selected table is not available', 'error');
        return;
    }
    
    if (guests > tbl.capacity) {
        showAdminNotification(`Table only seats ${tbl.capacity} guests`, 'error');
        return;
    }

    const reservationData = {
        user_id: null,
        customer_name: name,
        customer_email: email,
        customer_phone: phone,
        table_id: tableId,
        reservation_date: date,
        reservation_time: time,
        number_of_guests: guests,
        special_requests: reqs
    };

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
            
            // Update localStorage
            const reservations = getReservations();
            reservations.push(savedReservation);
            saveReservations(reservations);
            
            // Mark table as reserved
            const tables = getTables();
            const table = tables.find(t => t.table_id === tableId);
            if (table) {
                table.status = 'reserved';
                saveTables(tables);
            }
            
            // ========== TRIGGER EMAIL NOTIFICATION ==========
            // Send confirmation email for new reservation
            triggerEmail('reservation', savedReservation.reservation_id, 'pending');
            // =================================================
            
            loadReservations();
            loadDashboard();
            closeModal();
            showAdminNotification(`Reservation #${savedReservation.reservation_id} created!`, 'success');
        } else {
            showAdminNotification(result.message || 'Failed to create reservation', 'error');
        }
    } catch (error) {
        console.error('[API] Error creating reservation:', error);
        showAdminNotification('Network error. Please try again.', 'error');
    }
}

// ══════════════════════════════════════════════════════════════
// QUEUE MANAGEMENT - API INTEGRATED (FIXED TABLE ASSIGNMENT)
// ══════════════════════════════════════════════════════════════

function loadQueue() {
    const container = document.getElementById('queueManagement');
    const countEl = document.getElementById('queueCount');
    if (!container) return;

    const queue = getQueue().filter(q => q.status === 'waiting');
    if (countEl) countEl.textContent = '(' + queue.length + ' people)';

    if (queue.length === 0) {
        container.innerHTML = `<div style="padding:3rem;text-align:center;">Queue is empty</div>`;
        return;
    }

    container.innerHTML = queue.map((entry, i) => `
        <div class="queue-manage-item">
            <div class="queue-item-left">
                <div class="queue-position-badge">${i + 1}</div>
                <div>
                    <h4>${escapeHtml(entry.customer_name)}</h4>
                    <p>Party of ${entry.party_size} · ~${entry.estimated_wait_time} min wait</p>
                    ${entry.reserved_table ? `<p style="color:#8B4513; font-size:0.8rem;">📌 Reserved: ${escapeHtml(entry.reserved_table)}</p>` : ''}
                </div>
            </div>
            <div class="queue-item-actions">
                <button class="btn btn-small btn-primary" onclick="callCustomer(${entry.queue_id})">Call</button>
                <button class="btn btn-small btn-secondary" onclick="seatCustomer(${entry.queue_id})">Seat</button>
                <button class="btn btn-small btn-outline" onclick="removeFromQueue(${entry.queue_id})">Remove</button>
            </div>
        </div>
    `).join('');
}

function callCustomer(id) {
    const entry = getQueue().find(q => q.queue_id === id);
    showAdminNotification(`Calling ${entry?.customer_name}`, 'success');
    
    // ========== TRIGGER EMAIL NOTIFICATION ==========
    // Notify customer they're being called
    triggerEmail('queue', id, 'called');
    // =================================================
}

// ══════════════════════════════════════════════════════════════
// QUEUE MANAGEMENT - FIXED: Seat to reserved table only
// ══════════════════════════════════════════════════════════════

async function seatCustomer(id) {
    try {
        // First, get the queue entry to find the customer
        const queue = getQueue();
        const entry = queue.find(q => q.queue_id === id);
        
        if (!entry) {
            showAdminNotification('Queue entry not found', 'error');
            return;
        }
        
        // Check if this customer has a CONFIRMED reservation for TODAY
        const reservations = getReservations();
        const today = new Date().toISOString().split('T')[0];
        
        const customerReservation = reservations.find(r => 
            (r.user_id === entry.user_id || r.customer_email === entry.customer_email) && 
            r.status === 'confirmed' &&
            r.reservation_date === today
        );
        
        let tableToAssign = null;
        let tableIdToAssign = null;
        let tableNumberToAssign = null;
        
        // IMPORTANT: Only seat to the reserved table if they have a confirmed reservation
        if (customerReservation && customerReservation.table_id) {
            const tables = getTables();
            tableToAssign = tables.find(t => t.table_id === customerReservation.table_id);
            
            if (tableToAssign && tableToAssign.status === 'reserved') {
                tableIdToAssign = tableToAssign.table_id;
                tableNumberToAssign = tableToAssign.table_number;
                console.log(`[Queue] Customer has confirmed reservation for table: ${tableNumberToAssign}`);
                
                // Update the reservation status to 'completed' when seated
                await fetch(`reservations.php?id=${customerReservation.reservation_id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: 'completed' })
                });
                
                // Update local storage for reservation
                const updatedReservations = getReservations();
                const resIndex = updatedReservations.findIndex(r => r.reservation_id === customerReservation.reservation_id);
                if (resIndex !== -1) {
                    updatedReservations[resIndex].status = 'completed';
                    saveReservations(updatedReservations);
                }
            } else {
                showAdminNotification(`⚠️ Customer's reserved table ${tableToAssign?.table_number} is not available.`, 'warning');
                // Do NOT seat - they should wait for their reserved table
                showAdminNotification(`Please make table ${tableToAssign?.table_number} available first.`, 'error');
                return;
            }
        } else {
            // No reservation found - DO NOT SEAT
            showAdminNotification(`❌ Customer does not have a confirmed reservation for today.`, 'error');
            showAdminNotification(`Please ask them to make a reservation first.`, 'error');
            return;
        }
        
        if (!tableIdToAssign) {
            showAdminNotification('No reserved table found for this customer!', 'error');
            return;
        }
        
        // Update queue status to seated
        const queueResponse = await fetch(`queue.php?id=${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'seated' })
        });
        
        const queueResult = await queueResponse.json();
        
        if (queueResult.success) {
            console.log('[API] Queue status updated in database');
            
            // Update localStorage for queue
            if (entry) {
                entry.status = 'seated';
                saveQueue(queue);
            }
            
            // Update table status to occupied
            const tableResponse = await fetch(`table.php?id=${tableIdToAssign}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'occupied' })
            });
            
            const tableResult = await tableResponse.json();
            
            if (tableResult.success) {
                const tables = getTables();
                const table = tables.find(t => t.table_id === tableIdToAssign);
                if (table) {
                    table.status = 'occupied';
                    saveTables(tables);
                }
                
                showAdminNotification(`✅ Seated ${entry.customer_name} at their reserved table ${tableNumberToAssign}`, 'success');
            } else {
                showAdminNotification('Customer seated but table status update failed', 'warning');
            }
            
            loadQueue();
            loadDashboard();
            loadReservations(); // Refresh reservations to show updated status
            
            // Trigger customer notification
            triggerEmail('queue', id, 'ready');
            
        } else {
            showAdminNotification(queueResult.message || 'Failed to seat customer', 'error');
        }
    } catch (error) {
        console.error('[API] Error seating customer:', error);
        showAdminNotification('Network error. Please try again.', 'error');
    }
}

async function removeFromQueue(id) {
    try {
        const response = await fetch(`queue.php?id=${id}`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[API] Queue entry removed from database');
            
            // Update localStorage
            const queue = getQueue();
            const idx = queue.findIndex(q => q.queue_id === id);
            if (idx !== -1) {
                queue.splice(idx, 1);
                saveQueue(queue);
            }
            
            loadQueue();
            loadDashboard();
            showAdminNotification('Removed from queue', 'success');
        } else {
            showAdminNotification(result.message || 'Failed to remove from queue', 'error');
        }
    } catch (error) {
        console.error('[API] Error removing queue entry:', error);
        showAdminNotification('Network error. Please try again.', 'error');
    }
}

function refreshQueue() {
    loadQueue();
    loadDashboard();
    showAdminNotification('Queue refreshed!', 'success');
}

function callNextInQueue() {
    const queue = getQueue().filter(q => q.status === 'waiting');
    if (queue.length > 0) {
        const nextCustomer = queue[0];
        
        // Check if they have a reservation
        const reservations = getReservations();
        const today = new Date().toISOString().split('T')[0];
        const hasReservation = reservations.find(r => 
            (r.user_id === nextCustomer.user_id || r.customer_email === nextCustomer.customer_email) && 
            r.status === 'confirmed' &&
            r.reservation_date === today
        );
        
        const msg = hasReservation 
            ? `Calling ${nextCustomer.customer_name} (has reserved table)`
            : `⚠️ ${nextCustomer.customer_name} does not have a reservation. Please ask them to make one first.`;
        
        if (hasReservation) {
            showAdminNotification(msg, 'success');
            triggerEmail('queue', nextCustomer.queue_id, 'called');
        } else {
            showAdminNotification(msg, 'warning');
        }
    } else {
        showAdminNotification('Queue is empty!', 'error');
    }
}

// ══════════════════════════════════════════════════════════════
// ORDERS - API INTEGRATED (WITH EMAIL TRIGGERS)
// ══════════════════════════════════════════════════════════════

function loadOrders(data) {
    const grid = document.getElementById('ordersGrid');
    if (!grid) return;
    const list = data !== undefined ? data : getOrders();

    if (!list || list.length === 0) {
        grid.innerHTML = `<div class="empty-state" style="padding:3rem;text-align:center;color:var(--text-secondary);">
            <span style="font-size:3rem;">📋</span>
            <p>No orders yet</p>
        </div>`;
        return;
    }

    grid.innerHTML = list.map(order => {
        const items = order.items || [];
        const orderRef = order.order_ref || 'ORD-' + String(order.order_id).padStart(3, '0');
        
        return `
            <div class="order-card ${order.status}">
                <div class="order-header">
                    <div>
                        <div class="order-id">${escapeHtml(orderRef)}</div>
                        <div class="order-time">${order.created_at || 'Just now'}</div>
                    </div>
                    <span class="status-badge ${order.status}">${order.status}</span>
                </div>
                <div class="order-customer">
                    <strong>${escapeHtml(order.customer_name)}</strong><br>
                    <small>${escapeHtml(order.customer_phone || 'No phone')}</small>
                </div>
                <div class="order-items">
                    ${items.map(item => `
                        <div class="order-item">
                            <span>${item.quantity}× ${escapeHtml(item.item_name)}</span>
                            <span>$${((item.unit_price || 0) * (item.quantity || 1)).toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="order-total">
                    <strong>Total: $${(parseFloat(order.total_amount) || 0).toFixed(2)}</strong>
                </div>
                <div class="order-actions">${_orderActions(order.status, order.order_ref, order.order_id)}</div>
            </div>
        `;
    }).join('');
}

function _orderActions(status, ref, orderId) {
    const map = {
        pending: { label: '🍳 Start Preparing', next: 'preparing', class: 'btn-primary' },
        preparing: { label: '✅ Mark as Ready', next: 'ready', class: 'btn-primary' },
        ready: { label: '🎉 Complete Order', next: 'completed', class: 'btn-success' }
    };
    
    if (!map[status]) {
        return '<span class="status-badge completed">✓ Completed</span>';
    }
    
    const { label, next, class: btnClass } = map[status];
    return `<button class="btn btn-small ${btnClass}" onclick="updateOrderStatus(${orderId}, '${next}')" style="cursor:pointer;">
        ${label}
    </button>`;
}

async function updateOrderStatus(orderId, newStatus) {
    // Disable the button to prevent double clicks
    const buttons = document.querySelectorAll(`button[onclick*="updateOrderStatus(${orderId},"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.setAttribute('data-original-text', originalText);
        btn.textContent = '⏳ Updating...';
    });
    
    try {
        console.log(`[Admin] Updating order #${orderId} to status: ${newStatus}`);
        
        const response = await fetch(`orders.php?id=${orderId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });
        
        const result = await response.json();
        console.log('[Admin] Order update response:', result);
        
        if (result.success) {
            console.log('[API] Order status updated in database:', result);
            
            // Update localStorage
            const orders = getOrders();
            const orderIndex = orders.findIndex(o => o.order_id === orderId);
            if (orderIndex !== -1) {
                orders[orderIndex].status = newStatus;
                saveOrders(orders);
            }
            
            // ========== TRIGGER EMAIL NOTIFICATION ==========
            // Send email to customer about order status change
            triggerEmail('order', orderId, newStatus);
            // =================================================
            
            // Reload orders to refresh the display
            await refreshOrdersData();
            
            // Show success notification
            showAdminNotification(`✅ Order #${orderId} updated to ${newStatus}`, 'success');
            
            // Also refresh dashboard if needed
            loadDashboard();
            
        } else {
            // Show detailed error message
            console.error('[API] Error response:', result);
            const errorMsg = result.message || 'Failed to update order status';
            showAdminNotification(`❌ ${errorMsg}`, 'error');
            
            // Re-enable buttons
            buttons.forEach(btn => {
                btn.disabled = false;
                const originalText = btn.getAttribute('data-original-text') || 
                    (newStatus === 'preparing' ? 'Start Preparing' : 
                     newStatus === 'ready' ? 'Mark as Ready' : 'Complete Order');
                btn.textContent = originalText;
            });
        }
    } catch (error) {
        console.error('[API] Network error updating order:', error);
        showAdminNotification('❌ Network error. Please check your connection.', 'error');
        
        // Re-enable buttons
        buttons.forEach(btn => {
            btn.disabled = false;
            const originalText = btn.getAttribute('data-original-text') || 
                (newStatus === 'preparing' ? 'Start Preparing' : 
                 newStatus === 'ready' ? 'Mark as Ready' : 'Complete Order');
            btn.textContent = originalText;
        });
    }
}

// Helper function to refresh orders from database
async function refreshOrdersData() {
    try {
        const response = await fetch('orders.php');
        const result = await response.json();
        
        if (result.success && result.data?.orders) {
            saveOrders(result.data.orders);
            loadOrders();
            console.log('[Admin] Orders refreshed from database');
            return result.data.orders;
        }
    } catch (error) {
        console.error('[Admin] Error refreshing orders:', error);
    }
    return getOrders();
}

function filterOrders(filter, btn) {
    // Update active tab styling
    document.querySelectorAll('#orders-section .filter-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    
    // Filter orders
    const all = getOrders();
    let filtered = all;
    
    switch(filter) {
        case 'pending':
            filtered = all.filter(o => o.status === 'pending');
            break;
        case 'preparing':
            filtered = all.filter(o => o.status === 'preparing');
            break;
        case 'ready':
            filtered = all.filter(o => o.status === 'ready');
            break;
        case 'completed':
            filtered = all.filter(o => o.status === 'completed');
            break;
        default:
            filtered = all;
    }
    
    loadOrders(filtered);
    
    // Show notification with count
    showAdminNotification(`Showing ${filtered.length} ${filter === 'all' ? 'orders' : filter} orders`, 'success');
}

// Start orders polling for real-time updates
function startOrdersPolling() {
    // Refresh orders every 15 seconds to keep admin dashboard updated
    setInterval(async () => {
        try {
            const response = await fetch('orders.php');
            const result = await response.json();
            if (result.success && result.data?.orders) {
                const currentOrders = getOrders();
                const newOrders = result.data.orders;
                
                // Check if there are changes
                if (JSON.stringify(currentOrders) !== JSON.stringify(newOrders)) {
                    saveOrders(newOrders);
                    loadOrders();
                    console.log('[Admin] Orders auto-refreshed');
                }
            }
        } catch (error) {
            console.error('[Admin] Error auto-refreshing orders:', error);
        }
    }, 10000); // Refresh every 15 seconds
}

// ══════════════════════════════════════════════════════════════
// MENU MANAGEMENT
// ══════════════════════════════════════════════════════════════

function loadMenu(data) {
    const grid = document.getElementById('menuManagement');
    if (!grid) return;
    const list = data !== undefined ? data : getMenuItems();

    if (!list || list.length === 0) {
        grid.innerHTML = '<p style="padding:2rem;text-align:center;">No menu items found.</p>';
        return;
    }

    grid.innerHTML = list.map(item => `
        <div class="menu-manage-card">
            <div class="menu-manage-content">
                <div class="menu-manage-header">
                    <h3>${escapeHtml(item.item_name)}</h3>
                    <span>$${item.price.toFixed(2)}</span>
                </div>
                <p style="color:var(--text-secondary);">${item.category}</p>
                <span class="status-badge ${item.is_available ? 'confirmed' : 'cancelled'}">
                    ${item.is_available ? 'Available' : 'Unavailable'}
                </span>
                <div style="margin-top:1rem;display:flex;gap:0.5rem;">
                    <button class="btn btn-small btn-secondary" onclick="editMenuItem(${item.item_id})">Edit</button>
                    <button class="btn btn-small ${item.is_available ? 'btn-outline' : 'btn-primary'}"
                            onclick="toggleMenuItemAvailability(${item.item_id})">
                        ${item.is_available ? 'Disable' : 'Enable'}
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function toggleMenuItemAvailability(id) {
    const items = getMenuItems();
    const item = items.find(m => m.item_id === id);
    if (!item) return;
    item.is_available = !item.is_available;
    saveMenuItems(items);
    loadMenu();
    showAdminNotification(item.item_name + ' ' + (item.is_available ? 'enabled' : 'disabled'), 'success');
}

function editMenuItem(id) {
    const item = getMenuItems().find(m => m.item_id === id);
    if (!item) return;
    openModal('Edit Menu Item', `
        <div class="form-group"><label>Item Name</label>
            <input type="text" id="mi-name" value="${escapeHtml(item.item_name)}"></div>
        <div class="form-group"><label>Description</label>
            <textarea id="mi-desc" rows="2">${escapeHtml(item.description)}</textarea></div>
        <div class="form-group"><label>Price ($)</label>
            <input type="number" step="0.01" id="mi-price" value="${item.price}"></div>
        <div class="form-group"><label>Category</label>
            <select id="mi-cat">
                <option value="breakfast" ${item.category === 'breakfast' ? 'selected' : ''}>Breakfast</option>
                <option value="lunch" ${item.category === 'lunch' ? 'selected' : ''}>Lunch</option>
                <option value="beverages" ${item.category === 'beverages' ? 'selected' : ''}>Beverages</option>
                <option value="desserts" ${item.category === 'desserts' ? 'selected' : ''}>Desserts</option>
            </select></div>
        <button class="btn btn-primary btn-full" onclick="saveMenuItem(${id})">Save Changes</button>
    `);
}

function saveMenuItem(id) {
    const items = getMenuItems();
    const item = items.find(m => m.item_id === id);
    if (!item) return;
    item.item_name = document.getElementById('mi-name').value;
    item.description = document.getElementById('mi-desc').value;
    item.price = parseFloat(document.getElementById('mi-price').value);
    item.category = document.getElementById('mi-cat').value;
    saveMenuItems(items);
    loadMenu();
    closeModal();
    showAdminNotification(item.item_name + ' updated!', 'success');
}

function searchMenu(query) {
    const q = (query || '').toLowerCase();
    loadMenu(getMenuItems().filter(m =>
        m.item_name.toLowerCase().includes(q) || m.category.toLowerCase().includes(q)
    ));
}

function openMenuItemModal() {
    openModal('Add Menu Item', `
        <div class="form-group"><label>Item Name</label>
            <input type="text" id="add-mi-name" placeholder="e.g., Blueberry Muffin"></div>
        <div class="form-group"><label>Description</label>
            <textarea id="add-mi-desc" rows="2" placeholder="Brief description..."></textarea></div>
        <div class="form-group"><label>Category</label>
            <select id="add-mi-cat">
                <option value="breakfast">Breakfast</option>
                <option value="lunch">Lunch</option>
                <option value="beverages">Beverages</option>
                <option value="desserts">Desserts</option>
            </select></div>
        <div class="form-group"><label>Price ($)</label>
            <input type="number" step="0.01" id="add-mi-price" placeholder="9.99"></div>
        <div class="form-group"><label>Image filename</label>
            <input type="text" id="add-mi-img" placeholder="blueberry-muffin.jpg"></div>
        <button class="btn btn-primary btn-full" onclick="saveNewMenuItem()">Add to Menu</button>
    `);
}

function saveNewMenuItem() {
    const name = document.getElementById('add-mi-name')?.value?.trim();
    const desc = document.getElementById('add-mi-desc')?.value?.trim();
    const cat = document.getElementById('add-mi-cat')?.value;
    const price = parseFloat(document.getElementById('add-mi-price')?.value);
    const img = document.getElementById('add-mi-img')?.value?.trim();
    if (!name || !price) { showAdminNotification('Please fill required fields', 'error'); return; }
    const items = getMenuItems();
    const newId = items.length > 0 ? Math.max(...items.map(m => m.item_id)) + 1 : 1;
    items.push({
        item_id: newId, category: cat, item_name: name, description: desc, price,
        image: img ? 'img/menu/' + img : '', emoji: '🍴', is_available: true, is_featured: false
    });
    saveMenuItems(items);
    loadMenu();
    closeModal();
    showAdminNotification(name + ' added to menu!', 'success');
}

// ══════════════════════════════════════════════════════════════
// ANALYTICS
// ══════════════════════════════════════════════════════════════

function loadAnalytics() {
    const topEl = document.getElementById('topItems');
    if (topEl) {
        topEl.innerHTML = DB_TOP_ITEMS.map(item => `
            <div class="top-item">
                <div class="top-item-rank">${item.rank}</div>
                <div class="top-item-info">
                    <h4>${item.name}</h4>
                    <div class="top-item-bar">
                        <div class="top-item-bar-fill" style="width:${Math.round((item.sales / 245) * 100)}%;"></div>
                    </div>
                </div>
                <div class="top-item-sales">${item.sales}</div>
            </div>
        `).join('');
    }

    setTimeout(function () {
        drawBarChart('reservationChart', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [8, 12, 10, 15, 14, 18, 20]);
        drawBarChart('timeSlotChart', ['8am', '10am', '12pm', '2pm', '4pm', '6pm', '8pm'], [4, 6, 12, 8, 5, 15, 10]);
        drawBarChart('revenueChart', ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], [800, 1200, 950, 1400, 1300, 1800, 1600]);
    }, 100);
}

function drawBarChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.parentElement ? canvas.parentElement.offsetWidth || 400 : 400;
    const H = 200;
    canvas.width = W;
    canvas.height = H;
    const max = Math.max(...data);
    const barW = (W - 60) / labels.length - 8;
    const padL = 40, padB = 35, padT = 20;
    const chartH = H - padB - padT;
    ctx.clearRect(0, 0, W, H);
    ctx.strokeStyle = '#e8d5c4';
    ctx.lineWidth = 1;
    for (let i = 0; i <= 4; i++) {
        const y = padT + (chartH / 4) * i;
        ctx.beginPath();
        ctx.moveTo(padL, y);
        ctx.lineTo(W - 10, y);
        ctx.stroke();
    }
    data.forEach((val, i) => {
        const barH = (val / max) * chartH;
        const x = padL + i * (barW + 8);
        const y = padT + chartH - barH;
        ctx.fillStyle = '#D4A574';
        ctx.fillRect(x, y, barW, barH);
        ctx.fillStyle = '#6B4E3D';
        ctx.font = '11px Lato,sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(labels[i], x + barW / 2, H - 10);
    });
}

// ══════════════════════════════════════════════════════════════
// MODAL & NOTIFICATIONS
// ══════════════════════════════════════════════════════════════

function openModal(title, content) {
    const titleEl = document.getElementById('modalTitle');
    const bodyEl = document.getElementById('modalBody');
    const modal = document.getElementById('modal');
    if (!modal) return;
    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.innerHTML = content;
    modal.classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) modal.classList.remove('active');
}

function logout() {
    if (confirm('Logout?')) {
        // Clear PHP session via AJAX or redirect to logout script
        window.location.href = 'admin-logout.php';
    }
}

function _notifyCustomer(dataType) {
    try {
        localStorage.setItem('sc_customer_ping', JSON.stringify({ type: dataType, ts: Date.now() }));
        console.log('[Admin] Notified customer:', dataType);
    } catch(e) {}
}

function forceCustomerRefresh() {
    try {
        localStorage.setItem('sc_customer_ping', JSON.stringify({ type: 'force_refresh', ts: Date.now() }));
        showAdminNotification('Customer page refresh triggered!', 'success');
    } catch(e) {}
}

function showAdminNotification(message, type) {
    const n = document.createElement('div');
    n.className = (type === 'success') ? 'success-message' : 'error-message';
    n.innerHTML = message;
    Object.assign(n.style, {
        position: 'fixed', top: '20px', right: '20px',
        zIndex: '10000', minWidth: '280px', maxWidth: '420px',
        background: type === 'success' ? '#d1fae5' : '#fee2e2',
        color: type === 'success' ? '#065f46' : '#991b1b',
        padding: '1rem 1.25rem',
        borderRadius: '8px',
        borderLeft: `4px solid ${type === 'success' ? '#10b981' : '#ef4444'}`,
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
        fontWeight: '500'
    });
    document.body.appendChild(n);
    setTimeout(() => {
        n.style.transition = 'opacity 0.4s';
        n.style.opacity = '0';
        setTimeout(() => n.remove(), 400);
    }, 3000);
}
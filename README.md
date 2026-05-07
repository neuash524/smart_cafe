 Smart Café - Complete Production Setup Guide
✅ What's Included
Your complete Smart Café system is ready with ALL code files:

Core PHP Files (20+ files)
✅ customer_dashboard.php - Main customer website
✅ script.js - Customer JS with notification system
✅ styles.css - Complete styling
✅ admin_dashboard.php - Admin dashboard
✅ admin-script.js - Admin JS with email triggers
✅ admin-styles.css - Admin styling
✅ login.php - Customer login/registration
✅ admin-login.php - Admin login
✅ admin-logout.php - Admin logout
✅ logout.php - Customer logout
✅ config.php - Database configuration
✅ database_schema.sql - Complete database (11 tables)
✅ reservations.php - Reservation API with email
✅ orders.php - Order API with email triggers
✅ queue.php - Queue API with email triggers
✅ table.php - Table management API
✅ login_api.php - Authentication API
✅ register.php - Registration API
✅ notifications_api.php - Notification API
✅ users_api.php - User management API
✅ email_sender.php - PHPMailer SMTP integration
✅ cafe-data.js - Data layer with session sync
✅ api.js - API wrapper functions
✅ test_connection.php - Database test utility
✅ test_email.php - Email test utility

📁 Complete File Structure
text
smart-cafe/
├── PHPMailer-master/              ← EXTRACT THIS FOLDER (from zip)
│   └── src/                       ← The src folder MUST be inside
│       ├── PHPMailer.php
│       ├── SMTP.php
│       └── Exception.php
│
├── 📄 PHP FILES (Root Directory)
│   ├── customer_dashboard.php     ✅ Customer homepage
│   ├── admin_dashboard.php        ✅ Admin dashboard
│   ├── login.php                  ✅ Customer login + signup modal
│   ├── admin-login.php            ✅ Admin login
│   ├── admin-logout.php           ✅ Admin logout
│   ├── logout.php                 ✅ Customer logout
│   ├── config.php                 ⚠️ UPDATE DB CREDENTIALS
│   ├── reservations.php           ✅ API endpoint (emails on confirm/cancel)
│   ├── orders.php                 ✅ API endpoint (emails on status change)
│   ├── queue.php                  ✅ API endpoint (emails on seating)
│   ├── table.php                  ✅ API endpoint
│   ├── login_api.php              ✅ Authentication API
│   ├── register.php               ✅ Registration API
│   ├── notifications_api.php      ✅ Notification API
│   ├── users_api.php              ✅ User management API
│   ├── email_sender.php           ⚠️ UPDATE SMTP CREDENTIALS
│   ├── test_connection.php        ✅ Database test
│   ├── test_email.php             ✅ Email test
│   └── email_log.txt              (Auto-generated - email logs)
│
├── 📜 JAVASCRIPT FILES
│   ├── script.js                  ✅ Customer JS (notifications + cart)
│   ├── admin-script.js            ✅ Admin JS (email triggers + queue)
│   ├── cafe-data.js               ✅ Data layer (session + sync)
│   └── api.js                     ✅ API wrapper
│
├── 🎨 CSS FILES
│   ├── styles.css                 ✅ Customer styles
│   └── admin-styles.css           ✅ Admin styles
│
├── 🗄️ DATABASE
│   └── database_schema.sql        ⚠️ IMPORT THIS TO MySQL
│
└── 📷 IMAGES
    ├── menu/                      ← ADD YOUR 16 FOOD IMAGES HERE
    │   ├── pancakes.jpg
    │   ├── eggs-benedict.jpg
    │   ├── french-toast.jpg
    │   ├── avocado-toast.jpg
    │   ├── caesar-salad.jpg
    │   ├── club-sandwich.jpg
    │   ├── beef-burger.jpg
    │   ├── grilled-chicken.jpg
    │   ├── espresso.jpg
    │   ├── cappuccino.jpg
    │   ├── iced-latte.jpg
    │   ├── orange-juice.jpg
    │   ├── chocolate-cake.jpg
    │   ├── tiramisu.jpg
    │   ├── apple-pie.jpg
    │   └── cheesecake.jpg
    │
    ├── hero/                      ← OPTIONAL
    │   ├── cafe-interior.jpg
    │   └── coffee-cup.jpg
    │
    ├── features/                  ← OPTIONAL
    │   ├── reservation-icon.png
    │   ├── queue-icon.png
    │   ├── menu-icon.png
    │   └── payment-icon.png
    │
    ├── logo/                      ← OPTIONAL
    │   ├── logo.png
    │   └── favicon.ico
    │
    └── backgrounds/               ← OPTIONAL
        └── pattern-coffee.png
🚀 Quick Start (5 Steps)
Step 1: Extract PHPMailer
bash
# Extract PHPMailer-master.zip to your project folder
# After extraction, you should have:
# smart-cafe/PHPMailer-master/src/PHPMailer.php
# smart-cafe/PHPMailer-master/src/SMTP.php
# smart-cafe/PHPMailer-master/src/Exception.php

Step 2: Copy All Files to Web Server
bash
# XAMPP: C:\xampp\htdocs\smart_cafe\
# MAMP: /Applications/MAMP/htdocs/smart_cafe/
# WAMP: C:\wamp64\www\smart_cafe\

Step 3: Create Database & Import Schema
bash
# Option A: Using phpMyAdmin
# 1. Open http://localhost/phpmyadmin
# 2. Create database: smart_cafe (utf8mb4_general_ci)
# 3. Import database_schema.sql

# Option B: Using MySQL command line
mysql -u root -p
CREATE DATABASE smart_cafe;
USE smart_cafe;
SOURCE C:/xampp/htdocs/smart_cafe/database_schema.sql;
Step 4: Configure Credentials
📝 Update config.php:

php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');    // Change to 3307 if MySQL uses that port
define('DB_USER', 'root');
define('DB_PASS', '');        // Your MySQL password
define('DB_NAME', 'smart_cafe');
📧 Update email_sender.php (for email notifications):

php
define('SMTP_HOST', 'smtp.gmail.com');      // For Gmail
define('SMTP_PORT', 587);                    // 587 for TLS, 465 for SSL
define('SMTP_USER', 'bladin397@gmail.com'); // YOUR Gmail address
define('SMTP_PASS', 'vjvbdksgayrnrxlg');    // Gmail App Password (not your regular password)
define('SMTP_ENCRYPTION', 'tls');            // 'tls' or 'ssl'

Step 5: Add Your 16 Menu Images
text
Copy your food photos to: img/menu/
- pancakes.jpg
- eggs-benedict.jpg
- french-toast.jpg
- avocado-toast.jpg
- caesar-salad.jpg
- club-sandwich.jpg
- beef-burger.jpg
- grilled-chicken.jpg
- espresso.jpg
- cappuccino.jpg
- iced-latte.jpg
- orange-juice.jpg
- chocolate-cake.jpg
- tiramisu.jpg
- apple-pie.jpg
- cheesecake.jpg

Step 6: Test Your Installation
text
Test Database: http://localhost/smart_cafe/test_connection.php
Test Email:    http://localhost/smart_cafe/test_email.php
Customer Site: http://localhost/smart_cafe/customer_dashboard.php
Admin Login:   http://localhost/smart_cafe/admin-login.php
📧 EMAIL NOTIFICATION SYSTEM (CRITICAL)
What Emails Are Sent Automatically:
Action	Trigger	Email Sent To
Admin confirms reservation	Click "Confirm" in admin	Customer
Admin cancels reservation	Click "Cancel" in admin	Customer
Order → Preparing	Admin updates order status	Customer
Order → Ready	Admin updates order status	Customer
Order → Completed	Admin updates order status	Customer
Customer seated from queue	Admin clicks "Seat"	Customer
Gmail SMTP Setup (Required for emails to work):
Enable 2-Factor Authentication on your Google account

Generate App Password:

Go to Google Account → Security

Enable 2-Step Verification

Go to App Passwords

Select app: Mail

Select device: Other (Custom name)

Name it: Smart Café

Click Generate

Copy the 16-character password (spaces included)

Update email_sender.php:

php
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');  // Paste the 16-char password
Test Email:

text
Visit: http://localhost/smart_cafe/test_email.php
Check your inbox (and spam folder)
Email Troubleshooting:
Error Message	Solution
535-5.7.8 Username and Password not accepted	Wrong password - use App Password
Could not connect to SMTP host	Check internet / port 587
Connection timed out	Firewall blocking port 587
Email in spam folder	Mark as "Not spam" in your email
Email Log: All attempts are saved in email_log.txt in your project folder.

💾 Database Details
11 Tables Created by Schema:
Table	Purpose	Sample Data
users	Customer & admin accounts	13 users seeded
cafe_tables	Physical tables	8 tables (Table 1-8)
reservations	All reservations	40+ seeded
queue	Virtual queue entries	Queue entries
menu_categories	Food categories	Breakfast, Lunch, Beverages, Desserts
menu_items	Menu items	16 items seeded
orders	Customer orders	26 orders seeded
order_items	Items in each order	Linked to orders
payments	Payment records	Linked to orders
notifications	In-app notifications	Auto-generated
activity_logs	Admin action log	Auto-logged
Default Login Credentials:
Account Type	Email	Password
Admin	admin@smartcafe.com	admin123
Customer	aashish@example.com	customer123
Test Customer	(Register new)	(Your choice)

✨ Features That Work
Customer Website Features

1. Hero Section ✅
Animated title with typewriter effect
Live statistics (available tables, queue length, wait time)
Call-to-action buttons

2. Table Reservation ✅ (Pending Admin Approval)
Interactive form with validation
Date picker (min date = today)
8 clickable table cards with status (Available/Occupied/Reserved)
Login required to make reservation
Admin must approve before confirmation
Email notification sent when admin confirms/cancels

3. Menu & Pre-Order System ✅
16 menu items with images (emoji fallback)
Category filters (All, Breakfast, Lunch, Beverages, Desserts)
Quantity controls (+/- buttons)
Shopping cart with running total
Login required to order
Payment modal (Pay at counter)
Order status: Pending → Preparing → Ready → Completed
Email notifications for each status change

4. Virtual Queue ✅
Join queue form (login required)
Real-time queue display with positions
Wait time estimates
Admin must seat customers
Email notification when table is ready

5. Notification System ✅ (NEW)
🔔 Bell icon appears when logged in
Real-time notifications when admin:
Confirms/cancels your reservation
Updates your order status
Seats you from queue
Unread badge count
Click to view all notifications
Mark as read (single or all)

6. Authentication System ✅
Login page with email/password
Signup modal with:
First/Last name
Email (live availability check)
Phone number validation (+ prefix required)
Password strength meter
Terms agreement checkbox
Admin logins blocked from customer portal

Admin Dashboard Features
1. Dashboard Overview ✅
4 stat boxes (Available Tables, Today's Reservations, Current Queue, Today's Revenue)
Recent reservations list
Active queue display
Force customer page refresh button

2. Table Management ✅
Visual table layout grid
Toggle table status (Available/Occupied)
Add new tables modal
Search tables
Activity logging for status changes

3. Reservations Management ✅ (With Email)
Full reservations table
Filter tabs (All/Today/Upcoming/Past)
View/Edit reservation details
Confirm/Cancel → Auto-sends email to customer
Auto-updates table status

4. Queue Management ✅ (With Email)
Current queue list with positions
Call next customer
Seat customer → Auto-sends email notification
Remove from queue
Queue settings

5. Order Management ✅ (With Email)
Order cards with status
Status filters
Update order status → Auto-sends email at each step
In-app notifications sent to customer

6. Menu Management ✅
All 16 menu items displayed
Toggle item availability
Edit/Add menu items

7. Analytics ✅
Reservation trends chart
Popular time slots
Revenue overview
Top menu items ranking

🧪 Testing Checklist
Before First Use:
Run test_connection.php - Should show green success
Run test_email.php - Should send test email to your inbox

Customer Website:
Register new account (test phone: +1234567890)
Login with new account
🔔 Bell icon appears in navigation
Make a reservation (select table, date, time)
See "Reservation request submitted" message
Add items to cart
Checkout and pay
Join queue

Admin Dashboard:
Login at admin-login.php (admin@smartcafe.com / admin123)
Confirm the reservation you made
Check customer email - Should receive confirmation
Update order status (Pending → Preparing)
Check customer email - Should receive preparing notification
Update order status (Preparing → Ready)
Check customer email - Should receive ready notification
Update order status (Ready → Completed)
Check customer email - Should receive completed notification
Seat customer from queue
Check customer email - Should receive table ready notification

Notification System:
After admin actions, check 🔔 bell icon on customer dashboard
Unread badge should show number
Click bell to see notification list
Click notification to mark as read
Badge count decreases

🔧 Troubleshooting Guide
Database Connection Issues
Problem	Solution
"Database connection failed"	Check MySQL is running
Port 3306 error	Change to 3307 in config.php
Access denied	Check username/password
Unknown database	Create smart_cafe database first
Quick Test: http://localhost/smart_cafe/test_connection.php

Email Not Sending
Problem	Solution
SMTP error	Verify SMTP_USER and SMTP_PASS
535 Authentication failed	Use Gmail App Password, NOT regular password
Connection timeout	Check firewall / port 587
Email in spam	Mark as "Not spam"
Quick Test: http://localhost/smart_cafe/test_email.php

Check log: Open email_log.txt in project folder

Menu Images Not Loading
Problem	Solution
404 Image not found	Add images to img/menu/ folder
Wrong filename	Check case-sensitive matching
No images at all	System falls back to emojis automatically
Notifications Not Showing
Problem	Solution
No bell icon	Make sure you're logged in
Notifications empty	Admin needs to take action first
Console errors	Open F12 → Console tab
📱 Mobile Responsive
✅ Desktop (1200px+) - Full layout

✅ Tablet (768px - 1199px) - Adjusted spacing

✅ Mobile (< 768px) - Hamburger menu, stacked layouts

🔐 Security Features
Feature	Implementation
Password hashing	bcrypt (password_hash)
SQL injection	Prepared statements (PDO)
XSS protection	htmlspecialchars / sanitizeInput
Session management	PHP sessions + localStorage
Admin isolation	Separate login page, blocked from customer login
Input validation	Email, phone, date validation
📊 Database Schema Summary
text
users (id, email, password_hash, full_name, phone, user_type, is_active)
cafe_tables (id, table_number, capacity, status)
reservations (id, user_id, table_id, date, time, guests, status)
queue (id, user_id, customer_name, party_size, position, status)
menu_items (id, category_id, name, description, price, is_available)
orders (id, user_id, customer_name, total_amount, status, payment_status)
order_items (id, order_id, item_name, quantity, unit_price, subtotal)
payments (id, order_id, amount, payment_method, status)
notifications (id, user_id, type, title, message, is_read)
activity_logs (id, user_id, action, table_name, record_id, description)
🚀 Quick Reference
Important URLs
text
Customer Dashboard: http://localhost/smart_cafe/customer_dashboard.php
Admin Login:        http://localhost/smart_cafe/admin-login.php
Admin Dashboard:    http://localhost/smart_cafe/admin_dashboard.php
Test Database:      http://localhost/smart_cafe/test_connection.php
Test Email:         http://localhost/smart_cafe/test_email.php
Default Ports
text
Apache:     80 (XAMPP), 8888 (MAMP)
MySQL:      3306 (default), 3307 (some XAMPP)
Login Credentials
Type	Email	Password
Admin	admin@smartcafe.com	admin123
Customer	aashish@example.com	customer123
✨ Summary
What You Have:
✅ Complete PHP/MySQL backend with APIs
✅ Customer dashboard with real-time notifications
✅ Admin dashboard with email triggers
✅ Reservation, Queue, Order systems
✅ Email notification system (SMTP ready)
✅ Database schema with 11 tables + sample data
✅ Mobile responsive design
✅ 16 menu items ready

What You Need to Add:
📸 16 menu food images (optional - emoji fallback exists)
🔑 Gmail App Password for email notifications (if using Gmail)

Time to Complete Setup:
Basic (no email): 10 minutes
Full (with email): 20 minutes

🎉 Your Smart Café system is COMPLETE and PRODUCTION READY!

Project by: Aashish Neupane
Programme: BSc (Hons) Computer Science - DMU
Date: 6 May 2026
Status: ✅ Production Ready with Email Notifications!
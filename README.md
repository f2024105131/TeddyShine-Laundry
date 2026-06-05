# TeddyShine-Laundry
Laundry Management System for BSIT 4th Semester Project
# 🧺 TEDDY SHINE - Laundry Management System

![Version](https://img.shields.io/badge/version-1.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3)
![Status](https://img.shields.io/badge/status-Completed-brightgreen)

## 📋 Project Overview

**Teddy Shine** is a comprehensive web-based Laundry Management System developed as a semester project for BSIT 4th Semester Web Development course. The system allows residents to place laundry orders online, track their items in real-time through various processing stages, make payments, and view invoices. It also provides administrative tools for managing staff, services, delivery slots, and generating reports.

### 🎯 Problem Statement
Traditional laundry shops face challenges with manual order tracking, lost items, lack of transparency, delayed deliveries, and payment tracking issues. Teddy Shine solves these problems by providing a complete digital solution.

### ✨ Key Features
- 👤 **User Authentication** - Register, Login, Role-based access (Resident/Staff/Admin)
- 📦 **Order Management** - Place orders, select services, choose delivery slots
- 📍 **Real-time Tracking** - Track laundry through 5 stages (Washing → Drying → Ironing → Packing → Delivery)
- 👔 **Staff Management** - Assign collectors, washers, and delivery boys
- 💰 **Billing & Payments** - Automated invoicing, payment recording, due tracking
- 📊 **Reports** - Monthly reports, staff workload, revenue analysis
- 🕐 **Delivery Slots** - Manage morning, afternoon, and evening slots

---

## 🏗️ Database Schema

### Tables (15 tables + 5 views)

| Table | Description |
|-------|-------------|
| `Resident` | Customer information |
| `SignUp` | Authentication credentials |
| `Login` | Session tracking |
| `Staff` | Employee management |
| `DeliverySlots` | Time slots for delivery |
| `LaundryItem` | Individual laundry items |
| `ProcessStage` | Laundry stages (5 stages) |
| `Services` | Laundry services offered |
| `Orders` | Main order table |
| `OrderItems` | Services in each order |
| `Tracking` | Item tracking progress |
| `Invoice` | Billing information |
| `Payments` | Payment records |
| `Records` | Payment history (weak entity) |
| `Print` | Print receipt tracking |

### Views for Simplified Queries
- `v_Invoice_Payment_Summary` - Invoice with remaining balance
- `v_Print_Receipt` - Complete receipt data
- `v_Monthly_Records` - Monthly collection reports
- `v_Order_Payments` - Order to payment trace
- `v_Staff_Workload` - Staff performance metrics

---

## 🖥️ Technology Stack

| Technology | Purpose |
|------------|---------|
| **HTML5** | Page structure |
| **CSS3 (Internal)** | Styling within each PHP file |
| **Bootstrap 5.3** | Responsive design framework |
| **JavaScript** | Form validation, dynamic UI |
| **PHP 7.4+** | Backend logic, database operations |
| **MySQL 5.7+** | Database management |
| **XAMPP 3.3+** | Local server environment |
| **Git & GitHub** | Version control |

---

## 📁 Project Structure
TeddyShine_Laundry/
│





├── 📁 config/ # Configuration files
│ ├── database.php # MySQL connection
│ ├── session.php # Session management
│ └── functions.php # Common helper functions
│









├── 📁 includes/ # Reusable components
│ ├── header.php # Navbar + head section
│ ├── footer.php # Footer + scripts
│ ├── auth_check.php # Authentication checker
│ └── admin_check.php # Admin role verification
│










├── 📁 public/ # Public pages (no login required)
│ ├── index.php # Landing page
│ ├── register.php # Registration form
│ ├── login.php # Login form
│ └── logout.php # Logout handler
│






├── 📁 resident/ # Resident dashboard (10 files)
│ ├── dashboard.php # User dashboard
│ ├── place_order.php # Place new order
│ ├── my_orders.php # View all orders
│ ├── order_details.php # Single order details
│ ├── track_order.php # Track order status
│ ├── services.php # View services
│ ├── invoice.php # View invoice
│ ├── process_payment.php # Make payment
│ ├── payment_history.php # Payment records
│ └── profile.php # Edit profile
│




├── 📁 staff/ # Staff dashboard (5 files)
│ ├── dashboard.php # Staff overview
│ ├── assigned_orders.php # Assigned orders
│ ├── update_tracking.php # Update tracking
│ ├── save_tracking.php # Save tracking
│ └── delivery_list.php # Delivery schedule
│






├── 📁 admin/ # Admin panel (18 files)
│ ├── dashboard.php # Admin dashboard
│ ├── residents.php # Manage residents
│ ├── orders/ # Order management
│ ├── staff/ # Staff CRUD
│ ├── services/ # Service CRUD
│ ├── slots/ # Delivery slots CRUD
│ ├── invoices/ # Invoice management
│ └── reports/ # Report generation
│





├── 📁 assets/ # Static assets
│ ├── css/style.css # Custom styles
│ ├── js/ # JavaScript files
│ └── img/ # Images and icons
│




├── 📁 sql/ # Database files
│ └── teddy_shine.sql # Complete database dump
│



├── 📁 temp/ # Temporary files (prints)
├── .gitignore # Git ignore rules
├── README.md # This file
└── install.php # Setup script

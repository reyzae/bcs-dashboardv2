# 🎂 Bytebalok Dashboard

> **Business Management System untuk Kue Balok**

Modern dashboard untuk mengelola bisnis kue balok dengan fitur POS, inventory management, customer tracking, dan analytics.

---

## ✨ **Features**

- 🛒 **POS System** - Point of Sale dengan multiple payment methods
- 📦 **Product Management** - Kelola produk, kategori, dan stok
- 👥 **Customer Management** - Track customer dan loyalty
- 📊 **Reports & Analytics** - Sales reports dan statistik
- 🖨️ **Receipt Printing** - Professional receipt printing
- 📱 **Responsive Design** - Works on desktop, tablet, mobile
- 🔐 **User Management** - Multi-user dengan role-based access
- 📷 **Barcode Scanner** - Support untuk barcode scanner

---

## 🚀 **Quick Start**

### **1. Requirements**
```
- PHP 7.4+ (XAMPP/WAMP/MAMP)
- MySQL 5.7+
- Web browser (Chrome/Firefox/Edge)
```

### **2. Installation** (5 menit)

```bash
# 1. Clone atau download project
git clone https://github.com/your-repo/bcs-dashboard.git
cd bcs-dashboard

# 2. Create database
mysql -u root -p -e "CREATE DATABASE bytebalok_dashboard"

# 3. Import database
mysql -u root -p bytebalok_dashboard < database.sql

# 4. Import POS enhancements
mysql -u root -p bytebalok_dashboard < database_migration_pos_enhancements.sql

# 5. Configure database
# Edit config.env:
DB_HOST=localhost
DB_NAME=bytebalok_dashboard
DB_USER=root
DB_PASS=              # Kosongkan jika tidak ada password

# 6. Start server (PHP built-in)
cd public
php -S localhost:3000

# 6b. If using a different port/origin during development
# CORS now reflects the request origin to allow credentials,
# so ensure you access via the same origin as the dashboard pages.
```

### **3. Access Dashboard**
```
URL: http://localhost:3000/login.php
Username: admin
Password: password

> Tip: Centang “Remember me” saat login untuk sesi yang bertahan.
> Durasi default 30 hari, dapat diubah lewat `REMEMBER_DURATION_DAYS` pada `config.env`.
```

### **4. First Steps**
```
1. Login ✓
2. Go to Products → Add your kue balok products
3. Go to POS → Test transaction
4. Done! 🎉
```

---

## 📚 **Documentation**

**Lengkap dan terorganisir di folder `docs/`:**

### **Setup & Installation:**
- [Quick Start (5 minutes)](./docs/setup/QUICK_START.md) ⚡
- [Full Installation Guide](./docs/setup/INSTALLATION.md)
- [Deployment Guide](./docs/setup/DEPLOYMENT.md)

### **User Guides:**
- [POS System Complete Guide](./docs/guides/POS_SYSTEM.md) 🛒
- [User Management](./docs/guides/USER_MANAGEMENT.md)
- [Workflow & Best Practices](./docs/guides/WORKFLOW_GUIDE.md)
- [Testing Checklist](./docs/guides/TESTING_CHECKLIST.md)

### **Troubleshooting:**
- [Common Issues & Fixes](./docs/troubleshooting/COMMON_ISSUES.md) 🐛
- [MySQL XAMPP Fix](./docs/troubleshooting/FIX_MYSQL_XAMPP.md)
- [Navigation Fix](./docs/troubleshooting/NAVIGATION_FIX_GUIDE.md)

### **Reference:**
- [Changelog](./docs/CHANGELOG.md)
- [API Documentation](./docs/api/API_REFERENCE.md)

**📖 [Full Documentation Index](./docs/README.md)**

---

## 🎯 **Main Features**

### **1. POS System** 🛒
```
- Multiple payment methods (Cash, Card, QRIS, Transfer)
- Barcode scanner support
- Hold transactions
- Keyboard shortcuts (F2, F3, F8, F9, F12)
- Auto-save cart (localStorage)
- Real-time stock validation
- Professional receipt printing
```

### **2. Product Management** 📦
```
- CRUD products
- Categories & subcategories
- Stock management
- Low stock warnings
- Barcode support
- Image upload
- Bulk operations
```

### **3. Customer Management** 👥
```
- Customer database
- Search & autocomplete
- Purchase history
- Loyalty tracking
- Quick add from POS
```

### **4. Reports & Analytics** 📊
```
- Sales reports
- Top selling products
- Payment method breakdown
- Date range filtering
- Export to Excel/PDF
```

---

## 🏗️ **Project Structure**

```
bcs-dashboard/
├── app/
│   ├── controllers/     # Backend controllers
│   ├── models/          # Database models
│   └── config/          # Configuration files
├── public/
│   ├── dashboard/       # Dashboard pages
│   ├── assets/          # CSS, JS, images
│   └── api.php          # API router
├── docs/                # 📚 COMPLETE DOCUMENTATION
│   ├── setup/          # Setup guides
│   ├── guides/         # User guides
│   ├── troubleshooting/# Fixes & solutions
│   ├── api/            # API docs
│   └── archive/        # Old docs
├── database.sql         # Main database schema
├── database_migration_pos_enhancements.sql
├── config.env           # Database configuration
└── README.md            # This file
```

---

## ⚡ **Quick Commands**

```bash
# Start development server
cd public && php -S localhost:3000

# Import database
mysql -u root -p bytebalok_dashboard < database.sql

# Check database
mysql -u root -p bytebalok_dashboard -e "SHOW TABLES"

# Verify setup
mysql -u root -p bytebalok_dashboard < verify_pos_setup.sql
```

---

## 🐛 **Troubleshooting**

### **Cannot login?**
```
Username: admin
Password: password

If still error, check: docs/troubleshooting/COMMON_ISSUES.md

### **Cross-origin requests blocked?**
Pastikan origin (protocol+host+port) konsisten antara halaman dashboard dan panggilan API.
Di development, API akan meng-echo `Origin` untuk mengizinkan cookie/sesi.
Di production, origin dibatasi ke `APP_URL`.
```

### **Products tidak muncul?**
```
1. Import database.sql
2. Hard refresh: Ctrl + F5
3. Check console: F12
```

### **Database error?**
```
1. Check MySQL running
2. Verify config.env
3. Create database if missing
```

**📖 [Full Troubleshooting Guide](./docs/troubleshooting/COMMON_ISSUES.md)**

---

## 🔑 **Default Credentials**

```
Admin Account:
Username: admin
Password: password
Role: admin

⚠️ Change password after first login!
```

---

## 🛠️ **Technology Stack**

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+
- **Frontend:** Vanilla JavaScript (ES6+)
- **CSS:** Custom CSS dengan CSS Variables
- **Charts:** Chart.js
- **Icons:** Font Awesome 6

> Catatan: Untuk produksi, jalankan di HTTPS dan aktifkan HSTS (lihat `public/.htaccess`).

---

## 📦 **Database Tables**

```
Main Tables:
- users              # User accounts & roles
- products           # Product catalog
- categories         # Product categories
- customers          # Customer database
- transactions       # POS transactions
- transaction_items  # Transaction line items
- stock_movements    # Inventory movements
- hold_transactions  # Held POS transactions
- activity_logs      # Audit trail

Supporting Tables:
- orders             # Online orders
- order_items        # Order line items
```

---

## 🎂 **Khusus untuk Kue Balok**

### **Product Setup:**
```
Categories:
- Kue Balok Keju
- Kue Balok Coklat
- Kue Balok Pandan
- Kue Balok Mix
- Topping
- Packaging
```

### **Pricing:**
```
- Set harga per piece atau per box
- Update harga kapan saja (all users can update)
- Track price changes via activity logs
```

### **Stock Management:**
```
- Real-time stock tracking
- Low stock warnings
- Auto update setelah transaksi
- Stock movement audit trail
```

**📖 [Panduan Lengkap Kue Balok](./docs/guides/PANDUAN_KUE_BALOK.md)**

---

## 🚀 **Deployment**

**Production Ready!**

Panduan lengkap deploy ke production:
- [Deployment Guide](./docs/setup/DEPLOYMENT.md)

**Recommended Hosting:**
- VPS (DigitalOcean, Vultr, Linode)
- Shared Hosting (Hostinger, Niagahoster)
- Cloud (AWS, Google Cloud, Azure)

---

## 📝 **Version**

**Current Version:** 2.0  
**Status:** ✅ Production Ready  
**Last Updated:** October 2025

**Recent Updates:**
- ✅ Complete POS System
- ✅ Barcode scanner support
- ✅ Hold transaction feature
- ✅ Keyboard shortcuts
- ✅ Auto-save cart
- ✅ Professional receipt printing

**📖 [Full Changelog](./docs/CHANGELOG.md)**

---

## 📄 **License**

Proprietary - For Kue Balok Business Use

---

## 💬 **Support**

**Documentation:** [docs/README.md](./docs/README.md)  
**Issues:** Check [Troubleshooting](./docs/troubleshooting/COMMON_ISSUES.md)  
**Guides:** Check [User Guides](./docs/guides/)

---

## 🎉 **Get Started Now!**

```bash
# 1. Setup (5 menit)
mysql -u root -p -e "CREATE DATABASE bytebalok_dashboard"
mysql -u root -p bytebalok_dashboard < database.sql
mysql -u root -p bytebalok_dashboard < database_migration_pos_enhancements.sql

# 2. Configure
# Edit config.env with your database credentials

# 3. Run
cd public && php -S localhost:3000

# 4. Login
# http://localhost:3000/login.php
# Username: admin | Password: password
```

**Done! Ready to sell kue balok! 🎂💰**

---

**Happy Selling! 🚀**

*Bytebalok Dashboard - Built for Kue Balok Business*

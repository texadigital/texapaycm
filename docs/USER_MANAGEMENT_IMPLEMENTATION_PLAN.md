# TexaPay User Management & Features Implementation Plan

## 📋 Overview

This document outlines the comprehensive implementation plan for adding user management features, transaction limits, security settings, and support system to TexaPay.

## 🎯 Implementation Phases

### Phase 1: User Limits System (High Priority)
**Status**: Pending  
**Timeline**: Week 1  

#### 1.1 Database Structure
```sql
-- User Limits Table
CREATE TABLE user_limits (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    daily_limit_xaf INT DEFAULT 500000,
    monthly_limit_xaf INT DEFAULT 5000000,
    daily_count_limit INT DEFAULT 10,
    monthly_count_limit INT DEFAULT 100,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_limit (user_id)
);

-- Admin Settings Table
CREATE TABLE admin_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Transaction Limit Tracking
CREATE TABLE daily_transaction_summaries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    transaction_date DATE NOT NULL,
    total_amount_xaf INT DEFAULT 0,
    transaction_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, transaction_date)
);
```

#### 1.2 Features
- ✅ Dynamic daily/monthly XAF limits per user
- ✅ Transaction count limits (number of transactions)
- ✅ Real-time limit checking before transfers
- ✅ Admin configurable default limits
- ✅ Individual user limit overrides
- ✅ Limit usage tracking and reporting

#### 1.3 Implementation Files
- `database/migrations/create_user_limits_table.php`
- `database/migrations/create_admin_settings_table.php`
- `database/migrations/create_daily_transaction_summaries_table.php`
- `app/Models/UserLimit.php`
- `app/Models/AdminSetting.php`
- `app/Models/DailyTransactionSummary.php`
- `app/Services/LimitCheckService.php`
- `app/Http/Middleware/CheckUserLimits.php`

---

### Phase 2: Admin Dashboard (High Priority)
**Status**: Pending  
**Timeline**: Week 2  

#### 2.1 Admin Panel Structure
```
/admin/
├── dashboard (overview stats, charts)
├── users (manage users & limits)
│   ├── index (user list with search/filter)
│   ├── show/{user} (user details)
│   ├── edit/{user} (edit user & limits)
│   └── limits (bulk limit management)
├── transactions (view all transactions)
├── settings (global system settings)
├── reports (analytics & reports)
└── logs (system activity logs)
```

#### 2.2 Admin Features
- ✅ User management (view, edit, suspend, activate)
- ✅ Set individual user limits
- ✅ Global default limit settings
- ✅ Transaction monitoring and analytics
- ✅ System settings management
- ✅ Admin authentication & role-based access
- ✅ Export reports (PDF, Excel)
- ✅ Real-time dashboard with charts

#### 2.3 Admin Roles
- **Super Admin**: Full system access
- **Support Admin**: User support and basic management
- **View Only**: Read-only access to reports

#### 2.4 Implementation Files
- `app/Http/Controllers/Admin/AdminController.php`
- `app/Http/Controllers/Admin/UserManagementController.php`
- `app/Http/Controllers/Admin/TransactionController.php`
- `app/Http/Controllers/Admin/SettingsController.php`
- `app/Http/Controllers/Admin/ReportsController.php`
- `app/Http/Middleware/AdminAuth.php`
- `resources/views/admin/` (complete admin UI)

---

### Phase 3: User Profile Management (Medium Priority)
**Status**: Pending  
**Timeline**: Week 3  

#### 3.1 Profile Settings Structure
```
/profile/
├── personal-info (name, email, phone, avatar)
├── security (authentication methods)
├── limits (view current limits & usage)
├── notifications (email preferences)
└── account (account settings)
```

#### 3.2 Database Updates
```sql
-- Add profile fields to users table
ALTER TABLE users ADD COLUMN (
    full_name VARCHAR(255),
    notification_email VARCHAR(255),
    avatar_path VARCHAR(500),
    phone_verified_at TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    profile_completed_at TIMESTAMP NULL
);
```

#### 3.3 Features
- ✅ Update full name & notification email
- ✅ Phone number management & verification
- ✅ Profile picture upload & management
- ✅ View current transaction limits & usage
- ✅ Email notification preferences
- ✅ Account deactivation option

#### 3.4 Implementation Files
- `database/migrations/add_profile_fields_to_users_table.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Services/ProfileService.php`
- `resources/views/profile/` (profile management UI)

---

### Phase 4: Security Settings (High Priority)
**Status**: Pending  
**Timeline**: Week 3-4  

#### 4.1 Security Database Structure
```sql
-- User Security Settings
CREATE TABLE user_security_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    sms_login_enabled BOOLEAN DEFAULT true,
    face_id_enabled BOOLEAN DEFAULT false,
    pin_enabled BOOLEAN DEFAULT false,
    pin_hash VARCHAR(255),
    two_factor_enabled BOOLEAN DEFAULT false,
    backup_codes JSON,
    last_security_update TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_security (user_id)
);

-- Login History
CREATE TABLE login_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    login_method ENUM('sms', 'pin', 'face_id', 'password') DEFAULT 'sms',
    status ENUM('success', 'failed', 'blocked') DEFAULT 'success',
    location VARCHAR(255),
    device_info JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at)
);
```

#### 4.2 Security Features
- ✅ SMS Login toggle (on/off)
- ✅ Face ID authentication toggle (WebAuthn)
- ✅ PIN code setup and toggle
- ✅ Two-factor authentication with backup codes
- ✅ Login history & device management
- ✅ Security notifications & alerts
- ✅ Account lockout protection

#### 4.3 Implementation Files
- `database/migrations/create_user_security_settings_table.php`
- `database/migrations/create_login_history_table.php`
- `app/Models/UserSecuritySetting.php`
- `app/Models/LoginHistory.php`
- `app/Http/Controllers/SecurityController.php`
- `app/Services/AuthenticationService.php`
- `app/Http/Middleware/SecurityCheck.php`

---

### Phase 5: Support System (Medium Priority)
**Status**: Pending  
**Timeline**: Week 4  

#### 5.1 Support Features Structure
```
/support/
├── live-chat (real-time chat integration)
├── contact (call us, email, contact form)
├── help-center (searchable FAQs)
├── policies (privacy policy, terms of service)
├── tickets (support ticket system)
└── feedback (user feedback & ratings)
```

#### 5.2 Support Database Structure
```sql
-- Support Tickets
CREATE TABLE support_tickets (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ticket_number VARCHAR(20) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    category ENUM('technical', 'billing', 'general', 'complaint') DEFAULT 'general',
    assigned_to BIGINT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status_priority (status, priority),
    INDEX idx_user_created (user_id, created_at)
);

-- FAQ System
CREATE TABLE faqs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    order_index INT DEFAULT 0,
    is_published BOOLEAN DEFAULT true,
    view_count INT DEFAULT 0,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_order (category, order_index),
    INDEX idx_published (is_published)
);
```

#### 5.3 Support Features
- ✅ **Live Chat**: Tawk.to integration for real-time support
- ✅ **Contact Forms**: Structured contact with auto-routing
- ✅ **Call Support**: Click-to-call functionality
- ✅ **FAQ System**: Searchable knowledge base with categories
- ✅ **Support Tickets**: Full ticket management system
- ✅ **Policies**: Privacy Policy & Terms of Service pages
- ✅ **User Feedback**: Rating and feedback system

#### 5.4 Implementation Files
- `database/migrations/create_support_tickets_table.php`
- `database/migrations/create_faqs_table.php`
- `app/Models/SupportTicket.php`
- `app/Models/Faq.php`
- `app/Http/Controllers/SupportController.php`
- `app/Services/TicketService.php`
- `resources/views/support/` (complete support UI)

---

## 🎨 UI/UX Design Guidelines

### Design Principles
- **Consistency**: Match existing TexaPay design language
- **Mobile-First**: Responsive design for all screen sizes
- **Accessibility**: WCAG 2.1 AA compliant
- **Performance**: Optimized loading and interactions
- **Security**: Clear security indicators and feedback

### Color Scheme
- Primary: #3B82F6 (Blue)
- Secondary: #10B981 (Green)
- Warning: #F59E0B (Amber)
- Error: #EF4444 (Red)
- Dark: #1E293B (Slate)

### Component Library
- Modern card-based layouts
- Toggle switches for settings
- Progress indicators for limits
- Interactive charts and graphs
- Modal dialogs for confirmations

---

## 🔧 Technical Implementation Details

### Default Settings
```php
// Default user limits (XAF)
'default_daily_limit' => 500000,      // 500,000 XAF
'default_monthly_limit' => 5000000,   // 5,000,000 XAF
'default_daily_count' => 10,          // 10 transactions per day
'default_monthly_count' => 100,       // 100 transactions per month

// Admin settings
'admin_roles' => ['super_admin', 'support_admin', 'view_only'],
'session_timeout' => 120,             // 2 hours
'max_login_attempts' => 5,
'lockout_duration' => 30,             // 30 minutes
```

### Security Measures
- Rate limiting on all endpoints
- CSRF protection on all forms
- Input validation and sanitization
- Encrypted sensitive data storage
- Audit logging for admin actions
- Secure session management

### Performance Optimizations
- Database indexing for fast queries
- Caching for frequently accessed data
- Lazy loading for large datasets
- Image optimization for avatars
- CDN integration for static assets

---

## 📱 User Experience Flow

### Main Dashboard Navigation
```
TexaPay Dashboard
├── Send Money (existing)
├── Transactions (existing)
├── Profile Settings (NEW)
│   ├── Personal Information
│   ├── Security Settings
│   ├── Transaction Limits
│   └── Notification Preferences
└── Support (NEW)
    ├── Live Chat
    ├── Contact Us
    ├── Help Center (FAQs)
    ├── My Tickets
    └── Policies & Terms
```

### Admin Dashboard Navigation
```
Admin Dashboard
├── Overview (stats, charts, alerts)
├── User Management
│   ├── All Users (search, filter, export)
│   ├── User Details & Limits
│   ├── Suspended Users
│   └── Bulk Operations
├── Transaction Monitoring
│   ├── Real-time Transactions
│   ├── Failed Transactions
│   ├── Limit Violations
│   └── Transaction Reports
├── System Settings
│   ├── Default Limits
│   ├── Security Settings
│   ├── Email Templates
│   └── Feature Toggles
├── Support Management
│   ├── Open Tickets
│   ├── FAQ Management
│   └── User Feedback
└── Reports & Analytics
    ├── Transaction Reports
    ├── User Activity
    ├── System Performance
    └── Financial Reports
```

---

## ⏱️ Implementation Timeline

### Week 1: Foundation & Limits
- [ ] Database migrations for user limits
- [ ] UserLimit model and relationships
- [ ] LimitCheckService implementation
- [ ] Middleware for limit validation
- [ ] Basic admin authentication

### Week 2: Admin Dashboard
- [ ] Admin controllers and routes
- [ ] Admin UI components
- [ ] User management interface
- [ ] Limit management system
- [ ] Basic reporting dashboard

### Week 3: User Profile & Security
- [ ] Profile management system
- [ ] Security settings implementation
- [ ] Authentication method toggles
- [ ] Login history tracking
- [ ] Profile UI components

### Week 4: Support System
- [ ] Support ticket system
- [ ] FAQ management
- [ ] Live chat integration
- [ ] Contact forms
- [ ] Policy pages

### Week 5: Testing & Polish
- [ ] Comprehensive testing
- [ ] UI/UX refinements
- [ ] Performance optimization
- [ ] Documentation completion
- [ ] Deployment preparation

---

## 🔍 Configuration Questions

### 1. Default Limits
- **Daily Limit**: 500,000 XAF (≈ $850 USD)
- **Monthly Limit**: 5,000,000 XAF (≈ $8,500 USD)
- **Daily Count**: 10 transactions
- **Monthly Count**: 100 transactions

### 2. Admin Roles
- **Super Admin**: Full system access
- **Support Admin**: User support and basic management
- **View Only**: Read-only access

### 3. Live Chat Integration
- **Recommended**: Tawk.to (free, easy integration)
- **Alternative**: Intercom (paid, more features)
- **Custom**: Build internal chat system

### 4. Authentication Methods
- **SMS**: Primary authentication method
- **Face ID**: WebAuthn-based biometric authentication
- **PIN**: 4-6 digit PIN code
- **2FA**: TOTP-based two-factor authentication

### 5. Notification Types
- **Transaction confirmations**
- **Security alerts**
- **Limit warnings**
- **System maintenance**
- **Marketing updates** (opt-in)

---

## 📋 Success Metrics

### User Engagement
- Profile completion rate > 80%
- Security feature adoption > 60%
- Support ticket resolution time < 24 hours
- User satisfaction score > 4.5/5

### System Performance
- Page load times < 2 seconds
- API response times < 500ms
- 99.9% uptime
- Zero security incidents

### Business Impact
- Reduced support workload by 40%
- Increased user retention by 25%
- Improved compliance with regulations
- Enhanced user trust and satisfaction

---

## 🚀 Next Steps

1. **Review and approve** this implementation plan
2. **Start with Phase 1** (User Limits System)
3. **Set up development environment** for new features
4. **Create initial database migrations**
5. **Begin implementation** following the timeline

---

*This document will be updated as implementation progresses and requirements evolve.*

**Last Updated**: September 28, 2025  
**Version**: 1.0  
**Status**: Pending Approval

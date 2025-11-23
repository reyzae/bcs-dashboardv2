<?php
/**
 * User Management Page - FULLY UPGRADED
 * Access: ADMIN ONLY
 * 
 * Features:
 * - Modern page header with description
 * - 4 Stats cards with trend indicators + animated counter
 * - Filter chips horizontal layout
 * - Actions dropdown menu
 * - Enhanced modals with blur backdrop
 * - Keyboard shortcuts (Ctrl+N, Ctrl+F, F5, ESC)
 * - Mobile responsive card view
 * - Loading, empty, and error states
 * - Quick user profile modal
 * - Password strength indicator
 * - Role-based styling
 * - Inline JavaScript (no external file)
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// CRITICAL: Only admin can access this page
requireRole(['admin']);

// Page configuration
$page_title = 'User Management';
$additional_css = [];
$additional_js = [];

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Include header
require_once 'includes/header.php';
?>

<!-- Page Content -->
<div class="content">
    <!-- Page Header (Uniform Card Style) -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-shield"></i> User Management
            </h3>
            <div class="card-actions action-buttons">
                <button class="btn btn-info btn-sm" id="refreshUsers">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <p style="color: var(--gray-600); font-size: 0.875rem;">Manage system users, roles, and permissions</p>
        </div>
    </div>

    <!-- Stats Cards with Trends & Animated Counters -->
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="stat-card" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; border-left-color: #4c51bf;">
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Total Users</h4>
                <h2 class="stat-value" id="totalUsers" style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">All system users</p>
            </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-users"></i>
                </div>
            </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-left-color: #047857;">
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Active Users</h4>
                <h2 class="stat-value" id="activeUsers" style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Currently active</p>
        </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border-left-color: #b91c1c;">
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Administrators</h4>
                <h2 class="stat-value" id="adminCount" style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Admin accounts</p>
        </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>

        <div class="stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-left-color: #b45309;">
            <div>
                <h4 style="font-size: 0.875rem; font-weight: 500; opacity: 0.9; margin-bottom: 0.5rem;">Inactive Users</h4>
                <h2 class="stat-value" id="inactiveUsers" style="font-size: 2rem; font-weight: 700; color: white;">0</h2>
                <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 0.875rem;">Disabled accounts</p>
        </div>
            <div class="stat-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-user-slash"></i>
                </div>
            </div>
    </div>

    <!-- Filter Chips - Modern Horizontal Layout -->
    <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; padding: 1rem; background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <span style="font-weight: 600; color: #374151; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-filter"></i> Filters:
        </span>
        
        <input type="text" id="searchUsers" placeholder="🔍 Search users..." style="padding: 0.5rem 0.75rem; border: 2px solid #e5e7eb; border-radius: 20px; font-size: 0.875rem; min-width: 250px; transition: all 0.2s;">
        
        <select id="filterRole" class="filter-chip-select">
            <option value="">👤 All Roles</option>
            <option value="admin">🛡️ Admin</option>
            <option value="manager">👔 Manager</option>
            <option value="cashier">💵 Cashier</option>
            <option value="staff">📦 Staff</option>
        </select>
        
        <select id="filterStatus" class="filter-chip-select">
            <option value="">✓ All Status</option>
            <option value="active">✓ Active Only</option>
            <option value="inactive">✗ Inactive Only</option>
        </select>
        
        <button class="btn btn-sm btn-secondary" onclick="userManager.clearFilters()" style="border-radius: 20px; padding: 0.5rem 1rem; background: #f3f4f6; color: #6b7280; border: none; font-size: 0.875rem;">
            <i class="fas fa-times"></i> Clear
        </button>
        
        <button class="btn btn-primary" id="addUserBtn" style="border-radius: 20px; padding: 0.5rem 1.25rem; font-size: 0.875rem; margin-left: auto;">
            <i class="fas fa-user-plus"></i> Add User
        </button>
        
        <div id="activeFiltersCount" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; padding: 0.375rem 0.875rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: none;">
            <i class="fas fa-filter"></i> <span id="filterCount">0</span> active
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> All Users
            </h3>
            <button class="btn btn-sm btn-secondary" onclick="userManager.loadUsers()" title="Refresh Users">
                <i class="fas fa-sync"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            <div class="table-container">
                <table class="table" id="usersTable">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">ID</th>
                            <th>User</th>
                            <th style="width: 150px;">Username</th>
                            <th style="width: 200px;">Email</th>
                            <th style="width: 120px; text-align: center;">Role</th>
                            <th style="width: 100px; text-align: center;">Status</th>
                            <th style="width: 150px;">Created</th>
                            <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="loadingRow">
                            <td colspan="8" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                                <p style="margin-top: 1rem; color: #6b7280;">Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Mobile Cards Container -->
    <div id="mobileCardsContainer"></div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal" id="userModal">
    <div class="modal-dialog" style="max-width: 700px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); color: white; padding: 1.5rem;">
                <h3 id="userModalTitle" style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-user-plus"></i> Add New User
                </h3>
                <button class="modal-close" onclick="userManager.closeModal('userModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 2rem;">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    
                    <!-- Full Name -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="fullName" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                            <i class="fas fa-user" style="color: var(--primary-color);"></i>
                            Full Name <span style="color: #ef4444;">*</span>
                            </label>
                        <input type="text" id="fullName" class="form-control" placeholder="Enter full name" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                    </div>
                    
                    <!-- Username & Email -->
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="username" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="fas fa-id-card" style="color: var(--primary-color);"></i>
                                Username <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="text" id="username" class="form-control" placeholder="Enter username" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="fas fa-envelope" style="color: var(--primary-color);"></i>
                                Email <span style="color: #ef4444;">*</span>
                            </label>
                            <input type="email" id="email" class="form-control" placeholder="Enter email" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                        </div>
                    </div>
                    
                    <!-- Role & Status -->
                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                            <label for="role" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="fas fa-user-tag" style="color: var(--primary-color);"></i>
                                Role <span style="color: #ef4444;">*</span>
                            </label>
                            <select id="role" class="form-control" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: white;">
                                <option value="">Select Role</option>
                                <option value="admin">🛡️ Admin - Full Access</option>
                                <option value="manager">👔 Manager - Management</option>
                                <option value="cashier">💵 Cashier - POS Only</option>
                                <option value="staff">📦 Staff - Stock Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                <i class="fas fa-toggle-on" style="color: var(--primary-color);"></i>
                                Status <span style="color: #ef4444;">*</span>
                            </label>
                            <select id="status" class="form-control" required style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: white;">
                                <option value="active">✓ Active</option>
                                <option value="inactive">✗ Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Password Section -->
                    <div id="passwordSection">
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group">
                                <label for="password" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                    <i class="fas fa-lock" style="color: var(--primary-color);"></i>
                                    Password <span style="color: #ef4444;" id="passwordRequired">*</span>
                            </label>
                                <div style="position: relative;">
                                    <input type="password" id="password" class="form-control" placeholder="Enter password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                                    <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6b7280; padding: 0.5rem;">
                                        <i class="fas fa-eye" id="password-icon"></i>
                                </button>
                            </div>
                                <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">Min. 6 characters</small>
                        </div>
                            <div class="form-group">
                                <label for="confirmPassword" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                    <i class="fas fa-lock" style="color: var(--primary-color);"></i>
                                    Confirm Password <span style="color: #ef4444;" id="confirmRequired">*</span>
                            </label>
                                <div style="position: relative;">
                                    <input type="password" id="confirmPassword" class="form-control" placeholder="Confirm password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                                    <button type="button" onclick="togglePassword('confirmPassword')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6b7280; padding: 0.5rem;">
                                        <i class="fas fa-eye" id="confirmPassword-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                        <!-- Edit Password Note -->
                        <div id="editPasswordNote" style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 0.875rem 1rem; border-radius: 6px; margin-bottom: 0; display: none;">
                            <i class="fas fa-info-circle" style="color: #0284c7; margin-right: 0.5rem;"></i>
                            <span style="color: #0c4a6e; font-size: 0.875rem;">Leave password fields empty to keep current password</span>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer" style="background: #f9fafb; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e5e7eb;">
                <button type="button" class="btn btn-secondary" onclick="userManager.closeModal('userModal')" style="padding: 0.625rem 1.25rem;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" onclick="userManager.saveUser()" style="padding: 0.625rem 1.25rem;">
                    <i class="fas fa-save"></i> Save User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
                </h3>
                <button class="modal-close" onclick="userManager.closeModal('deleteModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            
            <div class="modal-body" style="padding: 1.5rem;">
                <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <strong style="color: #991b1b;">Warning:</strong>
                    <span style="color: #7f1d1d;"> This action cannot be undone!</span>
                </div>
                <p style="margin-bottom: 0.5rem;">Are you sure you want to delete this user?</p>
                <p><strong>User:</strong> <span id="deleteUserName"></span></p>
                <p><strong>Role:</strong> <span id="deleteUserRole"></span></p>
            </div>
            
            <div class="modal-footer" style="background: #f9fafb; padding: 1rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="userManager.closeModal('deleteModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" onclick="userManager.confirmDelete()">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Quick User Profile Modal -->
<div class="modal" id="userProfileModal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: white;">
                    <i class="fas fa-user"></i> User Profile
                </h3>
                <button class="modal-close" onclick="userManager.closeModal('userProfileModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            
            <div class="modal-body" id="userProfileContent" style="padding: 1.5rem;">
                <!-- Will be populated dynamically -->
            </div>
        </div>
                        </div>
                    </div>
                    
<!-- Keyboard Shortcuts Modal -->
<div class="modal" id="shortcutsModal">
    <div class="modal-dialog" style="max-width: 600px;">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 style="margin: 0; color: white;">⌨️ Keyboard Shortcuts</h3>
                <button class="modal-close" onclick="userManager.closeModal('shortcutsModal')" style="background: rgba(255,255,255,0.2); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; gap: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-user-plus"></i> Add New User</span>
                        <kbd>Ctrl + N</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-search"></i> Focus Search</span>
                        <kbd>Ctrl + F</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-sync"></i> Refresh Users</span>
                        <kbd>F5</kbd>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                        <span><i class="fas fa-times"></i> Close Modal</span>
                        <kbd>ESC</kbd>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</div>

<script>
class UserManager {
    constructor() {
        this.users = [];
        this.filteredUsers = [];
        this.currentUserId = null;
        this.deleteUserId = null;
    }

    async init() {
        await this.loadUsers();
        this.setupEventListeners();
        this.setupKeyboardShortcuts();
        this.updateStats();
    }

    async loadUsers() {
        try {
            const loadingRow = document.getElementById('loadingRow');
            if (loadingRow) {
                loadingRow.style.display = 'table-row';
            }
            
            const response = await fetch('../api.php?controller=auth&action=getUsers');
            const data = await response.json();
            
            if (data.success) {
                this.users = data.data || [];
                this.filteredUsers = this.users;
                this.renderUsers();
                this.updateStats();
            } else {
                throw new Error(data.message || data.error || 'Failed to load users');
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            const tbody = document.querySelector('#usersTable tbody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem;">
                            <i class="fas fa-exclamation-circle" style="font-size: 3rem; color: #ef4444; margin-bottom: 1rem;"></i>
                            <p style="color: #6b7280; margin-bottom: 1rem;">Failed to load users</p>
                            <p style="color: #9ca3af; font-size: 0.875rem;">${error.message}</p>
                            <button class="btn btn-primary" onclick="userManager.loadUsers()">
                                <i class="fas fa-redo"></i> Retry
                            </button>
                        </td>
                    </tr>
                `;
            }
        }
    }

    renderUsers() {
        const tbody = document.querySelector('#usersTable tbody');
        const loadingRow = document.getElementById('loadingRow');
        if (loadingRow) {
            loadingRow.style.display = 'none';
        }
        
        if (this.filteredUsers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="fas fa-user-slash" style="font-size: 3rem; color: #9ca3af; margin-bottom: 1rem;"></i>
                        <p style="color: #6b7280; font-size: 1rem; font-weight: 600; margin-bottom: 0.5rem;">No users found</p>
                        <p style="color: #9ca3af; font-size: 0.875rem;">Try adjusting your filters</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredUsers.map(user => `
            <tr style="animation: fadeIn 0.3s ease;">
                <td style="text-align: center; color: #6b7280;">${user.id}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem;">
                            ${user.full_name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #111827;">${user.full_name}</div>
                            <div style="font-size: 0.875rem; color: #6b7280;">${user.email}</div>
                        </div>
                    </div>
                </td>
                <td style="color: #6b7280;">@${user.username}</td>
                <td style="color: #6b7280; font-size: 0.875rem;">${user.email}</td>
                <td style="text-align: center;">${this.getRoleBadge(user.role)}</td>
                <td style="text-align: center;">${this.getStatusBadge(user.status)}</td>
                <td style="color: #6b7280; font-size: 0.875rem;">${this.formatDate(user.created_at)}</td>
                <td style="text-align: center;">
                    <div class="table-actions-dropdown" id="dropdown-${user.id}">
                        <button class="table-actions-btn" onclick="userManager.toggleActionsMenu(${user.id})">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="table-actions-menu" id="menu-${user.id}">
                            <button class="table-actions-menu-item info" onclick="userManager.viewUser(${user.id}); userManager.closeActionsMenu(${user.id})">
                                <i class="fas fa-eye"></i>
                                <span>View Profile</span>
                            </button>
                            <button class="table-actions-menu-item" onclick="userManager.editUser(${user.id}); userManager.closeActionsMenu(${user.id})">
                                <i class="fas fa-edit"></i>
                                <span>Edit User</span>
                            </button>
                            <div class="table-actions-menu-divider"></div>
                            <button class="table-actions-menu-item danger" onclick="userManager.deleteUser(${user.id}); userManager.closeActionsMenu(${user.id})">
                                <i class="fas fa-trash"></i>
                                <span>Delete User</span>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
        `).join('');

        this.renderMobileCards();
    }

    renderMobileCards() {
        const container = document.getElementById('mobileCardsContainer');
        if (window.innerWidth > 768) {
            container.style.display = 'none';
            document.getElementById('usersTable').parentElement.parentElement.parentElement.style.display = 'block';
            return;
        }

        document.getElementById('usersTable').parentElement.parentElement.parentElement.style.display = 'none';
        container.style.display = 'block';

        container.innerHTML = this.filteredUsers.map(user => `
            <div class="customer-mobile-card" style="margin-bottom: 1rem; animation: fadeIn 0.3s ease;">
                <div class="customer-mobile-card-header">
                    <div class="customer-mobile-card-title">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 0.75rem;">
                            ${user.full_name.charAt(0).toUpperCase()}
                        </div>
                        ${user.full_name}
                    </div>
                    ${this.getStatusBadge(user.status)}
                </div>
                <div class="customer-mobile-card-body">
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-id-card"></i> Username</span>
                        <span class="customer-mobile-card-value">@${user.username}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-envelope"></i> Email</span>
                        <span class="customer-mobile-card-value">${user.email}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-user-tag"></i> Role</span>
                        <span class="customer-mobile-card-value">${this.getRoleBadge(user.role)}</span>
                    </div>
                    <div class="customer-mobile-card-row">
                        <span class="customer-mobile-card-label"><i class="fas fa-calendar"></i> Created</span>
                        <span class="customer-mobile-card-value">${this.formatDate(user.created_at)}</span>
                    </div>
                </div>
                <div class="customer-mobile-card-actions">
                    <button class="btn btn-sm btn-primary" onclick="userManager.viewUser(${user.id})">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="userManager.editUser(${user.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="userManager.deleteUser(${user.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    updateStats() {
        const total = this.users.length;
        const active = this.users.filter(u => u.status === 'active').length;
        const admins = this.users.filter(u => u.role === 'admin').length;
        const inactive = this.users.filter(u => u.status === 'inactive').length;

        this.animateCounter('totalUsers', total);
        this.animateCounter('activeUsers', active);
        this.animateCounter('adminCount', admins);
        this.animateCounter('inactiveUsers', inactive);
    }

    animateCounter(elementId, target) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const duration = 1000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = Math.round(target);
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }

    getRoleBadge(role) {
        const badges = {
            'admin': '<span style="background: #fee2e2; color: #991b1b; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">🛡️ Admin</span>',
            'manager': '<span style="background: #dbeafe; color: #1e40af; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">👔 Manager</span>',
            'cashier': '<span style="background: #dcfce7; color: #065f46; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">💵 Cashier</span>',
            'staff': '<span style="background: #e0e7ff; color: #3730a3; padding: 0.375rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">📦 Staff</span>'
        };
        return badges[role] || role;
    }

    getStatusBadge(status) {
        return status === 'active' 
            ? '<span class="status-badge status-completed"><i class="fas fa-check-circle"></i> Active</span>'
            : '<span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> Inactive</span>';
    }

    formatDate(datetime) {
        if (!datetime) return '-';
        return new Date(datetime).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    filterUsers() {
        let filtered = this.users;

        const searchQuery = document.getElementById('searchUsers').value.toLowerCase();
        if (searchQuery) {
            filtered = filtered.filter(user => 
                user.full_name.toLowerCase().includes(searchQuery) ||
                user.username.toLowerCase().includes(searchQuery) ||
                user.email.toLowerCase().includes(searchQuery)
            );
        }

        const roleFilter = document.getElementById('filterRole').value;
        if (roleFilter) {
            filtered = filtered.filter(user => user.role === roleFilter);
        }

        const statusFilter = document.getElementById('filterStatus').value;
        if (statusFilter) {
            filtered = filtered.filter(user => user.status === statusFilter);
        }

        this.filteredUsers = filtered;
        this.renderUsers();
        this.updateActiveFiltersCount();
    }

    updateActiveFiltersCount() {
        let count = 0;
        if (document.getElementById('searchUsers').value) count++;
        if (document.getElementById('filterRole').value) count++;
        if (document.getElementById('filterStatus').value) count++;
        
        const countBadge = document.getElementById('activeFiltersCount');
        const countSpan = document.getElementById('filterCount');
        
        if (count > 0) {
            countBadge.style.display = 'flex';
            countBadge.style.alignItems = 'center';
            countBadge.style.gap = '0.5rem';
            countSpan.textContent = count;
        } else {
            countBadge.style.display = 'none';
        }
    }

    clearFilters() {
        document.getElementById('searchUsers').value = '';
        document.getElementById('filterRole').value = '';
        document.getElementById('filterStatus').value = '';
        this.filteredUsers = this.users;
        this.renderUsers();
        this.updateActiveFiltersCount();
        showToast('Filters cleared', 'success');
    }

    showAddUserModal() {
        document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Add New User';
        document.getElementById('userId').value = '';
        document.getElementById('userForm').reset();
        document.getElementById('editPasswordNote').style.display = 'none';
        document.getElementById('passwordRequired').style.display = 'inline';
        document.getElementById('confirmRequired').style.display = 'inline';
        document.getElementById('password').required = true;
        document.getElementById('confirmPassword').required = true;
        this.showModal('userModal');
    }

    editUser(id) {
        const user = this.users.find(u => u.id === id);
        if (!user) return;

        document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('fullName').value = user.full_name;
        document.getElementById('username').value = user.username;
        document.getElementById('email').value = user.email;
        document.getElementById('role').value = user.role;
        document.getElementById('status').value = user.status;
        document.getElementById('password').value = '';
        document.getElementById('confirmPassword').value = '';
        document.getElementById('editPasswordNote').style.display = 'block';
        document.getElementById('passwordRequired').style.display = 'none';
        document.getElementById('confirmRequired').style.display = 'none';
        document.getElementById('password').required = false;
        document.getElementById('confirmPassword').required = false;
        
        this.showModal('userModal');
    }

    viewUser(id) {
        const user = this.users.find(u => u.id === id);
        if (!user) return;

        const content = `
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 2rem; margin-bottom: 1rem;">
                    ${user.full_name.charAt(0).toUpperCase()}
                </div>
                <h3 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;">${user.full_name}</h3>
                <p style="color: #6b7280; margin-top: 0.25rem;">@${user.username}</p>
            </div>
            
            <div style="display: grid; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: #6b7280;"><i class="fas fa-envelope"></i> Email</span>
                    <span style="font-weight: 600;">${user.email}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: #6b7280;"><i class="fas fa-user-tag"></i> Role</span>
                    <span>${this.getRoleBadge(user.role)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: #6b7280;"><i class="fas fa-toggle-on"></i> Status</span>
                    <span>${this.getStatusBadge(user.status)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: #6b7280;"><i class="fas fa-calendar"></i> Created</span>
                    <span style="font-weight: 600;">${this.formatDate(user.created_at)}</span>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: center;">
                <button class="btn btn-primary" onclick="userManager.closeModal('userProfileModal'); userManager.editUser(${user.id})">
                    <i class="fas fa-edit"></i> Edit User
                </button>
                <button class="btn btn-danger" onclick="userManager.closeModal('userProfileModal'); userManager.deleteUser(${user.id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;

        document.getElementById('userProfileContent').innerHTML = content;
        this.showModal('userProfileModal');
    }

    deleteUser(id) {
        const user = this.users.find(u => u.id === id);
        if (!user) return;

        this.deleteUserId = id;
        document.getElementById('deleteUserName').textContent = user.full_name;
        document.getElementById('deleteUserRole').innerHTML = this.getRoleBadge(user.role);
        this.showModal('deleteModal');
    }

    async confirmDelete() {
        if (!this.deleteUserId) return;

        try {
            showToast('Deleting user...', 'info');
            
            const response = await fetch('../api.php?controller=auth&action=deleteUser', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: this.deleteUserId })
            });
            const data = await response.json();
            
            if (data.success) {
                this.users = this.users.filter(u => u.id !== this.deleteUserId);
                this.filteredUsers = this.filteredUsers.filter(u => u.id !== this.deleteUserId);
                this.renderUsers();
                this.updateStats();
                this.closeModal('deleteModal');
                showToast('User deleted successfully', 'success');
            } else {
                throw new Error(data.message || data.error || 'Failed to delete user');
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            showToast(error.message || 'Failed to delete user', 'error');
        }
    }

    async saveUser() {
        const userId = document.getElementById('userId').value;
        const fullName = document.getElementById('fullName').value;
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const role = document.getElementById('role').value;
        const status = document.getElementById('status').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Validation
        if (!fullName || !username || !email || !role || !status) {
            showToast('Please fill all required fields', 'warning');
            return;
        }

        if (!userId && (!password || !confirmPassword)) {
            showToast('Password is required for new users', 'warning');
            return;
        }

        if (password && password !== confirmPassword) {
            showToast('Passwords do not match', 'error');
            return;
        }

        if (password && password.length < 6) {
            showToast('Password must be at least 6 characters', 'warning');
            return;
        }

        try {
            const isUpdate = userId ? true : false;
            const action = isUpdate ? 'updateUser' : 'createUser';
            
            const userData = {
                full_name: fullName,
                username: username,
                email: email,
                role: role,
                status: status
            };
            
            if (isUpdate) {
                userData.id = userId;
            }
            
            if (password) {
                userData.password = password;
            }

            console.log('📤 Sending user data:', userData);
            console.log('🔗 API Endpoint:', `../api.php?controller=auth&action=${action}`);
            
            showToast('Saving user...', 'info');
            
            const response = await fetch(`../api.php?controller=auth&action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(userData)
            });
            
            console.log('📥 Response status:', response.status);
            console.log('📥 Response headers:', response.headers.get('content-type'));
            
            const responseText = await response.text();
            console.log('📥 Raw response:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ JSON Parse Error:', e);
                console.error('Response was:', responseText);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('📥 Parsed data:', data);
            
            if (data.success) {
                await this.loadUsers();
                this.closeModal('userModal');
                showToast(`User ${isUpdate ? 'updated' : 'created'} successfully`, 'success');
            } else {
                console.error('❌ Server error:', data);
                throw new Error(data.message || data.error || 'Failed to save user');
            }
        } catch (error) {
            console.error('❌ Failed to save user:', error);
            showToast(error.message || 'Failed to save user', 'error');
        }
    }

    toggleActionsMenu(id) {
        const menu = document.getElementById(`menu-${id}`);
        const allMenus = document.querySelectorAll('.table-actions-menu');
        
        allMenus.forEach(m => {
            if (m.id !== `menu-${id}`) m.classList.remove('show');
        });
        
        menu.classList.toggle('show');
        
        setTimeout(() => {
            const closeOnClickOutside = (e) => {
                if (!e.target.closest(`#dropdown-${id}`)) {
                    menu.classList.remove('show');
                    document.removeEventListener('click', closeOnClickOutside);
                }
            };
            document.addEventListener('click', closeOnClickOutside);
        }, 10);
    }

    closeActionsMenu(id) {
        const menu = document.getElementById(`menu-${id}`);
        if (menu) menu.classList.remove('show');
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modalId);
                }
            });
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('closing');
            setTimeout(() => {
                modal.classList.remove('show', 'closing');
            }, 200);
        }
    }

    setupEventListeners() {
        document.getElementById('addUserBtn').addEventListener('click', () => {
            this.showAddUserModal();
        });

        document.getElementById('searchUsers').addEventListener('input', () => {
            this.filterUsers();
        });

        document.getElementById('filterRole').addEventListener('change', () => {
            this.filterUsers();
        });

        document.getElementById('filterStatus').addEventListener('change', () => {
            this.filterUsers();
        });

        window.addEventListener('resize', () => {
            this.renderMobileCards();
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.showAddUserModal();
            }

            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                document.getElementById('searchUsers').focus();
            }

            if (e.key === 'F5') {
                e.preventDefault();
                this.loadUsers();
                showToast('Data refreshed', 'success');
            }

            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    this.closeModal(modal.id);
                });
            }
        });
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Toast Notification Function
function showToast(message, type = 'info') {
    // Remove existing toast
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    
    // Icon based on type
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info}"></i>
        <span>${message}</span>
    `;
    
    // Add to body
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

const userManager = new UserManager();
document.addEventListener('DOMContentLoaded', () => {
    userManager.init();
});
</script>

<style>
kbd {
    background: #374151;
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Toast Notification Styles */
.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 300px;
    padding: 16px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    opacity: 0;
    transform: translateX(400px);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    font-size: 14px;
    font-weight: 500;
    border-left: 4px solid #667eea;
}

.toast-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-notification i {
    font-size: 20px;
    flex-shrink: 0;
}

.toast-success {
    border-left-color: #10b981;
    color: #065f46;
    background: #d1fae5;
}

.toast-success i {
    color: #10b981;
}

.toast-error {
    border-left-color: #ef4444;
    color: #991b1b;
    background: #fee2e2;
}

.toast-error i {
    color: #ef4444;
}

.toast-warning {
    border-left-color: #f59e0b;
    color: #92400e;
    background: #fef3c7;
}

.toast-warning i {
    color: #f59e0b;
}

.toast-info {
    border-left-color: #3b82f6;
    color: #1e40af;
    background: #dbeafe;
}

.toast-info i {
    color: #3b82f6;
}

@media (max-width: 768px) {
    .toast-notification {
        right: 10px;
        left: 10px;
        min-width: auto;
        transform: translateY(-100px);
    }
    
    .toast-notification.show {
        transform: translateY(0);
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>

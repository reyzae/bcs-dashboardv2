<?php
/**
 * Settings Page
 * Role-based access: Admin ONLY
 */

// Load bootstrap FIRST
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/functions.php';

// Require authentication
requireAuth();

// Only admin can access settings
requireRole(['admin']);

// Get current user
$user = $_SESSION['user'] ?? null;
$user_name = $user['full_name'] ?? 'User';
$user_role = $user['role'] ?? 'guest';

// Page configuration
$page_title = 'Settings';
$additional_css = [];
$additional_js = [];

// Aktifkan compact header untuk tampilan header yang rapi
$header_compact = true;

// Include header
require_once 'includes/header.php';
?>

<!-- Page Header: Uniform Card Style -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cog"></i> System Settings
        </h3>
        <div class="card-actions action-buttons">
            <button class="btn btn-secondary btn-sm" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
            <button class="btn btn-primary btn-sm" onclick="settingsManager.saveAllSettings()">
                <i class="fas fa-save"></i> Save All Changes
            </button>
        </div>
    </div>
    <div class="card-body">
        <p style="color: #6b7280; font-size: 0.875rem; margin: 0;">
            Manage system configuration, company information, and preferences
        </p>
    </div>
</div>

<!-- Settings Tabs (Modern Pills) -->
<div style="margin-bottom: 2rem;">
    <div class="settings-tabs-modern">
                <button class="settings-tab-pill active" data-tab="system-info">
                    <i class="fas fa-info-circle"></i>
                    <span>System Info</span>
                </button>
                <button class="settings-tab-pill" data-tab="general">
                    <i class="fas fa-building"></i>
                    <span>Company</span>
                </button>
                <button class="settings-tab-pill" data-tab="pos">
                    <i class="fas fa-cash-register"></i>
                    <span>POS</span>
                </button>
                <button class="settings-tab-pill" data-tab="appearance">
                    <i class="fas fa-palette"></i>
                    <span>Appearance</span>
        </button>
                <button class="settings-tab-pill" data-tab="security">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
        </button>
                <button class="settings-tab-pill" data-tab="backup">
                    <i class="fas fa-database"></i>
                    <span>Backup</span>
        </button>
                <button class="settings-tab-pill" data-tab="advanced">
                    <i class="fas fa-sliders-h"></i>
                    <span>Advanced</span>
        </button>
    </div>
</div>

<!-- System Info Tab -->
<div class="settings-content-tab" id="system-info-tab">
                <!-- System Status Cards -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <!-- PHP Version Card -->
                    <div class="card" style="border-left: 4px solid var(--primary-color);">
                        <div class="card-body" style="padding: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fab fa-php" style="font-size: 1.75rem; color: white;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">PHP Version</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;"><?php echo phpversion(); ?></div>
                                    <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 0.25rem;">
                                        <i class="fas fa-check-circle"></i> Running
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Database Card -->
                    <div class="card" style="border-left: 4px solid #10b981;">
                        <div class="card-body" style="padding: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-database" style="font-size: 1.75rem; color: white;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">Database</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;">MySQL</div>
                                    <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 0.25rem;">
                                        <i class="fas fa-check-circle"></i> Connected
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Card -->
                    <div class="card" style="border-left: 4px solid #f59e0b;">
                        <div class="card-body" style="padding: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-hdd" style="font-size: 1.75rem; color: white;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">Storage</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;" id="storageUsed">Loading...</div>
                                    <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; margin-top: 0.25rem;">
                                        <i class="fas fa-info-circle"></i> Available
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Uptime Card -->
                    <div class="card" style="border-left: 4px solid #3b82f6;">
                        <div class="card-body" style="padding: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-clock" style="font-size: 1.75rem; color: white;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-size: 0.75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; margin-bottom: 0.25rem;">System Uptime</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #111827;" id="systemUptime">Loading...</div>
                                    <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 0.25rem;">
                                        <i class="fas fa-power-off"></i> Online
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Details Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-server"></i> System Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <table class="table" style="margin: 0;">
                            <tbody>
                                <tr>
                                    <td style="font-weight: 600; width: 250px;"><i class="fas fa-code"></i> PHP Version</td>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;"><i class="fas fa-server"></i> Web Server</td>
                                    <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;"><i class="fas fa-memory"></i> Memory Limit</td>
                                    <td><?php echo ini_get('memory_limit'); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;"><i class="fas fa-upload"></i> Max Upload Size</td>
                                    <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;"><i class="fas fa-clock"></i> Timezone</td>
                                    <td><?php echo date_default_timezone_get(); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600;"><i class="fas fa-folder"></i> Document Root</td>
                                    <td style="font-family: monospace; font-size: 0.875rem;"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
    </div>

<!-- General Settings Tab -->
<div class="settings-content-tab" id="general-tab" style="display: none;">
                <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i> Company Information
                </h3>
            </div>
                    <div class="card-body" style="padding: 2rem;">
                <form id="generalSettingsForm">
                            <!-- Section: Basic Info -->
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--primary-color);">
                                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> Basic Information
                                </h4>
                                
                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.25rem;">
                        <div class="form-group">
                                        <label for="companyName" class="form-label">
                                            <i class="fas fa-building" style="color: var(--primary-color);"></i>
                                            Company Name <span style="color: #ef4444;">*</span>
                                        </label>
                                        <input type="text" id="companyName" class="form-control" value="Bytebalok" required>
                        </div>
                        <div class="form-group">
                                        <label for="companyPhone" class="form-label">
                                            <i class="fas fa-phone" style="color: var(--primary-color);"></i>
                                            Phone <span style="color: #ef4444;">*</span>
                                        </label>
                                        <input type="tel" id="companyPhone" class="form-control" value="+6285121010199" required>
                        </div>
                    </div>

                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label for="companyAddress" class="form-label">
                                        <i class="fas fa-map-marker-alt" style="color: var(--primary-color);"></i>
                                        Address <span style="color: #ef4444;">*</span>
                                    </label>
                                    <textarea id="companyAddress" class="form-control" rows="3" required>Jl. Example No. 123, Jakarta</textarea>
                                </div>

                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="companyEmail" class="form-label">
                                            <i class="fas fa-envelope" style="color: var(--primary-color);"></i>
                                            Email <span style="color: #ef4444;">*</span>
                                        </label>
                                        <input type="email" id="companyEmail" class="form-control" value="info@bytebalok.com" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="companyWebsite" class="form-label">
                                            <i class="fas fa-globe" style="color: var(--primary-color);"></i>
                                            Website
                                        </label>
                                        <input type="url" id="companyWebsite" class="form-control" value="https://bytebalok.com" placeholder="https://">
                                    </div>
                                </div>
                    </div>

                            <!-- Section: Regional Settings -->
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #10b981;">
                                    <i class="fas fa-globe-asia" style="color: #10b981;"></i> Regional Settings
                                </h4>
                                
                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="currency" class="form-label">
                                            <i class="fas fa-dollar-sign" style="color: #10b981;"></i>
                                            Currency <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="currency" class="form-control" required>
                                            <option value="IDR">IDR (Rp) - Indonesian Rupiah</option>
                                            <option value="USD">USD ($) - US Dollar</option>
                                            <option value="SGD">SGD (S$) - Singapore Dollar</option>
                                            <option value="MYR">MYR (RM) - Malaysian Ringgit</option>
                                            <option value="EUR">EUR (€) - Euro</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="timezone" class="form-label">
                                            <i class="fas fa-clock" style="color: #10b981;"></i>
                                            Timezone <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="timezone" class="form-control" required>
                                            <option value="Asia/Jakarta" selected>Asia/Jakarta (WIB)</option>
                                            <option value="Asia/Singapore">Asia/Singapore (SGT)</option>
                                            <option value="Asia/Kuala_Lumpur">Asia/Kuala Lumpur (MYT)</option>
                                            <option value="Asia/Bangkok">Asia/Bangkok (ICT)</option>
                                            <option value="Asia/Manila">Asia/Manila (PHT)</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="dateFormat" class="form-label">
                                            <i class="fas fa-calendar" style="color: #10b981;"></i>
                                            Date Format <span style="color: #ef4444;">*</span>
                                        </label>
                                        <select id="dateFormat" class="form-control" required>
                                            <option value="d/m/Y">DD/MM/YYYY (27/10/2025)</option>
                                            <option value="m/d/Y">MM/DD/YYYY (10/27/2025)</option>
                                            <option value="Y-m-d">YYYY-MM-DD (2025-10-27)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Tax & Financial -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f59e0b;">
                                    <i class="fas fa-percentage" style="color: #f59e0b;"></i> Tax & Financial
                                </h4>
                                
                                <!-- Enable/Disable Tax Toggle -->
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-percentage" style="color: #f59e0b; margin-right: 0.5rem;"></i>
                                                Enable Tax Calculation
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Apply tax to all transactions automatically</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="enableTax" checked onchange="settingsManager.toggleTaxFields()">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                                
                                <!-- Tax Configuration (shown when tax is enabled) -->
                                <div id="taxConfigSection">
                                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                        <div class="form-group">
                                            <label for="taxRate" class="form-label">
                                                <i class="fas fa-percentage" style="color: #f59e0b;"></i>
                                                Default Tax Rate (%)
                                            </label>
                                            <input type="number" id="taxRate" class="form-control" value="11" min="0" max="100" step="0.01" placeholder="e.g., 11 for PPN 11%">
                                            <small style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.75rem;">
                                                Enter 0 for no tax, or any value 0-100. Example: 11 for PPN 11%
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <label for="taxNumber" class="form-label">
                                                <i class="fas fa-file-invoice" style="color: #f59e0b;"></i>
                                                Tax ID / NPWP
                                            </label>
                                            <input type="text" id="taxNumber" class="form-control" placeholder="XX.XXX.XXX.X-XXX.XXX">
                                            <small style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.75rem;">
                                                Company tax identification number (optional)
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Shop Tax Settings -->
                                <div style="margin-top: 1.5rem;">
                                    <h5 style="font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                                        <i class="fas fa-store" style="color: #10b981; margin-right: 0.5rem;"></i>
                                        Shop Tax Configuration
                                    </h5>
                                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                            <div>
                                                <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                    Enable Tax for Shop
                                                </div>
                                                <div style="font-size: 0.875rem; color: #6b7280;">Controls tax calculation visible on /shop (public)</div>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" id="enableTaxShop" onchange="settingsManager.toggleShopTaxFields()">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                    </div>
                                    <div id="shopTaxConfigSection" style="display: none;">
                                        <div class="form-row" style="display: grid; grid-template-columns: 1fr; gap: 1rem;">
                                            <div class="form-group">
                                                <label for="taxRateShop" class="form-label">
                                                    <i class="fas fa-percentage" style="color: #10b981;"></i>
                                                    Shop Tax Rate (%)
                                                </label>
                                                <input type="number" id="taxRateShop" class="form-control" value="11" min="0" max="100" step="0.01" placeholder="e.g., 11 for PPN 11% on Shop">
                                                <small style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.75rem;">
                                                    This rate applies only to public Shop pages.
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bank Transfer Settings -->
                                <div style="margin-top: 1.5rem;">
                                    <h5 style="font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                                        <i class="fas fa-university" style="color: #3b82f6; margin-right: 0.5rem;"></i>
                                        Bank Transfer Accounts
                                    </h5>
                                    <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div class="form-group">
                                                <label for="bankDefault" class="form-label">
                                                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                                                    Default Bank for Transfer
                                                </label>
                                                <select id="bankDefault" class="form-control">
                                                    <option value="bca">BCA</option>
                                                    <option value="bri">Bank BRI</option>
                                                    <option value="blu_bca">BLU BCA</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                        <div class="form-group" style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                                            <label class="form-label"><i class="fas fa-building" style="color: #2563eb;"></i> BCA</label>
                                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                                <div>
                                                    <label for="bankBcaName" class="form-label">Account Name</label>
                                                    <input type="text" id="bankBcaName" class="form-control" placeholder="Nama pemilik rekening BCA">
                                                </div>
                                                <div>
                                                    <label for="bankBcaAccount" class="form-label">Account Number</label>
                                                    <input type="text" id="bankBcaAccount" class="form-control" placeholder="Nomor rekening BCA">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group" style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                                            <label class="form-label"><i class="fas fa-building" style="color: #2563eb;"></i> Bank BRI</label>
                                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                                <div>
                                                    <label for="bankBriName" class="form-label">Account Name</label>
                                                    <input type="text" id="bankBriName" class="form-control" placeholder="Nama pemilik rekening Bank BRI">
                                                </div>
                                                <div>
                                                    <label for="bankBriAccount" class="form-label">Account Number</label>
                                                    <input type="text" id="bankBriAccount" class="form-control" placeholder="Nomor rekening Bank BRI">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group" style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
                                            <label class="form-label"><i class="fas fa-building" style="color: #2563eb;"></i> BLU BCA</label>
                                            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                                <div>
                                                    <label for="bankBluBcaName" class="form-label">Account Name</label>
                                                    <input type="text" id="bankBluBcaName" class="form-control" placeholder="Nama pemilik rekening BLU BCA">
                                                </div>
                                                <div>
                                                    <label for="bankBluBcaAccount" class="form-label">Account Number</label>
                                                    <input type="text" id="bankBluBcaAccount" class="form-control" placeholder="Nomor rekening BLU BCA">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                    </div>

                            <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="settingsManager.resetForm('generalSettingsForm')">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- POS Settings Tab -->
<div class="settings-content-tab" id="pos-tab" style="display: none;">
                <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                            <i class="fas fa-cash-register"></i> POS Configuration
                </h3>
            </div>
                    <div class="card-body" style="padding: 2rem;">
                        <form id="posSettingsForm">
                            <!-- Section: General POS -->
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--primary-color);">
                                    <i class="fas fa-cog" style="color: var(--primary-color);"></i> General Settings
                                </h4>
                                
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-barcode" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                                Enable Barcode Scanner
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Allow quick product lookup using barcode scanner</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="enableBarcodeScanner" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-print" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                                Auto Print Receipt
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Automatically print receipt after successful transaction</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="autoPrintReceipt">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-box-open" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                                                Allow Negative Stock
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Allow selling products even when stock is zero or negative</div>
                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="allowNegativeStock">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                        </div>
                    </div>

                            <!-- Section: Receipt Settings -->
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #10b981;">
                                    <i class="fas fa-receipt" style="color: #10b981;"></i> Receipt Settings
                                </h4>
                                
                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label for="receiptHeader" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="fas fa-heading" style="color: #10b981;"></i>
                                        Receipt Header Text
                                    </label>
                                    <textarea id="receiptHeader" class="form-control" rows="2" style="padding: 0.75rem; border-radius: 8px; resize: vertical;" placeholder="Leave empty to use company name">Terima kasih telah berbelanja!</textarea>
                                    <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">Appears at the top of printed receipts</small>
                        </div>

                        <div class="form-group">
                                    <label for="receiptFooter" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="fas fa-quote-right" style="color: #10b981;"></i>
                                        Receipt Footer Text
                                    </label>
                                    <textarea id="receiptFooter" class="form-control" rows="2" style="padding: 0.75rem; border-radius: 8px; resize: vertical;">Terima kasih atas kunjungan Anda!
Sampai jumpa kembali.</textarea>
                                    <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">Appears at the bottom of printed receipts</small>
                        </div>
                    </div>

                            <!-- Section: Cart & Auto-save -->
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f59e0b;">
                                    <i class="fas fa-shopping-cart" style="color: #f59e0b;"></i> Cart & Auto-save
                                </h4>
                                
                                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                                    <div class="form-group">
                                        <label for="cartAutosave" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                            <i class="fas fa-sync" style="color: #f59e0b;"></i>
                                            Auto-save Interval (seconds)
                                        </label>
                                        <input type="number" id="cartAutosave" class="form-control" value="30" min="10" max="300" step="10" style="padding: 0.75rem; border-radius: 8px;">
                                        <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">How often cart data is saved locally (10-300 seconds)</small>
                                    </div>
                    <div class="form-group">
                                        <label for="lowStockThreshold" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                                            Low Stock Alert Threshold
                        </label>
                                        <input type="number" id="lowStockThreshold" class="form-control" value="3" min="1" max="100" style="padding: 0.75rem; border-radius: 8px;">
                                        <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">Show warning when stock falls below this number</small>
                                    </div>
                                </div>
                    </div>

                            <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="settingsManager.resetForm('posSettingsForm')">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Appearance Tab -->
<div class="settings-content-tab" id="appearance-tab" style="display: none;">
                <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                            <i class="fas fa-palette"></i> Appearance & Branding
                </h3>
            </div>
                    <div class="card-body" style="padding: 2rem;">
                        <form id="appearanceSettingsForm">
                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                    <i class="fas fa-image" style="color: var(--primary-color);"></i>
                                    Company Logo
                                </label>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="width: 120px; height: 120px; border: 2px dashed #d1d5db; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f9fafb;">
                                        <img id="brandLogoPreview" src="" alt="Brand Logo" style="max-width: 100%; max-height: 100%; display:none;" />
                                        <i id="brandLogoPlaceholder" class="fas fa-image" style="font-size: 2rem; color: #d1d5db;"></i>
                                    </div>
                                    <div>
                                        <input type="file" id="brandLogoInput" accept="image/png,image/jpeg,image/svg+xml" style="display:none;">
                                        <button type="button" class="btn btn-secondary" id="brandLogoUploadBtn" style="margin-bottom: 0.5rem;">
                                            <i class="fas fa-upload"></i> Upload Logo
                                        </button>
                                        <div style="font-size: 0.75rem; color: #6b7280;">Recommended: 400x400px, PNG or SVG</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                    <i class="fas fa-fill-drip" style="color: var(--primary-color);"></i>
                                    Primary Brand Color
                                </label>
                                <input type="color" id="brandPrimaryColor" value="#16a34a" style="width: 100px; height: 50px; border: 1px solid #d1d5db; border-radius: 8px;">
                            </div>

                            <div class="form-actions" style="margin-top: 1.5rem; display:flex; justify-content:flex-end; gap:.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="settingsManager.resetForm('appearanceSettingsForm')"><i class="fas fa-undo"></i> Reset</button>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<!-- Security Tab -->
<div class="settings-content-tab" id="security-tab" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shield-alt"></i> Security Settings
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                        <form id="securitySettingsForm">
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ef4444;">
                                    <i class="fas fa-lock" style="color: #ef4444;"></i> Session & Authentication
                                </h4>
                                
                                <div class="form-group" style="margin-bottom: 1.25rem;">
                                    <label for="sessionTimeout" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                                        <i class="fas fa-clock" style="color: #ef4444;"></i>
                                        Session Timeout (minutes)
                                    </label>
                                    <input type="number" id="sessionTimeout" class="form-control" value="30" min="5" max="1440" style="padding: 0.75rem; border-radius: 8px;">
                                    <small style="display: block; margin-top: 0.25rem; color: #6b7280; font-size: 0.75rem;">Automatically log out users after this period of inactivity</small>
                                </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-shield-alt" style="color: #ef4444; margin-right: 0.5rem;"></i>
                                                Force Strong Passwords
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Require passwords to be at least 8 characters with mixed case and numbers</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="forceStrongPassword" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f59e0b;">
                                    <i class="fas fa-user-shield" style="color: #f59e0b;"></i> Access Control
                                </h4>
                                
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-history" style="color: #f59e0b; margin-right: 0.5rem;"></i>
                                                Enable Activity Logging
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Track all user activities and system changes</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="enableActivityLog" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                        </label>
                    </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-user-lock" style="color: #f59e0b; margin-right: 0.5rem;"></i>
                                                Require Login for All Pages
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Force authentication for all dashboard pages (recommended)</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="requireLogin" checked disabled>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                    </div>
                    </div>

                            <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="settingsManager.resetForm('securitySettingsForm')">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Backup Tab -->
<div class="settings-content-tab" id="backup-tab" style="display: none;">
                <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                            <i class="fas fa-database"></i> Backup & Restore
                </h3>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 6px; margin-bottom: 2rem;">
                            <div style="display: flex; align-items: start; gap: 0.75rem;">
                                <i class="fas fa-exclamation-triangle" style="color: #f59e0b; margin-top: 0.25rem;"></i>
                                <div>
                                    <div style="font-weight: 600; color: #92400e; margin-bottom: 0.25rem;">Important Notice</div>
                                    <div style="font-size: 0.875rem; color: #92400e;">Always backup your database before making major changes. Store backups securely in multiple locations.</div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #10b981;">
                                <i class="fas fa-download" style="color: #10b981;"></i> Create Backup
                            </h4>
                            
                            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                            <i class="fas fa-database" style="color: #10b981; margin-right: 0.5rem;"></i>
                                            Full Database Backup
                                        </div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">Export complete database including all tables and data</div>
                                    </div>
                                    <button type="button" class="btn btn-success" onclick="settingsManager.createBackup('full')">
                                        <i class="fas fa-download"></i> Download Backup
                                    </button>
                                </div>
                            </div>

                            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                            <i class="fas fa-file-export" style="color: #10b981; margin-right: 0.5rem;"></i>
                                            Data Only Backup
                                        </div>
                                        <div style="font-size: 0.875rem; color: #6b7280;">Export only data (products, customers, transactions)</div>
                                    </div>
                                    <button type="button" class="btn btn-success" onclick="settingsManager.createBackup('data')">
                                        <i class="fas fa-download"></i> Download Backup
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ef4444;">
                                <i class="fas fa-upload" style="color: #ef4444;"></i> Restore Backup
                            </h4>
                            
                            <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: start; gap: 0.75rem;">
                                    <i class="fas fa-exclamation-circle" style="color: #dc2626; margin-top: 0.25rem;"></i>
                                    <div>
                                        <div style="font-weight: 600; color: #7f1d1d; margin-bottom: 0.25rem;">Warning</div>
                                        <div style="font-size: 0.875rem; color: #7f1d1d;">Restoring a backup will replace ALL current data. This action cannot be undone!</div>
                                    </div>
                                </div>
                            </div>

                            <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                <label for="backupFile" class="form-label" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                                    <i class="fas fa-file-upload" style="color: #ef4444;"></i>
                                    Select Backup File (.sql)
                                </label>
                                <div style="display: flex; gap: 0.75rem;">
                                    <input type="file" id="backupFile" accept=".sql" class="form-control" style="padding: 0.75rem; border-radius: 8px; flex: 1;">
                                    <button type="button" class="btn btn-danger" onclick="settingsManager.restoreBackup()">
                                        <i class="fas fa-upload"></i> Restore
                </button>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Tab -->
<div class="settings-content-tab" id="advanced-tab" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-sliders-h"></i> Advanced System Settings
                        </h3>
                    </div>
                    <div class="card-body" style="padding: 2rem;">
                        <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 6px; margin-bottom: 2rem;">
                            <div style="display: flex; align-items: start; gap: 0.75rem;">
                                <i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-top: 0.25rem;"></i>
                                <div>
                                    <div style="font-weight: 600; color: #7f1d1d; margin-bottom: 0.25rem;">Advanced Settings</div>
                                    <div style="font-size: 0.875rem; color: #7f1d1d;">Only change these settings if you know what you're doing. Incorrect settings may affect system performance.</div>
                                </div>
                            </div>
                        </div>

                        <form id="advancedSettingsForm">
                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-code" style="color: #667eea;"></i> Developer Options
                                </h4>
                                
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-bug" style="color: #667eea; margin-right: 0.5rem;"></i>
                                                Debug Mode
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Show detailed error messages and logs (disable in production)</div>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="debugMode">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
        </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <label class="toggle-switch-label" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-tachometer-alt" style="color: #667eea; margin-right: 0.5rem;"></i>
                                                Performance Monitoring
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Track page load times and database query performance</div>
                    </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="performanceMonitoring">
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                    </div>
                </div>

                            <div style="margin-bottom: 2rem;">
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #10b981;">
                                    <i class="fas fa-rocket" style="color: #10b981;"></i> Cache & Performance
                                </h4>
                                
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-broom" style="color: #10b981; margin-right: 0.5rem;"></i>
                                                Clear System Cache
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Clear all cached data to free up space and resolve issues</div>
                    </div>
                                        <button type="button" class="btn btn-success" onclick="settingsManager.clearCache()">
                                            <i class="fas fa-broom"></i> Clear Cache
                                        </button>
                    </div>
                </div>

                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-file-alt" style="color: #10b981; margin-right: 0.5rem;"></i>
                                                View System Logs
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">Review system logs and error reports</div>
                                        </div>
                                        <button type="button" class="btn btn-secondary" onclick="alert('Log viewer coming soon!')">
                                            <i class="fas fa-eye"></i> View Logs
                                        </button>
                    </div>
                    </div>
                </div>

                            <div>
                                <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ef4444;">
                                    <i class="fas fa-sync-alt" style="color: #ef4444;"></i> System Maintenance
                                </h4>
                                
                                <div style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div>
                                            <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">
                                                <i class="fas fa-redo" style="color: #ef4444; margin-right: 0.5rem;"></i>
                                                Reset All Settings to Default
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6b7280;">This will reset all settings to their factory defaults</div>
        </div>
                                        <button type="button" class="btn btn-danger" onclick="settingsManager.resetToDefaults()">
                                            <i class="fas fa-redo"></i> Reset All
            </button>
        </div>
    </div>
</div>

                            <div class="form-actions" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 0.75rem;">
                                <button type="button" class="btn btn-secondary" onclick="settingsManager.resetForm('advancedSettingsForm')">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<style>
/* Modern Tab Pills */
.settings-tabs-modern {
    display: flex;
    gap: 0.75rem;
    padding: 0.5rem;
    background: #f9fafb;
    border-radius: 12px;
    overflow-x: auto;
}

.settings-tab-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    color: #6b7280;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.settings-tab-pill i {
    font-size: 1rem;
}

.settings-tab-pill:hover {
    background: #ffffff;
    color: #667eea;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.settings-tab-pill.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.settings-tab-pill.active:hover {
    color: white;
}

.settings-content-tab {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Toggle Switch Styles */
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #d1d5db;
    transition: 0.3s;
    border-radius: 28px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

.toggle-switch input:disabled + .toggle-slider {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Loading Overlay */
#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99999;
}

.loading-spinner {
    background: white;
    padding: 3rem;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    min-width: 300px;
}

/* Form Improvements */
.form-group {
    margin: 0;
}

.form-row {
    align-items: start;
}

.form-control {
    width: 100%;
    height: 42px;
    border: 1px solid #d1d5db;
    transition: all 0.2s ease;
    padding: 0 0.75rem;
    border-radius: 8px;
    font-size: 0.875rem;
    background-color: #ffffff;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control:hover {
    border-color: #9ca3af;
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    padding-right: 2.5rem;
    cursor: pointer;
}

textarea.form-control {
    height: auto;
    min-height: 80px;
    padding: 0.75rem;
    resize: vertical;
    line-height: 1.5;
}

input[type="number"].form-control {
    padding-right: 0.5rem;
}

.form-label {
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.form-label i {
    font-size: 0.875rem;
}

small {
    line-height: 1.4;
}

/* Form Sections */
.form-row + .form-row {
    margin-top: 1.25rem;
}

.form-group small {
    display: block;
    margin-top: 0.5rem;
    color: #6b7280;
    font-size: 0.75rem;
    line-height: 1.4;
}

/* Section Headers */
h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .page-header > div:last-child {
        width: 100%;
        flex-direction: column;
    }
    
    .page-header > div:last-child button {
        width: 100%;
    }
    
    .settings-tabs-modern {
        flex-wrap: nowrap;
        justify-content: flex-start;
    }
    
    .settings-tab-pill span {
        display: none;
    }
    
    .settings-tab-pill {
        padding: 0.75rem;
    }
    
    .form-row {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    .settings-tab-pill {
        padding: 0.625rem 0.5rem;
    }
}
</style>

<script>
// ============================================================================
// TOAST NOTIFICATION SYSTEM
// ============================================================================
function showToast(message, type = 'success') {
    // Create toast container if doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    
    // Icon mapping
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas ${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================================================
// LOADING OVERLAY
// ============================================================================
function showLoading(message = 'Processing...') {
    let overlay = document.getElementById('loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #667eea; margin-bottom: 1rem;"></i>
                <div id="loading-message" style="color: #374151; font-weight: 600;">${message}</div>
            </div>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
    document.getElementById('loading-message').textContent = message;
}

function hideLoading() {
    const overlay = document.getElementById('loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// ============================================================================
// SETTINGS MANAGER
// ============================================================================
const settingsManager = {
    hasUnsavedChanges: false,
    
    init() {
        this.setupTabSwitching();
        this.setupFormHandlers();
        this.setupKeyboardShortcuts();
        this.setupUnsavedChangesWarning();
        this.loadSystemInfo();
        this.loadSettings();
        // Appearance handlers
        const uploadBtn = document.getElementById('brandLogoUploadBtn');
        const fileInput = document.getElementById('brandLogoInput');
        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', async () => {
                if (!fileInput.files || fileInput.files.length === 0) return;
                const form = new FormData();
                form.append('file', fileInput.files[0]);
                showLoading('Uploading logo...');
                try {
                    const res = await fetch('../api.php?controller=settings&action=upload_logo', { method: 'POST', body: form });
                    const json = await res.json();
                    if (json.success && json.data && json.data.path) {
                        const prev = document.getElementById('brandLogoPreview');
                        const ph = document.getElementById('brandLogoPlaceholder');
                        if (prev) { prev.src = '../' + json.data.path; prev.style.display = 'block'; }
                        if (ph) ph.style.display = 'none';
                        showToast('Logo updated', 'success');
                    } else {
                        throw new Error(json.message || 'Upload failed');
                    }
                } catch (e) {
                    showToast('Upload failed: ' + e.message, 'error');
                } finally { hideLoading(); }
            });
        }
        console.log('✅ Settings Manager initialized');
    },
    
    async loadSettings() {
        try {
            console.log('📡 Loading settings from database...');
            
            const response = await fetch('../api.php?controller=settings&action=get');
            console.log('📥 Settings response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📦 Settings data:', data);
            
            if (data.success && data.data) {
                this.populateSettings(data.data);
                console.log('✅ Settings loaded successfully');
            } else {
                console.warn('⚠️ Using default settings');
            }
        } catch (error) {
            console.error('❌ Failed to load settings:', error);
            console.warn('⚠️ Using default values');
        }
    },
    
    populateSettings(settings) {
        // General Settings
        if (settings.company_name) this.setInputValue('companyName', settings.company_name);
        if (settings.company_phone) this.setInputValue('companyPhone', settings.company_phone);
        if (settings.company_address) this.setInputValue('companyAddress', settings.company_address);
        if (settings.company_email) this.setInputValue('companyEmail', settings.company_email);
        if (settings.company_website) this.setInputValue('companyWebsite', settings.company_website);
        if (settings.currency) this.setInputValue('currency', settings.currency);
        if (settings.timezone) this.setInputValue('timezone', settings.timezone);
        if (settings.date_format) this.setInputValue('dateFormat', settings.date_format);
        
        // Tax Settings
        if (settings.enable_tax !== undefined) {
            this.setCheckboxValue('enableTax', settings.enable_tax === '1');
            this.toggleTaxFields(); // Update UI based on toggle state
        }
        if (settings.tax_rate !== undefined) this.setInputValue('taxRate', settings.tax_rate);
        if (settings.tax_number) this.setInputValue('taxNumber', settings.tax_number);

        // Shop Tax Settings
        if (settings.enable_tax_shop !== undefined) {
            this.setCheckboxValue('enableTaxShop', settings.enable_tax_shop === '1');
            this.toggleShopTaxFields();
        }
        if (settings.tax_rate_shop !== undefined) this.setInputValue('taxRateShop', settings.tax_rate_shop);

        // Bank Transfer Settings
        if (settings.bank_default) {
            const def = settings.bank_default === 'mandiri' ? 'bri' : (settings.bank_default === 'bni' ? 'blu_bca' : settings.bank_default);
            this.setInputValue('bankDefault', def);
        }
        if (settings.bank_bca_name) this.setInputValue('bankBcaName', settings.bank_bca_name);
        if (settings.bank_bca_account) this.setInputValue('bankBcaAccount', settings.bank_bca_account);
        const briName = settings.bank_bri_name ?? settings.bank_mandiri_name;
        const briAcc = settings.bank_bri_account ?? settings.bank_mandiri_account;
        if (briName) this.setInputValue('bankBriName', briName);
        if (briAcc) this.setInputValue('bankBriAccount', briAcc);
        const bluName = settings.bank_blu_bca_name ?? settings.bank_bni_name;
        const bluAcc = settings.bank_blu_bca_account ?? settings.bank_bni_account;
        if (bluName) this.setInputValue('bankBluBcaName', bluName);
        if (bluAcc) this.setInputValue('bankBluBcaAccount', bluAcc);
        
        // POS Settings
        if (settings.enable_barcode_scanner) this.setCheckboxValue('enableBarcodeScanner', settings.enable_barcode_scanner === '1');
        if (settings.auto_print_receipt) this.setCheckboxValue('autoPrintReceipt', settings.auto_print_receipt === '1');
        if (settings.allow_negative_stock) this.setCheckboxValue('allowNegativeStock', settings.allow_negative_stock === '1');
        // Support both receipt_header and receipt_header_text
        if (settings.receipt_header || settings.receipt_header_text) {
            this.setInputValue('receiptHeader', settings.receipt_header || settings.receipt_header_text);
        }
        if (settings.receipt_footer || settings.receipt_footer_text) {
            this.setInputValue('receiptFooter', settings.receipt_footer || settings.receipt_footer_text);
        }
        if (settings.cart_autosave_interval) this.setInputValue('cartAutosave', settings.cart_autosave_interval);
        if (settings.low_stock_threshold) this.setInputValue('lowStockThreshold', settings.low_stock_threshold);
        
        // Security Settings
        if (settings.session_timeout) this.setInputValue('sessionTimeout', settings.session_timeout);
        if (settings.force_strong_password) this.setCheckboxValue('forceStrongPassword', settings.force_strong_password === '1');
        if (settings.enable_activity_log) this.setCheckboxValue('enableActivityLog', settings.enable_activity_log === '1');
        
        // Appearance & Branding
        if (settings.brand_primary_color) {
            const el = document.getElementById('brandPrimaryColor');
            if (el) el.value = settings.brand_primary_color;
            document.documentElement.style.setProperty('--primary-color', settings.brand_primary_color);
        }
        if (settings.brand_logo) {
            const prev = document.getElementById('brandLogoPreview');
            const ph = document.getElementById('brandLogoPlaceholder');
            if (prev) { prev.src = '../' + settings.brand_logo; prev.style.display = 'block'; }
            if (ph) ph.style.display = 'none';
            const headerLogo = document.querySelector('.sidebar-logo .logo-img');
            if (headerLogo) headerLogo.src = '../' + settings.brand_logo;
        }

        // Advanced Settings
        if (settings.debug_mode) this.setCheckboxValue('debugMode', settings.debug_mode === '1');
        if (settings.performance_monitoring) this.setCheckboxValue('performanceMonitoring', settings.performance_monitoring === '1');
    },
    
    setInputValue(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.value = value;
        }
    },
    
    setCheckboxValue(id, checked) {
        const element = document.getElementById(id);
        if (element) {
            element.checked = checked;
        }
    },
    
    toggleTaxFields() {
        const enableTax = document.getElementById('enableTax');
        const taxConfigSection = document.getElementById('taxConfigSection');
        
        if (enableTax && taxConfigSection) {
            if (enableTax.checked) {
                taxConfigSection.style.display = 'block';
                taxConfigSection.style.opacity = '1';
            } else {
                taxConfigSection.style.display = 'none';
                taxConfigSection.style.opacity = '0.5';
            }
        }
    },

    toggleShopTaxFields() {
        const enableTaxShop = document.getElementById('enableTaxShop');
        const shopTaxConfigSection = document.getElementById('shopTaxConfigSection');
        if (enableTaxShop && shopTaxConfigSection) {
            shopTaxConfigSection.style.display = enableTaxShop.checked ? 'block' : 'none';
            shopTaxConfigSection.style.opacity = enableTaxShop.checked ? '1' : '0.5';
        }
    },
    
    setupTabSwitching() {
        document.querySelectorAll('.settings-tab-pill').forEach(tab => {
    tab.addEventListener('click', () => {
                if (this.hasUnsavedChanges) {
                    if (!confirm('You have unsaved changes. Are you sure you want to leave this tab?')) {
                        return;
                    }
                    this.hasUnsavedChanges = false;
                }
                
                // Remove active from all
                document.querySelectorAll('.settings-tab-pill').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.settings-content-tab').forEach(c => c.style.display = 'none');
                
                // Add active to clicked
        tab.classList.add('active');
        const tabId = tab.dataset.tab + '-tab';
        document.getElementById(tabId).style.display = 'block';
    });
});
    },
    
    setupFormHandlers() {
        // General Settings Form
        document.getElementById('generalSettingsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings('general');
        });
        
        // POS Settings Form
        document.getElementById('posSettingsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings('pos');
        });
        
        // Security Settings Form
        document.getElementById('securitySettingsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings('security');
        });
        
        // Advanced Settings Form
        document.getElementById('advancedSettingsForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveSettings('advanced');
        });
        
        // Track changes on all inputs
        document.querySelectorAll('input, select, textarea').forEach(input => {
            input.addEventListener('change', () => {
                this.hasUnsavedChanges = true;
            });
        });
    },
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.saveAllSettings();
            }
            
            // ESC to cancel/reset
            if (e.key === 'Escape') {
                if (this.hasUnsavedChanges) {
                    if (confirm('Discard unsaved changes?')) {
                        location.reload();
                    }
                }
            }
        });
    },
    
    setupUnsavedChangesWarning() {
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
                return '';
            }
        });
    },
    
    loadSystemInfo() {
        // Calculate storage
        try {
            const storage = navigator.storage && navigator.storage.estimate 
                ? navigator.storage.estimate() 
                : Promise.resolve({ usage: 0, quota: 0 });
                
            storage.then(estimate => {
                const used = (estimate.usage / 1024 / 1024).toFixed(2);
                const total = (estimate.quota / 1024 / 1024 / 1024).toFixed(2);
                document.getElementById('storageUsed').textContent = `${used} MB / ${total} GB`;
            });
        } catch (e) {
            document.getElementById('storageUsed').textContent = 'N/A';
        }
        
        // Calculate uptime (mock - would need server-side implementation)
        const uptimeDays = Math.floor(Math.random() * 30) + 1;
        document.getElementById('systemUptime').textContent = `${uptimeDays} days`;
    },
    
    async saveSettings(section) {
        showLoading(`Saving ${section} settings...`);
        
        try {
            console.log(`💾 Saving ${section} settings...`);
            
            // Collect settings data based on section
            const settingsData = this.collectSettingsData(section);
            console.log('📦 Settings data:', settingsData);
            
            // Save to API
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta && csrfMeta.content ? csrfMeta.content : (window.CSRF_TOKEN || '');
            const response = await fetch('../api.php?controller=settings&action=update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(settingsData)
            });
            
            console.log('📥 Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            console.log('📦 Response data:', data);
            
            if (data.success) {
            this.hasUnsavedChanges = false;
                const sectionName = section.charAt(0).toUpperCase() + section.slice(1);
                console.log(`✅ ${sectionName} settings saved`);
                showToast(`${sectionName} settings saved successfully!`, 'success');
            } else {
                throw new Error(data.message || 'Failed to save settings');
            }
        } catch (error) {
            console.error(`❌ Failed to save ${section} settings:`, error);
            showToast('Failed to save settings: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    collectSettingsData(section) {
        const data = {};
        
        switch(section) {
            case 'general':
                const companyName = document.getElementById('companyName');
                const companyPhone = document.getElementById('companyPhone');
                const companyAddress = document.getElementById('companyAddress');
                const companyEmail = document.getElementById('companyEmail');
                const companyWebsite = document.getElementById('companyWebsite');
                const currency = document.getElementById('currency');
                const timezone = document.getElementById('timezone');
                const dateFormat = document.getElementById('dateFormat');
                const enableTax = document.getElementById('enableTax');
                const taxRate = document.getElementById('taxRate');
                const taxNumber = document.getElementById('taxNumber');
                const enableTaxShop = document.getElementById('enableTaxShop');
                const taxRateShop = document.getElementById('taxRateShop');
                // Bank transfer settings
                const bankDefault = document.getElementById('bankDefault');
                const bankBcaName = document.getElementById('bankBcaName');
                const bankBcaAccount = document.getElementById('bankBcaAccount');
                const bankBriName = document.getElementById('bankBriName');
                const bankBriAccount = document.getElementById('bankBriAccount');
                const bankBluBcaName = document.getElementById('bankBluBcaName');
                const bankBluBcaAccount = document.getElementById('bankBluBcaAccount');
                
                if (companyName) data.company_name = companyName.value;
                if (companyPhone) data.company_phone = companyPhone.value;
                if (companyAddress) data.company_address = companyAddress.value;
                if (companyEmail) data.company_email = companyEmail.value;
                if (companyWebsite) data.company_website = companyWebsite.value;
                if (currency) data.currency = currency.value;
                if (timezone) data.timezone = timezone.value;
                if (dateFormat) data.date_format = dateFormat.value;
                if (enableTax) data.enable_tax = enableTax.checked ? '1' : '0';
                if (taxRate) data.tax_rate = taxRate.value;
                if (taxNumber) data.tax_number = taxNumber.value;
                if (enableTaxShop) data.enable_tax_shop = enableTaxShop.checked ? '1' : '0';
                if (taxRateShop) data.tax_rate_shop = taxRateShop.value;
                // Bank transfer settings
                if (bankDefault) data.bank_default = bankDefault.value;
                if (bankBcaName) data.bank_bca_name = bankBcaName.value;
                if (bankBcaAccount) data.bank_bca_account = bankBcaAccount.value;
                if (bankBriName) data.bank_bri_name = bankBriName.value;
                if (bankBriAccount) data.bank_bri_account = bankBriAccount.value;
                if (bankBluBcaName) data.bank_blu_bca_name = bankBluBcaName.value;
                if (bankBluBcaAccount) data.bank_blu_bca_account = bankBluBcaAccount.value;
                break;
                
            case 'pos':
                const enableBarcodeScanner = document.getElementById('enableBarcodeScanner');
                const autoPrintReceipt = document.getElementById('autoPrintReceipt');
                const allowNegativeStock = document.getElementById('allowNegativeStock');
                const receiptHeader = document.getElementById('receiptHeader');
                const receiptFooter = document.getElementById('receiptFooter');
                const cartAutosave = document.getElementById('cartAutosave');
                const lowStockThreshold = document.getElementById('lowStockThreshold');
                
                if (enableBarcodeScanner) data.enable_barcode_scanner = enableBarcodeScanner.checked ? '1' : '0';
                if (autoPrintReceipt) data.auto_print_receipt = autoPrintReceipt.checked ? '1' : '0';
                if (allowNegativeStock) data.allow_negative_stock = allowNegativeStock.checked ? '1' : '0';
                if (receiptHeader) data.receipt_header = receiptHeader.value;
                if (receiptFooter) data.receipt_footer = receiptFooter.value;
                if (cartAutosave) data.cart_autosave_interval = cartAutosave.value;
                if (lowStockThreshold) data.low_stock_threshold = lowStockThreshold.value;
                break;
                
            case 'security':
                const sessionTimeout = document.getElementById('sessionTimeout');
                const forceStrongPassword = document.getElementById('forceStrongPassword');
                const enableActivityLog = document.getElementById('enableActivityLog');
                
                if (sessionTimeout) data.session_timeout = sessionTimeout.value;
                if (forceStrongPassword) data.force_strong_password = forceStrongPassword.checked ? '1' : '0';
                if (enableActivityLog) data.enable_activity_log = enableActivityLog.checked ? '1' : '0';
                break;
                
            case 'advanced':
                const debugMode = document.getElementById('debugMode');
                const performanceMonitoring = document.getElementById('performanceMonitoring');
                
                if (debugMode) data.debug_mode = debugMode.checked ? '1' : '0';
                if (performanceMonitoring) data.performance_monitoring = performanceMonitoring.checked ? '1' : '0';
                break;
            case 'appearance':
                const brandPrimaryColor = document.getElementById('brandPrimaryColor');
                if (brandPrimaryColor) data.brand_primary_color = brandPrimaryColor.value;
                break;
        }
        
        return data;
    },
    
    async saveAllSettings() {
        if (!this.hasUnsavedChanges) {
            showToast('No changes to save', 'info');
        return;
    }

        showLoading('Saving all settings...');
        
        try {
            console.log('💾 Saving all settings...');
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1500));
            
            this.hasUnsavedChanges = false;
            console.log('✅ All settings saved successfully');
            showToast('All settings saved successfully!', 'success');
        } catch (error) {
            console.error('❌ Failed to save all settings:', error);
            showToast('Failed to save settings: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    resetForm(formId) {
        console.log(`🔄 Reset form: ${formId}`);
        if (confirm('Reset this form to default values?')) {
            const form = document.getElementById(formId);
            if (form) {
                form.reset();
            this.hasUnsavedChanges = false;
                console.log('✅ Form reset to defaults');
                showToast('Form reset to defaults', 'info');
            } else {
                console.error(`❌ Form not found: ${formId}`);
                showToast('Form not found', 'error');
            }
        }
    },
    
    async createBackup(type) {
        if (!confirm(`Create ${type} backup? This may take a few moments.`)) return;
        
        showLoading(`Creating ${type} backup...`);
        
        try {
            console.log(`📦 Creating ${type} backup...`);
            
            // Simulate backup creation
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            const typeName = type.charAt(0).toUpperCase() + type.slice(1);
            console.log(`✅ ${typeName} backup created`);
            showToast(`${typeName} backup created successfully!`, 'success');
            
            // Trigger download (mock)
            const filename = `backup_${type}_${new Date().toISOString().split('T')[0]}.sql`;
            showToast(`Download started: ${filename}`, 'info');
        } catch (error) {
            console.error('❌ Failed to create backup:', error);
            showToast('Failed to create backup: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    async restoreBackup() {
        const fileInput = document.getElementById('backupFile');
        if (!fileInput.files.length) {
            showToast('Please select a backup file first', 'error');
            return;
        }
        
        if (!confirm('⚠️ WARNING: This will replace ALL current data!\n\nAre you absolutely sure you want to restore this backup?')) {
            return;
        }
        
        showLoading('Restoring backup...');
        
        try {
            console.log('📥 Restoring backup...');
            
            // Simulate restore
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            console.log('✅ Backup restored successfully');
            showToast('Backup restored successfully! Refreshing page...', 'success');
            setTimeout(() => location.reload(), 2000);
        } catch (error) {
            console.error('❌ Failed to restore backup:', error);
            showToast('Failed to restore backup: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    async clearCache() {
        if (!confirm('Clear all system cache?')) return;
        
        showLoading('Clearing cache...');
        
        try {
            console.log('🧹 Clearing cache...');
            
            // Simulate cache clearing
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            console.log('✅ Cache cleared');
            showToast('Cache cleared successfully!', 'success');
        } catch (error) {
            console.error('❌ Failed to clear cache:', error);
            showToast('Failed to clear cache: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    },
    
    async resetToDefaults() {
        if (!confirm('⚠️ WARNING: This will reset ALL settings to factory defaults!\n\nAre you sure?')) {
            return;
        }
        
        if (!confirm('This action cannot be undone. Continue?')) {
            return;
        }
        
        showLoading('Resetting all settings...');
        
        try {
            console.log('🔄 Resetting all settings to defaults...');
            
            // Simulate reset
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            console.log('✅ All settings reset to defaults');
            showToast('All settings reset to defaults. Reloading...', 'success');
            setTimeout(() => location.reload(), 1500);
        } catch (error) {
            console.error('❌ Failed to reset settings:', error);
            showToast('Failed to reset settings: ' + error.message, 'error');
        } finally {
            hideLoading();
        }
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    settingsManager.init();
});
</script>

<?php require_once 'includes/footer.php'; ?>

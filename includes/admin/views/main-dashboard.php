<?php
/**
 * Main Dashboard View
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/admin/views
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap orbital-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="orbital-admin-content">
        <div class="orbital-admin-header">
            <h2>Welcome to Orbital Editor Suite</h2>
            <p>Enhance your WordPress editor with powerful typography presets and advanced tools.</p>
        </div>

        <div class="orbital-admin-grid">
            <div class="orbital-card">
                <h3>Typography Presets</h3>
                <p>Create and manage typography presets for consistent design across your site.</p>
                <a href="<?php echo admin_url('admin.php?page=orbital-typography-presets-settings'); ?>" class="button button-primary">Configure Typography</a>
            </div>

            <div class="orbital-card">
                <h3>Updates</h3>
                <p>Check for plugin updates and manage your version.</p>
                <a href="<?php echo admin_url('admin.php?page=orbital-editor-suite-updates'); ?>" class="button">View Updates</a>
            </div>
        </div>
    </div>
</div>

<style>
.orbital-admin-page {
    max-width: 1200px;
}

.orbital-admin-content {
    margin-top: 20px;
}

.orbital-admin-header {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.orbital-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.orbital-card {
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.orbital-card h3 {
    margin-top: 0;
    color: #23282d;
}

.orbital-card p {
    color: #666;
    margin-bottom: 15px;
}
</style>
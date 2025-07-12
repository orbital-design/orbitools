<?php

/**
 * Ultra simple OptionsKit implementation
 */
/**
 * Current plugin version.
 */
define('ORBITAL_EDITOR_SUITE_VERSION', '1.0.0');

/**
 * Plugin directory path.
 */
define('ORBITAL_EDITOR_SUITE_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('ORBITAL_EDITOR_SUITE_URL', plugin_dir_url(__FILE__));

/**
 * Plugin file path.
 */
define('ORBITAL_EDITOR_SUITE_FILE', __FILE__);

add_action('plugins_loaded', function () {
    static $done = false;
    if ($done) return;
    $done = true;

    // Include Composer autoloader
    if (file_exists(ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php')) {
        require_once ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php';
    }

    // Simple main dashboard
    add_filter('orbital_editor_suite_menu', function ($menu) {
        $menu['page_title'] = __('🚀 WP OptionsKit Demo', 'wpok-demo');
        $menu['menu_title'] = __('OptionsKit Demo', 'wpok-demo');
        return $menu;
    });

    add_filter('orbital_editor_suite_settings_tabs', function ($tabs) {
        return array(
            'general'  => __('🏠 General', 'wpok-demo'),
            'content'  => __('📝 Content', 'wpok-demo'),
            'design'   => __('🎨 Design', 'wpok-demo'),
            'advanced' => __('⚙️ Advanced', 'wpok-demo'),
            'help'     => __('❓ Help', 'wpok-demo'),
        );
    });

    add_filter('orbital_editor_suite_registered_settings_sections', function ($subsections) {
        // You can organize fields into subsections within each tab
        $subsections = array(
            'general' => array(
                'basic'   => __('Basic Settings', 'wpok-demo'),
                'company' => __('Company Information', 'wpok-demo'),
            ),
            'content' => array(
                'posts' => __('Post Settings', 'wpok-demo'),
                'media' => __('Media Settings', 'wpok-demo'),
            ),
            'design' => array(
                'colors'     => __('Color Scheme', 'wpok-demo'),
                'typography' => __('Typography', 'wpok-demo'),
                'layout'     => __('Layout Options', 'wpok-demo'),
            ),
            'advanced' => array(
                'performance' => __('Performance', 'wpok-demo'),
                'security'    => __('Security', 'wpok-demo'),
                'developer'   => __('Developer Options', 'wpok-demo'),
            ),
        );

        return $subsections;
    });

    add_filter('orbital_editor_suite_registered_settings', function ($settings) {
        $settings = array(
            // General Tab
            'general' => array(
                // Basic Settings Section
                array(
                    'id'      => 'site_status',
                    'name'    => __('Site Status', 'wpok-demo'),
                    'desc'    => __('Control the public status of your site', 'wpok-demo'),
                    'type'    => 'select',
                    'options' => array(
                        'live'        => __('🟢 Live', 'wpok-demo'),
                        'maintenance' => __('🟡 Maintenance Mode', 'wpok-demo'),
                        'coming_soon' => __('🔵 Coming Soon', 'wpok-demo'),
                        'offline'     => __('🔴 Offline', 'wpok-demo'),
                    ),
                    'std' => 'live',
                    'section' => 'basic',
                ),
                array(
                    'id'      => 'enable_features',
                    'name'    => __('Enable Features', 'wpok-demo'),
                    'desc'    => __('Choose which features to enable on your site', 'wpok-demo'),
                    'type'    => 'multicheck',
                    'options' => array(
                        'comments'  => __('Comments System', 'wpok-demo'),
                        'ratings'   => __('Star Ratings', 'wpok-demo'),
                        'social'    => __('Social Sharing', 'wpok-demo'),
                        'analytics' => __('Analytics Tracking', 'wpok-demo'),
                        'seo'       => __('SEO Tools', 'wpok-demo'),
                    ),
                    'section' => 'basic',
                ),
                array(
                    'id'          => 'admin_email',
                    'name'        => __('Admin Email', 'wpok-demo'),
                    'desc'        => __('Where to send important notifications', 'wpok-demo'),
                    'type'        => 'text',
                    'std'         => get_option('admin_email'),
                    'placeholder' => 'admin@example.com',
                    'section'     => 'basic',
                ),
                // Company Information Section
                array(
                    'id'      => 'company_name',
                    'name'    => __('Company Name', 'wpok-demo'),
                    'desc'    => __('Your company or organization name', 'wpok-demo'),
                    'type'    => 'text',
                    'std'     => get_bloginfo('name'),
                    'section' => 'company',
                ),
                array(
                    'id'      => 'company_logo',
                    'name'    => __('Company Logo', 'wpok-demo'),
                    'desc'    => __('Upload your company logo', 'wpok-demo'),
                    'type'    => 'file',
                    'section' => 'company',
                ),
                array(
                    'id'          => 'company_description',
                    'name'        => __('Company Description', 'wpok-demo'),
                    'desc'        => __('Brief description of your company', 'wpok-demo'),
                    'type'        => 'textarea',
                    'rows'        => 5,
                    'placeholder' => 'Tell us about your company...',
                    'section'     => 'company',
                ),
            ),

            // Content Tab
            'content' => array(
                // Post Settings
                array(
                    'id'      => 'posts_per_page',
                    'name'    => __('Posts Per Page', 'wpok-demo'),
                    'desc'    => __('Number of posts to show per page', 'wpok-demo'),
                    'type'    => 'number',
                    'std'     => 10,
                    'min'     => 1,
                    'max'     => 50,
                    'section' => 'posts',
                ),
                array(
                    'id'      => 'post_layout',
                    'name'    => __('Post Layout', 'wpok-demo'),
                    'type'    => 'radio',
                    'options' => array(
                        'standard' => __('Standard', 'wpok-demo'),
                        'grid'     => __('Grid', 'wpok-demo'),
                        'list'     => __('List', 'wpok-demo'),
                        'masonry'  => __('Masonry', 'wpok-demo'),
                    ),
                    'std'     => 'standard',
                    'section' => 'posts',
                ),
                array(
                    'id'      => 'show_author',
                    'name'    => __('Show Author Info', 'wpok-demo'),
                    'desc'    => __('Display author information on posts', 'wpok-demo'),
                    'type'    => 'checkbox',
                    'std'     => '1',
                    'section' => 'posts',
                ),
                // Media Settings
                array(
                    'id'      => 'thumbnail_size',
                    'name'    => __('Thumbnail Size', 'wpok-demo'),
                    'type'    => 'select',
                    'options' => array(
                        'small'  => __('Small (150x150)', 'wpok-demo'),
                        'medium' => __('Medium (300x300)', 'wpok-demo'),
                        'large'  => __('Large (600x600)', 'wpok-demo'),
                    ),
                    'std'     => 'medium',
                    'section' => 'media',
                ),
                array(
                    'id'      => 'lazy_load',
                    'name'    => __('Lazy Load Images', 'wpok-demo'),
                    'desc'    => __('Load images as they come into view', 'wpok-demo'),
                    'type'    => 'checkbox',
                    'section' => 'media',
                ),
            ),

            // Design Tab
            'design' => array(
                // Color Scheme
                array(
                    'id'      => 'primary_color',
                    'name'    => __('Primary Color', 'wpok-demo'),
                    'desc'    => __('Main brand color', 'wpok-demo'),
                    'type'    => 'color',
                    'std'     => '#3498db',
                    'section' => 'colors',
                ),
                array(
                    'id'      => 'secondary_color',
                    'name'    => __('Secondary Color', 'wpok-demo'),
                    'desc'    => __('Accent color', 'wpok-demo'),
                    'type'    => 'color',
                    'std'     => '#2ecc71',
                    'section' => 'colors',
                ),
                array(
                    'id'      => 'text_color',
                    'name'    => __('Text Color', 'wpok-demo'),
                    'desc'    => __('Main text color', 'wpok-demo'),
                    'type'    => 'color',
                    'std'     => '#333333',
                    'section' => 'colors',
                ),
                array(
                    'id'      => 'background_color',
                    'name'    => __('Background Color', 'wpok-demo'),
                    'desc'    => __('Page background color', 'wpok-demo'),
                    'type'    => 'color',
                    'std'     => '#ffffff',
                    'section' => 'colors',
                ),
                // Typography
                array(
                    'id'      => 'font_family',
                    'name'    => __('Font Family', 'wpok-demo'),
                    'type'    => 'select',
                    'options' => array(
                        'system'     => __('System Font Stack', 'wpok-demo'),
                        'serif'      => __('Serif (Georgia)', 'wpok-demo'),
                        'sans-serif' => __('Sans-serif (Helvetica)', 'wpok-demo'),
                        'monospace'  => __('Monospace (Courier)', 'wpok-demo'),
                    ),
                    'std'     => 'system',
                    'section' => 'typography',
                ),
                array(
                    'id'      => 'font_size',
                    'name'    => __('Base Font Size', 'wpok-demo'),
                    'desc'    => __('Base font size in pixels', 'wpok-demo'),
                    'type'    => 'number',
                    'std'     => 16,
                    'min'     => 12,
                    'max'     => 24,
                    'section' => 'typography',
                ),
                // Layout
                array(
                    'id'      => 'container_width',
                    'name'    => __('Container Width', 'wpok-demo'),
                    'type'    => 'select',
                    'options' => array(
                        '1400' => __('Extra Wide (1400px)', 'wpok-demo'),
                        '1200' => __('Wide (1200px)', 'wpok-demo'),
                        '1024' => __('Normal (1024px)', 'wpok-demo'),
                        '800'  => __('Narrow (800px)', 'wpok-demo'),
                    ),
                    'std'     => '1200',
                    'section' => 'layout',
                ),
                array(
                    'id'      => 'sidebar_position',
                    'name'    => __('Sidebar Position', 'wpok-demo'),
                    'type'    => 'radio',
                    'options' => array(
                        'left'  => __('Left Sidebar', 'wpok-demo'),
                        'right' => __('Right Sidebar', 'wpok-demo'),
                        'none'  => __('No Sidebar (Full Width)', 'wpok-demo'),
                    ),
                    'std'     => 'right',
                    'section' => 'layout',
                ),
            ),

            // Advanced Tab
            'advanced' => array(
                // Performance
                array(
                    'id'      => 'enable_cache',
                    'name'    => __('Enable Caching', 'wpok-demo'),
                    'desc'    => __('Enable built-in caching system', 'wpok-demo'),
                    'type'    => 'checkbox',
                    'section' => 'performance',
                ),
                array(
                    'id'      => 'cache_expiry',
                    'name'    => __('Cache Expiry', 'wpok-demo'),
                    'type'    => 'select',
                    'options' => array(
                        '3600'   => __('1 Hour', 'wpok-demo'),
                        '43200'  => __('12 Hours', 'wpok-demo'),
                        '86400'  => __('1 Day', 'wpok-demo'),
                        '604800' => __('1 Week', 'wpok-demo'),
                    ),
                    'std'     => '86400',
                    'section' => 'performance',
                ),
                // Security
                array(
                    'id'          => 'api_key',
                    'name'        => __('API Key', 'wpok-demo'),
                    'desc'        => __('Your secret API key', 'wpok-demo'),
                    'type'        => 'password',
                    'placeholder' => 'Enter your API key...',
                    'section'     => 'security',
                ),
                array(
                    'id'          => 'allowed_ips',
                    'name'        => __('Allowed IP Addresses', 'wpok-demo'),
                    'desc'        => __('One IP address per line (leave empty to allow all)', 'wpok-demo'),
                    'type'        => 'textarea',
                    'rows'        => 5,
                    'placeholder' => "192.168.1.1\n10.0.0.1",
                    'section'     => 'security',
                ),
                // Developer
                array(
                    'id'      => 'debug_mode',
                    'name'    => __('Debug Mode', 'wpok-demo'),
                    'desc'    => __('Enable debug mode for troubleshooting', 'wpok-demo'),
                    'type'    => 'checkbox',
                    'section' => 'developer',
                ),
                array(
                    'id'          => 'custom_css',
                    'name'        => __('Custom CSS', 'wpok-demo'),
                    'desc'        => __('Add custom CSS code', 'wpok-demo'),
                    'type'        => 'textarea',
                    'rows'        => 10,
                    'placeholder' => "/* Your custom CSS here */\n.example {\n    color: red;\n}",
                    'section'     => 'developer',
                ),
            ),

            // Help Tab
            'help' => array(
                array(
                    'id'   => 'help_content',
                    'name' => '',
                    'desc' => '',
                    'type' => 'html',
                    'std'  => wpok_demo_get_help_content(),
                ),
            ),
        );

        return $settings;
    });

    // Initialize OptionsKit
    $kit = new \TDP\OptionsKit('orbital-editor-suite');
    $kit->set_page_title('Orbital Editor Suite');

    error_log('SIMPLE: OptionsKit initialized');
}, 1);

function wpok_demo_get_help_content()
{
    ob_start();
?>
<div style="max-width: 800px; line-height: 1.6;">
    <h2>🚀 Welcome to WP OptionsKit Demo!</h2>
    <p>This plugin demonstrates the power of WP OptionsKit - a VueJS-powered toolkit that lets you create beautiful
        WordPress admin screens using just hooks and filters.</p>

    <h3>✨ Key Features Demonstrated:</h3>
    <ul style="list-style: disc; margin-left: 20px;">
        <li><strong>Multiple Tabs</strong> - Organize settings into logical groups</li>
        <li><strong>Subsections</strong> - Further organize fields within tabs</li>
        <li><strong>Various Field Types</strong> - Text, select, checkbox, radio, color picker, file upload, and more
        </li>
        <li><strong>VueJS Powered</strong> - Smooth, reactive interface</li>
        <li><strong>Clean Code</strong> - Everything is built with hooks and filters</li>
        <li><strong>Extensible</strong> - Other plugins can modify these settings</li>
    </ul>

    <h3>📖 Available Field Types:</h3>
    <ul style="list-style: disc; margin-left: 20px;">
        <li><code>text</code> - Standard text input</li>
        <li><code>textarea</code> - Multi-line text input</li>
        <li><code>select</code> - Dropdown select</li>
        <li><code>multicheck</code> - Multiple checkboxes</li>
        <li><code>radio</code> - Radio buttons</li>
        <li><code>checkbox</code> - Single checkbox</li>
        <li><code>color</code> - Color picker</li>
        <li><code>file</code> - File upload</li>
        <li><code>number</code> - Number input</li>
        <li><code>password</code> - Password input</li>
        <li><code>html</code> - Custom HTML content</li>
    </ul>

    <h3>💻 Code Example:</h3>
    <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
// Initialize OptionsKit
$prefix = 'myplugin';
$panel = new \TDP\OptionsKit( $prefix );
$panel->set_page_title( 'My Plugin Settings' );

// Add a settings tab
add_filter( 'myplugin_settings_tabs', function( $tabs ) {
    $tabs['general'] = 'General';
    return $tabs;
});

// Add settings fields
add_filter( 'myplugin_registered_settings', function( $settings ) {
    $settings['general'] = array(
        array(
            'id'   => 'my_option',
            'name' => 'My Option',
            'type' => 'text',
            'desc' => 'Enter your value',
        ),
    );
    return $settings;
});</pre>

    <h3>🔧 Getting Option Values:</h3>
    <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">
// Get a specific option
$value = get_option( 'wpok_demo_my_option' );

// Get all options with prefix
$all_options = wp_load_alloptions();
$my_options = array();
foreach ( $all_options as $name => $value ) {
    if ( strpos( $name, 'wpok_demo_' ) === 0 ) {
        $my_options[$name] = $value;
    }
}</pre>

    <div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-radius: 5px;">
        <strong>🔗 Learn More:</strong> Visit the <a href="https://github.com/WPUserManager/wp-optionskit"
            target="_blank">WP OptionsKit GitHub repository</a> for complete documentation.
    </div>
</div>
<?php
    return ob_get_clean();
}
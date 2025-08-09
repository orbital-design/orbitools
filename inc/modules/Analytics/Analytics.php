<?php

/**
 * Analytics Module
 *
 * Main coordinator class for the Analytics module. This class acts as
 * the primary entry point and orchestrates the various components of the module.
 *
 * @package    Orbitools
 * @subpackage Modules/Analytics
 * @since      1.0.0
 */

namespace Orbitools\Modules\Analytics;

use Orbitools\Core\Abstracts\Module_Base;
use Orbitools\Modules\Analytics\Admin\Admin;
use Orbitools\Modules\Analytics\Admin\Settings;
use Orbitools\Modules\Analytics\Admin\Settings_Helper;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Module Class
 *
 * Coordinates all aspects of the Analytics functionality by managing
 * the interaction between admin and frontend components.
 *
 * @since 1.0.0
 */
class Analytics extends Module_Base
{
    /**
     * Module version
     */
    protected const VERSION = '1.0.0';

    /**
     * Admin handler instance
     *
     * @since 1.0.0
     * @var Admin
     */
    private $admin;

    /**
     * Whether the module has been initialized
     *
     * @since 1.0.0
     * @var bool
     */
    private static $initialized = false;

    /**
     * Initialize the Analytics module
     *
     * Sets up the module by calling the parent constructor which handles
     * the initialization logic via the Module_Base system.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Call parent constructor which handles initialization
        parent::__construct();
    }

    /**
     * Get the module's unique slug
     * 
     * @return string
     */
    public function get_slug(): string
    {
        return 'analytics';
    }

    /**
     * Get the module's display name
     * 
     * @return string
     */
    public function get_name(): string
    {
        return __('Analytics', 'orbitools');
    }

    /**
     * Get the module's description
     * 
     * @return string
     */
    public function get_description(): string
    {
        return __('Advanced analytics integration with Google Analytics 4, Google Tag Manager, and custom event tracking.', 'orbitools');
    }

    /**
     * Get module's default settings
     * 
     * @return array
     */
    public function get_default_settings(): array
    {
        return [
            'analytics_enabled' => true,
            'analytics_type' => 'ga4',
            'analytics_ga4_id' => '',
            'analytics_gtm_id' => '',
            'analytics_consent_mode' => false,
            'analytics_track_performance' => false,
            'analytics_ecommerce_enable' => false,
            'analytics_ecommerce_currency' => 'USD',
            'analytics_custom_events_enable' => false,
            'analytics_custom_events' => [
                'downloads' => false,
                'outbound' => false,
                'scroll' => false,
                'forms' => false
            ]
        ];
    }

    /**
     * Initialize the module
     * Called by Module_Base when module should be initialized
     * 
     * @return void
     */
    public function init(): void
    {
        // Always initialize admin functionality for module registration
        $this->admin = new Admin();
        
        // Initialize Settings class
        Settings::init();

        // Initialize frontend functionality
        $this->init_frontend_functionality();
    }

    /**
     * Initialize frontend module functionality
     *
     * Sets up frontend integration when the module is enabled.
     *
     * @since 1.0.0
     */
    private function init_frontend_functionality(): void
    {
        // Hook into WordPress
        add_action('wp_head', [$this, 'render_head_tracking'], 1);
        add_action('wp_body_open', [$this, 'render_body_tracking']);
        add_action('wp_footer', [$this, 'render_footer_tracking'], 99);
        
        // Enhanced ecommerce hooks (WooCommerce)
        if (class_exists('WooCommerce')) {
            $this->init_ecommerce_tracking();
        }

        // Initialize additional features
        $this->init_additional_features();
    }

    /**
     * Render head tracking code
     */
    public function render_head_tracking()
    {
        if (!$this->should_track()) {
            return;
        }

        $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');

        switch ($analytics_type) {
            case 'ga4':
                $this->render_ga4_head();
                break;
            case 'gtm':
                $this->render_gtm_head();
                break;
        }
    }

    /**
     * Render body tracking code (GTM only)
     */
    public function render_body_tracking()
    {
        if (!$this->should_track()) {
            return;
        }

        $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');

        if ($analytics_type === 'gtm') {
            $this->render_gtm_body();
        }
    }

    /**
     * Render footer tracking code
     */
    public function render_footer_tracking()
    {
        if (!$this->should_track()) {
            return;
        }

        // Add custom event tracking scripts
        $this->render_custom_events_script();
    }

    /**
     * Render GA4 head code
     */
    private function render_ga4_head()
    {
        $measurement_id = $this->get_analytics_setting('analytics_ga4_id');
        
        if (empty($measurement_id)) {
            return;
        }

        $config = $this->get_ga4_config();
        ?>
        <!-- Google Analytics 4 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($measurement_id); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            
            <?php if ($this->get_analytics_setting('analytics_consent_mode')): ?>
            // Consent Mode v2
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied',
                'analytics_storage': 'denied'
            });
            <?php endif; ?>
            
            gtag('js', new Date());
            gtag('config', '<?php echo esc_js($measurement_id); ?>', <?php echo wp_json_encode($config); ?>);
        </script>
        <?php
    }

    /**
     * Render GTM head code
     */
    private function render_gtm_head()
    {
        $container_id = $this->get_analytics_setting('analytics_gtm_id');
        
        if (empty($container_id)) {
            return;
        }
        ?>
        <!-- Google Tag Manager -->
        <script>
            <?php if ($this->get_analytics_setting('analytics_consent_mode')): ?>
            // Consent Mode v2 for GTM
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('consent', 'default', {
                'ad_storage': 'denied',
                'ad_user_data': 'denied', 
                'ad_personalization': 'denied',
                'analytics_storage': 'denied'
            });
            <?php endif; ?>
            
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','<?php echo esc_js($container_id); ?>');
        </script>
        <!-- End Google Tag Manager -->
        <?php
    }

    /**
     * Render GTM body code
     */
    private function render_gtm_body()
    {
        $container_id = $this->get_analytics_setting('analytics_gtm_id');
        
        if (empty($container_id)) {
            return;
        }
        ?>
        <!-- Google Tag Manager (noscript) -->
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($container_id); ?>"
        height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
        <!-- End Google Tag Manager (noscript) -->
        <?php
    }


    /**
     * Render custom events tracking script
     */
    private function render_custom_events_script()
    {
        if (!Settings_Helper::has_custom_events_enabled()) {
            return;
        }

        $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (Settings_Helper::is_custom_event_enabled('downloads')): ?>
            // Track file downloads
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;
                
                const href = link.getAttribute('href');
                const fileExtensions = /\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|mp3|mp4|avi|mov)$/i;
                
                if (fileExtensions.test(href)) {
                    <?php echo $this->get_tracking_code_js('file_download', [
                        'file_name' => 'href',
                        'link_text' => 'link.textContent.trim()'
                    ], $analytics_type); ?>
                }
            });
            <?php endif; ?>

            <?php if (Settings_Helper::is_custom_event_enabled('outbound')): ?>
            // Track outbound links
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;
                
                const href = link.getAttribute('href');
                const isExternal = href && (href.startsWith('http') && !href.includes(location.hostname));
                
                if (isExternal) {
                    <?php echo $this->get_tracking_code_js('click', [
                        'event_category' => '"outbound"',
                        'event_label' => 'href',
                        'transport_type' => '"beacon"'
                    ], $analytics_type); ?>
                }
            });
            <?php endif; ?>

            <?php if (Settings_Helper::is_custom_event_enabled('scroll')): ?>
            // Track scroll depth
            let scrollTracked = [];
            window.addEventListener('scroll', function() {
                const scrollPercent = Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100);
                
                [25, 50, 75, 100].forEach(threshold => {
                    if (scrollPercent >= threshold && !scrollTracked.includes(threshold)) {
                        scrollTracked.push(threshold);
                        <?php echo $this->get_tracking_code_js('scroll', [
                            'event_category' => '"engagement"',
                            'event_label' => 'threshold + "%"'
                        ], $analytics_type); ?>
                    }
                });
            });
            <?php endif; ?>

            <?php if (Settings_Helper::is_custom_event_enabled('forms')): ?>
            // Track form submissions
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.tagName === 'FORM') {
                    const formId = form.id || form.className || 'unknown';
                    <?php echo $this->get_tracking_code_js('form_submit', [
                        'event_category' => '"engagement"',
                        'event_label' => 'formId'
                    ], $analytics_type); ?>
                }
            });
            <?php endif; ?>
        });
        </script>
        <?php
    }

    /**
     * Initialize ecommerce tracking (WooCommerce)
     */
    private function init_ecommerce_tracking()
    {
        if (!$this->get_analytics_setting('analytics_ecommerce_enable')) {
            return;
        }

        // Purchase tracking
        add_action('woocommerce_thankyou', [$this, 'track_purchase'], 10, 1);
        
        // Product view tracking
        add_action('woocommerce_single_product_summary', [$this, 'track_product_view'], 5);
        
        // Add to cart tracking
        add_action('wp_footer', [$this, 'render_add_to_cart_tracking']);
        
        // Begin checkout tracking
        add_action('woocommerce_before_checkout_form', [$this, 'track_begin_checkout']);
    }

    /**
     * Track purchase event
     */
    public function track_purchase($order_id)
    {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'item_id' => $product->get_sku() ?: $product->get_id(),
                'item_name' => $product->get_name(),
                'category' => $this->get_product_category($product),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total() / $item->get_quantity()
            ];
        }

        // Use configured currency or fallback to order currency
        $currency = $this->get_analytics_setting('analytics_ecommerce_currency');
        if (empty($currency)) {
            $currency = $order->get_currency();
        }

        $purchase_data = [
            'transaction_id' => $order->get_order_number(),
            'value' => $order->get_total(),
            'currency' => $currency,
            'items' => $items
        ];
        
        $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');
        ?>
        <script>
        <?php echo $this->get_tracking_code_for_data('purchase', $purchase_data, $analytics_type); ?>
        </script>
        <?php
    }

    /**
     * Check if tracking should be enabled for current request
     */
    private function should_track()
    {
        return Settings_Helper::should_track();
    }

    /**
     * Get GA4 configuration
     */
    private function get_ga4_config()
    {
        $config = [];

        // Add custom config parameters
        $custom_config = $this->get_analytics_setting('analytics_custom_config');
        if (!empty($custom_config)) {
            $config_data = json_decode($custom_config, true);
            if (is_array($config_data)) {
                $config = array_merge($config, $config_data);
            }
        }

        return $config;
    }

    /**
     * Get analytics-specific setting value
     */
    private function get_analytics_setting($key, $default = '')
    {
        return Settings_Helper::get_analytics_setting(str_replace('analytics_', '', $key), $default);
    }

    /**
     * Get tracking code for data object (server-side)
     */
    private function get_tracking_code_for_data($event_name, $data, $analytics_type)
    {
        switch ($analytics_type) {
            case 'gtm':
                // Enhanced Ecommerce format for GTM
                $ecommerce_data = $this->convert_to_gtm_ecommerce_format($event_name, $data);
                return "window.dataLayer = window.dataLayer || []; window.dataLayer.push({\n" .
                       "    'event': '" . esc_js($ecommerce_data['event']) . "',\n" .
                       "    'ecommerce': " . wp_json_encode($ecommerce_data['ecommerce']) . "\n" .
                       "});";
            
            case 'ga4':
            default:
                return "if (typeof gtag !== 'undefined') {\n" .
                       "    gtag('event', '" . esc_js($event_name) . "', " . wp_json_encode($data) . ");\n" .
                       "}";
        }
    }

    /**
     * Get tracking code JavaScript for dynamic events
     */
    private function get_tracking_code_js($event_name, $params, $analytics_type)
    {
        $params_js = [];
        foreach ($params as $key => $value) {
            // Check if value is a JS variable (not quoted) or a string literal
            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                $params_js[] = "'" . esc_js($key) . "': " . $value;
            } else {
                $params_js[] = "'" . esc_js($key) . "': " . $value;
            }
        }
        $params_str = '{' . implode(', ', $params_js) . '}';
        
        switch ($analytics_type) {
            case 'gtm':
                return "window.dataLayer = window.dataLayer || []; window.dataLayer.push({\n" .
                       "    'event': '" . esc_js($event_name) . "',\n" .
                       "    'eventData': " . $params_str . "\n" .
                       "});";
            
            case 'ga4':
            default:
                return "if (typeof gtag !== 'undefined') {\n" .
                       "    gtag('event', '" . esc_js($event_name) . "', " . $params_str . ");\n" .
                       "}";
        }
    }

    /**
     * Get tracking code for JavaScript objects (cart tracking)
     */
    private function get_tracking_code_js_for_object($event_name, $data, $analytics_type)
    {
        switch ($analytics_type) {
            case 'gtm':
                $data_js = [];
                foreach ($data as $key => $value) {
                    if ($key === 'items' && is_array($value) && isset($value[0]['item_id']) && $value[0]['item_id'] === 'productId') {
                        $data_js[] = "'" . esc_js($key) . "': [{'item_id': productId, 'quantity': 1}]";
                    } else {
                        $data_js[] = "'" . esc_js($key) . "': '" . esc_js($value) . "'";
                    }
                }
                return "window.dataLayer = window.dataLayer || []; window.dataLayer.push({\n" .
                       "    'event': '" . esc_js($event_name) . "',\n" .
                       "    'ecommerce': {" . implode(', ', $data_js) . "}\n" .
                       "});";
            
            case 'ga4':
            default:
                $data_js = [];
                foreach ($data as $key => $value) {
                    if ($key === 'items' && is_array($value) && isset($value[0]['item_id']) && $value[0]['item_id'] === 'productId') {
                        $data_js[] = "'" . esc_js($key) . "': [{'item_id': productId, 'quantity': 1}]";
                    } else {
                        $data_js[] = "'" . esc_js($key) . "': '" . esc_js($value) . "'";
                    }
                }
                return "if (typeof gtag !== 'undefined') {\n" .
                       "    gtag('event', '" . esc_js($event_name) . "', {" . implode(', ', $data_js) . "});\n" .
                       "}";
        }
    }

    /**
     * Convert to GTM Enhanced Ecommerce format
     */
    private function convert_to_gtm_ecommerce_format($event_name, $data)
    {
        $gtm_event = $event_name;
        $ecommerce = [];
        
        // Map GA4/Universal events to GTM Enhanced Ecommerce events
        switch ($event_name) {
            case 'purchase':
                $gtm_event = 'purchase';
                $ecommerce = [
                    'transaction_id' => $data['transaction_id'] ?? '',
                    'affiliation' => get_bloginfo('name'),
                    'value' => $data['value'] ?? 0,
                    'currency' => $data['currency'] ?? 'USD',
                    'items' => $data['items'] ?? []
                ];
                break;
                
            case 'view_item':
                $gtm_event = 'view_item';
                $ecommerce = [
                    'currency' => $data['currency'] ?? 'USD',
                    'value' => $data['value'] ?? 0,
                    'items' => $data['items'] ?? []
                ];
                break;
                
            case 'add_to_cart':
                $gtm_event = 'add_to_cart';
                $ecommerce = [
                    'currency' => $data['currency'] ?? 'USD',
                    'value' => $data['value'] ?? 0,
                    'items' => $data['items'] ?? []
                ];
                break;
                
            case 'remove_from_cart':
                $gtm_event = 'remove_from_cart';
                $ecommerce = [
                    'currency' => $data['currency'] ?? 'USD',
                    'value' => $data['value'] ?? 0,
                    'items' => $data['items'] ?? []
                ];
                break;
                
            case 'begin_checkout':
                $gtm_event = 'begin_checkout';
                $ecommerce = [
                    'currency' => $data['currency'] ?? 'USD',
                    'value' => $data['value'] ?? 0,
                    'items' => $data['items'] ?? []
                ];
                break;
                
            default:
                // For other events, use the original data structure
                $ecommerce = $data;
        }
        
        return [
            'event' => $gtm_event,
            'ecommerce' => $ecommerce
        ];
    }


    /**
     * Get product category for analytics
     */
    private function get_product_category($product)
    {
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($categories) && !is_wp_error($categories)) {
            return $categories[0]->name;
        }
        return 'Uncategorized';
    }

    /**
     * Initialize additional tracking features
     */
    private function init_additional_features()
    {
        // Add debug mode for development (only for localhost/staging)
        if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options') && $this->is_development_environment()) {
            add_action('wp_footer', function() {
                if ($this->should_track()) {
                    ?>
                    <script>
                    if (console && console.log) {
                        console.log('OrbiTools Analytics Debug Mode - Config Loaded');
                        // Note: Actual tracking IDs not exposed for security
                    }
                    </script>
                    <?php
                }
            }, 999);
        }

        // Add performance tracking
        if ($this->get_analytics_setting('analytics_track_performance') && $this->get_analytics_setting('analytics_type') === 'ga4') {
            add_action('wp_footer', function() {
                if (!$this->should_track()) return;
                ?>
                <script>
                // Core Web Vitals tracking
                if ('PerformanceObserver' in window) {
                    new PerformanceObserver((list) => {
                        const entries = list.getEntries();
                        const lastEntry = entries[entries.length - 1];
                        if (typeof gtag !== 'undefined') {
                            gtag('event', 'timing_complete', {
                                'name': 'LCP',
                                'value': Math.round(lastEntry.startTime)
                            });
                        }
                    }).observe({entryTypes: ['largest-contentful-paint']});
                }
                </script>
                <?php
            }, 999);
        }
    }

    /**
     * Track product view
     */
    public function track_product_view()
    {
        if (!is_product()) return;

        global $product;
        if (!$product) return;

        $currency = $this->get_analytics_setting('analytics_ecommerce_currency');
        if (empty($currency) && function_exists('get_woocommerce_currency')) {
            $currency = get_woocommerce_currency();
        }
        if (empty($currency)) {
            $currency = 'USD';
        }

        $product_data = [
            'currency' => $currency,
            'value' => $product->get_price(),
            'items' => [[
                'item_id' => $product->get_sku() ?: $product->get_id(),
                'item_name' => $product->get_name(),
                'category' => $this->get_product_category($product),
                'quantity' => 1,
                'price' => $product->get_price()
            ]]
        ];
        
        $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');
        ?>
        <script>
        <?php echo $this->get_tracking_code_for_data('view_item', $product_data, $analytics_type); ?>
        </script>
        <?php
    }

    /**
     * Render add to cart tracking
     */
    public function render_add_to_cart_tracking()
    {
        if (!is_woocommerce()) return;
        
        $currency = $this->get_analytics_setting('analytics_ecommerce_currency');
        if (empty($currency) && function_exists('get_woocommerce_currency')) {
            $currency = get_woocommerce_currency();
        }
        if (empty($currency)) {
            $currency = 'USD';
        }
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track add to cart button clicks
            document.addEventListener('click', function(e) {
                const addToCartBtn = e.target.closest('.single_add_to_cart_button, button[name="add-to-cart"]');
                if (!addToCartBtn) return;

                // Get product data
                const productForm = addToCartBtn.closest('form.cart');
                if (!productForm) return;

                const productId = productForm.querySelector('[name="add-to-cart"]')?.value || 
                                productForm.querySelector('[name="product_id"]')?.value;
                
                if (!productId) return;

                // Simple add to cart tracking
                <?php 
                $add_to_cart_data = [
                    'currency' => $currency,
                    'value' => 0,
                    'items' => [['item_id' => 'productId', 'quantity' => 1]]
                ];
                echo $this->get_tracking_code_js_for_object('add_to_cart', $add_to_cart_data, $this->get_analytics_setting('analytics_type', 'ga4'));
                ?>
            });

            // Track remove from cart
            document.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove');
                if (!removeBtn || !removeBtn.href.includes('remove_item')) return;

                <?php 
                $remove_data = ['currency' => $currency];
                echo $this->get_tracking_code_js_for_object('remove_from_cart', $remove_data, $this->get_analytics_setting('analytics_type', 'ga4'));
                ?>
            });
        });
        </script>
        <?php
    }

    /**
     * Track begin checkout
     */
    public function track_begin_checkout()
    {
        if (!is_checkout() || is_order_received_page()) return;

        $currency = $this->get_analytics_setting('analytics_ecommerce_currency');
        if (empty($currency) && function_exists('get_woocommerce_currency')) {
            $currency = get_woocommerce_currency();
        }
        if (empty($currency)) {
            $currency = 'USD';
        }

        $cart_data = [];
        $cart_value = 0;

        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                $product = $cart_item['data'];
                $cart_data[] = [
                    'item_id' => $product->get_sku() ?: $product->get_id(),
                    'item_name' => $product->get_name(),
                    'category' => $this->get_product_category($product),
                    'quantity' => $cart_item['quantity'],
                    'price' => $product->get_price()
                ];
            }
            $cart_value = WC()->cart->get_total('edit');
        }

        if (!empty($cart_data)) {
            $checkout_data = [
                'currency' => $currency,
                'value' => $cart_value,
                'items' => $cart_data
            ];
            
            $analytics_type = $this->get_analytics_setting('analytics_type', 'ga4');
            ?>
            <script>
            <?php echo $this->get_tracking_code_for_data('begin_checkout', $checkout_data, $analytics_type); ?>
            </script>
            <?php
        }
    }

    /**
     * Check if this is a development environment
     *
     * @since 1.0.0
     * @return bool True if development environment
     */
    private function is_development_environment(): bool
    {
        // Check for common development indicators
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
        
        $dev_indicators = array(
            'localhost',
            '127.0.0.1',
            '.local',
            '.dev',
            '.test',
            'staging.'
        );
        
        foreach ($dev_indicators as $indicator) {
            if (strpos($server_name, $indicator) !== false) {
                return true;
            }
        }
        
        // Check for local IP ranges
        if (filter_var($server_addr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
            return false; // Public IP
        }
        
        return true; // Private/local IP or undetected
    }
}
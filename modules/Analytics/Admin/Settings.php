<?php

/**
 * Analytics Settings
 *
 * Handles field definitions and settings structure for the Analytics module.
 *
 * @package    Orbitools
 * @subpackage Modules/Analytics/Admin
 * @since      1.0.0
 */

namespace Orbitools\Modules\Analytics\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Settings Class
 *
 * Defines the admin structure and field configurations for the Analytics module.
 *
 * @since 1.0.0
 */
class Settings
{
    /**
     * Initialize settings functionality
     *
     * @since 1.0.0
     */
    public static function init(): void
    {
        // Any initialization logic for settings can go here
        // This method is called from the main Analytics module
    }

    /**
     * Get admin structure for the Analytics module
     *
     * @since 1.0.0
     * @return array Admin structure configuration.
     */
    public static function get_admin_structure(): array
    {
        return array(
            'sections' => array(
                'analytics' => array(
                    'title' => __('Analytics', 'orbitools'),
                    'icon' => array(
                        'type' => 'svg',
                        'value' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M500 89c13.8-11 16-31.2 5-45s-31.2-16-45-5L319.4 151.5 211.2 70.4c-11.7-8.8-27.8-8.5-39.2.6L12 199c-13.8 11-16 31.2-5 45s31.2 16 45 5l140.6-112.5 108.2 81.1c11.7 8.8 27.8 8.5 39.2-.6L500 89zM160 256v192c0 17.7 14.3 32 32 32s32-14.3 32-32V256c0-17.7-14.3-32-32-32s-32 14.3-32 32zM32 352v96c0 17.7 14.3 32 32 32s32-14.3 32-32v-96c0-17.7-14.3-32-32-32s-32 14.3-32 32zm288-64c-17.7 0-32 14.3-32 32v128c0 17.7 14.3 32 32 32s32-14.3 32-32V320c0-17.7-14.3-32-32-32zm96-32v192c0 17.7 14.3 32 32 32s32-14.3 32-32V256c0-17.7-14.3-32-32-32s-32 14.3-32 32z"/></svg>'
                    )
                ),
            ),
        );
    }

    /**
     * Get field definitions for the Analytics module
     *
     * @since 1.0.0
     * @return array Field definitions array.
     */
    public static function get_field_definitions(): array
    {
        return array(
            // Analytics Type Selection
            array(
                'id' => 'analytics_type',
                'name' => __('Analytics Type', 'orbitools'),
                'desc' => __('Choose your preferred analytics implementation', 'orbitools'),
                'type' => 'select',
                'section' => 'analytics',
                'options' => array(
                    'ga4' => __('Google Analytics 4 (GA4) - Recommended', 'orbitools'),
                    'gtm' => __('Google Tag Manager (GTM)', 'orbitools')
                ),
                'std' => 'ga4',
                'show_if' => array(
                    'field' => 'analytics_enabled',
                    'operator' => '===',
                    'value' => '1'
                )
            ),

            // GA4 Measurement ID
            array(
                'id' => 'analytics_ga4_id',
                'name' => __('GA4 Measurement ID', 'orbitools'),
                'desc' => __('Your GA4 Measurement ID (e.g., G-XXXXXXXXXX)', 'orbitools'),
                'type' => 'text',
                'section' => 'analytics',
                'placeholder' => 'G-XXXXXXXXXX',
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => '===',
                        'value' => 'ga4'
                    ),
                    'relation' => 'AND'
                )
            ),

            // GTM Container ID
            array(
                'id' => 'analytics_gtm_id',
                'name' => __('GTM Container ID', 'orbitools'),
                'desc' => __('Your Google Tag Manager Container ID (e.g., GTM-XXXXXXX)', 'orbitools'),
                'type' => 'text',
                'section' => 'analytics',
                'placeholder' => 'GTM-XXXXXXX',
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => '===',
                        'value' => 'gtm'
                    ),
                    'relation' => 'AND'
                )
            ),

            // GTM Notice
            array(
                'id' => 'analytics_gtm_notice',
                'name' => __('Google Tag Manager Configuration', 'orbitools'),
                'desc' => __('<strong>Note:</strong> With GTM, we provide structured dataLayer events for ecommerce and custom tracking. For ecommerce, we push Enhanced Ecommerce format data when enabled. For custom events, we push event data for downloads, outbound links, scroll tracking, and form submissions. You need to configure triggers and GA4 tags in your GTM container to use this data.', 'orbitools'),
                'type' => 'html',
                'section' => 'analytics',
                'std' => '',
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => '===',
                        'value' => 'gtm'
                    ),
                    'relation' => 'AND'
                )
            ),


            // Privacy Settings
            array(
                'id' => 'analytics_respect_dnt',
                'name' => __('Respect Do Not Track', 'orbitools'),
                'desc' => __('Honor browser Do Not Track settings (applies to all types)', 'orbitools'),
                'type' => 'checkbox',
                'section' => 'analytics',
                'std' => true,
                'show_if' => array(
                    'field' => 'analytics_enabled',
                    'operator' => '===',
                    'value' => '1'
                )
            ),

            array(
                'id' => 'analytics_consent_mode',
                'name' => __('Enable Consent Mode v2', 'orbitools'),
                'desc' => __('Enable Google Consent Mode v2 (RECOMMENDED for GDPR compliance). Sets default deny state until user grants consent via cookie banner or consent management platform (GA4 and GTM only)', 'orbitools'),
                'type' => 'checkbox',
                'section' => 'analytics',
                'std' => true,
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => 'in',
                        'value' => array('ga4', 'gtm')
                    ),
                    'relation' => 'AND'
                )
            ),

            // Enhanced Ecommerce
            array(
                'id' => 'analytics_ecommerce_enable',
                'name' => __('Enable Enhanced Ecommerce', 'orbitools'),
                'desc' => __('Track purchase, product view, add to cart, and checkout events (requires WooCommerce) - Works with GA4 and GTM', 'orbitools'),
                'type' => 'checkbox',
                'section' => 'analytics',
                'std' => false,
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => 'in',
                        'value' => array('ga4', 'gtm')
                    ),
                    'relation' => 'AND'
                )
            ),

            array(
                'id' => 'analytics_ecommerce_currency',
                'name' => __('Currency Code', 'orbitools'),
                'desc' => __('Default currency code for ecommerce tracking (e.g., USD, EUR, GBP)', 'orbitools'),
                'type' => 'text',
                'section' => 'analytics',
                'std' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD',
                'placeholder' => 'USD',
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_ecommerce_enable',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => 'in',
                        'value' => array('ga4', 'gtm')
                    ),
                    'relation' => 'AND'
                )
            ),

            // Custom Events
            array(
                'id' => 'analytics_custom_events',
                'name' => __('Custom Event Tracking', 'orbitools'),
                'desc' => __('Select which custom events to track (GA4: enhanced events, GTM: dataLayer pushes)', 'orbitools'),
                'type' => 'checkbox',
                'section' => 'analytics',
                'multiple' => true,
                'options' => array(
                    'downloads' => __('File Downloads - PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, MP3, MP4, AVI, MOV', 'orbitools'),
                    'outbound' => __('Outbound Links - Clicks on external websites', 'orbitools'),
                    'scroll' => __('Scroll Depth - Track 25%, 50%, 75%, and 100% page scroll', 'orbitools'),
                    'forms' => __('Form Submissions - Track HTML form submissions including Contact Form 7, Gravity Forms', 'orbitools')
                ),
                'std' => array('downloads', 'outbound'),
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => 'in',
                        'value' => array('ga4', 'gtm')
                    ),
                    'relation' => 'AND'
                )
            ),

            // Advanced Settings
            array(
                'id' => 'analytics_exclude_roles',
                'name' => __('Exclude User Roles', 'orbitools'),
                'desc' => __('Completely disable all analytics tracking for users with these roles (useful for excluding admins and editors)', 'orbitools'),
                'type' => 'select',
                'section' => 'analytics',
                'multiple' => true,
                'options' => self::get_user_roles(),
                'std' => array('administrator'),
                'show_if' => array(
                    'field' => 'analytics_enabled',
                    'operator' => '===',
                    'value' => '1'
                )
            ),

            array(
                'id' => 'analytics_track_performance',
                'name' => __('Track Core Web Vitals', 'orbitools'),
                'desc' => __('Track page performance metrics like Largest Contentful Paint (LCP) for SEO insights (GA4 only)', 'orbitools'),
                'type' => 'checkbox',
                'section' => 'analytics',
                'std' => false,
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => '===',
                        'value' => 'ga4'
                    ),
                    'relation' => 'AND'
                )
            ),

            array(
                'id' => 'analytics_custom_config',
                'name' => __('Custom Config Parameters', 'orbitools'),
                'desc' => __('JSON object to merge into GA4 config (advanced users only). Use for custom parameters like custom dimensions, debug mode, etc.', 'orbitools'),
                'type' => 'textarea',
                'section' => 'analytics',
                'placeholder' => '{"custom_parameter_1": "value1", "send_page_view": false}',
                'show_if' => array(
                    array(
                        'field' => 'analytics_enabled',
                        'operator' => '===',
                        'value' => '1'
                    ),
                    array(
                        'field' => 'analytics_type',
                        'operator' => '===',
                        'value' => 'ga4'
                    ),
                    'relation' => 'AND'
                )
            ),
        );
    }

    /**
     * Get available user roles
     *
     * @since 1.0.0
     * @return array User roles array.
     */
    private static function get_user_roles(): array
    {
        $roles = array();
        $wp_roles = wp_roles();

        foreach ($wp_roles->roles as $role_key => $role) {
            $roles[$role_key] = $role['name'];
        }

        return $roles;
    }
}
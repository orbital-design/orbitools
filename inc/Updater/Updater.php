<?php

/**
 * GitHub updater functionality for Orbitools.
 *
 * @package    Orbitools
 * @subpackage Orbitools/inc/Updater
 */

namespace Orbitools\Updater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub updater functionality.
 *
 * Handles automatic updates from GitHub repository.
 */
class Updater
{
    /**
     * Plugin file path.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin slug.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_slug;

    /**
     * Current version.
     *
     * @since 1.0.0
     * @var string
     */
    private $version;

    /**
     * GitHub repository information.
     *
     * @since 1.0.0
     * @var array
     */
    private $github_config;

    /**
     * Security logger instance.
     *
     * @since 1.0.0
     * @var object
     */
    private $security_logger;

    /**
     * Initialize the updater.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->plugin_file = ORBITOOLS_FILE;
        $this->plugin_slug = plugin_basename(ORBITOOLS_FILE);
        $this->version = ORBITOOLS_VERSION;
        
        $this->github_config = array(
            'user' => 'orbital-design',
            'repo' => 'orbitools',
            'access_token' => '', // Public repository
            'allowed_domains' => array('api.github.com', 'github.com', 'codeload.github.com')
        );

        // Initialize security logger
        $this->security_logger = $this;

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
    }


    /**
     * Check for plugin updates.
     *
     * @since 1.0.0
     * @param object $transient Update transient.
     * @return object Modified transient.
     */
    public function check_for_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_remote_version();

        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->get_github_url(),
                'package' => $this->get_download_url(),
                'tested' => get_bloginfo('version'),
                'compatibility' => (object) array()
            );
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub.
     *
     * @since 1.0.0
     * @return string Remote version.
     */
    private function get_remote_version(): string
    {
        $cached_version = get_transient('orbitools_remote_version');

        if ($cached_version !== false) {
            return $cached_version;
        }

        $response = $this->api_request('releases/latest');

        if (is_wp_error($response)) {
            return $this->version;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($release['tag_name'])) {
            $version = $this->parse_version($release['tag_name']);
            set_transient('orbitools_remote_version', $version, 12 * HOUR_IN_SECONDS);
            return $version;
        }

        return $this->version;
    }

    /**
     * Parse version from tag name.
     *
     * @since 1.0.0
     * @param string $tag_name Tag name from GitHub.
     * @return string Parsed version.
     */
    private function parse_version(string $tag_name): string
    {
        $version = ltrim($tag_name, 'v');

        // If tag is not a valid version number, try to extract or use default
        if (!preg_match('/^\d+\.\d+\.\d+/', $version)) {
            // Default to a version higher than current to trigger update
            $version = '1.0.1';
        }

        return $version;
    }

    /**
     * Plugin information popup.
     *
     * @since 1.0.0
     * @param mixed $result Plugin info result.
     * @param string $action Action being performed.
     * @param object $args Arguments.
     * @return mixed Plugin information or original result.
     */
    public function plugin_popup($result, $action, $args)
    {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $response = $this->api_request('releases/latest');

        if (is_wp_error($response)) {
            return $result;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        $remote_version = $this->parse_version($release['tag_name']);

        return (object) array(
            'name' => 'Orbitools',
            'slug' => $this->plugin_slug,
            'version' => $remote_version,
            'author' => 'Orbital Design',
            'homepage' => $this->get_github_url(),
            'short_description' => 'Advanced WordPress tools and utilities.',
            'sections' => array(
                'changelog' => isset($release['body']) ? wp_kses_post($release['body']) : '',
                'description' => 'Advanced WordPress tools and utilities with modern admin interface.'
            ),
            'download_link' => $this->get_download_url()
        );
    }

    /**
     * Post installation cleanup.
     *
     * @since 1.0.0
     * @param array $response Install response.
     * @param array $hook_extra Hook extra data.
     * @param array $result Install result.
     * @return array Modified result.
     */
    public function after_install($response, $hook_extra, $result)
    {
        global $wp_filesystem;

        $install_directory = plugin_dir_path($this->plugin_file);
        $wp_filesystem->move($result['destination'], $install_directory);
        $result['destination'] = $install_directory;

        if ($this->plugin_slug === $hook_extra['plugin']) {
            wp_cache_flush();
        }

        return $result;
    }

    /**
     * Make API request to GitHub.
     *
     * @since 1.0.0
     * @param string $endpoint API endpoint.
     * @return array|WP_Error API response.
     */
    private function api_request(string $endpoint)
    {
        $url = "https://api.github.com/repos/{$this->github_config['user']}/{$this->github_config['repo']}/{$endpoint}";

        // Validate URL before making request
        if (!$this->is_safe_url($url)) {
            $this->log_security_event('SSRF_ATTEMPT', array(
                'url' => $url,
                'endpoint' => $endpoint
            ));
            return new \WP_Error('invalid_url', 'Invalid update server URL');
        }

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Orbitools-Updater/' . $this->version
            )
        );

        $response = wp_remote_get($url, $args);

        // Log API requests for security monitoring
        $this->log_security_event('UPDATE_CHECK', array(
            'url' => $url,
            'response_code' => wp_remote_retrieve_response_code($response)
        ));

        return $response;
    }

    /**
     * Get GitHub repository URL.
     *
     * @since 1.0.0
     * @return string GitHub URL.
     */
    private function get_github_url(): string
    {
        return "https://github.com/{$this->github_config['user']}/{$this->github_config['repo']}";
    }

    /**
     * Get download URL for latest release.
     *
     * @since 1.0.0
     * @return string Download URL.
     */
    private function get_download_url(): string
    {
        $response = $this->api_request('releases/latest');

        if (!is_wp_error($response)) {
            $release = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($release['zipball_url'])) {
                return $release['zipball_url'];
            }
        }

        return "https://github.com/{$this->github_config['user']}/{$this->github_config['repo']}/archive/main.zip";
    }

    /**
     * Force update check by clearing cache.
     *
     * @since 1.0.0
     */
    public function force_update_check(): void
    {
        delete_transient('orbitools_remote_version');
        delete_transient('orbitools_changelog');
        delete_site_transient('update_plugins');
        set_transient('orbitools_last_checked', current_time('mysql'), 12 * HOUR_IN_SECONDS);
    }

    /**
     * Get update information for admin display.
     *
     * @since 1.0.0
     * @return array Update information.
     */
    public function get_update_info(): array
    {
        $remote_version = $this->get_remote_version();
        $has_update = version_compare($this->version, $remote_version, '<');

        return array(
            'current_version' => $this->version,
            'remote_version' => $remote_version,
            'has_update' => $has_update,
            'github_url' => $this->get_github_url(),
            'last_checked' => get_transient('orbitools_last_checked') ?: 'Never',
            'repository_type' => 'Public Repository'
        );
    }

    /**
     * Validate URL is safe for external requests
     *
     * @since 1.0.0
     * @param string $url URL to validate
     * @return bool True if safe, false otherwise
     */
    private function is_safe_url(string $url): bool
    {
        $parsed_url = parse_url($url);
        
        if (!$parsed_url || !isset($parsed_url['host'])) {
            return false;
        }

        // Only allow HTTPS
        if (!isset($parsed_url['scheme']) || $parsed_url['scheme'] !== 'https') {
            return false;
        }

        // Check against allowed domains
        if (!in_array($parsed_url['host'], $this->github_config['allowed_domains'], true)) {
            return false;
        }

        // Validate repository path structure
        $expected_path_prefix = "/repos/{$this->github_config['user']}/{$this->github_config['repo']}/";
        if (!isset($parsed_url['path']) || strpos($parsed_url['path'], $expected_path_prefix) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Verify package integrity (placeholder for future implementation)
     *
     * @since 1.0.0
     * @param string $package_path Path to downloaded package
     * @param array $release_info Release information from API
     * @return bool True if verified, false otherwise
     */
    private function verify_package_integrity(string $package_path, array $release_info): bool
    {
        // TODO: Implement SHA checksum verification when GitHub provides release checksums
        // For now, perform basic file validation
        
        if (!file_exists($package_path)) {
            return false;
        }

        // Check minimum file size (prevent empty/corrupted downloads)
        $file_size = filesize($package_path);
        if ($file_size < 1024) { // Less than 1KB is suspicious for a plugin
            $this->log_security_event('PACKAGE_SIZE_WARNING', array(
                'file_size' => $file_size,
                'package_path' => basename($package_path)
            ));
            return false;
        }

        // Verify ZIP file structure
        if (function_exists('zip_open')) {
            $zip = zip_open($package_path);
            if (!is_resource($zip)) {
                $this->log_security_event('PACKAGE_CORRUPT', array(
                    'package_path' => basename($package_path)
                ));
                return false;
            }
            zip_close($zip);
        }

        return true;
    }

    /**
     * Log security events
     *
     * @since 1.0.0
     * @param string $event_type Type of security event
     * @param array $context Additional context data
     * @return void
     */
    private function log_security_event(string $event_type, array $context = array()): void
    {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'user_id' => get_current_user_id(),
            'user_ip' => $this->get_user_ip(),
            'context' => $context
        );

        // Log to WordPress error log
        error_log('ORBITOOLS_SECURITY: ' . $event_type . ' - ' . wp_json_encode($log_entry));

        // Store in options for security dashboard (keep last 100 events)
        $security_log = get_option('orbitools_security_log', array());
        array_unshift($security_log, $log_entry);
        $security_log = array_slice($security_log, 0, 100);
        update_option('orbitools_security_log', $security_log);
    }

    /**
     * Get user IP address safely
     *
     * @since 1.0.0
     * @return string User IP address
     */
    private function get_user_ip(): string
    {
        // Sanitize and validate IP addresses from various headers
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}
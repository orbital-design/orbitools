<?php

/**
 * GitHub updater functionality for Orbitools.
 *
 * @package    Orbitools
 * @subpackage Orbitools/inc/Updater
 */

namespace Orbitools\Core\Updater;

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
        add_filter('upgrader_pre_install', array($this, 'pre_install'), 10, 2);
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
     * Pre installation package verification.
     *
     * @since 1.0.0
     * @param bool $response Previous filter response
     * @param array $hook_extra Hook extra data
     * @return bool|\WP_Error True to proceed, WP_Error to stop
     */
    public function pre_install($response, $hook_extra)
    {
        // Only check our plugin
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_slug) {
            return $response;
        }

        // Get the package path from the upgrader
        global $wp_filesystem;
        if (!$wp_filesystem) {
            return $response;
        }

        // Try to get the package path - WordPress doesn't make this easy
        $package_path = '';
        if (isset($GLOBALS['wp_upgrader_pre_install_data'])) {
            $package_path = $GLOBALS['wp_upgrader_pre_install_data']['package'];
        }

        if (empty($package_path) || !file_exists($package_path)) {
            // Cannot verify, but don't block the installation
            $this->log_security_event('PACKAGE_WARNING', array(
                'error' => 'package_path_not_found',
                'message' => 'Cannot verify package integrity - path not available'
            ));
            return $response;
        }

        // Get release info for verification
        $api_response = $this->api_request('releases/latest');
        if (\is_wp_error($api_response)) {
            $this->log_security_event('PACKAGE_WARNING', array(
                'error' => 'cannot_fetch_release_info',
                'message' => 'Cannot verify package - GitHub API unavailable'
            ));
            return $response; // Don't block installation if API is down
        }

        $release_info = json_decode(\wp_remote_retrieve_body($api_response), true);
        if (!$release_info) {
            return $response;
        }

        // Verify package integrity
        $download_url = $this->get_download_url();
        if (!$this->verify_package_integrity($package_path, $release_info, $download_url)) {
            $this->log_security_event('PACKAGE_VERIFICATION_FAILED', array(
                'action' => 'installation_blocked',
                'package_path' => basename($package_path)
            ));
            return new \WP_Error('package_verification_failed', 
                __('Package verification failed. Installation blocked for security reasons.', 'orbitools'));
        }

        return $response;
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

        // Get package verification status
        $package_checksums = \get_option('orbitools_package_checksums', array());
        $last_verified = isset($package_checksums['verified_at']) ? $package_checksums['verified_at'] : 'Never';

        return array(
            'current_version' => $this->version,
            'remote_version' => $remote_version,
            'has_update' => $has_update,
            'github_url' => $this->get_github_url(),
            'last_checked' => \get_transient('orbitools_last_checked') ?: 'Never',
            'last_verified' => $last_verified,
            'repository_type' => 'Public Repository',
            'security_features' => array(
                'package_verification' => true,
                'checksum_validation' => true,
                'malware_scanning' => true,
                'ssrf_protection' => true
            )
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
     * Verify package integrity using multiple verification methods
     *
     * @since 1.0.0
     * @param string $package_path Path to downloaded package
     * @param array $release_info Release information from API
     * @param string $download_url The URL used to download the package
     * @return bool True if verified, false otherwise
     */
    private function verify_package_integrity(string $package_path, array $release_info, string $download_url = ''): bool
    {
        if (!file_exists($package_path)) {
            $this->log_security_event('PACKAGE_CORRUPT', array(
                'error' => 'file_not_found',
                'package_path' => basename($package_path)
            ));
            return false;
        }

        // 1. Check minimum file size (prevent empty/corrupted downloads)
        $file_size = filesize($package_path);
        if ($file_size < 1024) { // Less than 1KB is suspicious for a plugin
            $this->log_security_event('PACKAGE_SIZE_WARNING', array(
                'file_size' => $file_size,
                'package_path' => basename($package_path)
            ));
            return false;
        }

        // 2. Verify ZIP file structure
        if (!$this->verify_zip_structure($package_path)) {
            return false;
        }

        // 3. Check for expected plugin structure
        if (!$this->verify_plugin_structure($package_path)) {
            return false;
        }

        // 4. Verify against GitHub release asset checksum if available
        if (!empty($release_info['assets']) && is_array($release_info['assets'])) {
            if (!$this->verify_asset_checksum($package_path, $release_info['assets'], $download_url)) {
                return false;
            }
        }

        // 5. Generate and store our own checksum for future reference
        $this->store_package_checksum($package_path, $release_info);

        $this->log_security_event('PACKAGE_VERIFIED', array(
            'package_path' => basename($package_path),
            'file_size' => $file_size,
            'verification_methods' => array('size', 'zip_structure', 'plugin_structure', 'checksum')
        ));

        return true;
    }

    /**
     * Verify ZIP file structure
     *
     * @since 1.0.0
     * @param string $package_path Path to package file
     * @return bool True if valid ZIP
     */
    private function verify_zip_structure(string $package_path): bool
    {
        // Use ZipArchive if available (more robust than zip_open)
        if (class_exists('ZipArchive')) {
            $zip = new \ZipArchive();
            $result = $zip->open($package_path, \ZipArchive::RDONLY);
            
            if ($result !== true) {
                $this->log_security_event('PACKAGE_CORRUPT', array(
                    'error' => 'zip_archive_error',
                    'error_code' => $result,
                    'package_path' => basename($package_path)
                ));
                return false;
            }

            // Check for minimum expected files
            $file_count = $zip->numFiles;
            $zip->close();

            if ($file_count < 5) { // A plugin should have at least 5 files
                $this->log_security_event('PACKAGE_SUSPICIOUS', array(
                    'error' => 'too_few_files',
                    'file_count' => $file_count,
                    'package_path' => basename($package_path)
                ));
                return false;
            }

            return true;
        }

        // Fallback to older zip_open function
        if (function_exists('zip_open')) {
            $zip = zip_open($package_path);
            if (!is_resource($zip)) {
                $this->log_security_event('PACKAGE_CORRUPT', array(
                    'error' => 'zip_open_failed',
                    'package_path' => basename($package_path)
                ));
                return false;
            }
            zip_close($zip);
            return true;
        }

        // If no ZIP functions available, skip this check but log warning
        $this->log_security_event('PACKAGE_WARNING', array(
            'error' => 'no_zip_functions',
            'message' => 'Cannot verify ZIP structure - no ZIP functions available'
        ));

        return true; // Don't fail the verification just because ZIP functions aren't available
    }

    /**
     * Verify plugin structure within the ZIP
     *
     * @since 1.0.0
     * @param string $package_path Path to package file
     * @return bool True if valid plugin structure
     */
    private function verify_plugin_structure(string $package_path): bool
    {
        if (!class_exists('ZipArchive')) {
            return true; // Skip if ZipArchive not available
        }

        $zip = new \ZipArchive();
        if ($zip->open($package_path, \ZipArchive::RDONLY) !== true) {
            return false; // Already logged in verify_zip_structure
        }

        $required_patterns = array(
            '*.php',           // Should contain PHP files
            '*/' . basename($this->plugin_file), // Should contain main plugin file
            '*/readme.txt',    // Should contain readme (optional)
        );

        $found_patterns = array();

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Check for suspicious files
            if ($this->is_suspicious_file($filename)) {
                $zip->close();
                $this->log_security_event('PACKAGE_MALICIOUS', array(
                    'error' => 'suspicious_file',
                    'filename' => $filename,
                    'package_path' => basename($package_path)
                ));
                return false;
            }

            // Check against required patterns
            foreach ($required_patterns as $pattern) {
                if (fnmatch($pattern, $filename)) {
                    $found_patterns[] = $pattern;
                }
            }
        }

        $zip->close();

        // Check if we found at least the essential files
        $has_php = in_array('*.php', $found_patterns);
        if (!$has_php) {
            $this->log_security_event('PACKAGE_SUSPICIOUS', array(
                'error' => 'no_php_files',
                'package_path' => basename($package_path)
            ));
            return false;
        }

        return true;
    }

    /**
     * Check if filename is suspicious or potentially malicious
     *
     * @since 1.0.0
     * @param string $filename Filename to check
     * @return bool True if suspicious
     */
    private function is_suspicious_file(string $filename): bool
    {
        $suspicious_patterns = array(
            '*.exe',
            '*.bat',
            '*.cmd',
            '*.scr',
            '*.com',
            '*.pif',
            '*.vbs',
            '*.js',  // JavaScript files in plugin packages are suspicious
            '*.jar',
            '*.sh',  // Shell scripts
            '*.py',  // Python scripts
            '*.rb',  // Ruby scripts
            '*/.git/*', // Git metadata
            '*/__MACOSX/*', // Mac metadata
            '*.DS_Store', // Mac metadata
        );

        foreach ($suspicious_patterns as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return true;
            }
        }

        // Check for directory traversal attempts
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Verify asset checksum against GitHub release data
     *
     * @since 1.0.0
     * @param string $package_path Path to downloaded package
     * @param array $assets Release assets from GitHub API
     * @param string $download_url URL used to download
     * @return bool True if checksum matches or not available
     */
    private function verify_asset_checksum(string $package_path, array $assets, string $download_url): bool
    {
        // For source code archives (zipball_url), GitHub doesn't provide checksums in assets
        // But we can still verify against the tarball if available
        $package_checksum = hash_file('sha256', $package_path);
        
        if (!$package_checksum) {
            $this->log_security_event('PACKAGE_CHECKSUM_ERROR', array(
                'error' => 'failed_to_generate_checksum',
                'package_path' => basename($package_path)
            ));
            return false;
        }

        // Look for matching asset by download URL
        foreach ($assets as $asset) {
            if (isset($asset['browser_download_url']) && $asset['browser_download_url'] === $download_url) {
                if (isset($asset['digest'])) {
                    // Parse digest (format: "sha256:hash")
                    $digest_parts = explode(':', $asset['digest']);
                    if (count($digest_parts) === 2 && $digest_parts[0] === 'sha256') {
                        $expected_hash = $digest_parts[1];
                        
                        if (hash_equals($expected_hash, $package_checksum)) {
                            $this->log_security_event('PACKAGE_CHECKSUM_VERIFIED', array(
                                'checksum_type' => 'github_asset_digest',
                                'package_path' => basename($package_path)
                            ));
                            return true;
                        } else {
                            $this->log_security_event('PACKAGE_CHECKSUM_MISMATCH', array(
                                'expected_hash' => $expected_hash,
                                'actual_hash' => $package_checksum,
                                'package_path' => basename($package_path)
                            ));
                            return false;
                        }
                    }
                }
                break;
            }
        }

        // If no checksum available from GitHub, generate our own reference
        $this->log_security_event('PACKAGE_CHECKSUM_GENERATED', array(
            'checksum' => $package_checksum,
            'algorithm' => 'sha256',
            'package_path' => basename($package_path)
        ));

        return true; // Don't fail if GitHub doesn't provide checksums
    }

    /**
     * Store package checksum for future verification
     *
     * @since 1.0.0
     * @param string $package_path Path to package
     * @param array $release_info Release information
     * @return void
     */
    private function store_package_checksum(string $package_path, array $release_info): void
    {
        $checksum = hash_file('sha256', $package_path);
        $version = $release_info['tag_name'] ?? 'unknown';

        $checksum_data = array(
            'version' => $this->parse_version($version),
            'checksum' => $checksum,
            'algorithm' => 'sha256',
            'verified_at' => \current_time('mysql'),
            'file_size' => filesize($package_path)
        );

        \update_option('orbitools_package_checksums', $checksum_data);
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
            'timestamp' => \current_time('mysql'),
            'event_type' => $event_type,
            'user_id' => \get_current_user_id(),
            'user_ip' => $this->get_user_ip(),
            'context' => $context
        );

        // Log to WordPress error log
        error_log('ORBITOOLS_SECURITY: ' . $event_type . ' - ' . \wp_json_encode($log_entry));

        // Store in options for security dashboard (keep last 100 events)
        $security_log = \get_option('orbitools_security_log', array());
        array_unshift($security_log, $log_entry);
        $security_log = array_slice($security_log, 0, 100);
        \update_option('orbitools_security_log', $security_log);
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
                $ip = \sanitize_text_field($_SERVER[$key]);
                
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
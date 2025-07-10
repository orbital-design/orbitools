<?php
/**
 * GitHub updater functionality.
 *
 * @package    Orbital_Editor_Suite
 * @subpackage Orbital_Editor_Suite/includes/updater
 */

namespace Orbital\Editor_Suite\Updater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub updater functionality.
 *
 * Handles automatic updates from GitHub repository.
 */
class GitHub_Updater {

    /**
     * Plugin file path.
     */
    private $plugin_file;

    /**
     * Plugin slug.
     */
    private $plugin_slug;

    /**
     * Current version.
     */
    private $version;

    /**
     * GitHub repository information.
     */
    private $github_config;

    /**
     * Initialize the updater.
     */
    public function __construct($plugin_file, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;
        
        $this->github_config = array(
            'user' => 'orbital-design',
            'repo' => 'orbital-editor-suite',
            'access_token' => '' // Public repository
        );

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_action('admin_init', array($this, 'handle_manual_check'));
    }

    /**
     * Handle manual update check.
     */
    public function handle_manual_check() {
        if (isset($_GET['orbital_check_update']) && $_GET['orbital_check_update'] === '1') {
            if (!current_user_can('manage_options')) {
                wp_die(__('Insufficient permissions.', 'orbital-editor-suite'));
            }
            
            $this->force_update_check();
            wp_redirect(admin_url('admin.php?page=orbital-editor-suite-updates&checked=1'));
            exit;
        }
    }

    /**
     * Check for plugin updates.
     */
    public function check_for_update($transient) {
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
     */
    private function get_remote_version() {
        $cached_version = get_transient('orbital_editor_suite_remote_version');

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
            set_transient('orbital_editor_suite_remote_version', $version, 12 * HOUR_IN_SECONDS);
            return $version;
        }

        return $this->version;
    }

    /**
     * Parse version from tag name.
     */
    private function parse_version($tag_name) {
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
     */
    public function plugin_popup($result, $action, $args) {
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
            'name' => 'Orbital Editor Suite',
            'slug' => $this->plugin_slug,
            'version' => $remote_version,
            'author' => 'Orbital Design',
            'homepage' => $this->get_github_url(),
            'short_description' => __('Professional suite of editor enhancements with typography utilities', 'orbital-editor-suite'),
            'sections' => array(
                'changelog' => isset($release['body']) ? wp_kses_post($release['body']) : '',
                'description' => __('Professional suite of editor enhancements with typography utilities and modern admin panel.', 'orbital-editor-suite')
            ),
            'download_link' => $this->get_download_url()
        );
    }

    /**
     * Post installation cleanup.
     */
    public function after_install($response, $hook_extra, $result) {
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
     */
    private function api_request($endpoint) {
        $url = "https://api.github.com/repos/{$this->github_config['user']}/{$this->github_config['repo']}/{$endpoint}";

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Orbital-Editor-Suite-Updater/' . $this->version
            )
        );

        return wp_remote_get($url, $args);
    }

    /**
     * Get GitHub repository URL.
     */
    private function get_github_url() {
        return "https://github.com/{$this->github_config['user']}/{$this->github_config['repo']}";
    }

    /**
     * Get download URL for latest release.
     */
    private function get_download_url() {
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
     */
    public function force_update_check() {
        delete_transient('orbital_editor_suite_remote_version');
        delete_transient('orbital_editor_suite_changelog');
        delete_site_transient('update_plugins');
        set_transient('orbital_editor_suite_last_checked', current_time('mysql'), 12 * HOUR_IN_SECONDS);
    }

    /**
     * Get update information for admin display.
     */
    public function get_update_info() {
        $remote_version = $this->get_remote_version();
        $has_update = version_compare($this->version, $remote_version, '<');

        return array(
            'current_version' => $this->version,
            'remote_version' => $remote_version,
            'has_update' => $has_update,
            'github_url' => $this->get_github_url(),
            'last_checked' => get_transient('orbital_editor_suite_last_checked') ?: __('Never', 'orbital-editor-suite'),
            'repository_type' => __('Public Repository', 'orbital-editor-suite')
        );
    }
}
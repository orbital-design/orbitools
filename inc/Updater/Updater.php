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
     * Initialize the updater.
     *
     * @since 1.0.0
     * @param string $plugin_file Plugin file path.
     * @param string $version Current plugin version.
     */
    public function __construct($plugin_file, $version)
    {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;
        
        $this->github_config = array(
            'user' => 'orbital-design',
            'repo' => 'orbitools',
            'access_token' => '' // Public repository
        );

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
        add_action('admin_init', array($this, 'handle_manual_check'));
    }

    /**
     * Handle manual update check.
     *
     * @since 1.0.0
     */
    public function handle_manual_check(): void
    {
        if (isset($_GET['orbitools_check_update']) && $_GET['orbitools_check_update'] === '1') {
            if (!current_user_can('manage_options')) {
                wp_die('Insufficient permissions.');
            }
            
            $this->force_update_check();
            wp_redirect(admin_url('admin.php?page=orbitools&tab=updates&checked=1'));
            exit;
        }
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

        $args = array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Orbitools-Updater/' . $this->version
            )
        );

        return wp_remote_get($url, $args);
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
}
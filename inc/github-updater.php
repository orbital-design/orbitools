<?php
/**
 * GitHub Updater for Orbital Editor Suite
 * Handles automatic updates from GitHub repository
 */

if (!defined('ABSPATH')) {
    exit;
}

class OES_GitHub_Updater {
    
    private $plugin_slug;
    private $version;
    private $github_user;
    private $github_repo;
    private $plugin_file;
    private $github_token;
    
    public function __construct($plugin_file, $version) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;
        $this->github_user = 'orbital-design';
        $this->github_repo = 'orbital-editor-suite';
        $this->github_token = ''; // No token needed for public repos
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function admin_init() {
        // Handle manual update check
        if (isset($_GET['oes_check_update']) && $_GET['oes_check_update'] === '1') {
            $this->force_update_check();
            wp_redirect(admin_url('admin.php?page=orbital-editor-suite&tab=updates&checked=1'));
            exit;
        }
    }
    
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
                'tested' => '6.4'
            );
        }
        
        return $transient;
    }
    
    public function plugin_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if ($args->slug === $this->plugin_slug) {
            $remote_version = $this->get_remote_version();
            $changelog = $this->get_changelog();
            
            return (object) array(
                'name' => 'Orbital Editor Suite',
                'slug' => $this->plugin_slug,
                'version' => $remote_version,
                'author' => 'Orbital',
                'homepage' => $this->get_github_url(),
                'short_description' => 'Professional suite of editor enhancements with typography utilities',
                'sections' => array(
                    'changelog' => $changelog,
                    'description' => 'Professional suite of editor enhancements with typography utilities and modern admin panel'
                ),
                'download_link' => $this->get_download_url()
            );
        }
        
        return $result;
    }
    
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
    
    private function get_remote_version() {
        $cached_version = get_transient('oes_remote_version');
        
        if ($cached_version !== false) {
            return $cached_version;
        }
        
        $request = wp_remote_get($this->get_api_url(), $this->get_request_args());
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $release = json_decode($body, true);
            
            if (isset($release['tag_name'])) {
                $tag_name = $release['tag_name'];
                $version = ltrim($tag_name, 'v');
                
                // If tag is not a valid version number, try to extract from release name or use a default
                if (!preg_match('/^\d+\.\d+\.\d+/', $version)) {
                    // Check if there's a version in the release name
                    if (isset($release['name']) && preg_match('/v?(\d+\.\d+\.\d+)/', $release['name'], $matches)) {
                        $version = $matches[1];
                    } else {
                        // Default to a version higher than current to trigger update
                        $version = '1.0.1';
                    }
                }
                
                set_transient('oes_remote_version', $version, 12 * HOUR_IN_SECONDS);
                return $version;
            }
        }
        
        return $this->version;
    }
    
    private function get_changelog() {
        $cached_changelog = get_transient('oes_changelog');
        
        if ($cached_changelog !== false) {
            return $cached_changelog;
        }
        
        $request = wp_remote_get($this->get_api_url(), $this->get_request_args());
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $release = json_decode($body, true);
            
            if (isset($release['body'])) {
                $changelog = wp_kses_post($release['body']);
                set_transient('oes_changelog', $changelog, 12 * HOUR_IN_SECONDS);
                return $changelog;
            }
        }
        
        return 'No changelog available.';
    }
    
    private function get_request_args() {
        return array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'Orbital-Editor-Suite-Updater/1.0'
            )
        );
    }
    
    private function get_api_url() {
        return "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}/releases/latest";
    }
    
    private function get_github_url() {
        return "https://github.com/{$this->github_user}/{$this->github_repo}";
    }
    
    private function get_download_url() {
        $request = wp_remote_get($this->get_api_url(), $this->get_request_args());
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $release = json_decode($body, true);
            
            if (isset($release['zipball_url'])) {
                return $release['zipball_url'];
            }
        }
        
        return "https://github.com/{$this->github_user}/{$this->github_repo}/archive/main.zip";
    }
    
    public function force_update_check() {
        delete_transient('oes_remote_version');
        delete_transient('oes_changelog');
        delete_site_transient('update_plugins');
    }
    
    public function get_update_info() {
        $remote_version = $this->get_remote_version();
        $has_update = version_compare($this->version, $remote_version, '<');
        
        // Debug information
        $debug_info = $this->get_debug_info();
        
        return array(
            'current_version' => $this->version,
            'remote_version' => $remote_version,
            'has_update' => $has_update,
            'github_url' => $this->get_github_url(),
            'last_checked' => get_transient('oes_last_checked') ?: 'Never',
            'debug' => $debug_info
        );
    }
    
    public function get_debug_info() {
        set_transient('oes_last_checked', current_time('mysql'), 12 * HOUR_IN_SECONDS);
        
        $request = wp_remote_get($this->get_api_url(), $this->get_request_args());
        
        $debug = array(
            'api_url' => $this->get_api_url(),
            'repository_type' => 'Public Repository',
            'response_code' => '',
            'error_message' => '',
            'raw_response' => ''
        );
        
        if (is_wp_error($request)) {
            $debug['error_message'] = $request->get_error_message();
        } else {
            $debug['response_code'] = wp_remote_retrieve_response_code($request);
            $body = wp_remote_retrieve_body($request);
            
            if ($debug['response_code'] === 200) {
                $release = json_decode($body, true);
                if (isset($release['tag_name'])) {
                    $debug['raw_response'] = sprintf(
                        'Found release - Tag: "%s", Name: "%s"', 
                        $release['tag_name'],
                        isset($release['name']) ? $release['name'] : 'No name'
                    );
                } else {
                    $debug['raw_response'] = 'No tag_name in response';
                }
            } else {
                $debug['raw_response'] = substr($body, 0, 200) . '...';
            }
        }
        
        return $debug;
    }
}
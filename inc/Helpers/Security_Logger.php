<?php

/**
 * Security Logger
 *
 * Centralized security logging system for Orbitools plugin
 *
 * @package    Orbitools
 * @subpackage Helpers
 * @since      1.0.0
 */

namespace Orbitools\Helpers;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Logger Class
 *
 * Handles all security-related logging and monitoring
 *
 * @since 1.0.0
 */
class Security_Logger
{
    /**
     * Singleton instance
     *
     * @since 1.0.0
     * @var Security_Logger|null
     */
    private static ?Security_Logger $instance = null;

    /**
     * Maximum number of log entries to keep
     *
     * @since 1.0.0
     * @var int
     */
    const MAX_LOG_ENTRIES = 1000;

    /**
     * Security event types
     *
     * @since 1.0.0
     * @var array
     */
    const EVENT_TYPES = array(
        'AUTH_FAILURE' => 'Authentication Failure',
        'NONCE_FAILURE' => 'Nonce Verification Failed',
        'PERMISSION_DENIED' => 'Permission Denied',
        'SSRF_ATTEMPT' => 'SSRF Attack Attempt',
        'SQL_INJECTION' => 'SQL Injection Attempt',
        'XSS_ATTEMPT' => 'XSS Attack Attempt',
        'UPDATE_CHECK' => 'Update Check Performed',
        'PACKAGE_CORRUPT' => 'Package Integrity Failed',
        'PACKAGE_SIZE_WARNING' => 'Suspicious Package Size',
        'ADMIN_ACCESS' => 'Admin Area Access',
        'SETTINGS_CHANGED' => 'Plugin Settings Modified',
        'MODULE_TOGGLED' => 'Module Enabled/Disabled',
        'CACHE_CLEARED' => 'Cache Cleared',
        'SECURITY_SCAN' => 'Security Scan Performed'
    );

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return Security_Logger
     */
    public static function instance(): Security_Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct() {}

    /**
     * Log a security event
     *
     * @since 1.0.0
     * @param string $event_type Event type from EVENT_TYPES
     * @param array $context Additional context data
     * @param string $severity Event severity (low, medium, high, critical)
     * @return void
     */
    public function log_event(string $event_type, array $context = array(), string $severity = 'medium'): void
    {
        // Validate event type
        if (!array_key_exists($event_type, self::EVENT_TYPES)) {
            error_log('ORBITOOLS_SECURITY: Invalid event type: ' . $event_type);
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'event_type' => $event_type,
            'event_name' => self::EVENT_TYPES[$event_type],
            'severity' => $this->validate_severity($severity),
            'user_id' => get_current_user_id(),
            'user_login' => $this->get_current_user_login(),
            'user_ip' => $this->get_user_ip(),
            'user_agent' => $this->get_user_agent(),
            'request_uri' => $this->get_request_uri(),
            'context' => $this->sanitize_context($context)
        );

        // Log to WordPress error log
        $log_message = sprintf(
            'ORBITOOLS_SECURITY [%s]: %s - User: %s (%d) IP: %s',
            strtoupper($severity),
            $event_type,
            $log_entry['user_login'],
            $log_entry['user_id'],
            $log_entry['user_ip']
        );
        error_log($log_message);

        // Store in database option
        $this->store_log_entry($log_entry);

        // Send alert for critical events
        if ($severity === 'critical') {
            $this->send_security_alert($log_entry);
        }
    }

    /**
     * Log authentication failure
     *
     * @since 1.0.0
     * @param string $action Action that failed
     * @param array $context Additional context
     * @return void
     */
    public function log_auth_failure(string $action, array $context = array()): void
    {
        $context['failed_action'] = $action;
        $this->log_event('AUTH_FAILURE', $context, 'high');
    }

    /**
     * Log nonce failure
     *
     * @since 1.0.0
     * @param string $action Action that failed
     * @param string $expected_nonce Expected nonce
     * @param string $received_nonce Received nonce
     * @return void
     */
    public function log_nonce_failure(string $action, string $expected_nonce = '', string $received_nonce = ''): void
    {
        $context = array(
            'failed_action' => $action,
            'expected_nonce' => substr($expected_nonce, 0, 10) . '...', // Partial for security
            'received_nonce' => substr($received_nonce, 0, 10) . '...'
        );
        $this->log_event('NONCE_FAILURE', $context, 'high');
    }

    /**
     * Get security log entries
     *
     * @since 1.0.0
     * @param int $limit Number of entries to retrieve
     * @param string $severity Filter by severity
     * @return array Log entries
     */
    public function get_log_entries(int $limit = 50, string $severity = ''): array
    {
        $security_log = get_option('orbitools_security_log', array());
        
        // Filter by severity if specified
        if (!empty($severity)) {
            $security_log = array_filter($security_log, function($entry) use ($severity) {
                return isset($entry['severity']) && $entry['severity'] === $severity;
            });
        }

        return array_slice($security_log, 0, $limit);
    }

    /**
     * Clear security log
     *
     * @since 1.0.0
     * @return bool Success status
     */
    public function clear_log(): bool
    {
        $this->log_event('SECURITY_SCAN', array('action' => 'log_cleared'));
        return delete_option('orbitools_security_log');
    }

    /**
     * Get security statistics
     *
     * @since 1.0.0
     * @return array Security statistics
     */
    public function get_security_stats(): array
    {
        $security_log = get_option('orbitools_security_log', array());
        $stats = array(
            'total_events' => count($security_log),
            'events_by_type' => array(),
            'events_by_severity' => array(),
            'recent_events' => 0
        );

        // Count events from last 24 hours
        $yesterday = date('Y-m-d H:i:s', strtotime('-24 hours'));

        foreach ($security_log as $entry) {
            // Count by type
            $type = $entry['event_type'] ?? 'unknown';
            $stats['events_by_type'][$type] = ($stats['events_by_type'][$type] ?? 0) + 1;

            // Count by severity
            $severity = $entry['severity'] ?? 'unknown';
            $stats['events_by_severity'][$severity] = ($stats['events_by_severity'][$severity] ?? 0) + 1;

            // Count recent events
            if (isset($entry['timestamp']) && $entry['timestamp'] >= $yesterday) {
                $stats['recent_events']++;
            }
        }

        return $stats;
    }

    /**
     * Validate severity level
     *
     * @since 1.0.0
     * @param string $severity Severity level
     * @return string Valid severity level
     */
    private function validate_severity(string $severity): string
    {
        $valid_severities = array('low', 'medium', 'high', 'critical');
        return in_array($severity, $valid_severities, true) ? $severity : 'medium';
    }

    /**
     * Get current user login safely
     *
     * @since 1.0.0
     * @return string User login or 'anonymous'
     */
    private function get_current_user_login(): string
    {
        $user = wp_get_current_user();
        return $user && $user->exists() ? $user->user_login : 'anonymous';
    }

    /**
     * Get user IP address safely
     *
     * @since 1.0.0
     * @return string User IP address
     */
    private function get_user_ip(): string
    {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                
                // Handle comma-separated IPs
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

    /**
     * Get user agent safely
     *
     * @since 1.0.0
     * @return string User agent string
     */
    private function get_user_agent(): string
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 255) : '';
    }

    /**
     * Get request URI safely
     *
     * @since 1.0.0
     * @return string Request URI
     */
    private function get_request_uri(): string
    {
        return isset($_SERVER['REQUEST_URI']) ? substr(sanitize_text_field($_SERVER['REQUEST_URI']), 0, 255) : '';
    }

    /**
     * Sanitize context data
     *
     * @since 1.0.0
     * @param array $context Context data
     * @return array Sanitized context
     */
    private function sanitize_context(array $context): array
    {
        $sanitized = array();
        
        foreach ($context as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_string($value)) {
                $sanitized[$key] = substr(sanitize_text_field($value), 0, 500);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize_context($value);
            } else {
                $sanitized[$key] = 'unsupported_type';
            }
        }
        
        return $sanitized;
    }

    /**
     * Store log entry in database
     *
     * @since 1.0.0
     * @param array $log_entry Log entry data
     * @return void
     */
    private function store_log_entry(array $log_entry): void
    {
        $security_log = get_option('orbitools_security_log', array());
        array_unshift($security_log, $log_entry);
        
        // Keep only the most recent entries
        if (count($security_log) > self::MAX_LOG_ENTRIES) {
            $security_log = array_slice($security_log, 0, self::MAX_LOG_ENTRIES);
        }
        
        update_option('orbitools_security_log', $security_log);
    }

    /**
     * Send security alert for critical events
     *
     * @since 1.0.0
     * @param array $log_entry Log entry data
     * @return void
     */
    private function send_security_alert(array $log_entry): void
    {
        // Get admin email
        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }

        $subject = sprintf(
            '[%s] Critical Security Alert: %s',
            get_bloginfo('name'),
            $log_entry['event_name']
        );

        $message = sprintf(
            "A critical security event has been detected on your WordPress site:\n\n" .
            "Event: %s\n" .
            "Time: %s\n" .
            "User: %s (ID: %d)\n" .
            "IP Address: %s\n" .
            "User Agent: %s\n" .
            "Request URI: %s\n\n" .
            "Context: %s\n\n" .
            "Please review your site's security logs immediately.\n\n" .
            "This is an automated security notification from the Orbitools plugin.",
            $log_entry['event_name'],
            $log_entry['timestamp'],
            $log_entry['user_login'],
            $log_entry['user_id'],
            $log_entry['user_ip'],
            $log_entry['user_agent'],
            $log_entry['request_uri'],
            wp_json_encode($log_entry['context'])
        );

        // Only send if we haven't sent an alert in the last hour (prevent spam)
        $last_alert = get_transient('orbitools_last_security_alert');
        if (!$last_alert) {
            wp_mail($admin_email, $subject, $message);
            set_transient('orbitools_last_security_alert', time(), HOUR_IN_SECONDS);
        }
    }
}
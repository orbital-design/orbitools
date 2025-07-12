<?php
/**
 * Simple OptionsKit test to debug the issue
 */

// Only run on plugins_loaded
add_action('plugins_loaded', function() {
    static $test_run = false;
    if ($test_run) {
        return;
    }
    $test_run = true;
    
    error_log('TEST: Simple OptionsKit test starting');
    
    // Include OptionsKit
    if (file_exists(ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php')) {
        require_once ORBITAL_EDITOR_SUITE_PATH . 'vendor/autoload.php';
    }
    
    // Test simple OptionsKit initialization
    $test_kit = new \TDP\OptionsKit('test-orbital');
    $test_kit->set_page_title('Test Orbital');
    
    error_log('TEST: OptionsKit initialized successfully');
}, 5);
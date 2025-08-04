<?php

namespace Orbitools;

use Orbitools\Abstracts\Module_Base;

/**
 * Test Module
 * 
 * Simple test module to verify the infrastructure works
 * This file will be deleted after testing
 * 
 * @package Orbitools
 * @since 1.0.0
 */
class Test_Module extends Module_Base
{
    protected const VERSION = '1.0.0';

    public function get_slug(): string
    {
        return 'test-module';
    }

    public function get_name(): string
    {
        return __('Test Module', 'orbitools');
    }

    public function get_description(): string
    {
        return __('Test module to verify infrastructure works', 'orbitools');
    }

    public function get_default_settings(): array
    {
        return [
            'test-module_enabled' => true,
            'test-module_test_setting' => 'default_value'
        ];
    }

    public function init(): void
    {
        // Test that settings work
        $test_value = $this->get_setting('test_setting', 'fallback');
        
        // Test that we can update settings
        $this->update_setting('test_setting', 'updated_value');
        
        // Test asset enqueueing (won't actually enqueue since files don't exist)
        $this->enqueue_frontend_style('test-style', 'test.css');
        
        // Add a simple admin notice to confirm it's working
        if (is_admin()) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>OrbiTools Test Module Infrastructure Working!</p></div>';
            });
        }
    }
}
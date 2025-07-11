<?php
/**
 * Debug Vue.js Admin
 * 
 * Simple debug file to test if Vue.js is working
 */

// Add this to your wp-config.php if not already there:
// define('WP_DEBUG', true);

add_action('admin_menu', 'debug_vue_admin_menu');
add_action('admin_enqueue_scripts', 'debug_vue_admin_scripts');

function debug_vue_admin_menu() {
    add_submenu_page(
        'orbital-editor-suite',
        'Vue Debug Test',
        'Vue Debug Test', 
        'manage_options',
        'vue-debug-test',
        'debug_vue_admin_page'
    );
}

function debug_vue_admin_scripts($hook) {
    if (strpos($hook, 'vue-debug-test') === false) {
        return;
    }
    
    // Enqueue Vue.js from CDN
    wp_enqueue_script('vue-js', 'https://unpkg.com/vue@3/dist/vue.global.js', array(), '3.0.0', true);
    
    // Inline script for testing
    wp_add_inline_script('vue-js', '
        document.addEventListener("DOMContentLoaded", function() {
            const { createApp } = Vue;
            
            createApp({
                data() {
                    return {
                        message: "Hello from Vue.js!",
                        count: 0,
                        isWorking: true
                    };
                },
                methods: {
                    increment() {
                        this.count++;
                    }
                }
            }).mount("#vue-debug-app");
        });
    ');
}

function debug_vue_admin_page() {
    ?>
    <div class="wrap">
        <h1>Vue.js Debug Test</h1>
        <p>This page tests if Vue.js is working properly.</p>
        
        <div id="vue-debug-app">
            <div style="background: #f0f0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
                <h2>{{ message }}</h2>
                <p>Count: {{ count }}</p>
                <button @click="increment" class="button button-primary">Click me!</button>
                <p v-if="isWorking" style="color: green; font-weight: bold;">✅ Vue.js is working!</p>
            </div>
        </div>
        
        <hr>
        
        <h2>Debug Information</h2>
        <ul>
            <li><strong>WP_DEBUG:</strong> <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></li>
            <li><strong>Current Hook:</strong> <?php echo isset($_GET['page']) ? $_GET['page'] : 'Not set'; ?></li>
            <li><strong>Vue.js CDN:</strong> https://unpkg.com/vue@3/dist/vue.global.js</li>
            <li><strong>Expected Menu Location:</strong> Orbital Editor Suite → Vue Debug Test</li>
        </ul>
        
        <h3>Check Browser Console</h3>
        <p>Open your browser's developer tools (F12) and check the Console tab for any JavaScript errors.</p>
    </div>
    <?php
}
?>
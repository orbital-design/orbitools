# AdminKit Instance Registry

The Instance Registry system allows AdminKit to properly handle multiple instances and detect which instance should control the current page. This is essential when you have AdminKit pages as children of other AdminKit pages or multiple AdminKit instances in the same WordPress installation.

## Key Features

- **Single Instance per Slug**: Ensures only one AdminKit instance exists per unique slug
- **Accurate Page Detection**: Determines which AdminKit instance owns the current page
- **Nested Page Support**: Handles AdminKit pages as children of other AdminKit pages
- **Backward Compatibility**: Falls back to original detection methods if registry is unavailable

## Usage Examples

### Basic Detection

```php
// Check if current page is owned by any AdminKit instance
if (AdminKit_is_page()) {
    echo "This is an AdminKit page";
}

// Check if current page is owned by a specific AdminKit instance
if (AdminKit_is_instance_page('orbitools')) {
    echo "This is the OrbiTools AdminKit page";
}

// Get the slug of the AdminKit instance that owns this page
$owner = AdminKit_get_page_owner();
if ($owner) {
    echo "Page owned by: " . $owner;
}
```

### Getting Active Instance

```php
// Get the active AdminKit instance for the current page
$active_kit = AdminKit_get_active();
if ($active_kit) {
    echo "Active AdminKit: " . $active_kit->get_slug();
}

// Get all registered AdminKit instances
$all_instances = AdminKit_get_all_instances();
foreach ($all_instances as $slug => $instance) {
    echo "Registered: " . $slug;
}
```

### Using the Registry Class Directly

```php
use Orbitools\AdminKit\Instance_Registry;

// Check if instance exists
if (Instance_Registry::instance_exists('my-plugin')) {
    $instance = Instance_Registry::get_instance('my-plugin');
}

// Get page information
$page_info = Instance_Registry::get_page_info();
echo "Owner: " . $page_info['owner'];
echo "Is Child: " . ($page_info['is_child'] ? 'Yes' : 'No');

// Check if current page is a child of an AdminKit page
if (Instance_Registry::is_child_page()) {
    echo "This page is a child of an AdminKit page";
}

// Check for specific parent
if (Instance_Registry::is_child_page('orbitools')) {
    echo "This page is a child of the OrbiTools AdminKit page";
}
```

### Conditional Rendering Based on Page Ownership

```php
// In your plugin's admin page
class My_Plugin_Admin {
    
    public function render_admin_notice() {
        // Only show on pages owned by this AdminKit instance
        if (AdminKit_is_instance_page('my-plugin')) {
            echo '<div class="notice notice-info"><p>My Plugin Admin Notice</p></div>';
        }
    }
    
    public function enqueue_scripts($hook) {
        // Only enqueue on our AdminKit pages
        if (AdminKit_is_instance_page('my-plugin')) {
            wp_enqueue_script('my-plugin-admin', 'path/to/script.js');
        }
    }
    
    public function customize_for_child_pages() {
        // Different behavior for child pages
        if (AdminKit_is_instance_page('my-plugin')) {
            // Full AdminKit page - show everything
            $this->render_full_interface();
        } elseif (Instance_Registry::is_child_page('my-plugin')) {
            // Child page - minimal interface
            $this->render_minimal_interface();
        }
    }
}
```

### Multiple AdminKit Instances

```php
// Register multiple AdminKit instances
$main_kit = AdminKit('my-plugin');
$secondary_kit = AdminKit('my-plugin-tools');

// Each will be automatically registered and managed
// The registry ensures proper page ownership detection

// Check which one is active
$active = AdminKit_get_active();
if ($active) {
    switch ($active->get_slug()) {
        case 'my-plugin':
            // Main plugin page logic
            break;
        case 'my-plugin-tools':
            // Tools page logic
            break;
    }
}
```

### Debug Information

```php
// Get debug information about the registry state
$debug = Instance_Registry::get_debug_info();
var_dump($debug);

// Output:
// array(
//     'registered_instances' => ['orbitools', 'my-plugin'],
//     'current_page_info' => [
//         'owner' => 'orbitools',
//         'is_child' => false,
//         'parent' => null,
//         'screen_id' => 'toplevel_page_orbitools'
//     ],
//     'ownership_cache' => [
//         'toplevel_page_orbitools' => 'orbitools'
//     ]
// )
```

## Page Ownership Detection Algorithm

The registry uses a prioritized approach to determine page ownership:

1. **Exact Matches** (Highest Priority)
   - `toplevel_page_{slug}`
   - `{slug}_page_{slug}`

2. **Subpage Matches**
   - `{slug}_page_*`

3. **Partial Matches** (Lowest Priority)
   - Any screen ID containing the slug

The algorithm checks longer slugs first to ensure more specific matches take precedence.

## Hooks and Filters

The Instance Registry provides several hooks for extensibility:

```php
// Fires when an instance is registered
add_action('adminkit_instance_registered', function($slug, $instance) {
    // Custom logic when instance is registered
});

// Fires when an instance is removed
add_action('adminkit_instance_removed', function($slug) {
    // Custom logic when instance is removed
});
```

## Backward Compatibility

The system maintains full backward compatibility. If the Instance Registry is not available, AdminKit falls back to the original detection methods. This ensures existing code continues to work without modification.

## Performance

- **Caching**: Page ownership detection is cached per screen ID
- **Lazy Loading**: Registry only loads when AdminKit instances are created
- **Efficient Lookups**: Uses array keys for O(1) instance retrieval
- **Memory Efficient**: Stores only references to instances, not duplicates

## Best Practices

1. **Always use the convenience functions** (`AdminKit_is_page()`, etc.) rather than accessing the registry directly
2. **Check for specific instances** when you need precise control
3. **Use child page detection** to provide appropriate interfaces for nested pages
4. **Clear cache** if you modify instances programmatically: `Instance_Registry::clear_cache()`

This registry system provides a robust foundation for managing multiple AdminKit instances and ensures proper behavior even in complex WordPress admin environments.
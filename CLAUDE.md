# Orbital Editor Suite - Developer Guide

## Debug Logging System

The plugin implements a global debug logging system that can be toggled on/off from the admin interface.

### Setup

**PHP Side**: Global debug setting stored in `orbital_editor_suite_options['settings']['enable_debug']`

**JavaScript Side**: Settings localized via `window.orbitalEditorSuiteGlobal.settings.enable_debug`

### Debug Functions

Use these functions instead of `console.log()` in JavaScript:

```javascript
function debugLog(...args) {
    const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
    if (globalSettings.enable_debug) {
        console.log('[Module Name Debug]', ...args);
    }
}

function debugWarn(...args) {
    const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
    if (globalSettings.enable_debug) {
        console.warn('[Module Name Debug]', ...args);
    }
}

function debugError(...args) {
    const globalSettings = window.orbitalEditorSuiteGlobal?.settings || {};
    if (globalSettings.enable_debug) {
        console.error('[Module Name Debug]', ...args);
    }
}
```

### Usage Pattern

- Replace `console.log()` with `debugLog()`
- Replace `console.warn()` with `debugWarn()` 
- Replace `console.error()` with `debugError()`
- All debug messages should be prefixed with `[Module Name Debug]`

### Localization (Required for each module)

Add this to your module's `enqueue_editor_assets()` method:

```php
// Localize global settings for debug logging
$global_options = get_option('orbital_editor_suite_options', array());
$global_settings = isset($global_options['settings']) ? $global_options['settings'] : array();

wp_localize_script(
    'your-script-handle',
    'orbitalEditorSuiteGlobal',
    array(
        'settings' => $global_settings
    )
);
```

### User Control

Users can enable/disable debug logging at:
**Settings → Orbital Editor → General Settings → "Enable Debug Logging" checkbox**

When enabled, debug messages appear in browser console. When disabled, console stays clean for production use.

## Architecture

### Module Admin System

The plugin uses a base `Module_Admin` class that modules can extend for consistent admin interfaces:

```php
class Your_Module_Admin extends Module_Admin {
    protected function get_admin_fields() {
        return array(
            'settings' => array(
                'title' => __('Module Settings', 'orbital-editor-suite'),
                'fields' => array(
                    'enabled' => array(
                        'type' => 'checkbox',
                        'label' => __('Enable Module', 'orbital-editor-suite')
                    )
                )
            )
        );
    }
}
```

### Available Field Types

- `checkbox` - Toggle switches with modern styling
- `text` - Text input fields
- `textarea` - Multi-line text areas
- `select` - Dropdown selects
- `multi_checkbox` - Checkbox grids with visual feedback

## Commands

### Lint & Type Check

Run these commands after making changes:

```bash
# Add lint/typecheck commands here when available
```

## Best Practices

1. Always use debug functions instead of direct console logging
2. Extend `Module_Admin` for consistent admin interfaces
3. Follow WordPress coding standards and security practices
4. Test with debug logging both enabled and disabled
5. Use proper nonces and capability checks for admin actions
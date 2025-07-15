# Typography Presets Module

A powerful WordPress block editor enhancement that replaces core typography controls with a preset-based system using CSS classes. This module integrates seamlessly with theme.json for developer-friendly configuration.

## 🎯 Overview

The Typography Presets module transforms WordPress block editor typography from individual controls (font size, line height, etc.) into organized, consistent presets. Instead of inline styles, it outputs semantic CSS classes like `has-type-preset-termina-16-400`.

## 🚀 Key Features

- **theme.json Integration**: Configure presets directly in your theme's theme.json file
- **CSS Classes**: No inline styles - uses WordPress-style CSS classes
- **Group Organization**: Organize presets into logical groups (Headings, Body, etc.)
- **Smart Labels**: Auto-generates readable labels like "Termina • 16px • Regular"
- **Block Integration**: Seamlessly replaces WordPress core typography controls
- **Flexible Targeting**: Choose which blocks get typography preset controls
- **Modular Architecture**: Clean separation of concerns for maintainability

## 🛠 Configuration

### Admin Interface Settings

Configure the module through **Settings → Orbitools → Modules → Typography Presets**:

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Typography Presets** | Activates the module | `false` |
| **Show Groups in Dropdown** | Organizes presets into groups | `false` |
| **Output Preset CSS** | Auto-generates CSS for presets | `true` |
| **Allowed Blocks** | Select which blocks support presets | Core blocks |

### theme.json Configuration

Add typography presets to your theme's `theme.json` file:

```json
{
  "version": 2,
  "settings": {
    "custom": {
      "orbital": {
        "plugins": {
          "oes": {
            "Typography_Presets": {
              "items": {
                "termina-16-400": {
                  "label": "Termina • 16px • Regular",
                  "group": "headings",
                  "description": "Primary heading font",
                  "properties": {
                    "font-family": "var(--wp--preset--font-family--termina)",
                    "font-weight": 400,
                    "font-size": "16px",
                    "line-height": "20px",
                    "letter-spacing": "0"
                  }
                },
                "termina-24-500": {
                  "label": "Termina • 24px • Medium",
                  "group": "headings",
                  "description": "Large heading font",
                  "properties": {
                    "font-family": "var(--wp--preset--font-family--termina)",
                    "font-weight": 500,
                    "font-size": "24px",
                    "line-height": "28px",
                    "letter-spacing": "0"
                  }
                },
                "inter-14-400": {
                  "label": "Inter • 14px • Regular",
                  "group": "body",
                  "description": "Body text font",
                  "properties": {
                    "font-family": "var(--wp--preset--font-family--inter)",
                    "font-weight": 400,
                    "font-size": "14px",
                    "line-height": "1.5",
                    "letter-spacing": "0"
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```

## 📋 Preset Structure

### Required Properties

Each preset must include:

| Property | Type | Required | Description |
|----------|------|----------|-------------|
| `label` | string | Yes | Display name in editor |
| `properties` | object | Yes | CSS properties |

### Optional Properties

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `description` | string | Helper text for users | `"Primary heading font"` |
| `group` | string | Group for organization | `"headings"` |

### CSS Properties

All standard CSS typography properties are supported:

```json
"properties": {
  "font-family": "var(--wp--preset--font-family--termina)",
  "font-weight": 400,
  "font-size": "16px",
  "line-height": "1.5",
  "letter-spacing": "0.02em",
  "text-transform": "uppercase",
  "margin-bottom": "1rem"
}
```

## 🎨 CSS Output

The module generates semantic CSS classes that are automatically injected into both the frontend and editor.

### Block Classes

When a preset is applied to a block:
- `has-type-preset` - Base class indicating preset is applied
- `has-type-preset-{preset-id}` - Specific preset class

### Generated CSS

Example output for `termina-16-400` preset:

```css
/* Typography Preset: Termina • 16px • Regular */
.has-type-preset-termina-16-400 {
    font-family: var(--wp--preset--font-family--termina);
    font-weight: 400;
    font-size: 16px;
    line-height: 20px;
    letter-spacing: 0;
}
```

## 🔧 Block Integration

### Supported Blocks

Default supported blocks:
- `core/paragraph`
- `core/heading`
- `core/list`
- `core/quote`
- `core/button`
- `core/group`
- `core/column`
- `core/cover`

### Customizing Block Support

Configure which blocks support typography presets through the **Allowed Blocks** setting in the admin interface.

### Editor Integration

The module:
1. **Removes** WordPress core typography controls when enabled
2. **Adds** Typography Presets dropdown in the Block Inspector
3. **Maintains** WordPress UI patterns and design consistency
4. **Provides** live preview of preset styles in the editor

## 🏗 Architecture

### File Structure

```
Typography_Presets/
├── Typography_Presets.php           # Main coordinator class
├── Admin/
│   ├── Admin.php                   # Admin integration and module registration
│   ├── Settings.php                # Settings definitions
│   └── Settings_Helper.php         # Settings normalization utilities
├── Core/
│   ├── Preset_Manager.php          # Preset loading and management
│   └── CSS_Generator.php           # CSS generation and output
├── Frontend/
│   ├── Block_Editor.php            # Block editor integration
│   └── Assets.php                  # Asset management
├── js/
│   ├── attribute-registration.js   # Block attribute registration
│   ├── editor-controls.js          # Editor UI controls
│   ├── class-application.js        # CSS class application
│   └── core-controls-removal.js    # Core control removal
└── README.md                       # This documentation
```

### Key Components

**Main Coordinator (`Typography_Presets.php`)**
- Orchestrates module initialization
- Manages component dependencies
- Handles module enable/disable state

**Admin Components**
- `Admin.php` - Module registration and admin integration
- `Settings.php` - Centralized settings definitions
- `Settings_Helper.php` - Settings normalization and validation

**Core Components**
- `Preset_Manager.php` - Loads and parses presets from theme.json
- `CSS_Generator.php` - Generates and outputs CSS for presets

**Frontend Components**
- `Block_Editor.php` - Handles block editor asset enqueuing and data localization
- `Assets.php` - Manages frontend asset loading

**JavaScript Components**
- `attribute-registration.js` - Registers preset attributes on blocks
- `editor-controls.js` - Adds preset dropdown to block inspector
- `class-application.js` - Applies CSS classes to blocks
- `core-controls-removal.js` - Removes core typography controls

## 🎯 Best Practices

### Naming Conventions

**Preset IDs:** Use kebab-case with descriptive patterns
```
termina-16-400
montserrat-24-500
inter-14-600
heading-large
body-small
```

**Groups:** Use semantic, lowercase group names
```
headings, body, utility, navigation, buttons
```

### Typography System Design

1. **Consistent Scale** - Use a systematic size scale (12, 14, 16, 20, 24, 32, 48)
2. **Limited Weights** - Stick to 3-4 font weights maximum
3. **Semantic Groups** - Organize by usage context, not just size
4. **Clear Labels** - Use descriptive labels that indicate usage
5. **CSS Variables** - Use WordPress font family variables for consistency

### Performance Considerations

- **CSS Classes vs Inline** - Classes are more performant and cacheable
- **Limited Presets** - Don't create excessive presets (aim for 10-20 total)
- **Group Organization** - Use groups to make large preset lists manageable
- **Caching** - CSS is cached with WordPress transients for performance

## 🔍 Troubleshooting

### Common Issues

**Presets not showing in editor:**
- Check if Typography Presets module is enabled in Orbitools settings
- Verify blocks are in the "Allowed Blocks" list
- Ensure theme.json file exists and has valid JSON syntax
- Check browser console for JavaScript errors

**theme.json not loading:**
- Verify JSON syntax is valid using a JSON validator
- Check file path: `{active-theme}/theme.json`
- Ensure WordPress version supports theme.json (5.8+)
- Clear any caching plugins

**CSS classes not applying:**
- Check if "Output Preset CSS" is enabled
- Verify CSS is being generated (check page source for `orbitools-typography-presets-css`)
- Clear any caching plugins
- Check that preset IDs match between theme.json and generated CSS

**Settings not saving:**
- Verify proper permissions for admin users
- Check for plugin conflicts
- Look for JavaScript errors in browser console
- Ensure nonce verification is passing

### Debug Mode

Enable debug logging in the main Orbitools settings to see detailed information about:
- Preset loading from theme.json
- CSS generation process
- JavaScript module loading
- Settings validation

## 🔮 Extending the Module

### Adding Custom Properties

The module supports any CSS property. Add custom properties to the `properties` object:

```json
"properties": {
  "font-family": "Custom Font",
  "font-variation-settings": "'wght' 400",
  "text-shadow": "0 1px 2px rgba(0,0,0,0.1)",
  "color": "var(--wp--preset--color--primary)"
}
```

### Custom Block Support

To add preset support to custom blocks, modify the `allowed_blocks` setting or extend the JavaScript filters.

### Programmatic Access

Access presets in PHP:
```php
// Get the main module instance
$typography_module = new \Orbitools\Modules\Typography_Presets\Typography_Presets();

// Get preset manager
$preset_manager = $typography_module->get_preset_manager();

// Get all presets
$presets = $preset_manager->get_presets();

// Get specific preset
$preset = $preset_manager->get_preset('termina-16-400');
```

### Hooks and Filters

The module provides several hooks for customization:

```php
// Filter presets before they're processed
add_filter('orbitools_typography_presets', function($presets) {
    // Modify presets array
    return $presets;
});

// Filter CSS output
add_filter('orbitools_typography_css', function($css) {
    // Modify generated CSS
    return $css;
});
```

## 📚 Related Documentation

- [WordPress theme.json documentation](https://developer.wordpress.org/themes/theme-json/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [CSS Typography Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Fonts)
- [Orbitools Main Documentation](../../README.md)

## 🤝 Contributing

When contributing to the Typography Presets module:

1. Follow WordPress coding standards
2. Test both admin and theme.json configuration methods
3. Ensure backward compatibility
4. Document any new features or settings
5. Test with various block types and themes
6. Maintain the modular architecture separation
7. Add appropriate unit tests for new functionality

## 📄 License

This module is part of Orbitools and follows the same GPL v2+ license.
# Typography Presets Module

A powerful WordPress block editor enhancement that replaces core typography controls with a preset-based system using CSS classes. Choose between user-friendly admin management or developer-focused theme.json configuration.

## 🎯 Overview

The Typography Presets module transforms WordPress block editor typography from individual controls (font size, line height, etc.) into organized, consistent presets. Instead of inline styles, it outputs semantic CSS classes like `has-type-preset-termina-16-400`.

## 🚀 Key Features

- **Dual Configuration**: Admin interface for users, theme.json for developers
- **CSS Classes**: No inline styles - uses WordPress-style CSS classes
- **Group Organization**: Organize presets into logical groups (Headings, Body, etc.)
- **Smart Labels**: Auto-generates readable labels like "Termina • 16px • Regular"
- **Block Integration**: Seamlessly replaces WordPress core typography controls
- **Flexible Targeting**: Choose which blocks get typography preset controls

## 🛠 Configuration Methods

### Method 1: Admin Interface (User-Friendly)

**Best for:** Content managers, non-technical users, quick setup

1. Go to **Orbital Editor → Typography Presets**
2. Set **Preset Generation Method** to "Admin Interface"
3. Configure settings and create presets using the visual interface
4. Presets are stored in WordPress database

**Features:**
- Visual preset creation form
- Real-time preview
- Point-and-click configuration
- No coding required

### Method 2: theme.json (Developer/Advanced)

**Best for:** Developers, version control, complex typography systems

1. Set **Preset Generation Method** to "theme.json"
2. Add configuration to your theme's `theme.json` file
3. Settings in theme.json override admin interface
4. Presets are version-controlled with your theme

## 📋 theme.json Configuration

### Basic Structure

```json
{
  "version": 2,
  "plugins": {
    "oes": {
      "Typography_Presets": {
        "settings": {
          "replace_core_controls": true,
          "show_groups": true,
          "output_preset_css": true
        },
        "groups": {
          "headings": { "title": "Headings & Standouts" },
          "body": { "title": "Body Text" }
        },
        "items": {
          "termina-16-400": {
            "label": "Termina • 16px • Regular",
            "group": "headings",
            "properties": {
              "font-family": "Termina",
              "font-weight": 400,
              "font-size": "16px",
              "line-height": "20px",
              "letter-spacing": "0"
            }
          }
        }
      }
    }
  }
}
```

### Settings Options

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `replace_core_controls` | boolean | `true` | Remove WordPress core typography controls |
| `show_groups` | boolean | `true` | Organize presets into groups in dropdown |
| `output_preset_css` | boolean | `true` | Auto-generate CSS for presets |

### Groups Structure

Define groups once, reference everywhere:

```json
"groups": {
  "headings": { "title": "Headings & Standouts" },
  "body": { "title": "Body Text" },
  "utility": { "title": "Utility Styles" }
}
```

### Items Structure

Each preset item can include:

| Property | Required | Description | Example |
|----------|----------|-------------|---------|
| `label` | No | Display name in editor | `"Termina • 16px • Regular"` |
| `description` | No | Helper text | `"Medium heading for cards"` |
| `group` | No | Group ID for organization | `"headings"` |
| `properties` | Yes | CSS properties object | See below |

### CSS Properties

All standard CSS typography properties are supported:

```json
"properties": {
  "font-family": "Termina",
  "font-weight": 400,
  "font-size": "16px",
  "line-height": "1.5",
  "letter-spacing": "0",
  "text-transform": "uppercase",
  "margin-bottom": "1rem"
}
```

## 🎨 CSS Output

The module generates semantic CSS classes:

### Block Classes

When a preset is applied to a block, it receives:
- `has-type-preset` - Base class indicating preset is applied
- `has-type-preset-{preset-id}` - Specific preset class

### Generated CSS

Example output for `termina-16-400` preset:

```css
.has-type-preset-termina-16-400 {
  font-family: Termina;
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

### Customizing Block Support

In admin interface:
1. Go to **Allowed Blocks** setting
2. Check/uncheck blocks to enable/disable

In theme.json, modify the module settings to include `allowed_blocks` array.

### Typography Controls Replacement

When enabled, the module:
1. **Removes** WordPress core typography controls (font size, line height, etc.)
2. **Adds** Typography Presets dropdown in their place
3. **Maintains** WordPress UI patterns and design

## 🏗 Architecture

### File Structure

```
typography-presets/
├── class-typography-presets.php          # Main module class
├── class-typography-presets-admin.php    # Admin interface
├── README.md                             # This documentation
└── assets/
    ├── js/typography-presets.js          # Block editor integration
    └── css/typography-presets.css        # Admin styles
```

### Key Classes

- **`Typography_Presets`** - Main module class, handles loading and parsing
- **`Typography_Presets_Admin`** - Admin interface extending base Module_Admin
- **Module system** - Pluggable architecture for easy extension

### WordPress Integration

- **Block Editor** - Hooks into `blocks.registerBlockType` and `editor.BlockEdit`
- **Settings API** - Uses WordPress Settings API for configuration
- **Options API** - Stores admin-configured presets in wp_options
- **theme.json** - Reads configuration from active theme

## 🎯 Best Practices

### Naming Conventions

**Preset IDs:** Use kebab-case with pattern `{font}-{size}-{weight}`
```
termina-16-400
montserrat-24-500
inter-14-600
```

**Groups:** Use semantic, lowercase group names
```
headings, body, utility, navigation
```

### Typography System Design

1. **Consistent Scale** - Use a systematic size scale (12, 14, 16, 20, 24, 32, 48)
2. **Limited Weights** - Stick to 3-4 font weights maximum
3. **Semantic Groups** - Organize by usage context, not just size
4. **Clear Labels** - Use descriptive labels that indicate usage

### Performance Considerations

- **CSS Classes vs Inline** - Classes are more performant and cacheable
- **Limited Presets** - Don't create excessive presets (aim for 10-20 total)
- **Group Organization** - Use groups to make large preset lists manageable

## 🔍 Troubleshooting

### Common Issues

**Presets not showing in editor:**
- Check if Typography Presets module is enabled in main settings
- Verify blocks are in the "Allowed Blocks" list
- Ensure JavaScript dependencies are loaded correctly

**theme.json not working:**
- Verify JSON syntax is valid
- Check file path: `{theme-directory}/theme.json`
- Ensure WordPress version supports theme.json (5.8+)

**CSS classes not applying:**
- Check if "Output Preset CSS" is enabled
- Verify CSS is being generated (check page source)
- Clear any caching plugins

### Debug Mode

Enable debug logging in main plugin settings to see detailed console output during preset loading and application.

## 🔮 Extending the Module

### Adding Custom Properties

The module supports any CSS property. Add custom properties to the `properties` object:

```json
"properties": {
  "font-family": "Custom Font",
  "font-variation-settings": "'wght' 400",
  "text-shadow": "0 1px 2px rgba(0,0,0,0.1)"
}
```

### Custom Block Support

To add preset support to custom blocks, add them to the `allowed_blocks` setting or modify the JavaScript filters.

### Programmatic Access

Access presets in PHP:
```php
$typography_module = new \Orbital\Editor_Suite\Modules\Typography_Presets\Typography_Presets();
$presets = $typography_module->get_presets();
```

## 📚 Related Documentation

- [WordPress theme.json documentation](https://developer.wordpress.org/themes/theme-json/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [CSS Typography Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Fonts)

## 🤝 Contributing

When contributing to the Typography Presets module:

1. Follow WordPress coding standards
2. Test both admin and theme.json configuration methods
3. Ensure backward compatibility
4. Document any new features or settings
5. Test with various block types and themes

## 📄 License

This module is part of the Orbital Editor Suite and follows the same GPL v2+ license.
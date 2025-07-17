# Layout Guides Module

Development tool that adds visual layout guides and debugging helpers to assist with theme development and debugging.

## Features

- **Grid Overlay**: Customizable grid with adjustable columns and gutters
- **Baseline Grid**: Horizontal baseline grid for typography alignment
- **Rulers**: Measurement rulers that follow mouse cursor
- **Element Spacing**: Visual highlighting of element margins and padding on hover
- **Keyboard Shortcuts**: Quick toggle with customizable keyboard shortcuts
- **Admin Bar Toggle**: Convenient toggle button in WordPress admin bar
- **Customizable Appearance**: Adjustable opacity, color, and grid settings

## Settings

### Basic Settings
- **Show Grid**: Toggle grid overlay visibility
- **Show Baseline Grid**: Toggle baseline grid visibility
- **Show Rulers**: Toggle measurement rulers
- **Show Element Spacing**: Toggle element spacing visualization on hover

### Grid Configuration
- **Grid Columns**: Number of columns in the grid (1-24)
- **Grid Gutter**: Space between columns in pixels (0-100px)
- **Baseline Height**: Height of baseline grid lines (8-48px)

### Appearance
- **Guide Opacity**: Transparency level of guides (0.1-1.0)
- **Guide Color**: Color of the layout guides (hex color)

### Interaction
- **Toggle Keyboard Shortcut**: Keyboard shortcut to show/hide guides
  - Ctrl+Shift+G (default)
  - Ctrl+Shift+L
  - Ctrl+Shift+R
  - Alt+Shift+G
  - Alt+Shift+L
- **Admin Bar Toggle**: Show toggle button in WordPress admin bar
- **Frontend Only**: Only show guides on frontend pages (not in admin)

## Usage

1. Enable the Layout Guides module in OrbiTools settings
2. Configure your preferred grid settings and appearance
3. Use the admin bar toggle or keyboard shortcut to show/hide guides
4. Hover over elements to see spacing visualization (if enabled)

## Development

### File Structure
```
Layout_Guides/
├── Layout_Guides.php          # Main module class
├── Admin/
│   ├── Admin.php              # Admin integration
│   ├── Settings.php           # Settings configuration
│   └── Settings_Helper.php    # Settings utilities
├── Core/
│   └── Guide_Renderer.php     # Guide rendering logic
├── Frontend/
│   └── Assets.php             # Asset management
├── css/
│   ├── layout-guides.css      # Main styles
│   └── admin-layout-guides.css # Admin styles
├── js/
│   └── layout-guides.js       # Interactive functionality
└── README.md
```

### CSS Custom Properties

The module uses CSS custom properties for easy customization:

```css
:root {
    --layout-guides-columns: 12;
    --layout-guides-gutter: 20px;
    --layout-guides-baseline: 24px;
    --layout-guides-opacity: 0.3;
    --layout-guides-color: #ff0000;
    --layout-guides-z-index: 9999;
}
```

### JavaScript API

The module exposes a JavaScript API for programmatic control:

```javascript
// Toggle guides
OrbitoolsLayoutGuides.toggleGuides();

// Show guides
OrbitoolsLayoutGuides.showGuides();

// Hide guides
OrbitoolsLayoutGuides.hideGuides();

// Update guides
OrbitoolsLayoutGuides.updateGuides();
```

## Responsive Behavior

The layout guides adapt to different screen sizes:

- **Desktop**: Full grid with configured columns
- **Tablet** (≤782px): Reduced gutter spacing
- **Mobile** (≤600px): Simplified 4-column grid

## Body Classes

The module adds body classes for CSS targeting:

- `orbitools-layout-guides`: Always present when module is enabled
- `orbitools-layout-guides--enabled`: When guides are active
- `orbitools-layout-guides--visible`: When guides are currently visible
- `orbitools-layout-guides--grid`: When grid is enabled
- `orbitools-layout-guides--baseline`: When baseline grid is enabled
- `orbitools-layout-guides--rulers`: When rulers are enabled
- `orbitools-layout-guides--spacing`: When spacing visualization is enabled

## Compatibility

- WordPress 5.0+
- Modern browsers with CSS Grid support
- Works with any theme
- Compatible with page builders
- Responsive design friendly

## Performance

The module is designed to be lightweight and performant:

- CSS uses efficient grid layouts and gradients
- JavaScript is minimal and event-driven
- Only loads when module is enabled
- Debounced resize handling
- Efficient DOM manipulation

## Troubleshooting

### Guides Not Showing
- Check that the module is enabled
- Verify user has appropriate permissions
- Check browser console for JavaScript errors
- Ensure CSS is loading properly

### Grid Not Aligning
- Check container max-width settings
- Verify grid column and gutter values
- Check for conflicting CSS styles
- Test with different themes

### Performance Issues
- Reduce guide opacity for better performance
- Disable spacing visualization if not needed
- Use keyboard shortcuts instead of admin bar toggle
- Test on different devices and browsers
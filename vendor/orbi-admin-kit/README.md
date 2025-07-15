# Orbi AdminKit

A lightweight, standalone admin page framework for WordPress plugins. Provides a clean API for building admin pages with tabs, sections, and fields using WordPress hooks and filters.

## Features

- **Clean API**: Simple, intuitive methods for setting up admin pages
- **Flexible Structure**: Support for tabs, sections, and various field types
- **Hook-based**: Extensible through WordPress hooks and filters
- **BEM CSS**: Clean, maintainable CSS methodology
- **Accessibility**: Built with accessibility in mind
- **Responsive**: Works on all screen sizes

## Basic Usage

```php
// Initialize the framework
$admin_kit = orbi_admin_kit('my-plugin');

// Configure the page
$admin_kit->set_page_title('My Plugin Settings');
$admin_kit->set_page_description('Configure your plugin settings.');
$admin_kit->set_page_header_image('path/to/logo.svg');
$admin_kit->set_page_header_bg_color('#32A3E2'); // Optional: defaults to Orbital blue

// Configure menu
$admin_kit->set_menu_config(array(
    'parent'     => 'options-general.php',
    'page_title' => 'My Plugin',
    'menu_title' => 'My Plugin',
    'capability' => 'manage_options',
));
```

## Header Customization

### Header Image
Set a logo or image to display in the header:

```php
$admin_kit->set_page_header_image('https://example.com/logo.svg');
$admin_kit->set_page_header_image(PLUGIN_URL . 'assets/images/logo.png');
```

**Default**: The Orbi logo is included by default. Set to empty string to remove: `$admin_kit->set_page_header_image('');`

### Header Background Color
Customize the header background color using any valid CSS color value:

```php
// Hex colors
$admin_kit->set_page_header_bg_color('#32A3E2');

// RGB/RGBA
$admin_kit->set_page_header_bg_color('rgb(50, 163, 226)');
$admin_kit->set_page_header_bg_color('rgba(50, 163, 226, 0.8)');

// HSL
$admin_kit->set_page_header_bg_color('hsl(204, 76%, 54%)');

// Named colors
$admin_kit->set_page_header_bg_color('blue');

// CSS variables
$admin_kit->set_page_header_bg_color('var(--primary-color)');

// Gradients
$admin_kit->set_page_header_bg_color('linear-gradient(90deg, #32A3E2, #1e5a8a)');
```

**Default**: The header background color defaults to Orbital blue (`#32A3E2`) if not specified.

## Admin Structure

Define your admin page structure using the `{slug}_admin_structure` filter:

```php
add_filter('my_plugin_admin_structure', function($structure) {
    return array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'display_mode' => 'cards',
            'sections' => array(
                'overview' => 'Overview',
                'stats' => 'Statistics',
            ),
        ),
        'settings' => array(
            'title' => 'Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'general' => 'General',
                'advanced' => 'Advanced',
            ),
        ),
    );
});
```

## Field Configuration

Add fields using the `{slug}_settings` filter:

```php
add_filter('my_plugin_settings', function($settings) {
    return array(
        'dashboard' => array(
            array(
                'id'      => 'site_title',
                'name'    => 'Site Title',
                'desc'    => 'Enter your site title',
                'type'    => 'text',
                'section' => 'overview',
            ),
        ),
        'settings' => array(
            array(
                'id'      => 'enable_feature',
                'name'    => 'Enable Feature',
                'desc'    => 'Check to enable this feature',
                'type'    => 'checkbox',
                'section' => 'general',
            ),
        ),
    );
});
```

## Available Field Types

- `text` - Single line text input
- `textarea` - Multi-line text input
- `checkbox` - Checkbox input (single or multiple)
- `radio` - Radio button input
- `select` - Dropdown select
- `number` - Number input
- `email` - Email input
- `url` - URL input
- `html` - Custom HTML content

## Hooks and Filters

### Filters
- `{slug}_admin_structure` - Define admin page structure
- `{slug}_settings` - Define field configuration
- `{slug}_labels` - Customize UI labels
- `{slug}_menu_config` - Override menu configuration
- `{slug}_pre_save_settings` - Modify settings before saving
- `{slug}_sanitize_setting` - Custom field sanitization

### Actions
- `{slug}_before_header` - Before header content
- `{slug}_render_header` - Custom header content
- `{slug}_after_header` - After header content
- `{slug}_after_nav` - After navigation
- `{slug}_after_notices` - After notices
- `{slug}_after_tabs` - After tabs
- `{slug}_after_content` - After main content
- `{slug}_render_footer` - Custom footer content
- `{slug}_after_footer` - After footer
- `{slug}_enqueue_assets` - Enqueue additional assets
- `{slug}_post_save_settings` - After settings are saved

## CSS Classes

The framework uses BEM methodology for CSS classes:

### Header
- `.orbi-admin__header` - Header container
- `.orbi-admin__header-content` - Header content wrapper
- `.orbi-admin__header-image` - Header image container
- `.orbi-admin__header-img` - Header image element
- `.orbi-admin__header-text` - Header text container
- `.orbi-admin__title` - Page title
- `.orbi-admin__description` - Page description

### Navigation
- `.orbi-admin__nav` - Navigation container
- `.orbi-admin__breadcrumbs` - Breadcrumb navigation
- `.orbi-admin__tabs` - Tab navigation
- `.orbi-admin__tab-link` - Individual tab link
- `.orbi-admin__tab-link--active` - Active tab

### Content
- `.orbi-admin__content` - Main content area
- `.orbi-admin__section-card` - Section card (cards mode)
- `.orbi-admin__section-fields` - Fields container
- `.field` - Individual field wrapper
- `.field--{type}` - Field type modifier

### Notices
- `.orbi-notice` - Notice container
- `.orbi-notice--{type}` - Notice type modifier (success, error, warning, info)
- `.orbi-notice--dismissible` - Dismissible notice

## Version

Current version: 1.0.0

## License

GPL v2 or later
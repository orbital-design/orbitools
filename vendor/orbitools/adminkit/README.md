# OrbiTools AdminKit

A comprehensive, lightweight admin page framework for WordPress plugins. Provides a clean API for building professional admin pages with tabs, sections, and fields using WordPress hooks and filters.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Quick Start](#quick-start)
- [Core Architecture](#core-architecture)
- [Basic Usage](#basic-usage)
- [Menu Configuration](#menu-configuration)
- [Page Structure](#page-structure)
- [Field System](#field-system)
- [Header Customization](#header-customization)
- [Hooks and Filters](#hooks-and-filters)
- [CSS System](#css-system)
- [JavaScript API](#javascript-api)
- [Security Features](#security-features)
- [Advanced Features](#advanced-features)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Overview

OrbiTools AdminKit is a modern WordPress admin framework that follows best practices and provides extensive customization options. It's built with accessibility, security, and developer experience in mind.

### Key Principles
- **Hook-based Architecture**: Extensible through WordPress hooks and filters
- **BEM CSS Methodology**: Clean, maintainable CSS structure
- **Accessibility First**: WCAG compliance and screen reader support
- **Responsive Design**: Mobile-first approach with adaptive layouts
- **Security Focused**: Built-in CSRF protection and input sanitization

## Features

### Core Features
- **Clean API**: Simple, intuitive methods for setting up admin pages
- **Flexible Structure**: Support for tabs, sections, and various field types
- **Hook-based**: Extensible through WordPress hooks and filters
- **BEM CSS**: Clean, maintainable CSS methodology
- **Accessibility**: Built with accessibility in mind
- **Responsive**: Works on all screen sizes
- **AJAX Support**: Real-time form submission without page reload
- **Deep Linking**: URL-based navigation with shareable links
- **Asset Management**: Automatic CSS/JS enqueuing with field-specific assets

### Advanced Features
- **Custom Field Types**: Register your own field types
- **Template System**: Custom templates for field rendering
- **Notice System**: Built-in success/error notifications
- **Settings Management**: Automatic database storage with sanitization
- **Multi-level Navigation**: Tabs with sub-sections
- **Breadcrumb Navigation**: Visual navigation hierarchy
- **Loading States**: User feedback during AJAX operations

## Quick Start

```php
// 1. Initialize AdminKit with basic configuration
AdminKit('my-plugin')->init(array(
    'title' => 'My Plugin Settings',
    'description' => 'Configure your plugin settings.',
    'menu' => array(
        'menu_type' => 'submenu',
        'parent' => 'options-general.php',
        'capability' => 'manage_options',
    ),
));

// 2. Define page structure
add_filter('my_plugin_adminkit_structure', function($structure) {
    return array(
        'general' => array(
            'title' => 'General Settings',
            'display_mode' => 'cards',
            'sections' => array(
                'basic' => 'Basic Options',
                'advanced' => 'Advanced Options'
            )
        )
    );
});

// 3. Add fields
add_filter('my_plugin_adminkit_fields', function($fields) {
    return array(
        'general' => array(
            array(
                'id' => 'site_title',
                'name' => 'Site Title',
                'desc' => 'Enter your site title',
                'type' => 'text',
                'section' => 'basic',
                'required' => true
            )
        )
    );
});
```

## Core Architecture

### Main Components

#### AdminKit Loader (`adminkit.php`)
- Singleton autoloader with PSR-4 compatibility
- Handles class loading and initialization
- Provides global `AdminKit()` function for easy access

#### Admin_Kit Class (`class-admin-framework.php`)
- Core framework class handling page creation and rendering
- Manages WordPress integration (menu, hooks, assets)
- Provides configuration API and data access methods

#### Field_Registry (`class-field-registry.php`)
- Manages field type registration and instantiation
- Handles field-specific asset enqueuing
- Provides field validation and sanitization

#### Page_Builder (`class-page-builder.php`)
- Coordinates view components for complete page rendering
- Handles component registration and rendering
- Manages page layout and structure

#### View Classes
- **Header_View**: Renders page header with logo and navigation
- **Content_View**: Renders main content area with tabs and sections

### File Structure
```
adminkit/
├── adminkit.php                 # Main loader file
├── classes/
│   ├── class-admin-framework.php    # Core framework
│   ├── class-field-registry.php     # Field management
│   ├── class-page-builder.php       # Page rendering
│   └── views/
│       ├── class-header-view.php    # Header component
│       └── class-content-view.php   # Content component
├── fields/
│   ├── class-field-base.php        # Base field class
│   ├── class-field-text.php        # Text field
│   ├── class-field-textarea.php    # Textarea field
│   ├── class-field-checkbox.php    # Checkbox field
│   ├── class-field-radio.php       # Radio field
│   ├── class-field-select.php      # Select field
│   ├── class-field-number.php      # Number field
│   └── class-field-html.php        # HTML field
├── templates/
│   ├── checkbox/                    # Checkbox templates
│   └── radio/                       # Radio templates
└── assets/
    ├── admin-framework.css          # Main stylesheet
    ├── admin-framework.js           # Main JavaScript
    └── orbi-logo.svg               # Default logo
```

## Basic Usage

### Initialization

```php
// Basic initialization
AdminKit('my-plugin')->init(array(
    'title' => 'My Plugin Settings',
    'description' => 'Configure your plugin settings.',
    'header_image' => PLUGIN_URL . 'assets/logo.svg',
    'header_bg_color' => '#32A3E2',
    'hide_title_description' => false,
    'menu' => array(
        'menu_type' => 'submenu',
        'parent' => 'options-general.php',
        'capability' => 'manage_options',
    ),
));
```

**Note**: You must call `init()` to set up the admin page. The framework no longer auto-initializes.

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `title` | string | 'AdminKit' | Page title |
| `description` | string | 'Extensible modular admin framework by Orbital' | Page description |
| `header_image` | string | Orbi logo | Header image URL |
| `header_bg_color` | string | '#32A3E2' | Header background color |
| `hide_title_description` | boolean | false | Hide title/description visually |
| `menu` | array | See Menu Configuration | Menu settings |

## Menu Configuration

The framework supports both top-level menu pages and submenus:

### Top-Level Menu Page

```php
AdminKit('my-plugin')->init(array(
    'title' => 'My Plugin',
    'menu' => array(
        'menu_type' => 'menu',
        'menu_title' => 'My Plugin',
        'capability' => 'manage_options',
        'icon_url' => 'dashicons-admin-plugins',
        'position' => 25
    )
));
```

### Submenu Page (Default)

```php
AdminKit('my-plugin')->init(array(
    'title' => 'My Plugin Settings',
    'menu' => array(
        'menu_type' => 'submenu',
        'parent' => 'options-general.php',
        'menu_title' => 'My Plugin',
        'capability' => 'manage_options'
    )
));
```

### Menu Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `menu_type` | string | 'submenu' | 'menu' for top-level, 'submenu' for submenu |
| `parent` | string | 'options-general.php' | Parent menu slug (submenu only) |
| `menu_title` | string | 'Settings' | Menu title |
| `capability` | string | 'manage_options' | Required capability |
| `icon_url` | string | Default SVG icon | Menu icon (top-level only) |
| `position` | integer | null | Menu position (top-level only) |

### Default Menu Icon

The framework includes a default SVG icon that's perfect for WordPress admin menus:

```php
// The default icon is automatically set to a professional SVG icon
// You can override it with:
'icon_url' => 'dashicons-admin-plugins', // Dashicon
'icon_url' => 'data:image/svg+xml;base64,PHN2Zy4uLg==', // Custom SVG
'icon_url' => PLUGIN_URL . 'assets/icon.svg', // External file
```

### Common Parent Menu Slugs

- `options-general.php` - Settings
- `tools.php` - Tools
- `themes.php` - Appearance
- `plugins.php` - Plugins
- `users.php` - Users
- `management.php` - Tools
- `edit.php` - Posts
- `edit.php?post_type=page` - Pages

## Page Structure

### Structure Definition

Define your admin page structure using the `{slug}_adminkit_structure` filter:

```php
add_filter('my_plugin_adminkit_structure', function($structure) {
    return array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'display_mode' => 'cards',
            'sections' => array(
                'overview' => 'Overview',
                'stats' => 'Statistics',
                'recent' => 'Recent Activity'
            ),
        ),
        'settings' => array(
            'title' => 'Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'general' => 'General',
                'advanced' => 'Advanced',
                'integrations' => 'Integrations'
            ),
        ),
        'tools' => array(
            'title' => 'Tools',
            'display_mode' => 'cards',
            'sections' => array(
                'import' => 'Import',
                'export' => 'Export'
            ),
        ),
    );
});
```

### Display Modes

#### Cards Mode (`display_mode: 'cards'`)
- Sections are displayed as stacked cards
- Good for dashboards and overview pages
- Each section is visually separated
- Mobile-friendly layout

#### Tabs Mode (`display_mode: 'tabs'`)
- Sections are displayed as sub-tabs
- Good for settings pages with many options
- Only one section visible at a time
- Reduces cognitive load

### Navigation Hierarchy

The framework provides a clear navigation hierarchy:

```
Plugin Name → Tab → Section → Field
```

Example:
```
My Plugin → Settings → General → Enable Feature
```

## Field System

### Available Field Types

#### Text Field
```php
array(
    'id' => 'site_title',
    'name' => 'Site Title',
    'desc' => 'Enter your site title',
    'type' => 'text',
    'section' => 'basic',
    'placeholder' => 'My Awesome Site',
    'min_length' => 3,
    'max_length' => 100,
    'required' => true
)
```

#### Textarea Field
```php
array(
    'id' => 'description',
    'name' => 'Description',
    'desc' => 'Site description',
    'type' => 'textarea',
    'section' => 'basic',
    'rows' => 5,
    'cols' => 50,
    'allow_html' => true,
    'placeholder' => 'Enter description...'
)
```

#### Checkbox Field

**Single Checkbox:**
```php
array(
    'id' => 'enable_feature',
    'name' => 'Enable Feature',
    'desc' => 'Check to enable this feature',
    'type' => 'checkbox',
    'section' => 'general',
    'std' => true
)
```

**Multiple Checkboxes:**
```php
array(
    'id' => 'enabled_features',
    'name' => 'Enabled Features',
    'desc' => 'Select which features to enable',
    'type' => 'checkbox',
    'section' => 'general',
    'options' => array(
        'feature1' => 'Feature One',
        'feature2' => 'Feature Two',
        'feature3' => 'Feature Three'
    ),
    'std' => array('feature1', 'feature2')
)
```

#### Radio Field
```php
array(
    'id' => 'color_scheme',
    'name' => 'Color Scheme',
    'desc' => 'Choose your preferred color scheme',
    'type' => 'radio',
    'section' => 'appearance',
    'options' => array(
        'light' => 'Light',
        'dark' => 'Dark',
        'auto' => 'Auto'
    ),
    'std' => 'light'
)
```

#### Select Field

**Single Select:**
```php
array(
    'id' => 'posts_per_page',
    'name' => 'Posts Per Page',
    'desc' => 'Number of posts to display',
    'type' => 'select',
    'section' => 'display',
    'options' => array(
        '5' => '5 Posts',
        '10' => '10 Posts',
        '20' => '20 Posts',
        '50' => '50 Posts'
    ),
    'std' => '10'
)
```

**Multiple Select:**
```php
array(
    'id' => 'post_types',
    'name' => 'Post Types',
    'desc' => 'Select post types to include',
    'type' => 'select',
    'section' => 'content',
    'multiple' => true,
    'size' => 5,
    'options' => array(
        'post' => 'Posts',
        'page' => 'Pages',
        'product' => 'Products'
    ),
    'std' => array('post', 'page')
)
```

#### Number Field
```php
array(
    'id' => 'max_items',
    'name' => 'Maximum Items',
    'desc' => 'Maximum number of items to display',
    'type' => 'number',
    'section' => 'limits',
    'min' => 1,
    'max' => 100,
    'step' => 1,
    'std' => 10
)
```

#### HTML Field
```php
array(
    'id' => 'info_box',
    'name' => '',
    'desc' => '',
    'type' => 'html',
    'section' => 'help',
    'std' => '<div class="notice notice-info"><p>This is an informational message.</p></div>'
)
```

### Field Configuration Options

#### Common Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `id` | string | required | Unique field identifier |
| `name` | string | '' | Field label |
| `desc` | string | '' | Field description/help text |
| `type` | string | required | Field type |
| `section` | string | required | Target section |
| `std` | mixed | '' | Default value |
| `required` | boolean | false | Is field required |
| `disabled` | boolean | false | Is field disabled |
| `class` | string/array | '' | Custom CSS classes |
| `attributes` | array | array() | Custom HTML attributes |

#### Type-Specific Properties

**Text/Textarea:**
- `min_length` - Minimum character length
- `max_length` - Maximum character length
- `placeholder` - Placeholder text
- `rows` - Number of rows (textarea only)
- `cols` - Number of columns (textarea only)
- `allow_html` - Allow HTML content (textarea only)

**Number:**
- `min` - Minimum value
- `max` - Maximum value
- `step` - Step increment

**Checkbox/Radio/Select:**
- `options` - Array of options (value => label)
- `multiple` - Allow multiple selection (select only)
- `size` - Number of visible options (select only)

### Field Validation

The framework provides automatic validation based on field type:

```php
// Text fields with length validation
array(
    'id' => 'username',
    'type' => 'text',
    'min_length' => 3,
    'max_length' => 20,
    'required' => true
)

// Number fields with range validation
array(
    'id' => 'age',
    'type' => 'number',
    'min' => 18,
    'max' => 100,
    'required' => true
)

// Email validation
array(
    'id' => 'email',
    'type' => 'email',
    'required' => true
)

// URL validation
array(
    'id' => 'website',
    'type' => 'url',
    'required' => false
)
```

### Custom Field Templates

You can use custom templates for field rendering:

```php
array(
    'id' => 'custom_field',
    'type' => 'checkbox',
    'template' => 'custom-checkbox.php',
    'template_path' => PLUGIN_PATH . 'templates/',
    'options' => array(
        'option1' => 'Option 1',
        'option2' => 'Option 2'
    )
)
```

## Header Customization

### Header Image
Set a logo or image to display in the header:

```php
// Set header image
AdminKit('my-plugin')->init(array(
    'header_image' => PLUGIN_URL . 'assets/logo.svg',
    // or
    'header_image' => 'https://example.com/logo.png',
    // or remove image
    'header_image' => '',
));

// Or use setter method
$admin_kit->set_page_header_image(PLUGIN_URL . 'assets/logo.svg');
```

**Default**: The Orbi logo is included by default. Set to empty string to remove.

### Header Background Color
Customize the header background color using any valid CSS color value:

```php
// Various color formats supported
AdminKit('my-plugin')->init(array(
    'header_bg_color' => '#32A3E2',                    // Hex
    'header_bg_color' => 'rgb(50, 163, 226)',          // RGB
    'header_bg_color' => 'rgba(50, 163, 226, 0.8)',   // RGBA
    'header_bg_color' => 'hsl(204, 76%, 54%)',         // HSL
    'header_bg_color' => 'blue',                       // Named color
    'header_bg_color' => 'var(--primary-color)',       // CSS variable
    'header_bg_color' => 'linear-gradient(90deg, #32A3E2, #1e5a8a)', // Gradient
));

// Or use setter method
$admin_kit->set_page_header_bg_color('#32A3E2');
```

**Default**: The header background color defaults to Orbital blue (`#32A3E2`).

### Hide Title and Description
Hide the title and description visually while keeping them accessible:

```php
AdminKit('my-plugin')->init(array(
    'title' => 'My Plugin Settings',
    'description' => 'Configure your plugin settings.',
    'hide_title_description' => true, // Hides visually but keeps for screen readers
));
```

When `hide_title_description` is `true`, the title and description receive the WordPress `screen-reader-text` class, making them invisible to sighted users but still accessible to screen readers for SEO and accessibility purposes.

## Hooks and Filters

### Filter Hooks

#### Core Structure Filters
```php
// Define page structure
add_filter('{slug}_adminkit_structure', function($structure) {
    return array(
        'tab_key' => array(
            'title' => 'Tab Title',
            'display_mode' => 'cards', // 'cards' or 'tabs'
            'sections' => array(
                'section_key' => 'Section Title'
            )
        )
    );
});

// Define fields
add_filter('{slug}_adminkit_fields', function($fields) {
    return array(
        'tab_key' => array(
            array(
                'id' => 'field_id',
                'name' => 'Field Name',
                'type' => 'text',
                'section' => 'section_key'
            )
        )
    );
});
```

#### Settings and Sanitization Filters
```php
// Modify settings before saving
add_filter('{slug}_pre_save_settings', function($settings) {
    // Modify $settings before database save
    return $settings;
});

// Custom field sanitization
add_filter('{slug}_sanitize_setting', function($value, $field) {
    if ($field['id'] === 'special_field') {
        // Custom sanitization logic
        return custom_sanitize($value);
    }
    return $value; // Let framework handle default sanitization
}, 10, 2);
```

#### Content and Display Filters
```php
// Modify page components
add_filter('{slug}_page_components', function($components) {
    // Add, remove, or modify page components
    return $components;
});

// Filter HTML field content
add_filter('adminkit_html_field_content', function($content, $field) {
    // Modify HTML field content
    return $content;
}, 10, 2);
```

### Action Hooks

#### Page Rendering Hooks
```php
// Before/after header
add_action('{slug}_before_header', function() {
    echo '<div class="custom-header-content">Custom content</div>';
});

add_action('{slug}_after_header', function() {
    echo '<div class="custom-notice">Important notice</div>';
});

// Content area hooks
add_action('{slug}_after_content', function() {
    echo '<div class="custom-footer">Custom footer content</div>';
});

add_action('{slug}_after_footer', function() {
    echo '<script>console.log("Custom JavaScript");</script>';
});
```

#### Asset and Settings Hooks
```php
// Enqueue additional assets
add_action('{slug}_enqueue_assets', function($hook_suffix) {
    wp_enqueue_script('my-custom-script', PLUGIN_URL . 'assets/script.js');
    wp_enqueue_style('my-custom-style', PLUGIN_URL . 'assets/style.css');
});

// After settings save
add_action('{slug}_post_save_settings', function($settings, $success) {
    if ($success) {
        // Perform actions after successful save
        do_action('my_plugin_settings_saved', $settings);
    }
}, 10, 2);
```

#### Custom Component Hooks
```php
// Render custom tab content
add_action('{slug}_render_tab_content', function($tab_key) {
    if ($tab_key === 'custom_tab') {
        echo '<div class="custom-tab-content">Custom content for this tab</div>';
    }
});

// Render custom components
add_action('{slug}_render_component_{component_name}', function() {
    echo '<div class="custom-component">Custom component content</div>';
});
```

#### Field Registration Hook
```php
// Register custom field types
add_action('orbi_register_fields', function() {
    \Orbitools\AdminKit\Field_Registry::register_field_type(
        'custom_field',
        PLUGIN_PATH . 'fields/custom-field.php',
        'My_Custom_Field_Class'
    );
});
```

### Complete Hook Reference

| Hook Type | Hook Name | Description |
|-----------|-----------|-------------|
| Filter | `{slug}_adminkit_structure` | Define page structure |
| Filter | `{slug}_adminkit_fields` | Define field configuration |
| Filter | `{slug}_pre_save_settings` | Modify settings before saving |
| Filter | `{slug}_sanitize_setting` | Custom field sanitization |
| Filter | `{slug}_page_components` | Modify page components |
| Filter | `adminkit_html_field_content` | Filter HTML field content |
| Action | `{slug}_before_header` | Before header content |
| Action | `{slug}_after_header` | After header content |
| Action | `{slug}_after_content` | After main content |
| Action | `{slug}_after_footer` | After footer |
| Action | `{slug}_enqueue_assets` | Enqueue additional assets |
| Action | `{slug}_post_save_settings` | After settings save |
| Action | `{slug}_render_tab_content` | Custom tab content |
| Action | `{slug}_render_component_{name}` | Custom component rendering |
| Action | `orbi_register_fields` | Register custom field types |

## CSS System

### BEM Methodology

The framework uses BEM (Block Element Modifier) methodology for CSS classes:

```css
/* Block */
.adminkit-header { }

/* Element */
.adminkit-header__content { }
.adminkit-header__image { }

/* Modifier */
.adminkit-header--compact { }
.adminkit-header__image--hidden { }
```

### CSS Class Reference

#### Header Classes
```css
.adminkit-header                 /* Header container */
.adminkit-header__content        /* Header content wrapper */
.adminkit-header__image          /* Header image container */
.adminkit-header__img           /* Header image element */
.adminkit-header__text          /* Header text container */
.adminkit-header__title         /* Page title */
.adminkit-header__description   /* Page description */
.adminkit-header--compact       /* Compact header modifier */
```

#### Navigation Classes
```css
.orbi-admin__breadcrumbs        /* Breadcrumb navigation */
.orbi-admin__tabs               /* Tab navigation */
.orbi-admin__tabs-nav           /* Tab navigation list */
.orbi-admin__tab-link           /* Individual tab link */
.orbi-admin__tab-link--active   /* Active tab */
.orbi-admin__subtabs            /* Sub-tab navigation */
.orbi-admin__subtab-link        /* Sub-tab link */
.orbi-admin__subtab-link--active /* Active sub-tab */
```

#### Content Classes
```css
.orbi-admin__section-card       /* Section card (cards mode) */
.orbi-admin__section-header     /* Section header */
.orbi-admin__section-title      /* Section title */
.orbi-admin__section-fields     /* Fields container */
.orbi-admin__section-content    /* Section content */
```

#### Field Classes
```css
.field                          /* Field wrapper */
.field--{type}                  /* Field type modifier */
.field--required                /* Required field modifier */
.field--disabled                /* Disabled field modifier */
.field__wrapper                 /* Field inner wrapper */
.field__label                   /* Field label */
.field__input                   /* Field input wrapper */
.field__description             /* Field description */
.field__error                   /* Field error message */

/* Specific field types */
.field--text { }
.field--textarea { }
.field--checkbox { }
.field--radio { }
.field--select { }
.field--number { }
.field--html { }
```

#### Checkbox/Radio Group Classes
```css
.field__checkbox-group          /* Checkbox group container */
.field__checkbox-item           /* Individual checkbox item */
.field__checkbox-input          /* Checkbox input */
.field__checkbox-label          /* Checkbox label */

.field__radio-group             /* Radio group container */
.field__radio-item              /* Individual radio item */
.field__radio-input             /* Radio input */
.field__radio-label             /* Radio label */
```

#### Form Classes
```css
.orbi-admin__settings-form      /* Main settings form */
.orbi-admin__form-section       /* Form section */
.orbi-admin__form-actions       /* Form action buttons */
.orbi-admin__save-button        /* Save button */
.orbi-admin__reset-button       /* Reset button */
```

#### Notice Classes
```css
.orbi-notice                    /* Notice container */
.orbi-notice--success           /* Success notice */
.orbi-notice--error             /* Error notice */
.orbi-notice--warning           /* Warning notice */
.orbi-notice--info              /* Info notice */
.orbi-notice--dismissible       /* Dismissible notice */
.orbi-notice__message           /* Notice message */
.orbi-notice__dismiss           /* Dismiss button */
```

### Responsive Design

The framework uses a mobile-first approach with these breakpoints:

```css
/* Mobile first (default) */
@media (max-width: 599px) {
    /* Mobile styles */
}

/* Tablet */
@media (min-width: 600px) and (max-width: 781px) {
    /* Tablet styles */
}

/* Desktop */
@media (min-width: 782px) {
    /* Desktop styles */
}
```

### CSS Custom Properties

The framework uses CSS custom properties for easy customization:

```css
:root {
    --adminkit-primary-color: #32A3E2;
    --adminkit-secondary-color: #1e5a8a;
    --adminkit-success-color: #46b450;
    --adminkit-error-color: #dc3232;
    --adminkit-warning-color: #ffb900;
    --adminkit-info-color: #00a0d2;

    --adminkit-border-radius: 4px;
    --adminkit-box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    --adminkit-transition: all 0.3s ease;

    --adminkit-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --adminkit-font-size-base: 14px;
    --adminkit-line-height-base: 1.5;

    --adminkit-spacing-xs: 4px;
    --adminkit-spacing-sm: 8px;
    --adminkit-spacing-md: 16px;
    --adminkit-spacing-lg: 24px;
    --adminkit-spacing-xl: 32px;
}
```

### Customization Examples

```css
/* Custom header styling */
.adminkit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    margin-bottom: 20px;
}

/* Custom field styling */
.field--text .field__input {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Custom button styling */
.orbi-admin__save-button {
    background: #46b450;
    border-radius: 6px;
    padding: 12px 24px;
    font-weight: 600;
}
```

## JavaScript API

### Core Functions

The framework provides a comprehensive JavaScript API for interacting with forms and settings:

#### Form Data Management
```javascript
// Get form data as object
const formData = orbitoolsAdminKit.serializeFormData();

// Get specific setting value
const value = orbitoolsAdminKit.getSetting('field_id');

// Set setting value programmatically
orbitoolsAdminKit.setSetting('field_id', 'new_value');

// Get all settings
const allSettings = orbitoolsAdminKit.getAllSettings();
```

#### Navigation
```javascript
// Navigate to specific tab
orbitoolsAdminKit.navigateToTab('tab_key');

// Navigate to specific section
orbitoolsAdminKit.navigateToSection('tab_key', 'section_key');

// Get current tab
const currentTab = orbitoolsAdminKit.getCurrentTab();

// Get current section
const currentSection = orbitoolsAdminKit.getCurrentSection();
```

#### UI Interactions
```javascript
// Scroll to specific element
orbitoolsAdminKit.scrollToElement('#element-id');

// Set loading state
orbitoolsAdminKit.setLoadingState(true);  // Show loading
orbitoolsAdminKit.setLoadingState(false); // Hide loading

// Show notice
orbitoolsAdminKit.showNotice('Settings saved!', 'success');
orbitoolsAdminKit.showNotice('Error occurred!', 'error');
```

#### Form Validation
```javascript
// Validate form
const isValid = orbitoolsAdminKit.validateForm();

// Validate specific field
const isFieldValid = orbitoolsAdminKit.validateField('field_id');

// Get validation errors
const errors = orbitoolsAdminKit.getValidationErrors();
```

### Event Handling

The framework triggers custom events you can listen to:

```javascript
// Form submission events
document.addEventListener('adminkit:form:submit', function(e) {
    console.log('Form submitted', e.detail);
});

document.addEventListener('adminkit:form:success', function(e) {
    console.log('Form saved successfully', e.detail);
});

document.addEventListener('adminkit:form:error', function(e) {
    console.log('Form save error', e.detail);
});

// Navigation events
document.addEventListener('adminkit:tab:change', function(e) {
    console.log('Tab changed', e.detail);
});

document.addEventListener('adminkit:section:change', function(e) {
    console.log('Section changed', e.detail);
});

// Field events
document.addEventListener('adminkit:field:change', function(e) {
    console.log('Field changed', e.detail);
});
```

### Advanced Usage

#### Custom Form Submission
```javascript
// Custom form submission with validation
function submitCustomForm() {
    if (orbitoolsAdminKit.validateForm()) {
        const formData = orbitoolsAdminKit.serializeFormData();

        // Add custom data
        formData.custom_field = 'custom_value';

        // Submit with custom callback
        orbitoolsAdminKit.submitForm(formData, function(response) {
            if (response.success) {
                orbitoolsAdminKit.showNotice('Custom submission successful!', 'success');
            } else {
                orbitoolsAdminKit.showNotice('Custom submission failed!', 'error');
            }
        });
    }
}
```

#### Field Manipulation
```javascript
// Enable/disable fields dynamically
orbitoolsAdminKit.setFieldDisabled('field_id', true);  // Disable
orbitoolsAdminKit.setFieldDisabled('field_id', false); // Enable

// Show/hide fields
orbitoolsAdminKit.setFieldVisible('field_id', false); // Hide
orbitoolsAdminKit.setFieldVisible('field_id', true);  // Show

// Add field validation
orbitoolsAdminKit.addFieldValidator('field_id', function(value) {
    if (value.length < 5) {
        return 'Field must be at least 5 characters long';
    }
    return true; // Valid
});
```

### Utility Functions

```javascript
// URL manipulation
orbitoolsAdminKit.updateURL('tab_key', 'section_key');
orbitoolsAdminKit.getURLParameter('tab');

// Element utilities
orbitoolsAdminKit.findElement('.class-name');
orbitoolsAdminKit.addClass(element, 'class-name');
orbitoolsAdminKit.removeClass(element, 'class-name');

// Animation utilities
orbitoolsAdminKit.fadeIn(element);
orbitoolsAdminKit.fadeOut(element);
orbitoolsAdminKit.slideUp(element);
orbitoolsAdminKit.slideDown(element);
```

## Security Features

### Built-in Security Measures

#### CSRF Protection
```php
// Automatic nonce verification for all AJAX requests
wp_verify_nonce($_POST['orbi_nonce'], 'orbitools_adminkit_' . $this->slug);
```

#### Capability Checks
```php
// User capability verification
if (!current_user_can($this->menu_config['capability'])) {
    wp_send_json_error('Insufficient permissions');
}
```

#### Input Sanitization
```php
// Automatic sanitization based on field type
switch ($field['type']) {
    case 'text':
        return sanitize_text_field($value);
    case 'email':
        return sanitize_email($value);
    case 'url':
        return esc_url_raw($value);
    case 'number':
        return intval($value);
    // ... more types
}
```

#### Template Security
```php
// Template path validation
private function validate_template_path($template_path) {
    $allowed_paths = array(
        ABSPATH,
        WP_CONTENT_DIR,
        get_template_directory(),
        get_stylesheet_directory()
    );

    foreach ($allowed_paths as $allowed_path) {
        if (strpos($template_path, $allowed_path) === 0) {
            return true;
        }
    }

    return false;
}
```

### Security Best Practices

#### 1. Field Validation
```php
// Always validate field values
add_filter('my_plugin_sanitize_setting', function($value, $field) {
    if ($field['id'] === 'user_email') {
        if (!is_email($value)) {
            return ''; // Invalid email
        }
    }
    return $value;
}, 10, 2);
```

#### 2. Capability Checks
```php
// Use appropriate capabilities
AdminKit('my-plugin')->init(array(
    'menu' => array(
        'capability' => 'manage_options', // For admin settings
        // or
        'capability' => 'edit_posts',     // For editor settings
        // or
        'capability' => 'read',           // For read-only pages
    )
));
```

#### 3. Data Escaping
```php
// Always escape output
array(
    'id' => 'custom_html',
    'type' => 'html',
    'std' => '<p>' . esc_html($user_content) . '</p>'
)
```

#### 4. SQL Injection Prevention
```php
// Use WordPress database methods
$settings = get_option($this->slug . '_settings', array());
update_option($this->slug . '_settings', $sanitized_data);
```

## Advanced Features

### Custom Field Types

Create custom field types by extending the `Field_Base` class:

```php
// Create custom field class
class Custom_Color_Field extends \Orbitools\AdminKit\Fields\Field_Base {

    public function render() {
        $value = esc_attr($this->value);
        $id = esc_attr($this->field['id']);

        echo '<input type="color" id="' . $id . '" name="' . $id . '" value="' . $value . '">';
    }

    public function get_assets() {
        return array(
            array(
                'type' => 'css',
                'handle' => 'custom-color-field',
                'src' => PLUGIN_URL . 'assets/color-field.css'
            ),
            array(
                'type' => 'js',
                'handle' => 'custom-color-field',
                'src' => PLUGIN_URL . 'assets/color-field.js'
            )
        );
    }
}

// Register the field type
add_action('orbi_register_fields', function() {
    \Orbitools\AdminKit\Field_Registry::register_field_type(
        'color',
        PLUGIN_PATH . 'fields/custom-color-field.php',
        'Custom_Color_Field'
    );
});
```

### Custom Page Components

Create custom page components:

```php
// Register custom component
add_filter('my_plugin_page_components', function($components) {
    $components['custom_dashboard'] = array(
        'class' => 'My_Custom_Dashboard_Component',
        'file' => PLUGIN_PATH . 'components/dashboard.php'
    );
    return $components;
});

// Render custom component
add_action('my_plugin_render_component_custom_dashboard', function() {
    echo '<div class="custom-dashboard">Custom dashboard content</div>';
});
```

### Asset Management

#### Field-Specific Assets
```php
class My_Field extends Field_Base {
    public function get_assets() {
        return array(
            array(
                'type' => 'css',
                'handle' => 'my-field-styles',
                'src' => PLUGIN_URL . 'assets/my-field.css',
                'deps' => array('wp-admin'),
                'version' => '1.0.0'
            ),
            array(
                'type' => 'js',
                'handle' => 'my-field-scripts',
                'src' => PLUGIN_URL . 'assets/my-field.js',
                'deps' => array('jquery'),
                'version' => '1.0.0',
                'in_footer' => true
            )
        );
    }
}
```

#### Global Assets
```php
// Enqueue global assets
add_action('my_plugin_enqueue_assets', function($hook_suffix) {
    wp_enqueue_style('my-plugin-admin', PLUGIN_URL . 'assets/admin.css');
    wp_enqueue_script('my-plugin-admin', PLUGIN_URL . 'assets/admin.js', array('jquery'));

    wp_localize_script('my-plugin-admin', 'MyPluginAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('my_plugin_nonce')
    ));
});
```

### Conditional Fields

Show/hide fields based on other field values:

```php
// Add conditional logic
add_filter('my_plugin_adminkit_fields', function($fields) {
    return array(
        'general' => array(
            array(
                'id' => 'enable_advanced',
                'name' => 'Enable Advanced Features',
                'type' => 'checkbox',
                'section' => 'basic'
            ),
            array(
                'id' => 'advanced_option',
                'name' => 'Advanced Option',
                'type' => 'text',
                'section' => 'basic',
                'depends_on' => 'enable_advanced', // Field dependency
                'depends_value' => true
            )
        )
    );
});
```

### Import/Export Settings

```php
// Add import/export functionality
add_action('my_plugin_after_content', function() {
    ?>
    <div class="import-export-section">
        <h3>Import/Export Settings</h3>
        <button id="export-settings" class="button">Export Settings</button>
        <input type="file" id="import-file" accept=".json" style="display:none;">
        <button id="import-settings" class="button">Import Settings</button>
    </div>

    <script>
    document.getElementById('export-settings').addEventListener('click', function() {
        const settings = orbitoolsAdminKit.getAllSettings();
        const blob = new Blob([JSON.stringify(settings, null, 2)], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'settings.json';
        a.click();
    });

    document.getElementById('import-settings').addEventListener('click', function() {
        document.getElementById('import-file').click();
    });

    document.getElementById('import-file').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const settings = JSON.parse(e.target.result);
                    orbitoolsAdminKit.importSettings(settings);
                    orbitoolsAdminKit.showNotice('Settings imported successfully!', 'success');
                } catch (error) {
                    orbitoolsAdminKit.showNotice('Invalid settings file!', 'error');
                }
            };
            reader.readAsText(file);
        }
    });
    </script>
    <?php
});
```

## Examples

### Complete Plugin Example

```php
<?php
/**
 * Plugin Name: My Admin Plugin
 * Description: Example plugin using OrbiTools AdminKit
 */

// Initialize AdminKit
add_action('admin_init', function() {
    if (function_exists('AdminKit')) {
        AdminKit('my-admin-plugin')->init(array(
            'title' => 'My Admin Plugin',
            'description' => 'A comprehensive admin interface example',
            'header_image' => plugin_dir_url(__FILE__) . 'assets/logo.svg',
            'header_bg_color' => '#2271b1',
            'menu' => array(
                'menu_type' => 'menu',
                'menu_title' => 'My Plugin',
                'capability' => 'manage_options',
                'icon_url' => 'dashicons-admin-plugins',
                'position' => 25
            )
        ));
    }
});

// Define page structure
add_filter('my_admin_plugin_adminkit_structure', function($structure) {
    return array(
        'dashboard' => array(
            'title' => 'Dashboard',
            'display_mode' => 'cards',
            'sections' => array(
                'overview' => 'Overview',
                'stats' => 'Statistics',
                'recent' => 'Recent Activity'
            )
        ),
        'settings' => array(
            'title' => 'Settings',
            'display_mode' => 'tabs',
            'sections' => array(
                'general' => 'General',
                'appearance' => 'Appearance',
                'advanced' => 'Advanced'
            )
        ),
        'tools' => array(
            'title' => 'Tools',
            'display_mode' => 'cards',
            'sections' => array(
                'import' => 'Import',
                'export' => 'Export',
                'maintenance' => 'Maintenance'
            )
        )
    );
});

// Define fields
add_filter('my_admin_plugin_adminkit_fields', function($fields) {
    return array(
        'dashboard' => array(
            array(
                'id' => 'welcome_message',
                'name' => '',
                'type' => 'html',
                'section' => 'overview',
                'std' => '<div class="notice notice-info"><p>Welcome to My Admin Plugin!</p></div>'
            ),
            array(
                'id' => 'total_users',
                'name' => 'Total Users',
                'type' => 'html',
                'section' => 'stats',
                'std' => '<strong>' . count_users()['total_users'] . '</strong>'
            )
        ),
        'settings' => array(
            array(
                'id' => 'site_title',
                'name' => 'Site Title',
                'desc' => 'Enter your site title',
                'type' => 'text',
                'section' => 'general',
                'required' => true,
                'std' => get_bloginfo('name')
            ),
            array(
                'id' => 'site_description',
                'name' => 'Site Description',
                'desc' => 'Enter your site description',
                'type' => 'textarea',
                'section' => 'general',
                'rows' => 3,
                'std' => get_bloginfo('description')
            ),
            array(
                'id' => 'enable_features',
                'name' => 'Enable Features',
                'desc' => 'Select features to enable',
                'type' => 'checkbox',
                'section' => 'general',
                'options' => array(
                    'comments' => 'Comments',
                    'search' => 'Search',
                    'widgets' => 'Widgets'
                ),
                'std' => array('comments', 'search')
            ),
            array(
                'id' => 'color_scheme',
                'name' => 'Color Scheme',
                'desc' => 'Choose your color scheme',
                'type' => 'radio',
                'section' => 'appearance',
                'options' => array(
                    'light' => 'Light',
                    'dark' => 'Dark',
                    'auto' => 'Auto'
                ),
                'std' => 'light'
            ),
            array(
                'id' => 'posts_per_page',
                'name' => 'Posts Per Page',
                'desc' => 'Number of posts to display',
                'type' => 'select',
                'section' => 'appearance',
                'options' => array(
                    '5' => '5 Posts',
                    '10' => '10 Posts',
                    '20' => '20 Posts',
                    '50' => '50 Posts'
                ),
                'std' => '10'
            ),
            array(
                'id' => 'cache_duration',
                'name' => 'Cache Duration',
                'desc' => 'Cache duration in minutes',
                'type' => 'number',
                'section' => 'advanced',
                'min' => 1,
                'max' => 1440,
                'std' => 60
            ),
            array(
                'id' => 'debug_mode',
                'name' => 'Debug Mode',
                'desc' => 'Enable debug mode',
                'type' => 'checkbox',
                'section' => 'advanced',
                'std' => false
            )
        ),
        'tools' => array(
            array(
                'id' => 'import_data',
                'name' => 'Import Data',
                'type' => 'html',
                'section' => 'import',
                'std' => '<button class="button button-primary">Import Data</button>'
            ),
            array(
                'id' => 'export_data',
                'name' => 'Export Data',
                'type' => 'html',
                'section' => 'export',
                'std' => '<button class="button button-secondary">Export Data</button>'
            )
        )
    );
});

// Custom validation
add_filter('my_admin_plugin_sanitize_setting', function($value, $field) {
    if ($field['id'] === 'site_title' && strlen($value) < 3) {
        add_settings_error(
            'my_admin_plugin_settings',
            'site_title_error',
            'Site title must be at least 3 characters long.',
            'error'
        );
        return get_option('my_admin_plugin_settings')['site_title'] ?? '';
    }
    return $value;
}, 10, 2);

// After save action
add_action('my_admin_plugin_post_save_settings', function($settings, $success) {
    if ($success && isset($settings['site_title'])) {
        // Update WordPress site title
        update_option('blogname', $settings['site_title']);
    }
}, 10, 2);
```

### Custom Field Example

```php
<?php
/**
 * Custom Date Field Example
 */
class Custom_Date_Field extends \Orbitools\AdminKit\Fields\Field_Base {

    public function render() {
        $value = esc_attr($this->value);
        $id = esc_attr($this->field['id']);
        $name = esc_attr($this->field['id']);

        // Format for display
        $display_value = $value ? date('Y-m-d', strtotime($value)) : '';

        echo '<input type="date" id="' . $id . '" name="' . $name . '" value="' . $display_value . '"';

        // Add attributes
        if (isset($this->field['min'])) {
            echo ' min="' . esc_attr($this->field['min']) . '"';
        }
        if (isset($this->field['max'])) {
            echo ' max="' . esc_attr($this->field['max']) . '"';
        }
        if (isset($this->field['required']) && $this->field['required']) {
            echo ' required';
        }

        echo '>';
    }

    public function get_assets() {
        return array(
            array(
                'type' => 'css',
                'handle' => 'custom-date-field',
                'src' => plugin_dir_url(__FILE__) . 'assets/date-field.css'
            ),
            array(
                'type' => 'js',
                'handle' => 'custom-date-field',
                'src' => plugin_dir_url(__FILE__) . 'assets/date-field.js',
                'deps' => array('jquery')
            )
        );
    }

    public function sanitize($value) {
        // Validate date format
        if (empty($value)) {
            return '';
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);
        if ($date && $date->format('Y-m-d') === $value) {
            return $value;
        }

        return '';
    }
}

// Register the field
add_action('orbi_register_fields', function() {
    \Orbitools\AdminKit\Field_Registry::register_field_type(
        'date',
        __DIR__ . '/fields/custom-date-field.php',
        'Custom_Date_Field'
    );
});

// Use the field
add_filter('my_plugin_adminkit_fields', function($fields) {
    $fields['general'][] = array(
        'id' => 'event_date',
        'name' => 'Event Date',
        'desc' => 'Select the event date',
        'type' => 'date',
        'section' => 'events',
        'min' => date('Y-m-d'),
        'max' => date('Y-m-d', strtotime('+1 year')),
        'required' => true
    );

    return $fields;
});
```

## Troubleshooting

### Common Issues

#### 1. Page Not Showing
**Problem**: Admin page doesn't appear in menu
**Solution**:
- Check if `init()` was called
- Verify user has required capability
- Ensure no PHP errors in initialization

```php
// Debug initialization
add_action('admin_init', function() {
    if (function_exists('AdminKit')) {
        $admin_kit = AdminKit('my-plugin');
        if ($admin_kit) {
            $admin_kit->init(array(
                'title' => 'My Plugin',
                'menu' => array(
                    'capability' => 'manage_options'
                )
            ));
        } else {
            error_log('AdminKit not available');
        }
    }
});
```

#### 2. Fields Not Saving
**Problem**: Form submissions don't save data
**Solution**:
- Check nonce verification
- Verify field IDs match in structure and fields filters
- Ensure proper sanitization

```php
// Debug field saving
add_action('my_plugin_post_save_settings', function($settings, $success) {
    error_log('Settings save result: ' . ($success ? 'success' : 'failed'));
    error_log('Settings data: ' . print_r($settings, true));
}, 10, 2);
```

#### 3. CSS/JS Not Loading
**Problem**: Styles or scripts not loading
**Solution**:
- Check asset paths are correct
- Verify hook suffix matches page
- Ensure assets are enqueued properly

```php
// Debug asset loading
add_action('my_plugin_enqueue_assets', function($hook_suffix) {
    error_log('Enqueuing assets for: ' . $hook_suffix);
    wp_enqueue_style('debug-style', plugin_dir_url(__FILE__) . 'debug.css');
});
```

#### 4. Hook Not Firing
**Problem**: Custom hooks not executing
**Solution**:
- Verify hook name matches slug
- Check hook priority and parameters
- Ensure function is defined before hook runs

```php
// Debug hooks
add_action('my_plugin_before_header', function() {
    error_log('Before header hook fired');
});
```

### Debug Mode

Enable debug mode for detailed logging:

```php
// Enable debug mode
define('ADMINKIT_DEBUG', true);

// Add debug logging
add_filter('my_plugin_pre_save_settings', function($settings) {
    if (defined('ADMINKIT_DEBUG') && ADMINKIT_DEBUG) {
        error_log('AdminKit Debug - Settings before save: ' . print_r($settings, true));
    }
    return $settings;
});
```

### Performance Optimization

#### 1. Conditional Loading
```php
// Only load on admin pages
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'my-plugin') {
        // Load heavy resources only on plugin page
        wp_enqueue_script('heavy-script', 'script.js');
    }
});
```

#### 2. Asset Minification
```php
// Use minified assets in production
$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
wp_enqueue_script('my-script', 'script' . $suffix . '.js');
```

#### 3. Caching
```php
// Cache expensive operations
add_filter('my_plugin_adminkit_fields', function($fields) {
    $cache_key = 'my_plugin_fields_' . md5(serialize($some_data));
    $cached_fields = wp_cache_get($cache_key);

    if ($cached_fields === false) {
        $cached_fields = expensive_field_generation();
        wp_cache_set($cache_key, $cached_fields, '', 3600); // 1 hour
    }

    return $cached_fields;
});
```

---

## Version History

### 1.0.0
- Initial release
- Core framework with field system
- BEM CSS methodology
- Responsive design
- Accessibility features
- Security enhancements
- Menu type selection (menu/submenu)
- Custom field support
- Hook system
- JavaScript API

---

## License

GPL v2 or later

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

---

## Support

For support and questions:
- GitHub Issues: [Report issues](https://github.com/orbital-design/orbitools)
- Documentation: [Full documentation](https://orbital.co.uk/docs/adminkit)
- Community: [Discord community](https://discord.gg/orbital)

---

*AdminKit is developed and maintained by [Orbital Design](https://orbital.co.uk/)*

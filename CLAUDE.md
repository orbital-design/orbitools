# Orbital Editor Suite - Developer Guide

## Overview

Orbital Editor Suite is a modern WordPress plugin that provides enhanced editor functionality with a focus on typography management and modern admin interfaces.

## WP OptionsKit Field Types

**IMPORTANT: Definitive list of supported field types based on source code analysis**

OptionsKit supports exactly these 11 field types (confirmed from PHP sanitization filters and Vue.js components):

1. **`text`** - Text input field
2. **`textarea`** - Multi-line text area  
3. **`radio`** - Radio button group (requires `options` array)
4. **`select`** - Dropdown select (requires `options` array)
5. **`checkbox`** - Single checkbox
6. **`multiselect`** - Multi-select dropdown (requires `options` array)
7. **`multicheckbox`** (alias: `multicheck`) - Multiple checkboxes (requires `options` array)
8. **`file`** - File upload/media selector
9. **`anchor`** - Link/anchor field
10. **`html`** - Raw HTML content display (for `std` property)
11. **`hidden`** - Hidden form field

**Source Evidence:**
- PHP sanitization filters in `class-wpok-rest-server.php` lines 78-85
- Vue.js components: `formit-text`, `formit-textarea`, `formit-radio`, `formit-select`, `formit-checkbox`, `formit-multiselect`, `formit-multicheckbox`, `formit-file`, `formit-anchor`, `formit-html`, `formit-hidden`

**DO NOT use unsupported field types like:**
- `checkbox_list` (doesn't exist)
- `number` (not supported)
- `color` (not supported)
- `date` (not supported)

## Architecture

### Vue.js Admin Interfaces

The plugin uses Vue.js 3.0 for all admin interfaces, providing a modern, reactive user experience:

- **Main Dashboard**: Comprehensive plugin management with module controls
- **Typography Presets**: Advanced typography management with live preview
- **Updates**: GitHub-integrated update management
- **System Info**: Comprehensive diagnostic information

### Module System

The plugin uses a modular architecture where individual features can be enabled/disabled:

- **Typography Presets**: Replace core typography controls with preset utility classes
- Future modules can be easily added to the system

## File Structure

```
orbital-editor-suite/
├── orbital-editor-suite.php          # Main plugin file
├── includes/
│   ├── class-plugin.php              # Core plugin class
│   ├── class-loader.php              # Hook loader
│   ├── class-activator.php           # Plugin activation
│   ├── admin/
│   │   ├── class-admin.php           # Admin hooks
│   │   ├── class-admin-pages.php     # Menu registration
│   │   ├── class-main-vue-admin.php  # Main Vue.js interface
│   │   └── class-updates-vue-admin.php # Updates Vue.js interface
│   └── modules/
│       └── typography-presets/
│           ├── class-typography-presets.php      # Main module class
│           ├── class-typography-presets-admin.php # Legacy admin (redirects)
│           └── class-typography-presets-vue-admin.php # Vue.js admin
├── assets/
│   ├── js/
│   │   ├── main-vue-app.js           # Main dashboard Vue app
│   │   ├── typography-presets-vue-app.js # Typography Vue app
│   │   ├── updates-vue-app.js        # Updates Vue app
│   │   └── vue-components.js         # Shared Vue components
│   └── css/
│       ├── main-vue-styles.css       # Main dashboard styles
│       ├── typography-presets-vue-styles.css # Typography styles
│       ├── updates-vue-styles.css    # Updates styles
│       └── vue-components-styles.css # Shared component styles
```

## Vue.js Implementation

### Component Architecture

All Vue.js interfaces share common components and utilities:

- **OrbitalLoadingSpinner**: Loading states
- **OrbitalStatusMessage**: Success/error messages
- **OrbitalFormField**: Form field wrapper
- **OrbitalToggleSwitch**: Toggle switch component
- **OrbitalCard**: Card layout component
- **OrbitalTabs**: Tab navigation

### Data Flow

1. PHP localizes data via `wp_localize_script()`
2. Vue.js components receive data on mount
3. AJAX calls handle real-time updates
4. Components update reactively based on data changes

## Development Guidelines

### Adding New Modules

1. Create module directory in `includes/modules/`
2. Implement main module class extending base functionality
3. Create Vue.js admin interface using shared components
4. Register module in `get_available_modules()` method
5. Add module-specific settings sanitization

### Vue.js Best Practices

- Use shared components from `vue-components.js`
- Follow consistent naming conventions
- Implement proper error handling for AJAX calls
- Use reactive data properties for UI updates
- Include loading states for async operations

### CSS Standards

- Use consistent design tokens across interfaces
- Implement responsive design for all screen sizes
- Follow WordPress admin design patterns
- Use CSS custom properties for maintainability

## System Information

The System Info tab provides comprehensive diagnostic information:

- **WordPress Environment**: Version, theme, debug settings
- **Module Status**: Enabled modules and class loading status
- **File Status**: Plugin file existence and modification dates
- **Server Information**: PHP, MySQL, and server details
- **Active Plugins**: Complete list of active plugins

## Debug and Development

### Debug Mode

Enable WordPress debug mode for development:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Development Mode

The plugin automatically adapts its behavior based on the WordPress debug configuration. Enable debug mode for detailed error reporting and diagnostic information.

## Security Considerations

- All AJAX endpoints use nonce verification
- User capability checks on all admin actions
- Proper data sanitization for all inputs
- No sensitive information in client-side code

## Performance Optimization

- CSS and JavaScript only loaded on relevant admin pages
- Modular loading prevents unnecessary code execution
- Efficient Vue.js rendering with conditional components
- Minimal DOM manipulation and reactive updates

## Future Roadmap

- Additional typography modules (font loading, variable fonts)
- Block editor enhancements
- Advanced caching mechanisms
- Multi-site compatibility improvements
- Theme integration tools

## Support and Troubleshooting

Use the System Info tab in the admin interface to diagnose issues. It provides comprehensive information about:

- Plugin file status
- Module loading status
- Server configuration
- WordPress environment
- Active plugin conflicts

For development support, ensure `WP_DEBUG` is enabled and check the WordPress debug log for detailed error information.

## ARCHITECTURAL ANALYSIS AND FINDINGS

### Current Plugin Architecture Issues

After thorough analysis, several architectural issues have been identified:

#### 1. Admin Page Structure Problems

**ISSUE**: Mixing PHP components with Vue.js app containers causes rendering conflicts.

**PROBLEM**: 
- PHP-generated headers/tabs placed inside Vue.js app containers
- WordPress admin notices not properly positioned
- Inconsistent admin page structure across modules

**PROPER PATTERN**:
```php
// CORRECT Structure:
<div class="wrap">
    <?php settings_errors(); ?>
    <!-- PHP-rendered static elements (header, tabs) -->
    <div id="vue-app">
        <!-- Vue.js dynamic content only -->
    </div>
</div>
```

**WRONG PATTERN**:
```php
// INCORRECT - Don't do this:
<div id="vue-app">
    <div v-else>
        <?php render_header(); ?> <!-- PHP inside Vue -->
    </div>
</div>
```

#### 2. Hook and Filter Usage

**CURRENT**: Manual admin page registration with hardcoded dependencies
**SHOULD BE**: Hook-driven modular system

```php
// CURRENT (problematic):
$vue_admin = new Typography_Presets_Vue_Admin($this);
$vue_admin->add_admin_menu();

// BETTER (hook-driven):
do_action('orbital_editor_suite_admin_pages');
// Modules register themselves via this hook
```

#### 3. Asset Loading Issues

**PROBLEM**: Inconsistent Vue.js dependency management
- Some modules load Vue.js, others assume it's loaded
- Dependency conflicts and 404 errors

**SOLUTION**: Centralized asset management with proper dependencies

#### 4. Component Framework Issues

**PROBLEM**: Created `Admin_Components` class but used it incorrectly
- Mixing static PHP rendering with dynamic Vue.js content
- Not following WordPress admin patterns properly

### Recommended Architecture

#### 1. Admin Page Structure Standard

Every admin page should follow this pattern:

```php
public function render_admin_page() {
    ?>
    <div class="wrap">
        <?php settings_errors(); ?>
        
        <!-- Static header (PHP rendered) -->
        <div class="orbital-header">
            <h1><?php echo $page_title; ?></h1>
        </div>
        
        <!-- Admin notices container -->
        <div class="orbital-notices"></div>
        
        <!-- Static tabs (PHP rendered) -->
        <div class="orbital-tabs">
            <!-- PHP rendered tabs -->
        </div>
        
        <!-- Vue.js app for dynamic content -->
        <div id="vue-app">
            <!-- Only dynamic content here -->
        </div>
    </div>
    <?php
}
```

#### 2. Module Registration Pattern

Modules should register admin pages via hooks:

```php
// In module class:
add_action('orbital_editor_suite_admin_pages', array($this, 'register_admin_page'));

public function register_admin_page() {
    add_submenu_page(
        'orbital-editor-suite',
        $title,
        $menu_title,
        'manage_options',
        $menu_slug,
        array($this, 'render_admin_page')
    );
}
```

#### 3. Asset Management Standard

```php
// Central asset loading in main admin class
public function enqueue_scripts($hook) {
    if ($this->is_orbital_page($hook)) {
        // Load Vue.js once
        wp_enqueue_script('vue-js', $vue_url, array(), '3.0.0', true);
        
        // Load page-specific Vue apps with Vue dependency
        $this->load_page_assets($hook);
    }
}
```

#### 4. Component Library Approach

Instead of mixing PHP/Vue, create:
- **PHP Components**: Static elements (headers, tabs, notices)
- **Vue Components**: Dynamic interfaces within tab content
- **CSS Framework**: Consistent styling across both

### Current State Assessment

**WORKING WELL**:
- Module system architecture
- Settings management
- Vue.js individual apps

**NEEDS FIXING**:
- Admin page structure (PHP/Vue separation)
- Asset loading consistency  
- Component framework approach
- Notice positioning

### Next Steps Priority

1. **Fix admin page structure** - Separate PHP static elements from Vue apps
2. **Standardize asset loading** - Central Vue.js management
3. **Simplify component approach** - Clear PHP vs Vue boundaries
4. **Test and validate** - Ensure all pages work consistently

### WordPress Best Practices Violations

1. **Admin notices**: Should use `settings_errors()` in proper location
2. **Hook usage**: Should leverage WordPress hook system more extensively
3. **Asset dependencies**: Should use proper wp_enqueue_script dependencies
4. **Page structure**: Should follow WordPress admin page patterns

### Key Learning

**The issue isn't with WP Options Kit** - there is no "WP Options Kit" in this codebase. The issue is with architectural decisions that mix static PHP rendering with dynamic Vue.js content inappropriately.

**Solution**: Clear separation of concerns - PHP for static structure, Vue.js for dynamic content within that structure.

## WP OPTIONS KIT LIBRARY ANALYSIS

### Overview
WP Options Kit (WPUserManager/wp-optionskit) is a WordPress toolkit for creating administration panels powered by Vue.js. It's designed to simplify creating complex settings interfaces for plugins and themes.

### Architecture Deep Dive

#### 1. PHP Backend Structure

**Main Class: `OptionsKit` (TDP namespace)**
```php
// Initialization pattern
class OptionsKit {
    private $slug;
    private $version;
    private $page_title;
    
    public function __construct($slug) {
        $this->slug = $slug;
        $this->init();
    }
    
    private function init() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'scripts'));
        // REST API registration
    }
}
```

**Key Features:**
- Namespace-based configuration (`TDP`)
- Dynamic slug-based initialization
- WordPress hook integration
- REST API controller registration

#### 2. REST API Integration

**WPOK_Rest_Server Class:**
```php
class WPOK_Rest_Server extends WP_REST_Controller {
    // Registers '/records' endpoint
    // Handles UPDATE operations for settings
    // Permission checking with 'manage_options'
    // Sanitization for different field types
}
```

**Security & Sanitization:**
- Uses `current_user_can('manage_options')`
- Multiple sanitization methods:
  - `sanitize_text_field()`
  - `sanitize_textarea_field()` 
  - `sanitize_multiple_field()`
  - `sanitize_checkbox_field()`

#### 3. Vue.js Frontend Architecture

**Entry Point (main.js):**
```javascript
// Vue initialization pattern
new Vue({
    el: '#optionskit-page',
    router,
    render: h => h(App)
});

// WordPress integration
Vue.prototype.$optionsKitSettings = optionsKitSettings;
```

**Key Libraries:**
- `vue-formit-fields` - Form handling
- `vue-wp-notice` - WordPress-style notifications
- Vue Router for navigation

#### 4. Main App Component (App.vue)

**Data Structure:**
```javascript
data() {
    return {
        pageTitle: this.$optionsKitSettings.page_title,
        model: this.$optionsKitSettings.options,
        form: new Formit(),
        success: false
    }
}
```

**Core Methods:**
- `submit()` - REST API form submission with nonce
- `detectMainTab()` - Dynamic tab detection
- Route watching for tab management

#### 5. Component Architecture

**Fields Wrapper (fields-wrapper.vue):**
- Dynamic field rendering using table structure
- Conditional field display with `maybeShowField()`
- Complex visibility rules (operators: `!=`, `===`, `>`, `in`)
- Error handling integration
- Dynamic component selection via `formit-{type}`

**Component Pattern:**
```vue
<component 
    :is="getFieldComponentName(field.type)"
    :field="field"
    :form="form"
    v-model="model[field.id]"
/>
```

#### 6. WordPress Integration Patterns

**Data Flow:**
1. PHP localizes settings via `wp_localize_script()`
2. Vue accesses via `this.$optionsKitSettings`
3. Form submission to REST endpoint
4. WordPress saves to options table

**Hook System:**
- `{prefix}_menu` - Menu configuration
- `{prefix}_settings_tabs` - Tab structure
- `{prefix}_registered_settings_sections` - Sections
- `{prefix}_registered_settings` - Individual settings

### Key Design Principles

#### 1. Configuration-Driven
- Settings defined through WordPress filters
- Dynamic UI generation from configuration
- Minimal hardcoded structure

#### 2. Modular Architecture
- Separate concerns: PHP backend, Vue frontend
- Component-based Vue structure
- REST API for clean separation

#### 3. WordPress Standards
- Uses WordPress Settings API
- Proper capability checking
- WordPress coding standards
- Admin UI consistency

#### 4. Developer Experience
- Hot reloading in development
- Modern build tools (Babel, PostCSS)
- Component-based development

### Comparison with Our Implementation

#### What We're Doing Wrong:
1. **Mixed Rendering**: PHP inside Vue containers
2. **Manual Registration**: Hardcoded admin page setup
3. **No Configuration System**: Settings hardcoded
4. **Inconsistent Assets**: Multiple Vue.js loading

#### What We Should Adopt:
1. **Clear Separation**: PHP structure, Vue content
2. **Filter-Based Config**: Dynamic settings via hooks
3. **REST API**: Proper frontend/backend communication
4. **Component Library**: Reusable form components

### Implementation Recommendations

#### 1. Restructure Admin Pages
```php
// Follow WP Options Kit pattern
class Orbital_Admin_Panel {
    public function __construct($slug) {
        $this->slug = $slug;
        $this->init_hooks();
    }
    
    private function render_page() {
        ?>
        <div class="wrap">
            <div id="orbital-<?php echo $this->slug; ?>-app"></div>
        </div>
        <?php
    }
}
```

#### 2. REST API Integration
```php
// Create proper REST endpoints
class Orbital_REST_Controller extends WP_REST_Controller {
    public function register_routes() {
        register_rest_route('orbital/v1', '/settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_settings'),
            'permission_callback' => array($this, 'check_permissions')
        ));
    }
}
```

#### 3. Vue.js Standardization
```javascript
// Single Vue initialization pattern
new Vue({
    el: '#orbital-app',
    data: {
        settings: orbital_settings,
        form: new FormHandler()
    },
    methods: {
        saveSettings() {
            // REST API call
        }
    }
});
```

### Critical Learnings

1. **WP Options Kit separates PHP and Vue completely** - No mixed rendering
2. **Uses WordPress filters extensively** - Configuration-driven approach
3. **REST API is the bridge** - Clean frontend/backend separation
4. **Component-based Vue architecture** - Reusable, modular design
5. **WordPress standards compliance** - Proper hooks, capabilities, sanitization

### Next Steps Priority

1. **Adopt WP Options Kit patterns** - Clear PHP/Vue separation
2. **Implement REST API layer** - Proper data communication
3. **Create configuration system** - Filter-based settings
4. **Build component library** - Reusable Vue components
5. **Standardize asset loading** - Single Vue.js instance per page

## COMPREHENSIVE ARCHITECTURAL RESTRUCTURING PLAN

### Executive Summary

Based on comparison with WP Options Kit patterns and analysis of our current implementation, I've identified critical architectural gaps that need addressing. Our current mixed PHP/Vue approach violates WordPress and Vue.js best practices. This plan outlines a systematic restructuring to adopt proven patterns.

### Phase 1: Foundation Restructuring (CRITICAL - Do First)

#### 1.1 Implement Complete PHP/Vue Separation
**Current Problem**: PHP rendering mixed with Vue.js directives in admin pages
**Solution**: Adopt WP Options Kit pattern - PHP for static structure, Vue for dynamic content only

**Files to Restructure:**
- `class-typography-presets-vue-admin.php:140-157` - Remove Vue directives from PHP-rendered tabs
- `class-main-vue-admin.php` - Ensure clean separation
- `class-updates-vue-admin.php` - Apply same pattern

**New Pattern:**
```php
public function render_admin_page() {
    ?>
    <div class="wrap">
        <?php settings_errors(); ?>
        <div class="orbital-header"><!-- PHP static header --></div>
        <div class="orbital-notices"></div>
        <nav class="orbital-tabs"><!-- PHP static tabs --></nav>
        <div id="orbital-app"><!-- Vue dynamic content only --></div>
    </div>
    <?php
}
```

#### 1.2 Create REST API Layer
**Current Problem**: Using legacy AJAX handlers instead of proper REST API
**Solution**: Implement WordPress REST API endpoints for all admin functionality

**New Files to Create:**
- `includes/api/class-rest-controller.php` - Base REST controller
- `includes/api/class-settings-controller.php` - Settings endpoint
- `includes/api/class-presets-controller.php` - Typography presets endpoint

**Benefits:**
- Standardized data validation
- Proper authentication/authorization
- Future API extensibility
- Better error handling

#### 1.3 Standardize Asset Loading
**Current Problem**: Multiple Vue.js instances, inconsistent dependency management
**Solution**: Centralized asset management with proper dependency chains

**Changes Required:**
- `class-admin.php` - Central Vue.js loading
- Remove individual Vue.js loading from module admin classes
- Create asset dependency system

### Phase 2: Configuration System (MEDIUM Priority)

#### 2.1 Implement Filter-Based Configuration
**Current Problem**: Hardcoded admin page structures
**Solution**: WordPress filter-driven configuration system

**Pattern to Implement:**
```php
// Allow modules to register via filters
$admin_pages = apply_filters('orbital_admin_pages', array());
$settings_tabs = apply_filters('orbital_settings_tabs', array());
$form_fields = apply_filters('orbital_form_fields', array());
```

#### 2.2 Create Settings Schema System
**Current Problem**: Manual sanitization scattered across files
**Solution**: Centralized validation schema system

**New Structure:**
```php
class Orbital_Settings_Schema {
    public function get_schema($module) {
        return apply_filters("orbital_settings_schema_{$module}", array(
            'field_name' => array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => array($this, 'validate_field')
            )
        ));
    }
}
```

### Phase 3: Component Modernization (LOW Priority)

#### 3.1 Build Vue.js Component Library
**Current Problem**: Duplicated Vue.js code across admin pages
**Solution**: Reusable component library

**Components to Create:**
- `OrbitalFormField` - Standardized form fields
- `OrbitalModal` - Modal dialogs
- `OrbitalNotice` - Admin notices
- `OrbitalLoader` - Loading states
- `OrbitalTabs` - Tab navigation (Vue-controlled)

#### 3.2 Implement Modern Build System
**Current Problem**: Manual script concatenation
**Solution**: Modern build pipeline with hot reloading

### Critical Issues Identified in Current Code

#### 1. Typography Admin Page (URGENT FIX NEEDED)
**File**: `class-typography-presets-vue-admin.php:140-157`
**Issue**: Vue.js directives in PHP-rendered HTML
```php
// WRONG - Vue directives in PHP:
<button @click="activeTab = 'settings'" :class="['orbital-tab', { active: activeTab === 'settings' }]">
```

**Fix**: Move tab control to Vue.js component, render tabs statically in PHP

#### 2. Asset Loading Conflicts
**Files**: Multiple admin classes loading Vue.js independently
**Issue**: Potential script conflicts and dependency issues
**Fix**: Central asset loading in `class-admin.php`

#### 3. AJAX vs REST API Inconsistency
**Files**: `class-typography-presets-vue-admin.php:105-111`
**Issue**: Using legacy `wp_ajax_` handlers instead of REST API
**Fix**: Implement proper REST endpoints

#### 4. Settings Sanitization Scattered
**File**: `class-admin-pages.php:149-182`
**Issue**: Module-specific sanitization in generic admin pages class
**Fix**: Move to dedicated settings controller with schema validation

### Implementation Priority Order

1. **IMMEDIATE (This Week)**:
   - Fix Typography admin page PHP/Vue separation
   - Centralize Vue.js asset loading
   - Test admin page functionality

2. **SHORT TERM (Next 2 Weeks)**:
   - Implement REST API layer
   - Create settings schema system
   - Update all admin pages to use new patterns

3. **MEDIUM TERM (Next Month)**:
   - Build Vue.js component library
   - Implement filter-based configuration
   - Add comprehensive error handling

4. **LONG TERM (Future)**:
   - Modern build system
   - Performance optimization
   - Advanced caching

### Success Metrics

- **Architectural Compliance**: All admin pages follow PHP/Vue separation
- **Code Reusability**: Shared components reduce duplication by 60%+
- **Maintainability**: Configuration-driven system reduces hardcoded structures
- **Performance**: Single Vue.js instance per page, optimized asset loading
- **WordPress Standards**: Full compliance with WordPress coding standards

### Risk Mitigation

- **Backward Compatibility**: Maintain existing settings during transition
- **Testing**: Comprehensive testing of each restructured component
- **Rollback Plan**: Git branching for easy rollback if issues arise
- **Documentation**: Update CLAUDE.md with new patterns as implemented

This restructuring plan will transform our admin system from a problematic mixed-architecture to a clean, maintainable, WordPress-compliant solution following proven WP Options Kit patterns.
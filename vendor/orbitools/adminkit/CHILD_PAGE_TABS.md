# AdminKit Child Page Tab Integration

AdminKit now automatically discovers and integrates child pages (subpages) into the main tab navigation when you're on a top-level AdminKit page. This creates a seamless navigation experience where users can access both AdminKit tabs and external plugin pages from the same interface.

## Features

### Automatic Discovery
- **WordPress Menu Integration**: Automatically discovers subpages using WordPress `$submenu` global
- **Permission Aware**: Only shows pages the current user has permission to access
- **Top-Level Only**: Only works when the current AdminKit instance is a top-level page

### Visual Distinction
- **External Link Indicators**: Child page tabs show "↗" icon to indicate external navigation
- **Different Styling**: Italic text and gradient background to distinguish from regular tabs
- **Hover Effects**: Special hover states that highlight the external nature

### Smart Navigation
- **JavaScript Bypass**: Child page tabs bypass JavaScript tab switching
- **Direct Navigation**: Click child page tabs to navigate directly to those pages
- **Preserves Regular Tabs**: AdminKit tabs continue to work with JavaScript switching

## How It Works

### 1. Page Discovery Process

```php
// AdminKit automatically queries WordPress submenu structure
global $submenu;
$parent_slug = $this->admin_kit->get_slug(); // e.g., 'orbitools'

if (isset($submenu[$parent_slug])) {
    foreach ($submenu[$parent_slug] as $priority => $submenu_item) {
        // Skip parent page (priority 0)
        // Check user capabilities
        // Add to navigation
    }
}
```

### 2. Tab Generation

Child pages are automatically added to the tab navigation with:
- **Unique Keys**: Prefixed with `child_` to distinguish from regular tabs
- **Proper URLs**: Direct links to `admin.php?page=child_slug`
- **Special Attributes**: `data-child-page="true"` for JavaScript handling

### 3. JavaScript Handling

```javascript
// Child page tabs bypass JavaScript navigation
if (link.hasAttribute('data-child-page') && link.getAttribute('data-child-page') === 'true') {
    // Don't prevent default - let browser navigate
    return;
}
```

## Visual Examples

### Main AdminKit Page with Child Pages
```
[General] [Advanced] [ACF Settings ↗] [Custom Tools ↗]
  ^regular   ^regular    ^child page      ^child page
```

### Child Page Styling
- **Regular Tab**: Normal background, JavaScript switching
- **Child Tab**: Italic text, gradient background, external icon
- **Hover State**: Blue gradient with highlighted icon

## Requirements

### For Automatic Discovery to Work:
1. **Top-Level AdminKit Page**: Must be a top-level page, not a subpage
2. **WordPress Submenus**: Child pages must be registered using WordPress `add_submenu_page()`
3. **Proper Parent**: Child pages must have the AdminKit slug as their parent

### Example Child Page Registration:
```php
// This will be automatically discovered and added to AdminKit tabs
add_submenu_page(
    'orbitools',              // Parent slug (matches AdminKit slug)
    'ACF Settings',           // Page title
    'ACF Settings',           // Menu title
    'manage_options',         // Capability
    'acf-settings',           // Menu slug
    'acf_settings_callback'   // Callback function
);
```

## Testing

### Test 1: Verify Child Page Discovery
1. Add a child page to your AdminKit parent
2. Visit the main AdminKit page
3. Check if child page appears in tab navigation
4. Verify it has the external link icon (↗)

### Test 2: Test Navigation Behavior
1. Click regular AdminKit tabs - should switch without page reload
2. Click child page tabs - should navigate to new pages
3. Verify JavaScript doesn't interfere with child page navigation

### Test 3: Verify Conditional Behavior
1. Visit child page directly - should NOT show child page tabs
2. Visit main AdminKit page - should show child page tabs
3. Verify only top-level pages include child page tabs

## Customization

### Disable Child Page Integration
```php
// Filter to disable child page tab integration
add_filter('adminkit_include_child_page_tabs', '__return_false');
```

### Customize Child Page Appearance
```css
/* Modify child page tab styling */
.orbi-admin__tab-link--child-page {
    border-left-color: #your-color;
    background: your-gradient;
}

/* Change external link indicator */
.orbi-admin__tab-link--child-page:after {
    content: "→"; /* Different arrow */
    font-size: 12px;
}
```

### Filter Child Pages
```php
// Filter to modify which child pages are included
add_filter('adminkit_child_pages', function($child_pages, $admin_kit) {
    // Remove specific pages
    unset($child_pages['child_unwanted_page']);
    
    // Add custom pages
    $child_pages['child_custom'] = 'Custom Page';
    
    return $child_pages;
}, 10, 2);
```

## Benefits

1. **Unified Navigation**: Single interface for both AdminKit and external pages
2. **User Experience**: Intuitive navigation without leaving the AdminKit interface
3. **Visual Clarity**: Clear distinction between internal and external navigation
4. **Automatic**: No manual configuration required
5. **Flexible**: Works with any WordPress plugin that adds subpages

## Technical Details

### File Changes
- **Header_View.php**: Added child page discovery and rendering
- **admin-framework.js**: Enhanced tab handling for child pages
- **admin-framework.css**: Added child page styling
- **Instance Registry**: Integration for page detection

### Performance
- **Cached**: Child page data is cached within the request
- **Efficient**: Only queries WordPress globals, no database calls
- **Conditional**: Only runs on top-level AdminKit pages

### Security
- **Capability Checks**: Respects WordPress user capabilities
- **Sanitization**: All child page data is properly sanitized
- **Nonce Protection**: Maintains WordPress security standards

This feature creates a seamless bridge between AdminKit and other WordPress plugins, providing a unified admin experience while maintaining the distinct behaviors appropriate for each context.
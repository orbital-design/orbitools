# AdminKit Child Page Navigation

AdminKit now intelligently handles tab navigation based on page context. When AdminKit detects that it's running on a child page (like ACF pages under an AdminKit parent), it automatically switches from JavaScript-based tab switching to standard href navigation.

## How It Works

### Main AdminKit Pages
- **JavaScript Tab Switching**: Tabs switch content dynamically using JavaScript
- **URL Updates**: Browser history is updated without page reload
- **Smooth Experience**: Fast, single-page-app-like navigation

### Child Pages (ACF, Other Plugins)
- **Return Navigation**: Regular AdminKit tabs link back to main page with tab parameter
- **External Navigation**: Child page tabs link to other external pages  
- **Visual Indicators**: Left arrows (←) show return links, right arrows (↗) show external links
- **Page Reloads**: Each tab click navigates to a new page
- **Compatible**: Works with any plugin that adds pages under AdminKit

## Detection Logic

The system uses multiple detection methods:

1. **Instance Registry** (Primary): Checks `adminkit_page_info.is_child`
2. **URL Analysis** (Fallback): Compares page parameter with registered AdminKit slugs
3. **DOM Context** (Backup): Looks for AdminKit elements on non-AdminKit pages

## Visual Indicators

### Child Pages Get Special Styling:
- **Hover Effects**: Tab links show blue background on hover
- **Navigation Arrows**: Subtle "→" arrows appear on hover
- **Breadcrumb Labels**: Shows "(External)" indicator
- **Console Logging**: Logs "Child page detected" for debugging

## Navigation Flow Examples

### Complete Navigation Flow
```
Main AdminKit Page → Child Page → Back to Main Page
     ↓                   ↓              ↓
[General] [Advanced] [ACF ↗] → [← General] [← Advanced] [Other Child ↗] → [General] [Advanced] [ACF ↗]
JavaScript switching    →       Return navigation        →      JavaScript switching
```

### Scenario 1: OrbiTools Main Page
```
URL: /wp-admin/admin.php?page=orbitools&tab=modules
Behavior: JavaScript tab switching
Detection: adminkit_page_info.owner = 'orbitools', is_child = false
Tab Links: Regular AdminKit tabs + Child page tabs (with ↗ icons)
```

### Scenario 2: ACF Page Under OrbiTools
```
URL: /wp-admin/admin.php?page=acf-tools&parent=orbitools
Behavior: Return navigation (← arrows) + External navigation (↗ arrows)
Detection: adminkit_page_info.owner = null, is_child = true
Tab Links: Return to main page tabs + Other child page tabs
```

### Scenario 3: Navigation Examples
```
On Main Page:
- Click "General" → JavaScript switch to General tab
- Click "ACF Settings ↗" → Navigate to /admin.php?page=acf-settings

On Child Page:
- Click "← General" → Navigate to /admin.php?page=orbitools&tab=general
- Click "Other Tool ↗" → Navigate to /admin.php?page=other-tool
```

## Implementation Details

### JavaScript Changes

```javascript
// New method to detect child pages
isChildPage: function() {
    if (typeof adminkit_page_info !== 'undefined') {
        return adminkit_page_info.is_child;
    }
    // Fallback detection logic...
}

// Updated tab handler
handleTabSwitch: function(e, link) {
    if (this.isChildPage()) {
        // Don't prevent default - let browser navigate
        return;
    }
    // Normal JavaScript tab switching...
}
```

### PHP Changes

```php
// AdminKit passes page info to JavaScript
wp_localize_script('orbitools-adminkit', 'adminkit_page_info', array(
    'owner' => Instance_Registry::get_page_owner(),
    'is_child' => Instance_Registry::is_child_page(),
    'screen_id' => get_current_screen()->id
));
```

### CSS Enhancements

```css
/* Child page specific styles */
.orbi-admin--child-page .orbi-admin__tab-link:hover {
    background-color: #f0f4f8;
}

.orbi-admin--child-page .orbi-admin__tab-link:hover:after {
    content: "→";
    opacity: 1;
}
```

## Testing

### Test 1: Main AdminKit Page
1. Visit your main AdminKit page
2. Click tabs - should switch without page reload
3. Check console - no "child page" message
4. URLs should update without reload

### Test 2: Child Page (ACF Example)
1. Add ACF page as child of AdminKit
2. Visit the ACF page
3. Click AdminKit tabs - should navigate to new pages
4. Check console - should see "AdminKit: Child page detected"
5. Hover tabs - should see navigation arrows

## Benefits

1. **Seamless Integration**: Works with any plugin that adds child pages
2. **User Experience**: Appropriate navigation method for each context
3. **No Conflicts**: Prevents JavaScript interference with other plugins
4. **Visual Feedback**: Users understand when tabs will navigate vs switch
5. **Backward Compatible**: Existing AdminKit pages work exactly as before

## Debugging

### Console Messages
- `"AdminKit: Child page detected - tab navigation enabled"` - Child page mode active
- Check `adminkit_page_info` object in browser console for detection data

### CSS Classes
- `.orbi-admin--child-page` - Added to AdminKit wrapper on child pages
- Check for this class to verify detection is working

### JavaScript Variables
```javascript
// Check these in browser console
console.log(adminkit_page_info);
console.log(window.OrbitoolsAdminKit.isChildPage());
```

This system ensures that AdminKit's tab navigation works appropriately whether you're on a main AdminKit page or a child page from another plugin, providing the best user experience for each context.
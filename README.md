# Orbitools WordPress Plugin

A comprehensive WordPress plugin providing advanced layout blocks, responsive controls, and typography management for modern WordPress themes.

## 🚀 Features

### Layout Blocks System
- **Collection Block**: Container for organizing content layouts
- **Entry Block**: Individual content items with flexible sizing
- **Spacer Block**: Responsive spacing control with breakpoint support
- **Read More Block**: Collapsible content container with customizable toggle button

### Responsive Design System
- Breakpoint-based controls (sm: 810px, md: 1080px, lg: 1360px, xl: 1600px)
- Theme.json integration for consistent spacing values
- CSS utility class generation for responsive behavior

### Typography Presets
- Centralized typography management
- Theme-configurable font presets
- Gutenberg editor integration

### Security Features
- OWASP Top 10 compliance
- Package integrity verification with SHA256 checksums
- Comprehensive security logging system
- Input sanitization and capability checks

## 📁 Project Structure

```
orbitools/
├── src/blocks/                    # WordPress Gutenberg blocks
│   ├── collection/               # Collection container block
│   ├── entry/                   # Entry item block  
│   ├── spacer/                  # Responsive spacer block
│   ├── read-more/               # Collapsible content block
│   └── utils/                   # Shared utilities
│       ├── responsive-controls.tsx  # Responsive control system
│       └── config-reader.ts        # Configuration management
├── modules/                      # Plugin modules
│   ├── Layout_Blocks/           # Block registration
│   └── Typography_Presets/      # Typography system
├── config/                      # Configuration files
│   └── defaults.json            # Plugin default settings
├── build/                       # Compiled assets
└── webpack.*.js                 # Build configuration
```

## 🛠 Development

### Building the Plugin
```bash
# Install dependencies
npm install

# Build all assets
npm run build

# Build only blocks
npm run build:blocks

# Build only assets  
npm run build:assets
```

### Block Development
Each block follows WordPress standards:
- `block.json` - Block metadata and registration
- `index.tsx` - Block registration and imports
- `edit.tsx` - Editor interface
- `save.tsx` - Frontend output
- `*.scss` - Styling (editor and frontend)

### Configuration System
The plugin uses a hierarchical configuration system:
1. **Theme config**: `/wp-content/themes/[theme]/config/orbitools.json` (priority)
2. **Plugin defaults**: `/config/defaults.json` (fallback)

## 🎨 Block Usage

### Collection Block
Container for organizing layouts with different systems:
- **Row Layout**: Horizontal arrangement
- **Grid Layout**: 5-column or 12-column systems
- **Responsive**: Different layouts per breakpoint

### Entry Block  
Individual content items within Collections:
- **Column Width**: Custom sizing in grid layouts
- **Spacing Controls**: Theme-based gap sizing
- **Content**: Supports all WordPress blocks

### Spacer Block
Responsive spacing with theme integration:
- **Height Control**: Uses theme.json spacing values
- **Responsive**: Different heights per breakpoint  
- **Special Values**: Default, None, [Theme Sizes], Fill
- **Output**: Single `<div>` with height CSS classes

### Read More Block
Collapsible content container with smooth animations:
- **Button Text**: Customizable open/close text states
- **Icon Options**: None, Chevron, Arrow, or Plus icons
- **Animations**: Smooth slide transitions with proper accessibility
- **Spacing Controls**: Orbitools padding and gap controls on inner content
- **Accessibility**: Full ARIA support with proper attributes

## 🔧 Technical Details

### WordPress Integration
- **WordPress 6.0+**: Modern block editor features
- **Theme.json Support**: Automatic spacing and typography integration
- **PHP 8.0+**: Modern PHP features and performance

### Build System
- **Webpack**: Asset compilation and optimization
- **TypeScript**: Type-safe development
- **SCSS**: Advanced styling capabilities
- **WordPress Scripts**: Standard WordPress build tools

### Security
- **Nonce Verification**: All AJAX requests protected
- **Capability Checks**: Proper permission validation
- **Input Sanitization**: All user input cleaned
- **Package Verification**: SHA256 integrity checks for updates

### Customization Hooks

#### Read More Block Icon Customization
Filter to customize or add new icon types for the Read More block:

```php
// In your theme's functions.php or plugin
add_filter('orbitools/read_more/icons', function($icons) {
    // Override existing icons
    $icons['chevron'] = '<span class="custom-chevron">→</span>';
    
    // Add new icon types
    $icons['heart'] = '<span class="custom-heart">♥</span>';
    $icons['star'] = '<span class="custom-star">★</span>';
    
    // Use Font Awesome or other icon fonts
    $icons['arrow'] = '<i class="fas fa-arrow-down"></i>';
    
    return $icons;
});
```

**Icon Requirements:**
- Icons should be wrapped in a `<span>` with appropriate classes
- Use `currentColor` for SVG stroke/fill to inherit theme colors
- Icons will automatically rotate on toggle (add CSS transitions as needed)
- All HTML will be properly escaped for security

#### Query Loop Block Template System
The Query Loop block supports custom templates through a high-performance function-based system. Templates are registered via WordPress filters and called directly without file includes or output buffering.

##### Creating Custom Templates

**1. Define Template Functions**
Create template functions in your theme's `functions.php` or plugin:

```php
/**
 * Custom Query Loop Template Function
 * 
 * @param WP_Post $post The post object to render
 * @param string $layout_type Layout type ('grid', 'list', etc.)
 * @param string $columns Number of columns (for grid layouts)
 * @return string Rendered HTML for the post item
 */
function orbitools_query_loop_template_my_custom($post, $layout_type, $columns) {
    $html = '<article class="my-custom-template" data-post-id="' . $post->ID . '" data-template="my-custom">';
    
    // Featured image
    if (has_post_thumbnail($post->ID)) {
        $html .= '<div class="custom-image">';
        $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
        $html .= get_the_post_thumbnail($post->ID, 'medium');
        $html .= '</a>';
        $html .= '</div>';
    }
    
    // Content
    $html .= '<div class="custom-content">';
    $html .= '<h3><a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a></h3>';
    $html .= '<p>' . esc_html(wp_trim_words(get_the_excerpt($post->ID), 30)) . '</p>';
    $html .= '</div>';
    
    $html .= '</article>';
    
    return $html;
}
```

**2. Register Templates with OrbiTools**
Use the filter hook to make templates available in the editor:

```php
add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
    
    // Register template for grid layout only
    if (function_exists('orbitools_query_loop_template_my_custom')) {
        $templates['my-custom'] = [
            'label' => 'My Custom Template',
            'description' => 'Custom template with special styling',
            'callback' => 'orbitools_query_loop_template_my_custom',
            'layouts' => ['grid'] // Only available for grid layout
        ];
    }
    
    // Register template for all layout types
    if (function_exists('orbitools_query_loop_template_universal')) {
        $templates['universal'] = [
            'label' => 'Universal Template',
            'description' => 'Works with any layout type',
            'callback' => 'orbitools_query_loop_template_universal',
            'layouts' => ['grid', 'list'] // Available for both layouts
        ];
    }
    
    return $templates;
}, 10, 2);
```

##### Template Registration Parameters

**Required Parameters:**
- **`label`**: Display name shown in the editor dropdown
- **`description`**: Brief description of the template's purpose
- **`callback`**: Function name or callable that renders the template

**Optional Parameters:**
- **`layouts`**: Array of layout types this template supports (`['grid', 'list']`). If omitted, assumes all layouts are supported.

**Examples:**
```php
$templates['my-template'] = [
    'label' => 'My Template',
    'description' => 'Custom template description',
    'callback' => 'my_template_function_name',
    'layouts' => ['grid'] // Only show for grid layouts
];

// Function can be any name - no naming restrictions!
function my_template_function_name($post, $layout_type) {
    // Template implementation
}
```

##### Layout Filtering

Templates are automatically filtered based on the current layout type:
- Templates with `'layouts' => ['grid']` only appear in grid layout dropdowns
- Templates with `'layouts' => ['list']` only appear in list layout dropdowns  
- Templates with `'layouts' => ['grid', 'list']` appear in both
- Templates without `layouts` parameter appear in all layouts

##### Template Function Parameters

All template functions receive three parameters:

```php
function orbitools_query_loop_template_example($post, $layout_type, $columns) {
    // $post: WP_Post object - the current post being rendered
    // $layout_type: string - 'grid', 'list', etc.
    // $columns: string - number of columns for grid layouts ('2', '3', '4', '5')
}
```

**$post Object:**
- Access post data: `$post->ID`, `$post->post_title`, `$post->post_content`
- Use WordPress functions: `get_the_title($post->ID)`, `get_permalink($post->ID)`
- Check post meta: `get_post_meta($post->ID, 'key', true)`

**$layout_type String:**
- `'grid'` - Grid layout with columns
- `'list'` - List layout (single column)
- Custom layout types from theme/plugin extensions

**$columns String:**
- `'2'`, `'3'`, `'4'`, `'5'` - Number of columns for grid layouts
- Always provided, even for list layouts (defaults to '3')
- Use for responsive styling or conditional layout logic

##### Layout-Specific Templates

Restrict templates to specific layout types for better organization:

```php
add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
    
    // Grid-only template
    if ($layout_type === 'grid') {
        $templates['magazine-grid'] = [
            'label' => 'Magazine Grid',
            'description' => 'Magazine-style grid cards',
            'type' => 'function',
            'metadata' => [...]
        ];
    }
    
    // List-only template
    if ($layout_type === 'list') {
        $templates['timeline-list'] = [
            'label' => 'Timeline List',
            'description' => 'Timeline-style list items',
            'type' => 'function',
            'metadata' => [...]
        ];
    }
    
    return $templates;
}, 10, 2);
```

##### Advanced Template Features

**Conditional Styling:**
```php
function orbitools_query_loop_template_adaptive($post, $layout_type, $template_data) {
    $base_class = 'adaptive-template';
    $layout_class = $base_class . '--' . $layout_type;
    
    $html = '<article class="' . $base_class . ' ' . $layout_class . '">';
    
    // Different content based on layout
    if ($layout_type === 'grid') {
        // Compact grid content
        $html .= '<div class="grid-content">...</div>';
    } else {
        // Detailed list content
        $html .= '<div class="list-content">...</div>';
    }
    
    $html .= '</article>';
    return $html;
}
```

**Post Type Specific Logic:**
```php
function orbitools_query_loop_template_versatile($post, $layout_type, $template_data) {
    $post_type = get_post_type($post->ID);
    
    $html = '<article class="versatile-template versatile-template--' . $post_type . '">';
    
    switch ($post_type) {
        case 'post':
            // Blog post specific content
            $html .= '<div class="post-meta">By ' . get_the_author_meta('display_name', $post->post_author) . '</div>';
            break;
        case 'page':
            // Page specific content
            $html .= '<div class="page-type">Page</div>';
            break;
        case 'product':
            // Custom post type content
            $price = get_post_meta($post->ID, '_price', true);
            if ($price) {
                $html .= '<div class="product-price">$' . esc_html($price) . '</div>';
            }
            break;
    }
    
    // Common content for all post types
    $html .= '<h3><a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a></h3>';
    
    $html .= '</article>';
    return $html;
}
```

##### Template Security & Best Practices

**Always Escape Output:**
```php
// ✅ Correct - escaped output
$html .= '<h3>' . esc_html(get_the_title($post->ID)) . '</h3>';
$html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
$html .= '<img src="' . esc_attr($image_url) . '" alt="' . esc_attr($alt_text) . '">';

// ❌ Wrong - unescaped output (security risk)
$html .= '<h3>' . get_the_title($post->ID) . '</h3>';
```

**Performance Considerations:**
```php
// ✅ Good - minimal database queries
function orbitools_query_loop_template_efficient($post, $layout_type, $template_data) {
    // Use data already available in $post object
    $title = $post->post_title;
    $content = $post->post_content;
    
    // Batch meta queries if needed
    $meta_keys = ['key1', 'key2', 'key3'];
    $meta_values = get_post_meta($post->ID);
    
    return $html;
}

// ❌ Avoid - excessive function calls
function orbitools_query_loop_template_slow($post, $layout_type, $template_data) {
    // Each get_the_* function may trigger additional queries
    $title = get_the_title($post->ID);
    $excerpt = get_the_excerpt($post->ID);
    $date = get_the_date('', $post->ID);
    // ... multiple individual meta calls
}
```

**Error Handling:**
```php
function orbitools_query_loop_template_safe($post, $layout_type, $template_data) {
    // Validate post object
    if (!$post || !isset($post->ID)) {
        return '<article class="error">Invalid post data</article>';
    }
    
    // Check for required meta
    $featured_text = get_post_meta($post->ID, '_featured_text', true);
    if (!$featured_text) {
        $featured_text = 'No featured text available';
    }
    
    // Safe thumbnail check
    $thumbnail = '';
    if (has_post_thumbnail($post->ID)) {
        $thumbnail = get_the_post_thumbnail($post->ID, 'medium');
    }
    
    return $html;
}
```

##### Testing Templates

1. **Create and register** your template function
2. **Build the plugin**: `npm run build`
3. **Add Query Loop block** to a page/post
4. **Select your template** from the Results Settings dropdown
5. **Preview changes** in editor and frontend
6. **Test with different post types** and content variations

##### Complete Example: Magazine Template

```php
/**
 * Magazine-style template with image overlay
 */
function orbitools_query_loop_template_magazine_hero($post, $layout_type, $template_data) {
    $html = '<article class="magazine-hero" data-post-id="' . $post->ID . '" data-template="magazine-hero">';
    
    // Background image container
    if (has_post_thumbnail($post->ID)) {
        $image_url = get_the_post_thumbnail_url($post->ID, 'large');
        $html .= '<div class="magazine-hero__background" style="background-image: url(' . esc_url($image_url) . ');">';
    } else {
        $html .= '<div class="magazine-hero__background magazine-hero__background--no-image">';
    }
    
    // Overlay content
    $html .= '<div class="magazine-hero__overlay">';
    $html .= '<div class="magazine-hero__content">';
    
    // Category badge
    if (get_post_type($post->ID) === 'post') {
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $html .= '<span class="magazine-hero__category">' . esc_html($categories[0]->name) . '</span>';
        }
    }
    
    // Title
    $html .= '<h3 class="magazine-hero__title">';
    $html .= '<a href="' . esc_url(get_permalink($post->ID)) . '">';
    $html .= esc_html(get_the_title($post->ID));
    $html .= '</a>';
    $html .= '</h3>';
    
    // Excerpt
    $excerpt = wp_trim_words(get_the_excerpt($post->ID), 20);
    $html .= '<p class="magazine-hero__excerpt">' . esc_html($excerpt) . '</p>';
    
    // Meta information
    $html .= '<div class="magazine-hero__meta">';
    $html .= '<time class="magazine-hero__date">' . esc_html(get_the_date('M j, Y', $post->ID)) . '</time>';
    
    if (get_post_type($post->ID) === 'post') {
        $html .= '<span class="magazine-hero__author">by ' . esc_html(get_the_author_meta('display_name', $post->post_author)) . '</span>';
    }
    $html .= '</div>';
    
    $html .= '</div>'; // content
    $html .= '</div>'; // overlay
    $html .= '</div>'; // background
    $html .= '</article>';
    
    return $html;
}

// Register the template
add_filter('orbitools/query_loop/available_templates', function($templates, $layout_type) {
    if (function_exists('orbitools_query_loop_template_magazine_hero')) {
        $templates['magazine-hero'] = [
            'label' => 'Magazine Hero',
            'description' => 'Hero-style cards with image backgrounds and overlay text',
            'type' => 'function',
            'metadata' => [
                'Template Name' => 'Magazine Hero',
                'Description' => 'Perfect for featured content and blog posts',
                'Author' => 'Your Theme',
                'Version' => '1.0.0',
                'Supports' => ['featured-images', 'excerpts', 'categories', 'authors', 'dates']
            ]
        ];
    }
    return $templates;
}, 10, 2);
```

This template system provides maximum flexibility while maintaining excellent performance through direct function calls and proper WordPress integration.

## 📚 Development Notes

**Important**: See `CLAUDE.md` for detailed development guidelines, patterns, and lessons learned. This file contains crucial information about:

- Systematic API change procedures
- WordPress block development patterns  
- Common pitfalls and how to avoid them
- Quality assurance checklists
- Technical implementation details

### Key Development Rules
1. **Systematic Changes**: When fixing core APIs, update ALL instances across the codebase
2. **Consistent Patterns**: Use established patterns for `useSettings`, responsive controls, etc.
3. **Test Thoroughly**: Verify all related functionality, not just new features
4. **Clean Codebase**: Remove unused files and maintain consistent structure

## 🐛 Troubleshooting

### Common Issues
1. **Spacing Controls Missing**: Check `useSettings('spacing.spacingSizes')` format (string, not array)
2. **Build Errors**: Ensure all files are properly imported and TypeScript types are correct
3. **Block Not Showing**: Verify webpack config and PHP registration in `Layout_Blocks.php`

### Debug Steps
1. Check browser console for JavaScript errors
2. Verify build output in `/build/blocks/` directory
3. Confirm block registration in WordPress admin
4. Test with default theme to isolate theme conflicts

## 📄 License

This plugin is proprietary software developed for specific WordPress implementations.

---

*For detailed development guidelines and technical patterns, always refer to `CLAUDE.md` before making changes.*
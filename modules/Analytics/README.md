# Analytics Module

The Analytics module provides comprehensive Google Analytics tracking for WordPress websites with support for GA4 and Google Tag Manager, enhanced ecommerce, custom events, and privacy compliance features.

## Features

### 📊 **Analytics Platforms**
- **Google Analytics 4 (GA4)** - Modern, event-based analytics (Recommended)
- **Google Tag Manager (GTM)** - Centralized tag management with dataLayer integration

### 🛒 **Enhanced Ecommerce Tracking**
- Purchase event tracking
- Product view tracking
- Add to cart events
- Remove from cart events
- Begin checkout tracking
- Cart abandonment detection
- Configurable currency settings

### 🎯 **Custom Event Tracking**
- File download tracking (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, MP3, MP4, AVI, MOV)
- Outbound link click tracking
- Scroll depth tracking (25%, 50%, 75%, 100%)
- Form submission tracking (Contact Form 7, Gravity Forms, HTML forms)

### 🔒 **Privacy & Compliance**
- Google Consent Mode v2 support (enabled by default)
- Do Not Track header respect
- User role exclusion (administrators excluded by default)
- Privacy-first configuration defaults

### ⚡ **Performance Monitoring**
- Core Web Vitals tracking (Largest Contentful Paint)
- Real-time performance data in GA4
- Optional performance tracking (GA4 only)

### 🛠 **Advanced Features**
- Custom GA4 config parameters (JSON)
- User role exclusion settings
- WooCommerce integration
- Contact Form 7 integration
- Gravity Forms integration
- Enhanced dataLayer support for GTM

## Installation & Setup

### 1. Enable the Module

1. Navigate to **OrbiTools > Modules** in your WordPress admin
2. Find the **Analytics** module card
3. Click the toggle to enable the module
4. The Analytics section will appear in the modules tab

### 2. Basic Configuration

1. Go to **OrbiTools > Modules > Analytics**
2. Choose your **Analytics Type**:
   - **GA4** (Recommended)
   - **GTM** (For advanced tag management)
3. Enter your tracking ID:
   - **GA4**: `G-XXXXXXXXXX`
   - **GTM**: `GTM-XXXXXXX`

## Configuration Options

### Analytics Type Selection

#### Google Analytics 4 (GA4) - Recommended
```
Measurement ID: G-XXXXXXXXXX
```
- Modern event-based tracking
- Enhanced privacy controls
- Automatic enhanced measurement
- Cross-platform tracking
- Built-in conversion tracking

#### Google Tag Manager (GTM)
```
Container ID: GTM-XXXXXXX
```
- Centralized tag management
- Advanced tracking without code changes
- Multiple vendor support
- Custom event configuration
- A/B testing integration


### Privacy & Compliance Settings

#### Do Not Track Respect
- **Enabled by default**
- Honors browser DNT headers
- Prevents tracking when DNT=1
- Complete privacy respect

#### Consent Mode v2
- **Enabled by default**
- Google's privacy-friendly tracking
- Sets default deny state until consent granted
- Requires consent management platform integration
- Available for GA4 and GTM only

### Enhanced Ecommerce Configuration

#### Requirements
- WooCommerce plugin installed and active
- Valid analytics tracking ID configured

#### Tracked Events
- **Product Views**: `view_item`
- **Add to Cart**: `add_to_cart`
- **Remove from Cart**: `remove_from_cart`
- **Begin Checkout**: `begin_checkout`
- **Purchase**: `purchase`
- **Cart Abandonment**: Custom event

#### Currency Settings
```php
Default: Auto-detect from WooCommerce
Fallback: USD
Custom: Set your preferred currency code
```

### Custom Events Configuration

The module provides a unified **Custom Event Tracking** multi-checkbox field with the following options:

#### File Downloads
- **Files tracked**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR, MP3, MP4, AVI, MOV
- **GA4**: `file_download` event with file name and link text
- **GTM**: DataLayer push with event data

#### Outbound Links
- **Tracks**: Clicks on external websites
- **GA4**: `click` event with outbound category
- **GTM**: DataLayer push with event data

#### Scroll Depth
- **Tracks**: 25%, 50%, 75%, and 100% page scroll
- **GA4**: `scroll` event with engagement category
- **GTM**: DataLayer push with event data

#### Form Submissions
- **Compatible with**: Contact Form 7, Gravity Forms, HTML forms
- **GA4**: `form_submit` event with form identifier
- **GTM**: DataLayer push with event data

#### Performance Tracking (Advanced)
- **Tracks**: Core Web Vitals (Largest Contentful Paint)
- **GA4 only**: `timing_complete` event with LCP metrics
- **Use case**: SEO insights and performance monitoring

### Advanced Settings

#### User Role Exclusion
Prevent tracking for specific user roles:
- Administrators (default)
- Editors
- Authors
- Contributors
- Subscribers

**Use Cases:**
- Exclude internal traffic
- Filter out content creators
- Remove admin activity from analytics

#### Custom Config Parameters (GA4 Only)
Advanced GA4 configuration via JSON:
```json
{
  "debug_mode": true,
  "send_page_view": false,
  "custom_parameter_1": "value1"
}
```
- **Use cases**: Custom dimensions, debug mode, disable auto page views
- **Advanced users only**: Direct GA4 config object manipulation

## Implementation Details

### Code Structure
```
/modules/Analytics/
├── Analytics.php              # Main coordinator class
└── Admin/
    ├── Admin.php             # Module registration & admin integration
    ├── Settings.php          # Field configurations & admin structure  
    └── Settings_Helper.php   # Utility functions for settings
```

### Hook Integration

#### WordPress Hooks
- `wp_head` - Analytics tracking code (priority 1)
- `wp_body_open` - GTM noscript fallback
- `wp_footer` - Custom events & performance tracking (priority 99)

#### WooCommerce Hooks
- `woocommerce_single_product_summary` - Product view tracking (priority 5)
- `woocommerce_thankyou` - Purchase tracking
- `woocommerce_before_checkout_form` - Begin checkout tracking

### Performance Considerations

#### Optimized Loading
- Async script loading
- Conditional script inclusion
- Cached field value evaluation
- Minimal DOM queries

#### Core Web Vitals
- Largest Contentful Paint (LCP) tracking
- First Input Delay (FID) tracking
- Cumulative Layout Shift (CLS) ready

## Troubleshooting

### Common Issues

#### Analytics Not Tracking
1. **Check Module Status**: Ensure Analytics module is enabled
2. **Verify Tracking ID**: Confirm correct format (G-, GTM-, UA-)
3. **Check User Role**: Admin users excluded by default
4. **Browser Settings**: Disable ad blockers for testing
5. **Do Not Track**: Check if DNT is enabled in browser

#### Ecommerce Not Working
1. **WooCommerce Active**: Ensure WooCommerce is installed and active
2. **Ecommerce Enabled**: Check "Enable Enhanced Ecommerce" setting
3. **Currency Configuration**: Verify currency code is correct
4. **Product Data**: Ensure products have proper SKUs and categories

#### Custom Events Not Firing
1. **Settings Check**: Verify specific event tracking is enabled
2. **Console Errors**: Check browser console for JavaScript errors
3. **gtag Availability**: Ensure Google Analytics script loads correctly
4. **Element Selectors**: Verify DOM elements exist for tracking

### Debug Mode

When `WP_DEBUG` is enabled and user has `manage_options` capability:

```javascript
// Console output includes:
console.log('OrbiTools Analytics Debug Mode');
console.log('Analytics Type:', 'ga4');
console.log('Tracking ID:', 'G-XXXXXXXXXX');

// All gtag calls are logged:
console.log('Analytics Event:', arguments);
```

### Testing Analytics Implementation

#### Real-Time Reports
1. Open Google Analytics
2. Navigate to **Real-time** reports
3. Browse your website in another tab
4. Verify events appear in real-time

#### Google Analytics Debugger
Install browser extension for detailed tracking information:
- **GA Debugger** (Chrome/Firefox)
- **Google Analytics Debugger** 
- View detailed event data in console

#### Google Tag Assistant
Use Google's official debugging tool:
1. Install **Tag Assistant Legacy** extension
2. Enable recording
3. Browse your website
4. Review tag firing and data

## Privacy & Compliance

### GDPR Compliance
- IP anonymization enabled by default
- Consent Mode v2 support
- Do Not Track respect
- User role exclusion options
- Minimal data collection approach

### CCPA Compliance
- User privacy controls
- Data minimization practices
- Transparent tracking disclosure
- Opt-out mechanisms via DNT

### Best Practices
1. **Cookie Notice**: Implement cookie consent banner
2. **Privacy Policy**: Disclose analytics usage
3. **Data Retention**: Configure appropriate retention periods
4. **User Rights**: Provide opt-out mechanisms
5. **Regular Audits**: Review tracking implementation

## API Reference

### Settings Helper Methods

```php
// Check if module is enabled
Settings_Helper::is_module_enabled(): bool

// Get analytics setting
Settings_Helper::get_analytics_setting(string $key, $default = ''): mixed

// Check if tracking should be enabled
Settings_Helper::should_track(): bool

// Normalize boolean values
Settings_Helper::normalize_boolean($value): bool
```

### Main Analytics Methods

```php
// Tracking code rendering
render_head_tracking(): void
render_body_tracking(): void  
render_footer_tracking(): void

// Ecommerce tracking
track_purchase(int $order_id): void
track_product_view(): void
render_add_to_cart_tracking(): void
track_begin_checkout(): void

// Utility methods
should_track(): bool
get_setting(string $key, $default = ''): mixed
get_product_category($product): string
```

## Support & Contributing

### Documentation
- [Google Analytics 4 Documentation](https://developers.google.com/analytics/devguides/collection/ga4)
- [Google Tag Manager Documentation](https://developers.google.com/tag-manager)
- [Google Consent Mode Documentation](https://developers.google.com/tag-manager/consent)

### Issues & Feature Requests
For bug reports and feature requests, please use the OrbiTools GitHub repository.

### Version History
- **v1.0.0** - Initial release with GA4, GTM, UA support
- Enhanced ecommerce tracking
- Custom events system
- Privacy compliance features
- WooCommerce integration

---

*This module is part of the OrbiTools plugin ecosystem, providing modular WordPress functionality with a focus on performance, privacy, and user experience.*
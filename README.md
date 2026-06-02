# MyProtector.org Review Platform

A comprehensive WordPress review platform plugin (Trustpilot-style) built with custom post types, allowing users to browse businesses, submit reviews, rate businesses, and search/filter listings.

## Features

### Custom Post Types
- **Business**: Company listings with detailed information
- **Review**: User-submitted reviews with ratings

### Business Fields
- Company Name
- Website URL
- Phone
- Email
- Address
- Category (Custom Taxonomy)
- Logo (Featured Image)
- Description
- Average Rating (Auto-calculated)
- Total Reviews (Auto-calculated)

### Review Fields
- Review Title
- Review Content
- Rating (1-5 Stars)
- Reviewer Name
- Reviewer Email
- Submission Date
- Approval Status (Pending/Approved/Rejected)

### Frontend Features
- Business Directory Page (`/businesses/`)
- Business Detail Page
- Review Submission Form
- Search Businesses
- Filter by Category
- Filter by Rating
- Pagination

### User Features
- User Registration
- User Login
- User Profile
- My Reviews Page

### Moderation Features
- Reviews submitted as pending
- Admin approval workflow
- Bulk approve/reject actions
- Spam protection (Honeypot + Rate limiting)
- Email notification to admin when review submitted

### SEO Features
- SEO-friendly URLs (`/business/business-name/`)
- Schema.org markup (LocalBusiness + Reviews)
- OpenGraph meta tags
- Twitter Card support
- Custom document titles

### Security Features
- Sanitize all inputs
- Escape all outputs
- Nonce verification
- Capability checks
- Prepared SQL statements
- Rate limiting

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Installation Steps

1. **Download the Plugin**
   ```
   Download the mpr-reviews folder from this repository
   ```

2. **Upload to WordPress**
   - Upload the `mpr-reviews` folder to `/wp-content/plugins/`
   - Or use WordPress Admin: Plugins > Add New > Upload Plugin

3. **Activate the Plugin**
   - Go to WordPress Admin > Plugins
   - Find "MPR Reviews" and click "Activate"

4. **Configure Settings**
   - Go to MPR Reviews > Settings
   - Configure:
     - Reviews per page
     - Require registration for reviews
     - Admin notification email

5. **Add Businesses**
   - Go to MPR Reviews > Add New Business
   - Fill in business details
   - Upload logo
   - Assign category

6. **Configure Permalinks**
   - Go to Settings > Permalinks
   - Select "Post name" option
   - Save changes

## Shortcodes

Use these shortcodes in any page:

### Business Directory
```
[mpr_business_directory per_page="10" columns="2" show_search="true" show_filters="true"]
```

### Single Business
```
[mpr_business id="123"]
```

### Review Form
```
[mpr_review_form business_id="123"]
```

### Search Box
```
[mpr_search placeholder="Search businesses..."]
```

### Featured Businesses
```
[mpr_featured_businesses count="4"]
```

### Login Form
```
[mpr_login_form]
```

### Registration Form
```
[mpr_register_form]
```

### User Profile
```
[mpr_user_profile]
```

### My Reviews
```
[mpr_my_reviews]
```

## Template Files

Copy these templates to your theme to customize appearance:

- `templates/archive-mpr_business.php` - Business listing page
- `templates/single-mpr_business.php` - Single business page

Or create in your theme:
- `single-mpr_business.php`
- `archive-mpr_business.php`

## Theme Functions (Optional)

Add to your theme's `functions.php` for customizations:

```php
// Customize number of reviews per page
add_filter('mpr_reviews_per_page', function() {
    return 20;
});

// Add custom business meta field
add_filter('mpr_business_meta_fields', function($fields) {
    $fields['custom_field'] = 'Custom Value';
    return $fields;
});

// Modify review form validation
add_filter('mpr_review_validation', function($errors, $data) {
    // Add custom validation
    return $errors;
});
```

## Admin Dashboard

The plugin adds a custom admin dashboard under "MPR Reviews" menu:
- Dashboard: Overview statistics
- Businesses: Manage business listings
- Reviews: Approve/reject reviews
- Categories: Manage business categories
- Settings: Plugin configuration

## Database

The plugin creates one additional database table:
- `{prefix}mpr_rating_logs` - Stores helpful votes on reviews

## Security Measures

1. **Input Sanitization**
   - All form inputs are sanitized using appropriate WordPress functions
   - Email addresses: `sanitize_email()`
   - URLs: `esc_url_raw()`
   - Text: `sanitize_text_field()` / `sanitize_textarea_field()`

2. **Output Escaping**
   - All output uses `esc_html()`, `esc_attr()`, `esc_url()`, `esc_textarea()`
   - Content uses `wp_kses()` for allowed HTML

3. **Nonce Verification**
   - All forms use `wp_nonce_field()`
   - All AJAX calls use `check_ajax_referer()`

4. **SQL Injection Prevention**
   - All database queries use `$wpdb->prepare()`
   - Meta queries use `WP_Query` with proper arguments

5. **Capability Checks**
   - Admin functions check `current_user_can()`
   - User functions verify authentication

6. **Spam Protection**
   - Honeypot field (hidden from users, bots fill it)
   - Rate limiting (5 submissions per minute per IP)
   - Content spam detection patterns

## Uninstall

When uninstalling the plugin:
- All plugin options are removed
- Custom database tables are dropped
- Optionally removes all businesses, reviews, and categories (configurable)

## Support

For issues or feature requests, please contact MyProtector.org support.

## Version History

### 1.0.0
- Initial release
- Custom post types (Business, Review)
- Review submission and moderation
- Admin dashboard
- SEO features
- User authentication
- Shortcodes for all major features

---

## File Structure

```
mpr-reviews/
├── mpr-reviews.php                    # Main plugin entry point
├── uninstall.php                      # Cleanup on uninstall
├── includes/
│   ├── class-mpr-post-types.php       # Custom post types & taxonomies
│   ├── class-mpr-meta-fields.php      # Meta boxes & custom fields
│   ├── class-mpr-shortcodes.php        # Frontend shortcodes
│   ├── class-mpr-ajax.php             # AJAX handlers
│   ├── class-mpr-security.php         # Security utilities
│   ├── class-mpr-seo.php               # SEO & schema markup
│   ├── class-mpr-user.php              # User management
│   └── class-mpr-database.php          # Database utilities
├── admin/
│   └── class-mpr-admin.php             # Admin functionality
├── frontend/
│   └── class-mpr-frontend.php          # Frontend templates
├── templates/
│   ├── single-mpr_business.php         # Single business template
│   └── archive-mpr_business.php        # Business listing template
├── assets/
│   ├── css/
│   │   ├── mpr-styles.css              # Frontend styles
│   │   └── mpr-admin.css                # Admin styles
│   └── js/
│       ├── mpr-scripts.js              # Frontend JavaScript
│       └── mpr-admin.js                 # Admin JavaScript
└── languages/
    └── mpr-reviews.pot                  # Translation template
```
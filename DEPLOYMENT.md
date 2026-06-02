# MPR Reviews - Installation and Deployment Guide

## Overview

This guide provides step-by-step instructions for installing and deploying the MPR Reviews WordPress plugin on a production server.

## Prerequisites

Before installation, ensure your server meets the following requirements:

### Server Requirements
- **Web Server**: Apache (mod_rewrite) or Nginx
- **PHP Version**: 7.4 or higher (8.0+ recommended)
- **MySQL Version**: 5.6 or higher (8.0+ recommended) or MariaDB 10.1+
- **WordPress Version**: 5.0 or higher
- **Memory Limit**: Minimum 256M (512M recommended)
- **SSL**: HTTPS required for secure operations

### Browser Requirements
- Modern browsers: Chrome, Firefox, Safari, Edge
- JavaScript enabled for AJAX functionality

---

## Installation Methods

### Method 1: Via WordPress Admin (Recommended for Single Sites)

1. **Download the Plugin**
   - Download the `mpr-reviews` folder as a ZIP file
   - Or clone from repository: `git clone [repository-url]`

2. **Upload via Admin Panel**
   - Log in to WordPress Admin
   - Navigate to: Plugins > Add New > Upload Plugin
   - Click "Choose File" and select the `mpr-reviews.zip`
   - Click "Install Now"

3. **Activate**
   - Click "Activate Plugin"
   - A success message should appear

4. **Complete Setup**
   - Proceed to "Post-Installation Configuration" section below

---

### Method 2: Via FTP/SFTP

1. **Upload Files**
   ```bash
   # Connect via FTP/SFTP
   # Upload the mpr-reviews folder to:
   /wp-content/plugins/mpr-reviews/
   ```

2. **Set Permissions**
   ```bash
   # If needed, set appropriate permissions
   chmod 755 /wp-content/plugins/mpr-reviews/
   chmod 644 /wp-content/plugins/mpr-reviews/*.php
   ```

3. **Activate via WordPress Admin**
   - Go to Plugins
   - Find "MPR Reviews" and click "Activate"

---

### Method 3: Via WP-CLI (Command Line)

```bash
# Download plugin
wp plugin install /path/to/mpr-reviews.zip

# Or clone from Git
wp plugin install https://github.com/myprotector/mpr-reviews.zip

# Activate
wp plugin activate mpr-reviews

# Flush rewrite rules
wp rewrite flush
```

---

## Post-Installation Configuration

### Step 1: Configure Permalinks

1. Go to Settings > Permalinks
2. Select "Post name" option
3. Click "Save Changes"

**Why?** The plugin uses custom rewrite rules for SEO-friendly URLs.

### Step 2: Plugin Settings

1. Navigate to: MPR Reviews > Settings
2. Configure the following:

#### General Settings
- **Reviews Per Page**: Default 10 (adjust based on your needs)
- **Require Registration**: Enable if you want users to log in before reviewing

#### Email Notifications
- **Admin Notifications**: Enable to receive emails for new reviews
- **Admin Email**: Set the email address for notifications

3. Click "Save Changes"

### Step 3: Create Required Pages

Create the following pages with their shortcodes:

#### Businesses Directory Page
```markdown
Title: Businesses
Slug: businesses
Content: [mpr_business_directory]
```

#### Login Page
```markdown
Title: Login
Slug: login
Content: [mpr_login_form]
```

#### Registration Page
```markdown
Title: Register
Slug: register
Content: [mpr_register_form]
```

#### User Profile Page
```markdown
Title: Profile
Slug: profile
Content: [mpr_user_profile]
```

#### My Reviews Page
```markdown
Title: My Reviews
Slug: my-reviews
Content: [mpr_my_reviews]
```

### Step 4: Configure Menu

1. Go to Appearance > Menus
2. Add links to your pages:
   - Businesses -> `/businesses/`
   - Login/Register (conditional based on user state)
   - My Account -> `/profile/`

---

## Initial Business Setup

### Adding Businesses

1. Navigate to: MPR Reviews > Add New Business
2. Fill in the details:
   - **Title**: Business name (required)
   - **Description**: Business overview
   - **Featured Image**: Business logo
   - **Excerpt**: Short description for listings

3. In the "Business Details" meta box:
   - **Website**: Full URL (https://...)
   - **Phone**: Contact number
   - **Email**: Contact email
   - **Address**: Full address
   - **Category**: Select appropriate category

4. Click "Publish"

### Creating Categories

1. Navigate to: MPR Reviews > Categories
2. Click "Add New Category"
3. Enter:
   - **Name**: e.g., "Restaurants", "Retail"
   - **Slug**: Auto-generated, edit if needed
   - **Description**: Optional

4. Click "Add New Category"

---

## Deployment Checklist

### Pre-Deployment

- [ ] Test plugin on staging environment
- [ ] Verify all functionality works
- [ ] Check for PHP errors in debug mode
- [ ] Test on mobile devices

### Server Configuration

#### Apache (.htaccess)
Ensure mod_rewrite is enabled:
```apache
# WordPress .htaccess should contain:
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
```

#### Nginx Configuration
```nginx
# Add to your server block:
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

### Security Checklist

- [ ] SSL certificate installed and working
- [ ] WordPress security plugins configured
- [ ] File permissions set correctly (755 for dirs, 644 for files)
- [ ] Database credentials secure
- [ ] Admin account uses strong password
- [ ] Two-factor authentication enabled for admin

### Performance Checklist

- [ ] Enable caching (W3 Total Cache, WP Super Cache, etc.)
- [ ] Image optimization (use WebP format)
- [ ] Minify CSS/JS files
- [ ] Enable GZIP compression
- [ ] Consider CDN for static assets
- [ ] Database optimized (regular cleanup)

---

## Troubleshooting

### Issue: Plugin not activating

**Solution:**
1. Check PHP version (requires 7.4+)
2. Verify WordPress version (requires 5.0+)
3. Check for conflicting plugins
4. Review PHP error logs

### Issue: 404 on business pages

**Solution:**
1. Go to Settings > Permalinks
2. Select "Post name" option
3. Click "Save Changes" twice
4. If still not working, try "Plain" then back to "Post name"

### Issue: Reviews not showing

**Solution:**
1. Check if reviews are published (not pending)
2. Verify business ID is correct in review
3. Clear any caching plugins

### Issue: Email notifications not working

**Solution:**
1. Configure SMTP plugin (WPMail SMTP, WP Mail SMTP)
2. Check spam folder
3. Verify admin email in settings

### Issue: Styling conflicts

**Solution:**
1. Check for theme CSS overrides
2. Add custom CSS to fix conflicts
3. Use !important sparingly

---

## Maintenance

### Regular Tasks

1. **Review Moderation**
   - Check pending reviews daily
   - Approve or reject within 48 hours

2. **Database Cleanup**
   - Run monthly: Delete spam reviews
   - Clear transients if using caching

3. **Plugin Updates**
   - Check for updates monthly
   - Test updates on staging first

4. **Backup**
   - Daily database backups
   - Weekly full backups
   - Store backups off-site

### Monitoring

Set up monitoring for:
- Website uptime
- Page load speed
- Error rates
- 404 errors
- Plugin conflicts

---

## Support

For technical support:
- **Email**: support@myprotector.org
- **Documentation**: [Online Docs]
- **Forum**: [Community Forum]

---

## Development Reference

### File Structure
```
mpr-reviews/
├── mpr-reviews.php          # Main plugin file
├── uninstall.php            # Cleanup on uninstall
├── includes/
│   ├── class-mpr-post-types.php
│   ├── class-mpr-meta-fields.php
│   ├── class-mpr-shortcodes.php
│   ├── class-mpr-ajax.php
│   ├── class-mpr-security.php
│   ├── class-mpr-seo.php
│   ├── class-mpr-user.php
│   └── class-mpr-database.php
├── admin/
│   └── class-mpr-admin.php
├── frontend/
│   └── class-mpr-frontend.php
├── templates/
│   ├── single-mpr_business.php
│   └── archive-mpr_business.php
├── assets/
│   ├── css/
│   │   ├── mpr-styles.css
│   │   └── mpr-admin.css
│   └── js/
│       ├── mpr-scripts.js
│       └── mpr-admin.js
└── languages/
    └── mpr-reviews.pot
```

### Database Tables

| Table | Purpose |
|-------|---------|
| `{prefix}posts` | Stores businesses and reviews |
| `{prefix}postmeta` | Stores custom fields |
| `{prefix}terms` | Stores categories and tags |
| `{prefix}term_relationships` | Links businesses to categories |
| `{prefix}mpr_rating_logs` | Stores helpful votes |

### Custom Post Types

| Type | Slug | Has Archive |
|------|------|-------------|
| mpr_business | /business/ | /businesses/ |
| mpr_review | /review/ | No |

### Taxonomies

| Taxonomy | Object Type |
|----------|-------------|
| mpr_business_category | mpr_business |
| mpr_review_tag | mpr_review |

---

*Version 1.0.0 | Last Updated: June 2026*

=== Airtable Bonds Manager ===
Contributors: yourname
Tags: airtable, bonds, finance, database, integration
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress plugin for managing bonds with Airtable integration. Allows users to submit email addresses and view their bonds.

== Description ==

The Airtable Bonds Manager plugin integrates WordPress with Airtable to provide a seamless bond management system. Users can submit their email addresses to request access to their bonds, and view their bond information through a responsive interface.

**Key Features:**

* **Email Submission**: Users can submit their email address to request access to their bonds
* **Airtable Integration**: Seamlessly integrates with Airtable API to create AccessRequest records
* **Bond Display**: Shows user's bonds in a responsive, filterable interface
* **Local Database Mirror**: Creates local WordPress database tables that mirror Airtable structure
* **Debug Panels**: Built-in debug functionality for troubleshooting
* **Responsive Design**: Mobile-friendly interface with loading spinners and smooth animations
* **AJAX-Powered**: Background data loading for better user experience
* **Search and Filter**: Users can search and filter their bonds by status, type, and other criteria
* **Security**: Proper WordPress security practices with nonces and data sanitization

**Use Case:**
Perfect for insurance agencies, surety companies, or any business that needs to provide clients with secure access to view their bond information stored in Airtable.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/airtable-bonds` directory, or install the plugin through the WordPress plugins screen directly.

2. Activate the plugin through the 'Plugins' screen in WordPress.

3. Configure your Airtable API settings:
   - Go to WordPress Admin > Settings > Airtable Bonds
   - Enter your Airtable API Key
   - Enter your Airtable Base ID

4. Use the shortcodes to display the forms:
   - `[airtable_email_form]` for the email submission form
   - `[airtable_bonds_display uid="XXXX"]` for displaying bonds (where XXXX is the AccessRequest record ID)

5. Set up URL rewriting (optional):
   - The plugin automatically creates clean URLs like `/bonds/XXXX`
   - Make sure your permalink structure is set to something other than "Plain"

== Usage ==

**Email Form:**
Add the email submission form to any page or post using:
```
[airtable_email_form]
```

Add debug mode:
```
[airtable_email_form debug="true"]
```

**Bonds Display:**
Display bonds for a specific user using:
```
[airtable_bonds_display uid="XXXX"]
```

Where XXXX is the AccessRequest record ID (without the "rec" prefix).

**URL Access:**
Users can also access their bonds directly via URL:
```
https://yoursite.com/bonds/XXXX
```

**Debug Mode:**
Add `?debug=1` to any page URL to enable debug panels for troubleshooting.

== Database Schema ==

The plugin creates the following database tables:

* **wp_airtable_entity** - Mirrors the Entity table from Airtable (companies, people, etc.)
* **wp_airtable_access_request** - Mirrors the AccessRequest table from Airtable
* **wp_airtable_activity** - Mirrors the Activity table from Airtable (bonds and transactions)
* **wp_airtable_docgen** - Mirrors the DocGen table from Airtable

These tables are automatically created on plugin activation and serve as a local cache/mirror of your Airtable data.

== Configuration ==

**Airtable Settings:**

1. Get your Airtable API Key:
   - Go to https://airtable.com/account
   - Generate a personal access token
   - Copy the token

2. Get your Base ID:
   - Open your Airtable base
   - Go to Help > API documentation
   - Copy the Base ID (starts with "app")

3. Enter these in WordPress Admin > Settings > Airtable Bonds

**Required Airtable Structure:**
Your Airtable base must have tables with the following names and key fields:

* **Entity** - Contains companies, people, contacts
* **AccessRequest** - Created when users submit email addresses
* **Activity** - Contains bond information and transactions
* **DocGen** - Document generation tracking

== Frequently Asked Questions ==

= How do I get my Airtable API credentials? =

1. Go to https://airtable.com/account
2. Under "Personal access tokens", click "Generate new token"
3. Give it appropriate permissions for your base
4. Copy the generated token

For the Base ID, open your Airtable base, go to Help > API documentation, and copy the Base ID.

= What happens when a user submits their email? =

1. The plugin searches for an Entity record with that email address
2. If found, it creates an AccessRequest record in Airtable
3. The user receives the unique access ID that can be used to view their bonds
4. Airtable automation can be set up to send an email with the access link

= How do I customize the appearance? =

The plugin includes comprehensive CSS that can be overridden in your theme. All styles are prefixed with `.airtable-bonds-` for easy customization.

= Can I modify the database structure? =

The plugin creates tables based on your Airtable schema. If you modify your Airtable structure, you may need to deactivate and reactivate the plugin to recreate the tables.

= Is the data synchronized in real-time? =

The plugin creates local copies of data for performance. Data is fetched from Airtable when needed, but you may want to set up periodic synchronization for high-traffic sites.

== Changelog ==

= 1.0.0 =
* Initial release
* Email submission functionality
* Bonds display with search and filtering
* Airtable API integration
* Local database mirroring
* Responsive design
* Debug panels
* Security features

== Upgrade Notice ==

= 1.0.0 =
Initial release of the Airtable Bonds Manager plugin.

== Development ==

**File Structure:**
```
airtable-bonds/
├── airtable-bonds.php          # Main plugin file
├── includes/
│   ├── class-database.php      # Database operations
│   ├── class-airtable.php      # Airtable API wrapper
│   └── class-ajax.php          # AJAX handlers
├── templates/
│   ├── email-form.php          # Email submission form
│   └── bonds-display.php       # Bonds display template
├── assets/
│   ├── css/
│   │   └── style.css           # Plugin styles
│   └── js/
│       └── main.js             # JavaScript functionality
└── README.txt                  # This file
```

**Security Features:**
* WordPress nonces for AJAX requests
* Data sanitization and validation
* Prepared SQL statements
* Permission checks
* HTML escaping for output

**Performance Features:**
* Local database caching
* AJAX-powered loading
* Debounced search input
* Optimized database queries
* Minimal external API calls

== Support ==

For support, feature requests, or bug reports, please contact the developer or submit issues through your preferred channel.

**Requirements:**
* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher
* Valid Airtable account and API access
* Properly structured Airtable base

== License ==

This plugin is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

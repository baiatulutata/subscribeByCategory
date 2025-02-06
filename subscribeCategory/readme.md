# Category-Based Email Subscription Plugin for WordPress

This plugin allows users to subscribe to email lists based on the category of a post or page.  It provides a settings page to configure the mapping between categories and email list IDs, a toggle to activate/deactivate the plugin, a way to customize CSS, and a shortcode to display the subscription form anywhere on your site.  It also stores subscription data in a database table and provides an export to CSV feature.

## Installation

1.  Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2.  Activate the plugin through the 'Plugins' screen in WordPress.

## Usage

### 1. Configuration

1.  After activating the plugin, a new menu item called "Category Email Subscription" will appear in your WordPress admin menu.
2.  Click on "Category Email Subscription" -> "Category Email Subscription" to access the plugin settings.

### 2. Email List Mapping

1.  On the settings page, you'll see a table listing all your categories.
2.  For each category, enter the corresponding ID of your email list in the "Email List ID" column.  This ID is specific to your email marketing service (Mailchimp, Constant Contact, etc.).  Consult your email service's documentation to find the correct list IDs.

### 3. Targeting Categories

1.  Check the boxes in the "Targeted" column next to the categories where you want the subscription form to appear.  If a category isn't targeted, the form won't be displayed for posts in that category.

### 4. Custom CSS

1.  Click on "Category Email Subscription" -> "CSS Customization" to add custom CSS styles.
2.  Enter your CSS code in the textarea provided.  This allows you to customize the appearance of the subscription form and other plugin elements.  **Important:** Be careful when adding custom CSS, and ensure that it is valid to prevent any layout issues.

### 5. Activation/Deactivation

1.  On the main settings page ("Category Email Subscription" -> "Category Email Subscription"), you'll find a toggle switch labeled "Plugin Activation".
2.  Use this toggle to activate or deactivate the plugin.  When deactivated, the subscription form will not be displayed on your site.

### 6. Displaying the Subscription Form (Shortcode)

The plugin provides a shortcode `[cbes_subscription_form]` to display the subscription form anywhere on your website.

*   **On a Single Post Page:** When used on a single post page, the form will automatically subscribe users to the email list associated with that post's category.  Simply use `[cbes_subscription_form]` in your post content.
*   **Specifying a Category:** You can specify a category ID or name using the `category_id` or `category_name` attributes:
    *   `[cbes_subscription_form category_id="123"]` (Replace `123` with the actual category ID).
    *   `[cbes_subscription_form category_name="Category Name"]` (Replace "Category Name" with the actual category name).
*   **Outside of a Post:** If you want to display the form on a page that isn't a single post and you are not providing a category id or name, a message will be displayed indicating that a category must be specified.

### 7. Viewing and Exporting Subscriptions

1.  Click on "Category Email Subscription" -> "Subscribers Data" to view a list of all subscribers.
2.  On this page, you'll see a table with the subscription data (email, category, subscription date).
3.  You can also export this data to a CSV file by clicking the "Export to CSV" button.

## Database Table

The plugin creates a database table named `wp_cbes_subscriptions` to store the subscription data.  This table includes the following columns:

*   `id`: Unique ID of the subscription.
*   `email`: Subscriber's email address.
*   `category_id`: ID of the category they subscribed to.
*   `subscribed_at`: Date and time of subscription.

## Troubleshooting

*   **Form Not Displaying:** Make sure the plugin is activated and the category is targeted in the settings.  Also, double-check that you are using the shortcode correctly or are on a single post page. Ensure that you have configured the email list id correctly.
*   **Error Messages:** If you encounter any error messages, check your WordPress debug log for more details.
*   **CSS Issues:** If you're having trouble with custom CSS, use your browser's developer tools to inspect the elements and identify the correct CSS selectors.

## Support

If you have any questions or issues with the plugin, please contact me through the WordPress plugin support forums.

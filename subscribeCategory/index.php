<?php
/**
 * Plugin Name: Category-Based Email Subscription
 * Description: Allows users to subscribe to email lists based on post categories.
 * Version: 1.0.0
 * Author: Ionut Baldazar
 */
register_activation_hook(__FILE__, 'cbes_create_subscription_table');

function cbes_create_subscription_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cbes_subscriptions'; // Use a prefix for your table name
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        category_id mediumint(9) NOT NULL,
        subscribed_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";



    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


// Enqueue scripts and styles (for the form)
function cbes_enqueue_scripts() {
    wp_enqueue_script('cbes-script', plugin_dir_url(__FILE__) . 'cbes-script.js', array('jquery'), '1.0', true);
    wp_enqueue_style('cbes-style', plugin_dir_url(__FILE__) . 'cbes-style.css'); // Create this CSS file
    wp_localize_script('cbes-script', 'cbes_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'cbes_enqueue_scripts');

// Database Table Setup (Run this once on plugin activation)

// Settings Page
function cbes_settings_page() {
    add_options_page(
        'Category Email Subscription Settings',
        'Category Email Subscription',
        'manage_options',
        'cbes-settings',
        'cbes_settings_page_content'
    );
}
add_action('admin_menu', 'cbes_settings_page');

function cbes_settings_page_content() {
    ?>
    <div class="wrap">
        <h1>Category Email Subscription Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('cbes_settings_group');
            do_settings_sections('cbes-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function cbes_register_settings() {
    register_setting('cbes_settings_group', 'cbes_email_lists', 'cbes_sanitize_email_lists');
    register_setting('cbes_settings_group', 'cbes_targeted_categories', 'cbes_sanitize_targeted_categories'); // Register targeted categories

    add_settings_section(
        'cbes_email_lists_section',
        'Email Lists Configuration',
        'cbes_email_lists_section_callback',
        'cbes-settings'
    );
}
add_action('admin_init', 'cbes_register_settings');

function cbes_email_lists_section_callback() {
    echo 'Configure the mapping between categories and email list IDs, and select targeted categories.';
    $email_lists = get_option('cbes_email_lists', array());
    $targeted_categories = get_option('cbes_targeted_categories', array()); // Get the targeted categories

    $categories = get_categories(array('hide_empty' => false));

    echo '<table class="wp-list-table widefat fixed striped table-view-list categories">';
    echo '<thead><tr><th>Targeted</th><th>Category</th><th>Email List ID</th></tr></thead>'; // Added "Targeted" column
    echo '<tbody>';
    foreach ($categories as $category) {
        $category_id = $category->term_id;
        $list_id = isset($email_lists[$category_id]) ? $email_lists[$category_id] : '';
        $is_targeted = in_array($category_id, $targeted_categories); // Check if category is targeted

        echo '<tr>';
        echo '<td><input type="checkbox" name="cbes_targeted_categories[]" value="' . $category_id . '" ' . checked($is_targeted, true, false) . ' /></td>'; // Checkbox for targeting
        echo '<td>' . esc_html($category->name) . '</td>';
        echo '<td><input type="text" name="cbes_email_lists[' . $category_id . ']" value="' . esc_attr($list_id) . '" class="regular-text" /></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
function cbes_sanitize_targeted_categories($input) {
    $sanitized = array();
    if (is_array($input)) {
        foreach ($input as $category_id) {
            $category_id = intval($category_id);
            if ($category_id > 0) {
                $sanitized[] = $category_id;
            }
        }
    }
    return $sanitized;
}
function cbes_sanitize_email_lists($input) {
    $sanitized = array();
    if (is_array($input)) {
        foreach ($input as $category_id => $list_id) {
            $category_id = intval($category_id); // Sanitize category ID
            $list_id = sanitize_text_field($list_id); // Sanitize list ID
            if ($category_id > 0 && !empty($list_id)) { // Basic validation
                $sanitized[$category_id] = $list_id;
            }
        }
    }
    return $sanitized;
}

// Add the subscription form to single posts
function cbes_add_subscription_form($content) {
    if (is_single()) {
        $categories = get_the_category();
        if ($categories) {
            $category_id = $categories[0]->term_id;
            $targeted_categories = get_option('cbes_targeted_categories', array());

       if (in_array($category_id, $targeted_categories)) { // Check if the category is targeted
                $category_name = $categories[0]->name;

           $content.= '<div id="cbes-subscription-form">';
           $content.= '<h3>Subscribe for more ' . esc_html($category_name) . ' content!</h3>';
           $content.= '<form id="cbes-form">';
           $content.= '<input type="hidden" name="category_id" value="' . esc_attr($category_id) . '" />';
           $content.= '<input type="email" name="email" placeholder="Your Email" required />';
           $content.= '<button type="submit">Subscribe</button>';
           $content.= '<div id="cbes-message"></div>'; // For displaying messages
           $content.= '</form>';
           $content.= '</div>';
        }}
    }
    return $content;
}
add_action('the_content', 'cbes_add_subscription_form');


// Handle AJAX subscription request
add_action('wp_ajax_cbes_subscribe', 'cbes_handle_subscription');
add_action('wp_ajax_nopriv_cbes_subscribe', 'cbes_handle_subscription'); // For non-logged-in users
function cbes_handle_subscription() {
    if (isset($_POST['email']) && isset($_POST['category_id'])) {
        $email = sanitize_email($_POST['email']);
        $category_id = intval($_POST['category_id']);

        $email_lists = get_option('cbes_email_lists', array());
        $list_id = isset($email_lists[$category_id]) ? $email_lists[$category_id] : null;

        if (!$list_id) {
            wp_send_json_error(array('message' => 'No email list configured for this category.'));
            wp_die();
        }

        $subscribed = cbes_subscribe_to_list($email, $list_id, $category_id); // Pass the category ID!

        if ($subscribed) {
            wp_send_json_success(array('message' => 'Subscription successful!'));
        } else {
            wp_send_json_error(array('message' => 'Subscription failed. Please try again.'));
        }

    } else {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }
    wp_die();

}


// Example function to subscribe to an email list (replace with your service's API)
function cbes_subscribe_to_list($email, $list_id, $category_id) { // Add $category_id as a parameter
    global $wpdb;
    $table_name = $wpdb->prefix . 'cbes_subscriptions';

    // 1. Check if the user is already subscribed (optional but recommended)
    $existing_subscription = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE email = %s AND category_id = %d", $email, $category_id)
    );

    if ($existing_subscription) {
        return false; // Or handle it differently (e.g., update the subscription date)
    }

    // 2. Insert the subscription data into the database
    $result = $wpdb->insert(
        $table_name,
        array(
            'email' => $email,
            'category_id' => $category_id,
        ),
        array('%s', '%d') // Data types for security
    );
print_r($wpdb->last_error);
    if ($result !== false) {
        // Optionally, you can still integrate with your email marketing service here.
        // Example:  cbes_send_to_mailchimp($email, $list_id); // Your Mailchimp/other service function
        return true; // Database insert was successful
    } else {
        return false; // Database insert failed
    }
    die();
}
// Add a meta box to categories to store the email list ID
function cbes_add_category_meta_box() {
    add_meta_box(
        'cbes_category_meta_box',
        'Email List Settings',
        'cbes_render_category_meta_box',
        'category',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes_category', 'cbes_add_category_meta_box');

function cbes_render_category_meta_box($term) {
    wp_nonce_field('cbes_category_meta_box_nonce', 'cbes_category_meta_box_nonce');
    $email_list_id = get_term_meta($term->term_id, 'email_list_id', true);
    ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row"><label for="email_list_id">Email List ID</label></th>
            <td>
                <input type="text" name="email_list_id" id="email_list_id" value="<?php echo esc_attr($email_list_id); ?>" class="regular-text" />
                <span class="description">Enter the ID of the email list for this category.</span>
            </td>
        </tr>
    </table>
    <?php
}

// Save the email list ID when the category is updated
function cbes_save_category_meta_box($term_id) {
    if (!isset($_POST['cbes_category_meta_box_nonce']) || !wp_verify_nonce($_POST['cbes_category_meta_box_nonce'], 'cbes_category_meta_box_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['email_list_id'])) {
        update_term_meta($term_id, 'email_list_id', sanitize_text_field($_POST['email_list_id']));
    }
}
add_action('edited_category', 'cbes_save_category_meta_box');
add_action('create_category', 'cbes_save_category_meta_box');


// Admin Page to View Subscriptions
function cbes_subscriptions_page() {
    add_submenu_page(
        'options-general.php', // Parent page (you can change this)
        'Category Subscriptions',
        'Category Subscriptions',
        'manage_options',
        'cbes-subscriptions',
        'cbes_subscriptions_page_content'
    );
}
add_action('admin_menu', 'cbes_subscriptions_page');

function cbes_subscriptions_page_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cbes_subscriptions';
    $subscriptions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY subscribed_at DESC");

    echo '<div class="wrap">';
    echo '<h1>Category Subscriptions</h1>';

    // Export to CSV button
    echo '<form method="post" action="">';
    echo '<input type="hidden" name="cbes_export_csv" value="1" />';
    echo '<input type="submit" class="button button-primary" value="Export to CSV" />';
    echo '</form>';

    if ($subscriptions) {
        echo '<table class="wp-list-table widefat fixed striped table-view-list">';
        echo '<thead><tr><th>ID</th><th>Email</th><th>Category</th><th>Subscribed At</th></tr></thead>';
        echo '<tbody>';
        foreach ($subscriptions as $subscription) {
            $category = get_term($subscription->category_id);
            $category_name = $category ? $category->name : 'Unknown';
            echo '<tr>';
            echo '<td>' . $subscription->id . '</td>';
            echo '<td>' . esc_html($subscription->email) . '</td>';
            echo '<td>' . esc_html($category_name) . '</td>';
            echo '<td>' . $subscription->subscribed_at . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No subscriptions found.</p>';
    }
    echo '</div>';
}
// Handle CSV export
add_action('admin_init', 'cbes_handle_csv_export');

function cbes_handle_csv_export() {
    if (isset($_POST['cbes_export_csv'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cbes_subscriptions';
        $subscriptions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY subscribed_at DESC", ARRAY_A); // ARRAY_A for associative array

        if ($subscriptions) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=category_subscriptions.csv');

            $output = fopen('php://output', 'w');

            // Add header row
            fputcsv($output, array_keys($subscriptions[0])); // Use keys from the first row as headers

            foreach ($subscriptions as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
            exit; // Important: Stop further processing
        } else {
            // Handle no subscriptions (e.g., display a message or redirect)
            wp_die('No subscriptions to export.'); // Or wp_redirect(admin_url('options-general.php?page=cbes-subscriptions'));
        }
    }
}
?>

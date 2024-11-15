<?php
// Check if Polylang is active before executing any Polylang-specific code
if (!function_exists('pll_current_language')) {
    return;
}

// Save customer language when order is created
add_action('woocommerce_checkout_create_order', 'save_customer_language_to_order', 10, 2);
function save_customer_language_to_order($order, $data) {
    $current_language = pll_current_language();
    $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : '';
    
    // Save both current language and browser language
    $order->update_meta_data('customer_language', $current_language);
    $order->update_meta_data('browser_language', $browser_language);
}

// Get language for a specific order
function get_customer_order_language($order_id) {
    $order = wc_get_order($order_id);
    if ($order) {
        return $order->get_meta('customer_language');
    }
    return false;
}

// Add console logging for current language (for testing)
add_action('wp_footer', 'log_current_language_to_console');
function log_current_language_to_console() {
    $current_language = pll_current_language();
    $current_language_name = pll_current_language('name');
    $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : '';
    
    ?>
    <script>
        console.log('Current Language Information:');
        console.log('Language Code:', '<?php echo esc_js($current_language); ?>');
        console.log('Language Name:', '<?php echo esc_js($current_language_name); ?>');
        console.log('Browser Language:', '<?php echo esc_js($browser_language); ?>');
    </script>
    <?php
}

// Optional: Display language information in admin order view
add_action('woocommerce_admin_order_data_after_billing_address', 'display_order_language_in_admin');
function display_order_language_in_admin($order) {
    $language = $order->get_meta('customer_language');
    $browser_language = $order->get_meta('browser_language');
    
    if ($language) {
        echo '<p><strong>Order Language:</strong> ' . esc_html($language) . '</p>';
    }
    if ($browser_language) {
        echo '<p><strong>Browser Language:</strong> ' . esc_html($browser_language) . '</p>';
    }
}

// Optional: Add language column to orders list in admin
add_filter('manage_edit-shop_order_columns', 'add_order_language_column');
function add_order_language_column($columns) {
    $new_columns = array();
    
    foreach ($columns as $column_name => $column_info) {
        $new_columns[$column_name] = $column_info;
        
        if ($column_name === 'order_status') {
            $new_columns['order_language'] = __('Language', 'your-text-domain');
        }
    }
    
    return $new_columns;
}

add_action('manage_shop_order_posts_custom_column', 'add_order_language_column_content');
function add_order_language_column_content($column) {
    global $post;
    
    if ($column === 'order_language') {
        $order = wc_get_order($post->ID);
        $language = $order->get_meta('customer_language');
        
        if ($language) {
            // If you want to show language name instead of code
            if (function_exists('pll_languages_list')) {
                $languages = pll_languages_list(['fields' => 'name']);
                echo esc_html(ucfirst($language));
            } else {
                echo esc_html(ucfirst($language));
            }
        } else {
            echo '-';
        }
    }
}

// Helper function to get all orders with their languages
function get_all_orders_with_languages() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    $query = "
        SELECT O.ID as order_id, 
               O.post_date_gmt as order_date, 
               M.meta_value as language
        FROM {$prefix}posts O 
        LEFT JOIN {$prefix}postmeta M ON M.post_id = O.ID 
        WHERE O.post_type = 'shop_order' 
        AND M.meta_key = 'customer_language'
        ORDER BY O.post_date_gmt DESC
    ";

    return $wpdb->get_results($query);
}

// Test function you can use in your templates
function test_current_language() {
    if (function_exists('pll_current_language')) {
        $current_language = pll_current_language();
        $current_language_name = pll_current_language('name');
        $browser_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : '';
        
        echo "Current Language Code: " . esc_html($current_language) . "<br>";
        echo "Current Language Name: " . esc_html($current_language_name) . "<br>";
        echo "Browser Language: " . esc_html($browser_language) . "<br>";
    } else {
        echo "Polylang is not active";
    }
}

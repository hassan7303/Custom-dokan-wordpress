<?php
/*
Plugin Name: Custom Dokan
Description: Plugin for Custom Dokan Functionality
Version: 1.0
Author: Hassan Ali Askari
License: MIT
*/

defined("ABSPATH") || exit;

/**
 * مخفی کردن اطلاعات فروشندگان از لیست فروشندگان
 * Hide Seller Information in Dokan Store Listing
 *
 * This function hides seller information such as email, phone number, or 
 * other details in the seller listing page if the user hasn't paid for access.
 *
 * @param array $store_info The store information for a specific seller.
 * @param int $store_id The ID of the seller/store.
 * 
 * @return array The modified store information.
 */
function hide_dokan_seller_info(array $store_info, int $store_id): array
{
    // فقط در صورتی که در صفحه فروشگاه هستیم، اطلاعات را مخفی کنیم
    if (is_seller_store_page()) {
        $user_has_paid = check_user_payment_for_seller($store_id);

        if (!$user_has_paid) {
            $store_info['email'] = __('[Hidden until purchase]', 'your-textdomain');
            $store_info['phone'] = __('[Hidden until purchase]', 'your-textdomain');
        }
    }

    return $store_info;
}
add_filter('dokan_store_info', 'hide_dokan_seller_info', 10, 2);


/**
 * Redirect Users From Store Listing Page and Vendor Store Pages to Home Page.
 * 
 * @return void
 */
function redirect_dokan_seller_listing(): void
{
    $current_url = $_SERVER['REQUEST_URI'];
    if (is_page('store-listing') || strpos($current_url, '/store/') !== false) {
        wp_redirect(home_url());
        exit();
    }
}
add_action('template_redirect', 'redirect_dokan_seller_listing');


/**
 * فیلتر کردن شماره تماس فروشنده‌ها در پروفایل فروشنده
 * Hide Seller's Phone in Profile Unless Purchased
 * 
 * @param array $data
 * @param WP_User $store_user
 * 
 * @return array
 */
function hide_seller_phone_if_not_purchased(array $data, $store_user): array
{
    $user_id = get_current_user_id();
    if (!check_user_payment_for_seller($store_user->ID)) {
        unset($data['phone']);
    }
    return $data;
}
add_filter('dokan_store_profile_frame_data', 'hide_seller_phone_if_not_purchased', 10, 2);


/**
 * اضافه کردن شماره تلفن فروشنده به جزئیات سفارش در صفحه حساب کاربری
 * Show Seller Information in Order Details if Purchased
 * 
 * @param WC_Order $order
 * 
 * @return void
 */
function show_all_seller_phones_in_order_details($order): void
{
    global $wpdb;

    // بررسی اینکه سفارش تکمیل شده است
    if ($order->get_status() === 'completed') {
        ?>
        <h3>اطلاعات فروشندگان محصولات خریداری‌شده</h3>
        <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">نام محصول</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">نام فروشنده</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">شماره تلفن فروشنده</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">حداقل سفارش</th>
                    <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">ایمیل فروشنده</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // دریافت آیتم‌های سفارش
                foreach ($order->get_items() as $item) {
                    $product_id = $item->get_product_id();
                    $product_name = get_the_title($product_id);

                    $vendors = get_vendors_by_product($product_id);
                    foreach ($vendors as $vendor) {
                        if ($vendor["seller_id"] !== '1') {
                            $results[] = get_seller_info_by_id($vendor["seller_id"]);
                        } else {
                            $admin_id = $vendor["seller_id"]; //1
                            $phone_number = get_user_meta($admin_id, 'billing_phone', true);
                            $admin_email = get_option('admin_email');
                            ?>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($product_name); ?></td>
                                <td style="padding: 10px; border: 1px solid #ddd;">ادمین</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <?php echo $phone_number  ? esc_html($phone_number ) : 'شماره تلفن موجود نیست'; ?>
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    اطلاعات موجود نیست
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <?php echo esc_html($admin_email); ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }

                    foreach ($results as $result) {

                        $profile_settings = maybe_unserialize($result['meta_value']);

                        // استخراج شماره تلفن و اطلاعات فروشنده
                        $seller_phone = isset($profile_settings['phone']) ? $profile_settings['phone'] : 'شماره تلفن موجود نیست';
                        $seller_name = $result['user_nicename'];
                        $seller_email = $result['user_email'];
                        $minimum_order = isset($profile_settings['minimum_order']) ? $profile_settings['minimum_order'] : 'اطلاعات موجود نیست';

                        ?>
                        <tr>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($product_name); ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($seller_name); ?></td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo $seller_phone ? esc_html($seller_phone) : 'شماره تلفن موجود نیست'; ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo esc_html($minimum_order); ?>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;">
                                <?php echo esc_html($seller_email); ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p><strong>سفارش تکمیل نشده است.</strong></p>';
    }
}

add_action('woocommerce_order_details_after_order_table', 'show_all_seller_phones_in_order_details');



/**
 * دریافت اطلاعات فروشنده از جدول‌های مرتبط با استفاده از ایدی فروشنده
 * Get seller information from related tables using the seller's ID.
 *
 * @param int $seller_id
 * 
 * @return array
 */
function get_seller_info_by_id($seller_id): array
{
    global $wpdb;

    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT pm.seller_id, um.meta_value, u.user_nicename, u.user_email
         FROM {$wpdb->prefix}dokan_product_map pm
         JOIN {$wpdb->prefix}usermeta um ON pm.seller_id = um.user_id AND um.meta_key = 'dokan_profile_settings'
         JOIN {$wpdb->prefix}users u ON pm.seller_id = u.ID
         WHERE pm.seller_id = %d AND um.meta_key = 'dokan_profile_settings' AND um.meta_value != '' AND pm.is_trash = 0",
        $seller_id
    ), ARRAY_A);

    return $result ?: [];
}



/**
 * دریافت اطلاعات فروشندگان مرتبط با محصول از جدول wp_dokan_product_map
 * Get the information of sellers related to the product from the wp_dokan_product_map table.
 *
 * @param int $product_id
 * 
 * @return array
 */
function get_vendors_by_product(int $product_id): array
{
    global $wpdb;

    $map_id = $wpdb->get_var($wpdb->prepare(
        "SELECT map_id FROM {$wpdb->prefix}dokan_product_map WHERE product_id = %d",
        $product_id
    ));

    if ($map_id) {
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT seller_id FROM {$wpdb->prefix}dokan_product_map WHERE map_id = %d AND is_trash = 0",
                $map_id
            ),
            ARRAY_A
        );

        return $results ?: [];
    }

    return [];
}


/**
 * Redirect Users to a Custom Page When No Products Are Found
 * 
 * @return void
 */
function my_custom_no_products_found_message(): void
{
    wp_redirect(home_url('/product-request-form'));
    exit();
}
add_action('woocommerce_no_products_found', 'my_custom_no_products_found_message');


/**
 * حذف اطلاع‌رسانی‌های ایمیل به فروشنده
 * Disable Email Notifications for Vendors
 * 
 * @param string $recipient
 * @param WC_Order $order
 * 
 * @return string
 */
function disable_vendor_order_notifications(string $recipient, $order): string
{
    if (isset($order->get_items()['vendor'])) {
        return '';  // Disable email for vendors
    }
    return $recipient;
}
add_filter('woocommerce_email_recipient_customer_processing_order', 'disable_vendor_order_notifications', 10, 2);
add_filter('woocommerce_email_recipient_customer_completed_order', 'disable_vendor_order_notifications', 10, 2);
add_filter('woocommerce_email_recipient_customer_invoice', 'disable_vendor_order_notifications', 10, 2);


/**
 * حذف فروشندگان از مشاهده سفارشات
 * Hide Orders from Vendor's View in Admin Panel
 * 
 * @param WP_Query $query
 * 
 * @return void
 */
function hide_orders_from_vendors($query): void
{
    if (current_user_can('dokan_vendor') && is_admin() && $query->is_main_query() && $query->get('post_type') === 'shop_order') {
        $query->set('author', 0);  // Prevent vendors from seeing orders
    }
}
add_action('pre_get_posts', 'hide_orders_from_vendors');


/**
 * مخفی کردن سفارشات در داشبورد فروشنده
 * Hide Orders in Vendor Dashboard
 * 
 * @param WP_Query $order_query
 * 
 * @return void
 */
function hide_orders_from_vendor_dashboard($order_query): void
{
    if (current_user_can('dokan_vendor')) {
        $order_query->query_vars['post_status'] = array();  // Remove order statuses for vendors
    }
}
add_action('dokan_admin_order_list', 'hide_orders_from_vendor_dashboard', 10, 2);


/**
 * مخفی کردن آمار سفارشات در داشبورد فروشنده
 * Hide Order Stats in Vendor Dashboard
 * 
 * @param array $data
 * 
 * @return array
 */
function hide_order_stats_from_vendor_dashboard(array $data): array
{
    if (current_user_can('dokan_vendor')) {
        $data['orders'] = array();  // Empty order stats for vendors
    }
    return $data;
}
add_filter('dokan_get_dashboard_widget_data', 'hide_order_stats_from_vendor_dashboard', 10, 2);


/**
 * Helper Function to Check if User Has Paid for Access to a Seller's Information
 * 
 * @param int $seller_id
 * 
 * @return bool
 */
function check_user_payment_for_seller(int $seller_id): bool
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => 'completed',
    ));

    foreach ($customer_orders as $order) {
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $vendor_id = get_post_field('post_author', $product_id);

            if ($vendor_id == $seller_id) {
                return true;
            }
        }
    }

    return false;
}


/**
 * Helper function dd for var_dump and die.
 * 
 * @param mixed $data
 * 
 * @return never
 */
function dd(mixed $data): never
{
    echo "<pre dir='ltr'/>";
    var_dump($data);
    echo '<pre/>';
    die;
}
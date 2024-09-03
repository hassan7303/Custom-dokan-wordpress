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
function hide_dokan_seller_info(array $store_info, int $store_id):array
{
    $user_has_paid = check_user_payment_for_seller($store_id);
    
    if (!$user_has_paid) {
        $store_info['email'] = __('[Hidden until purchase]', 'your-textdomain');
        $store_info['phone'] = __('[Hidden until purchase]', 'your-textdomain');
    }

    return $store_info;
}
add_filter('dokan_store_info', 'hide_dokan_seller_info', 10, 2);


/**
 * Remove All Sellers from Dokan Seller Listing
 * 
 * @param array $args
 * 
 * @return array
 */
function disable_dokan_seller_listing(array $args): array
{
    $args['meta_query'] = array(
        array(
            'key'     => 'non_existent_key',
            'value'   => 'non_existent_value',
            'compare' => '='
        )
    );
    return $args;
}
add_filter('dokan_seller_listing_args', 'disable_dokan_seller_listing');


/**
 * Redirect Users From Store Listing Page to Home Page.
 * 
 * @return void
 */
function redirect_dokan_seller_listing():void
{
    if (is_page('store-listing')) {
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
function hide_seller_phone_if_not_purchased(array $data,WP_User $store_user):array
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
function show_all_seller_phones_in_order_details(WC_Order $order):void
{
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
                    $vendor_id = get_post_field('post_author', $product_id);
                    $seller_name = get_the_author_meta('display_name', $vendor_id);  
                    $seller_phone = get_user_meta($vendor_id, 'billing_phone', true);  
                    $minimum_order = get_user_meta($vendor_id, 'minimum_order', true);
                    $seller_email = get_the_author_meta('user_email', $vendor_id); 
                    
                    ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($product_name); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo esc_html($seller_name); ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $seller_phone ? esc_html($seller_phone) : 'شماره تلفن موجود نیست'; ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $minimum_order ? esc_html($minimum_order) : 'اطلاعات موجود نیست'; ?></td>
                        <td style="padding: 10px; border: 1px solid #ddd;"><?php echo $seller_email ? esc_html($seller_email) : 'ایمیل موجود نیست'; ?></td>
                    </tr>
                    <?php
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
 * Redirect Users to a Custom Page When No Products Are Found
 * 
 * @return void
 */
function my_custom_no_products_found_message():void
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
function disable_vendor_order_notifications(string $recipient,WC_Order $order):string
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
function hide_orders_from_vendors(WP_Query $query):void
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
 * @param array $args
 * 
 * @return void
 */
function hide_orders_from_vendor_dashboard(WP_Query $order_query,array $args):void
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
 * @param string $widget
 * 
 * @return array
 */
function hide_order_stats_from_vendor_dashboard(array $data,string $widget):array
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
function check_user_payment_for_seller(int $seller_id):bool
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    $customer_orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status'      => 'completed',
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
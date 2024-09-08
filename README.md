# Custom Dokan Plugin

## Description

>The **Custom Dokan** plugin extends the functionality of the Dokan plugin for WooCommerce.
>
>This plugin is designed to customize how seller information is displayed and managed on the site. 
>
>Features include hiding seller information, controlling access based on user payments, and modifying admin and vendor interactions.

## Features

- **Hide Seller Information**: Hide seller contact information (like phone numbers and emails) unless the user has purchased access.
    ```php
    function hide_dokan_seller_info(array $store_info, int $store_id): array
    ```
  
- **Disable Seller Listings**: Sellers can be hidden from public listing pages.
    ```php
    function disable_dokan_seller_listing(array $args): array
    ```

- **Custom Redirects**: Redirect users from the store listing page to a custom page.
    ```php
    function redirect_dokan_seller_listing(): void
    ```

- **Show Seller Information in Order Details**: Display seller information in order details when an order is completed.
    ```php
    function show_all_seller_phones_in_order_details(WC_Order $order): void
    ```

- **Disable Email Notifications**: Disable vendor order email notifications.
    ```php
    function disable_vendor_order_notifications(string $recipient, WC_Order $order): string
    ```

- **Hide Orders**: Prevent vendors from viewing orders in the admin panel and vendor dashboard.
    ```php
    function hide_orders_from_vendors(WP_Query $query): void
    ```

- **Order-Related Customizations**: Control visibility of orders, order statuses, and statistics in the vendor dashboard.
    ```php
    function hide_orders_from_vendor_dashboard(WP_Query $order_query, array $args): void
    ```

    ```php
    function hide_order_stats_from_vendor_dashboard(array $data, string $widget): array
    ```

## Installation

1. Upload the `custom-dokan` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin settings as needed.

## Usage

>To use, you must first have the Dokan and WooCommerce plugins installed.
>
>The plugin automatically integrates with the Dokan and WooCommerce plugins.
>
>No additional configuration is required beyond the default plugin settings. 
>
>For specific behavior, review the function implementations in the plugin code.

## Author and Support

>Hassan Ali Askari  
>Instagram: [@hassanali7303](https://www.instagram.com/hasan_ali_askari)  
>LinkedIn: [Hassan Ali Askari](https://www.linkedin.com/in/hassan-ali-askari)  
>For support, please contact [hassanali7303@gmail.com](mailto:hassanali7303@gmail.com).

## License

This plugin is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

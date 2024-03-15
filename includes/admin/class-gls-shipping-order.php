<?php

/**
 * Handles showing of order Information
 *
 * @since     1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GLS_Shipping_Order
{

    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_gls_shipping_info_meta_box'));
        add_action('wp_ajax_gls_generate_label', array($this, 'generate_label_and_tracking_number'));
    }

    public function add_gls_shipping_info_meta_box()
    {
        $screen = 'shop_order';

        if (class_exists('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')) {
            $screen = wc_get_container()->get(Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
        }

        add_meta_box(
            'gls_shipping_info_meta_box',
            __('GLS Shipping Info', 'gls_croatia'),
            array($this, 'gls_shipping_info_meta_box_content'),
            $screen,
            'side',
            'default'
        );
    }

    private function display_gls_pickup_info($order_id)
    {
        $order = wc_get_order($order_id);

        $gls_pickup_info = $order->get_meta('_gls_pickup_info', true);
        $tracking_code   = $order->get_meta('_gls_tracking_code', true);

        if (!empty($gls_pickup_info)) {
            $pickup_info = json_decode($gls_pickup_info);

            echo '<strong>' . __('GLS Pickup Location:', 'gls_croatia') . '</strong><br/>';
            echo '<strong>' . __('ID:', 'gls_croatia') . '</strong> ' . esc_html($pickup_info->id) . '<br>';
            echo '<strong>' . __('Name:', 'gls_croatia') . '</strong> ' . esc_html($pickup_info->name) . '<br>';
            echo '<strong>' . __('Address:', 'gls_croatia') . '</strong> ' . esc_html($pickup_info->contact->address) . ', ' . esc_html($pickup_info->contact->city) . ', ' . esc_html($pickup_info->contact->postalCode) . '<br>';
            echo '<strong>' . __('Country:', 'gls_croatia') . '</strong> ' . esc_html($pickup_info->contact->countryCode) . '<br>';
        }

        if ($tracking_code) {
            $gls_shipping_method_settings = get_option("woocommerce_gls_shipping_method_settings");
            $tracking_url = "https://gls-group.eu/" . $gls_shipping_method_settings['country'] . "/en/parcel-tracking/?match=" . $tracking_code;

            echo '<br/><br/><strong>' . __('GLS Tracking Number: ', 'gls_croatia') . '<a href="' . $tracking_url . '" target="_blank">' . $tracking_code . '</a></strong><br><br>';
        }
    }

    public function gls_shipping_info_meta_box_content($order_or_post_id)
    {

        $order = ($order_or_post_id instanceof WP_Post)
            ? wc_get_order($order_or_post_id->ID)
            : $order_or_post_id;

        $gls_print_label = $order->get_meta('_gls_print_label', true);

        $this->display_gls_pickup_info($order->get_id(), false);
?>
        <h4 style="margin-bottom:0px;">
            <div style="margin-top:10px;">
                <?php if ($gls_print_label) { ?>
                    <a class="button primary" href="<?php echo $gls_print_label; ?>" target="_blank"><?php _e("Print Label", "gls_croatia"); ?></a>
                <?php } else { ?>
                    <button type="button" class="button gls-print-label" order-id="<?php echo $order->get_id(); ?>">
                        <?php _e("Generate Shipping Label", "gls_croatia"); ?>
                    </button>
                <?php } ?>
            </div>
            <div id="gls-info"></div>
        </h4>
<?php
    }

    public function generate_label_and_tracking_number()
    {
        $nonce = $_POST['postNonce'];
        $order_id = $_POST['orderId'];

        if (!wp_verify_nonce($nonce, 'import-nonce')) {
            die('Busted!');
        }

        try {
            $prepare_data = new GLS_Shipping_API_Data($order_id);
            $data = $prepare_data->generate_post_fields();

            $api = new GLS_Shipping_API_Service();
            $body = $api->send_order($data, $order_id);
            $this->save_label_and_tracking_info($body, $order_id);

            wp_send_json_success(array('success' => true));
        } catch (Exception $e) {
            error_log($e->getMessage());
            wp_send_json_error(array('success' => false, 'error' => $e->getMessage()));
            return;
        }
    }

    public function save_label_and_tracking_info($body, $order_id)
    {
        $order = wc_get_order($order_id);
        if (!empty($body['Labels'])) {
            $this->save_print_labels($body['Labels'], $order_id, $order);
        }

        if (!empty($body['PrintLabelsInfoList'])) {
            $this->save_tracking_info($body['PrintLabelsInfoList'], $order_id, $order);
        }
    }

    public function save_print_labels($labels, $order_id, $order)
    {
        $label_print = implode(array_map('chr', $labels));
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['url'] . '/shipping_label_' . $order_id . '.pdf';
        $file_path = $upload_dir['path'] . '/shipping_label_' . $order_id . '.pdf';
        file_put_contents($file_path, $label_print);
        $order->update_meta_data('_gls_print_label', $file_url);
        $order->save();
    }

    public function save_tracking_info($printLabelsInfoList, $order_id, $order)
    {
        $tracking_code = $printLabelsInfoList[0]['ParcelNumber'] ?? null;
        $parcel_id = $printLabelsInfoList[0]['ParcelId'] ?? null;
        $order->update_meta_data('_gls_tracking_code', $tracking_code);
        $order->update_meta_data('_gls_parcel_id', $parcel_id);
        $order->save();
    }
}

new GLS_Shipping_Order();

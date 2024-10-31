<?php

if (!defined('ABSPATH')) {
    exit;
}

class Searchmindai_Product_JSON_Generator {

    private static $instance = null;

    private function __construct() {
        add_action('init', array($this, 'register_json_endpoint'));
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_json_endpoint() {
        add_rewrite_rule('^searchmindai-product-collection/?$', 'index.php?searchmindai_product_json=1', 'top');
        add_rewrite_tag('%searchmindai_product_json%', '([^&]+)');
        add_action('template_redirect', array($this, 'generate_json'));
    }

    public function generate_json() {
        global $wp_query;

        if (isset($wp_query->query_vars['searchmindai_product_json'])) {
            $this->generate_products_json();
        }
    }

    private function generate_products_json() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
        );

        $products = new WP_Query($args);
        $products_array = array();

        if ($products->have_posts()) {
            while ($products->have_posts()) {
                $products->the_post();
                global $product;

                $short_description = apply_filters('woocommerce_short_description', get_the_excerpt());
                $short_description = str_replace("\r", ' ', str_replace("\n", ' ', $short_description));
                $short_description = trim($short_description);
                $short_description = preg_replace('/\s+/', ' ', $short_description);
                $short_description = preg_replace("/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑçÇãÃ ]+/", "", $short_description);

                $long_description = apply_filters('the_content', get_the_content());
                $long_description = str_replace("\r", ' ', str_replace("\n", ' ', $long_description));
                $long_description = trim($long_description);
                $long_description = preg_replace('/\s+/', ' ', $long_description);
                $long_description = preg_replace("/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑçÇãÃ ]+/", "", $long_description);

// Get product images
                $thumbnail_id = get_post_thumbnail_id(get_the_ID());
                $image_url = '';

                // Check if there are gallery images
                if ($thumbnail_id) {
                    $image_url = wp_get_attachment_image_url($thumbnail_id, 'medium'); // Thumbnail size image URL
                } else {
                    // If no gallery images, use the shop default image
                    $image_url = wc_placeholder_img_src('medium');
                }

                $products_array[] = array(
                    'id' => get_the_ID(),
                    'name' => get_the_title(),
                    'price' => $product->get_price(),
                    'short_description' => $short_description,
                    'description' => $long_description,
                    'image' => $image_url,
                    'url' => get_permalink(),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json($products_array);
        exit;
    }
}

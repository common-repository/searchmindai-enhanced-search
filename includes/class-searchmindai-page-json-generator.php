<?php

if (!defined('ABSPATH')) {
    exit;
}

class Searchmindai_Page_JSON_Generator {

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
        add_rewrite_rule('^searchmindai-page-collection/?$', 'index.php?searchmindai_page_json=1', 'top');
        add_rewrite_tag('%searchmindai_page_json%', '([^&]+)');
        add_action('template_redirect', array($this, 'generate_json'));
    }

    public function generate_json() {
        global $wp_query;

        if (isset($wp_query->query_vars['searchmindai_page_json'])) {
            $this->generate_pages_json();
        }
    }

    private function generate_pages_json() {
        // Array of page slugs or IDs to exclude
        $exclude_slugs = array('checkout', 'cart', 'my-account', 'shop');
        $exclude_ids = array_map(function($slug) {
            $page = get_page_by_path($slug);
            return $page ? $page->ID : 0;
        }, $exclude_slugs);

        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__not_in' => $exclude_ids, // Exclude specific pages
        );

        $pages = new WP_Query($args);
        $pages_array = array();

        if ($pages->have_posts()) {
            while ($pages->have_posts()) {
                $pages->the_post();

                $long_description = apply_filters('the_content', get_the_content());
                $long_description = stripslashes($long_description);
                $long_description = preg_replace('/\/\*![\s\S]*?\*\//', '', $long_description);

// Remove <style> tags and their content
                $long_description = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $long_description);

// Remove inline styles
                $long_description = preg_replace('/style="[^"]*"/i', '', $long_description);
                $long_description = strip_tags($long_description);
                $long_description = str_replace("\r", ' ', str_replace("\n", ' ', $long_description));

                $long_description = strip_tags($long_description);
                $long_description = trim($long_description);
                $long_description = preg_replace('/\s+/', ' ', $long_description);
                $long_description = preg_replace("/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑçÇãÃ ]+/", "", $long_description);

                $pages_array[] = array(
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'description' => $long_description,
                    'url' => get_permalink(),
                    'tags' => wp_get_post_tags(get_the_ID(), array('fields' => 'names')),
                );
            }
            wp_reset_postdata();
        }

        wp_send_json($pages_array);
        exit;
    }
}

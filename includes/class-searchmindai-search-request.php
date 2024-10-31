<?php
/**
 * Handle search request and display results directly.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Searchmindai_Search_Request {

    private static $instance = null;

    private function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_request'));
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^searchmind-request/term/([^/]+)/page/([0-9]+)/?', 'index.php?searchmind_term=$matches[1]&searchmind_page=$matches[2]', 'top');
        add_rewrite_rule('^searchmind-request/term/([^/]+)/?', 'index.php?searchmind_term=$matches[1]', 'top');
        add_rewrite_tag('%searchmind_term%', '([^&]+)');
        add_rewrite_tag('%searchmind_page%', '([0-9]+)');
    }

    public function handle_request() {
        global $wp_query;

        if (isset($wp_query->query_vars['searchmind_term'])) {
            $search_term = $wp_query->query_vars['searchmind_term'];
            $page = $wp_query->query_vars['searchmind_page'];
            $this->perform_search($search_term,$page);
            exit;
        }
    }

    private function perform_search($search_term,$page) {
        //return;
        $options = get_option('searchmindai_settings');
        $search_id = isset($options['search_id']) ? esc_attr($options['search_id']) : '';
        $token_id = isset($options['token_id']) ? esc_attr($options['token_id']) : '';
        $limit = isset($options['item_qty']) ? esc_attr($options['item_qty']) : '';;
        if (empty($search_id) || empty($token_id)) {
            echo 'Search ID and Token ID are required.';
            exit;
        }


        $page_aux = $page;
        $page_prev = $page-1;


        if($page > 1){
            $offset = '&offset='.$page_aux;
        } else {
            $offset = '';
        }
        // Making a request to SearchMindAI API for advanced search results.
        $api_url = 'https://api.searchmindai.com/';
        $url = $api_url .'v2/search?query='.urlencode($search_term).'&apiKey='.$token_id.'&limit='.$limit.$offset.'&documentType=product';

        // Note: The SearchMindAI API processes the search query and returns results based on the site’s product catalog.
        // No personal user data is sent to the API. The API key and document type are required for authentication and
        // specifying the type of search being performed.

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            echo 'Request to external API failed.';
            exit;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'Invalid JSON response from external API.';
            exit;
        }

        $total_items = $data['total'];

        $item_start = 1;
        $item_limit = $limit;

        if($page > 1){
            $item_start = (($page-1)*$limit)+1;
            $item_limit = $limit*$page;
        }

        if($item_limit > $total_items){
            $item_limit = $total_items;
        }
        if($page>1){
            $page_next = $page+1;
        } else {
            $page_next = 2;
        }

        $maxpage = ceil($total_items/$limit);


        $storeUrl = home_url();

        echo '<h2>AI Search Results:</h2>';
        echo '<p>Items '.esc_html($item_start).'-'.esc_html($item_limit).' of '.esc_html($total_items).'</p>';
        //print_r($data['results']);
        echo '<div class="wp-block-group"><ul class="wp-block-post-template-is-layout-grid">';
        foreach ($data['results'] as $products){
            // Get the product object
            $product = wc_get_product($products['id']);
            // Get the product name, price, and permalink
            $product_name = $product->get_name();
            $product_price = $product->get_price();
            $product_permalink = $product->get_permalink();

            // Get product images
            $thumbnail_id = get_post_thumbnail_id($products['id']);
            $image_url = '';

            // Check if there are gallery images
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_image_url($thumbnail_id, 'medium'); // Thumbnail size image URL
            } else {
                // If no gallery images, use the shop default image
                $image_url = wc_placeholder_img_src('medium');
            }


            // Output the product information
            echo "<li class='wp-block-post product_id_".esc_html($products['id'])."'>";
            echo "<div class='image-wrap'><a href='".esc_html($product_permalink)."'><img src='".esc_html($image_url)."' alt='".esc_html($product_name)."'></a></div>";
            echo "<h5><a href='".esc_html($product_permalink)."'>".esc_html($product_name)."</a></h5>";
            echo "<p>Price: $ ".esc_html($product_price)."</p>";
            echo "<p><a href='".esc_html($product_permalink)."'>Ver Mas</a></p>";
            echo "</li>";
        }
        echo '</ul></div>';

        ////////////////////////
        ///
        ///

        echo '<div class="pages-wrap"><ul class="pages-items">';
        if($page>1) {
            echo '<li class="item pages-item-previous">
                                        <a class="action  previous" href="' . esc_html($storeUrl) .  '/?s=' . esc_html($search_term) .'&page=' . esc_html($page_prev) .'" title="Previous">
                        <span>Previous</span>
                    </a>
                </li>';
        }

        $page_aux_max = $page_aux + 3;
        $page_aux_min = $page_aux - 3;
        if($page_aux_min < 1){
            $page_aux_min = 1;
        }
        //echo $maxpage;
        if($page_aux_max > $maxpage){
            $page_aux_max = $maxpage;

        }

        for ($page_number = $page_aux_min; $page_number <= $page_aux_max; $page_number++) {
            if($page_number == $page_aux){
                echo '<li class="item current">
                        <strong class="page">
                            <span>'.esc_html($page_number).'</span>
                        </strong>
                    </li>';
            } else{
                echo '<li class="item">
                        <a href="'.esc_html($storeUrl).'/?s='.esc_html($search_term).'&page='.esc_html($page_number).'" class="page">
                            <span>'.esc_html($page_number).'</span>
                        </a>
                    </li>';
            }
        }

        if($page_next<=$maxpage){
        echo '<li class="item pages-item-next">
                                        <a class="action  next" href="'.esc_html($storeUrl).'/?s='.esc_html($search_term).'&page='.esc_html($page_next).'" title="Next">
                        <span>Next</span>
                    </a>
                </li>';

}
        echo '</ul></div><div class="clear"></div>';




    }
}

// Initialize the class
Searchmindai_Search_Request::get_instance();

<?php
/*
Plugin Name: Searchmindai Enhanced Search
Description: A plugin that replace the default WordPress search with a custom AI search results.
Version: 1.0.0
Author: Searchmindai
License: GPL
Text Domain: searchmindai-enhanced-search
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include the class files
require_once plugin_dir_path(__FILE__) . 'includes/class-searchmindai-product-json-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-searchmindai-page-json-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-searchmindai-admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-searchmindai-search-request.php';

// Initialize the plugin
function searchmindai_enhanced_search_init() {
    Searchmindai_Product_JSON_Generator::get_instance();
    Searchmindai_Page_JSON_Generator::get_instance();
    Searchmindai_Admin_Settings::get_instance();
    Searchmindai_Search_Request::get_instance();
}
add_action('plugins_loaded', 'searchmindai_enhanced_search_init');

// Register activation hook to flush rewrite rules
register_activation_hook(__FILE__, 'searchmindai_enhanced_search_activate');
function searchmindai_enhanced_search_activate() {
    // Ensure the endpoints and templates are registered
    Searchmindai_Product_JSON_Generator::get_instance();
    Searchmindai_Page_JSON_Generator::get_instance();
    // Flush rewrite rules to ensure the new endpoints are recognized
    flush_rewrite_rules();
}

// Register deactivation hook to flush rewrite rules
register_deactivation_hook(__FILE__, 'searchmindai_enhanced_search_deactivate');
function searchmindai_enhanced_search_deactivate() {
    // Flush rewrite rules to remove the custom endpoints
    flush_rewrite_rules();
}
//Register CSS File
function searchmindai_enqueue_styles() {
    if (is_search()) {
        wp_register_style('searchmindai-styles', plugins_url('/css/searchmindai.css', __FILE__));
        wp_enqueue_style('searchmindai-styles');
    }
}
add_action('wp_enqueue_scripts', 'searchmindai_enqueue_styles');

//Register Js File
function searchmindai_enqueue_search_scripts() {
    if (is_search()) {
        // Register and enqueue the empty script file
        wp_register_script('searchmindai-scripts', plugins_url('/js/searchmindai.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_script('searchmindai-scripts');

        // Localize the PHP variables to pass to JS
        $search_vars = array(
            'searchTerm' => trim(preg_replace("/[^a-zA-Z0-9 ]+/", "", get_search_query())),
            'page'       => get_query_var('page') ? get_query_var('page') : 1,
            'elementor'  => class_exists('Elementor\Plugin') ? 'true' : 'false',  // Pass a string value 'true' or 'false'
            'ajaxUrl'    => esc_url(home_url('/searchmind-request/term/'))
        );
        wp_localize_script('searchmindai-scripts', 'searchmindai_vars', $search_vars);

        // Add inline script using localized variables
        $inline_js = "
        jQuery(function() {
            var searchterm = searchmindai_vars.searchTerm;
            var page = searchmindai_vars.page;
            var elementor = JSON.parse(searchmindai_vars.elementor);  // Convert 'true' or 'false' string to a boolean
            if (searchterm !== '') {
                jQuery('.elementor-posts-container').html('');
                jQuery('.elementor-posts-container').append('<div class=\"loader\"></div>');
                jQuery('.wp-block-query').html('');
                jQuery('.wp-block-query').append('<div class=\"loader\"></div>');
                searchCatalog(searchterm, page, elementor);
            }
        });
        
        // Function to search catalog using AJAX
        function searchCatalog(searchterm, page, elementor) {
            var chatlog = jQuery.ajax({
                url: searchmindai_vars.ajaxUrl + searchterm + '/page/' + page,
                type: 'GET',
                dataType: 'html'
            });

            chatlog.done(function(msg) {
                if (msg) {
                    if (elementor) {
                        jQuery('.elementor-posts-container').append(msg);
                        jQuery('.elementor-posts-container').show();
                    } else {
                        jQuery('.wp-block-query').append(msg);
                        jQuery('.wp-block-query').show();
                    }
                    jQuery('.loader').remove();
                }
            });

            chatlog.fail(function(jqXHR, textStatus, errorThrown) {
                console.error(errorThrown);
            });
        }
        ";
        wp_add_inline_script('searchmindai-scripts', $inline_js);
    }
}
add_action('wp_enqueue_scripts', 'searchmindai_enqueue_search_scripts');





?>

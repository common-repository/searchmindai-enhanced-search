<?php

if (!defined('ABSPATH')) {
    exit;
}

class Searchmindai_Admin_Settings {

    private static $instance = null;

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_admin_menu() {
        add_options_page(
            'Searchmindai Enhanced Search Settings',
            'Searchmindai Settings',
            'manage_options',
            'searchmindai-enhanced-search',
            array($this, 'create_admin_page')
        );
    }

    public function settings_init() {
        register_setting('searchmindai_settings', 'searchmindai_settings');

        add_settings_section(
            'searchmindai_settings_section',
            __('Searchmindai Enhanced Search Settings', 'searchmindai-enhanced-search'),
            array($this, 'settings_section_callback'),
            'searchmindai_settings'
        );

        add_settings_field(
            'search_id',
            __('Search ID', 'searchmindai-enhanced-search'),
            array($this, 'search_id_render'),
            'searchmindai_settings',
            'searchmindai_settings_section'
        );

        add_settings_field(
            'token_id',
            __('Token ID', 'searchmindai-enhanced-search'),
            array($this, 'token_id_render'),
            'searchmindai_settings',
            'searchmindai_settings_section'
        );

        add_settings_field(
            'item_qty',
            __('Default Item Qty', 'searchmindai-enhanced-search'),
            array($this, 'item_qty_render'),
            'searchmindai_settings',
            'searchmindai_settings_section'
        );

    }

    public function create_admin_page() {
        ?>
        <form action='options.php' method='post'>
            <?php
            settings_fields('searchmindai_settings');
            do_settings_sections('searchmindai_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function settings_section_callback() {
        $product_json_url = home_url('/searchmindai-product-collection');
        $page_json_url = home_url('/searchmindai-page-collection');
        echo 'Before you enable this plugin you need to register in <a href="https://www.searchmindai.com/registration" target="_blank">SearchMindAI.com</a> to complete the configuration of your Store and get your credentials.
<br><br>
Copy these URLs and paste them in their respective places in SearcMindAi.com panel, under the Store > Catalogs tab and start the AI indexation.';
        echo '<p><strong>Product JSON URL:</strong> <a href="' . esc_url($product_json_url) . '">' . esc_url($product_json_url) . '</a></p>';
        echo '<p><strong>Page JSON URL:</strong> <a href="' . esc_url($page_json_url) . '">' . esc_url($page_json_url) . '</a></p>';
        echo '<p>Enter your settings below:</p>';
    }

    public function search_id_render() {
        $options = get_option('searchmindai_settings');
        ?>
        <input type='text' name='searchmindai_settings[search_id]' value='<?php echo isset($options['search_id']) ? esc_attr($options['search_id']) : ''; ?>'>
        <?php
    }

    public function token_id_render() {
        $options = get_option('searchmindai_settings');
        ?>
        <input type='text' name='searchmindai_settings[token_id]' value='<?php echo isset($options['token_id']) ? esc_attr($options['token_id']) : ''; ?>'>
        <?php
    }
    public function item_qty_render() {
        $options = get_option('searchmindai_settings');
        ?>
        <input type='text' name='searchmindai_settings[item_qty]' value='<?php echo isset($options['item_qty']) ? esc_attr($options['item_qty']) : ''; ?>'>
        <?php
    }
}

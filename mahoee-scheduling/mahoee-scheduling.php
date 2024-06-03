<?php
/*
Plugin Name: Mahoee Scheduling
Plugin URI: https://github.com/mahoee-labs/wordpress-plugins/
Author: Mahoee
Author URI: http://mahoee.com/
Description: Manage Let's Encrypt certificates for a multisite network.
Version: 0.1.0
License: MIT License
License URI: https://opensource.org/licenses/MIT
Text Domain: mahoee-scheduling

 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__ . '/vendor/autoload.php';

define('MAHOEE_SCHEDULING_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once MAHOEE_SCHEDULING_PLUGIN_DIR . 'admin/site-settings.php';
require_once MAHOEE_SCHEDULING_PLUGIN_DIR . 'web/shortcode.php';

// Site admin menu
function mahoee_scheduling_site_menu()
{
    add_menu_page(
        'Agendamentos desse Site',
        'Agendamentos',
        'manage_options',
        'mahoee-scheduling',
        'mahoee_scheduling_site_settings_page',
        'dashicons-schedule',
        31
    );
}
add_action('admin_menu', 'mahoee_scheduling_site_menu');

// Register the shortcode
function mahoee_scheduling_register_shortcode()
{
    add_shortcode('scheduling_options', 'mahoee_scheduling_options_shortcode_callback');
    add_shortcode('scheduling_actions', 'mahoee_scheduling_actions_shortcode_callback');
}
add_action('init', 'mahoee_scheduling_register_shortcode');
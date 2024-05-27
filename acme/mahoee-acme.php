<?php
/*
Plugin Name: Mahoee ACME
Plugin URI: https://github.com/mahoee-labs/wordpress-plugins/
Author: Mahoee
Author URI: http://mahoee.com/
Description: Manage Let's Encrypt certificates for a multisite network.
Version: 0.1
License: MIT License
License URI: https://opensource.org/licenses/MIT
Text Domain: mahoee-acme

 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once __DIR__ . '/vendor/autoload.php';

define('MAHOEE_ACME_PLUGIN_DIR', plugin_dir_path(__FILE__));

use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Filesystem;

function mahoee_acme_init_filesystem()
{
    // Get the uploads directory
    $upload_dir = wp_upload_dir();
    $storage_dir = $upload_dir['basedir'] . '/mahoee-acme/';

    if (!file_exists($storage_dir)) {
        if (!mkdir($storage_dir, 0755, true)) {
            error_log('Failed to create storage directory: ' . $storage_dir);
            return false;
        }
        error_log('Storage directory created: ' . $storage_dir);
    } else {
        error_log('Storage directory already exists: ' . $storage_dir);
    }

    $adapter = new LocalAdapter($storage_dir);
    return new Filesystem($adapter);
}

$filesystem = mahoee_acme_init_filesystem();
if (!$filesystem) {
    add_action('admin_notices', function () {
        echo '<div class="error"><p>Failed to initialize the filesystem for MAHOEE ACME plugin. Please check directory permissions.</p></div>';
    });
    error_log('Failed to initialize the filesystem.');
    return;
}
error_log('Filesystem initialized successfully.');

// Include necessary files
require_once MAHOEE_ACME_PLUGIN_DIR . 'admin/network-settings.php';
require_once MAHOEE_ACME_PLUGIN_DIR . 'admin/site-settings.php';
require_once MAHOEE_ACME_PLUGIN_DIR . 'includes/acme-handler.php';
require_once MAHOEE_ACME_PLUGIN_DIR . 'includes/http-challenge.php';

// Network admin menu
function mahoee_acme_network_menu()
{
    add_menu_page(
        'Certificados da Rede',
        'Certificados',
        'manage_network_options',
        'mahoee-acme',
        'mahoee_acme_network_settings_page',
        'dashicons-awards',
        81
    );
}
add_action('network_admin_menu', 'mahoee_acme_network_menu');

// Site admin menu
function mahoee_acme_site_menu()
{
    add_menu_page(
        'Certificados desse Site',
        'Certificados',
        'manage_options',
        'mahoee-acme',
        'mahoee_acme_site_settings_page',
        'dashicons-awards',
        81
    );
}
add_action('admin_menu', 'mahoee_acme_site_menu');

// Handle ACME requests and challenges
add_action('init', 'mahoee_acme_handle_http_challenge');
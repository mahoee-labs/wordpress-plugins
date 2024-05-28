<?php
/*
Plugin Name: Mahoee Tracking
Plugin URI: https://github.com/mahoee-labs/wordpress-plugins/
Author: Mahoee
Author URI: http://mahoee.com/
Description: Plugin to record visits to multisite network sites and provide reports.
Version: 0.2.5
License: MIT License
License URI: https://opensource.org/licenses/MIT
Text Domain: mahoee-tracking
 */

require_once 'vendor/maxmind/autoload.php';

use MaxMind\Db\Reader;

register_activation_hook(__FILE__, 'mahoee_tracking_create_table');
function mahoee_tracking_create_table()
{
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'mahoee_tracking';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        site_id bigint(20) NOT NULL,
        ip varchar(100) NOT NULL,
        city varchar(100) DEFAULT '' NOT NULL,
        state varchar(100) DEFAULT '' NOT NULL,
        country varchar(100) DEFAULT '' NOT NULL,
        visit_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

add_action('wp_footer', 'mahoee_tracking_record_visit');
function mahoee_tracking_record_visit()
{
    if (!is_user_logged_in()) {
        global $wpdb;
        $table_name = $wpdb->base_prefix . 'mahoee_tracking';
        $ip = $_SERVER['REMOTE_ADDR'];
        $site_id = get_current_blog_id();

        $geo_data = get_geo_data($ip);

        $wpdb->insert(
            $table_name,
            array(
                'site_id' => $site_id,
                'ip' => $ip,
                'city' => $geo_data['city'],
                'state' => $geo_data['state'],
                'country' => $geo_data['country'],
                'visit_time' => current_time('mysql'),
            )
        );

        $max_visits = 1000;
        $wpdb->query("
            DELETE FROM $table_name
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT id
                    FROM $table_name
                    ORDER BY visit_time DESC
                    LIMIT $max_visits
                ) sub
            )
        ");
    }
}

function get_geo_data($ip)
{
    $database_file = trailingslashit(WP_CONTENT_DIR) . 'uploads/geoip_database.mmdb';

    try {
        $reader = new Reader($database_file);
        $record = $reader->get($ip);

        // Retrieve geo data from the record
        $city = isset($record['city']['names']['en']) ? $record['city']['names']['en'] : 'Unknown';
        $state = isset($record['subdivisions'][0]['names']['en']) ? $record['subdivisions'][0]['names']['en'] : 'Unknown';
        $country = isset($record['country']['names']['en']) ? $record['country']['names']['en'] : 'Unknown';

        return array(
            'city' => $city,
            'state' => $state,
            'country' => $country,
        );
    } catch (AddressNotFoundException $e) {
        return array(
            'city' => 'Unknown',
            'state' => 'Unknown',
            'country' => 'Unknown',
        );
    } catch (\Exception $e) {
        return array(
            'city' => 'Error',
            'state' => 'Error',
            'country' => 'Error',
        );
    }
}

add_action('network_admin_menu', 'mahoee_tracking_admin_menu');
function mahoee_tracking_admin_menu()
{
    add_menu_page(
        'Visit Tracker',
        'Visit Tracker',
        'manage_network_options',
        'mahoee-tracking',
        'mahoee_tracking_admin_page',
        'dashicons-chart-line'
    );
}

function mahoee_tracking_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'mahoee_tracking';

    $results = $wpdb->get_results("
        SELECT site_id, ip, city, state, country, visit_time
        FROM $table_name
        ORDER BY visit_time DESC
        LIMIT 15
    ");

    echo '<div class="wrap">';
    echo '<h1>Visit Tracker - Últimas Visitas</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Site ID</th><th>IP</th><th>Cidade</th><th>Estado</th><th>País</th><th>Horário da Visita</th></tr></thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->site_id) . '</td>';
        echo '<td>' . esc_html($row->ip) . '</td>';
        echo '<td>' . esc_html($row->city) . '</td>';
        echo '<td>' . esc_html($row->state) . '</td>';
        echo '<td>' . esc_html($row->country) . '</td>';
        echo '<td>' . esc_html($row->visit_time) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

add_action('network_admin_menu', 'mahoee_tracking_geo_data_menu');
function mahoee_tracking_geo_data_menu()
{
    add_submenu_page(
        'mahoee-tracking',
        'Geo Data Lookup',
        'Geo Data Lookup',
        'manage_network_options',
        'mahoee-tracking-geo-data',
        'mahoee_tracking_geo_data_page'
    );
}

function mahoee_tracking_geo_data_page()
{
    echo '<div class="wrap">';
    echo '<h1>Geo Data Lookup</h1>';
    echo '<form method="post" action="">';
    echo '<label for="ip_address">IP Address: </label>';
    echo '<input type="text" id="ip_address" name="ip_address" value="" class="regular-text" />';
    echo '<input type="submit" name="lookup_geo_data" class="button button-primary" value="Lookup" />';
    echo '</form>';

    if (isset($_POST['lookup_geo_data'])) {
        $ip_address = sanitize_text_field($_POST['ip_address']);
        $geo_data = get_geo_data($ip_address);

        echo '<h2>Geo Data for IP: ' . esc_html($ip_address) . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>City</th><th>State</th><th>Country</th></tr></thead>';
        echo '<tbody>';
        echo '<tr>';
        echo '<td>' . esc_html($geo_data['city']) . '</td>';
        echo '<td>' . esc_html($geo_data['state']) . '</td>';
        echo '<td>' . esc_html($geo_data['country']) . '</td>';
        echo '</tr>';
        echo '</tbody></table>';
    }

    echo '</div>';
}

add_action('network_admin_menu', 'mahoee_tracking_settings_menu');
function mahoee_tracking_settings_menu()
{
    add_submenu_page(
        'mahoee-tracking',
        'Configurações do Visit Tracker',
        'Configurações',
        'manage_network_options',
        'mahoee-tracking-settings',
        'mahoee_tracking_settings_page'
    );
}

function mahoee_tracking_settings_page()
{
    echo '<div class="wrap">';
    echo '<h1>Configurações do Visit Tracker</h1>';
    echo '<form method="post" action="edit.php?action=save_mahoee_tracking_settings">';
    settings_fields('mahoee_tracking_settings_group');
    do_settings_sections('mahoee-tracking-settings');
    wp_nonce_field('mahoee_tracking_settings_nonce', 'mahoee_tracking_settings_nonce');
    submit_button();
    echo '</form>';
    echo '</div>';
}

add_action('network_admin_edit_save_mahoee_tracking_settings', 'mahoee_tracking_save_settings');
function mahoee_tracking_save_settings()
{
    // // Check if the user has the necessary permissions and the request is valid
    // if (isset($_POST['mahoee_tracking_settings_submit']) && check_admin_referer('mahoee_tracking_settings_nonce', 'mahoee_tracking_settings_nonce')) {
    // Save settings using update_network_option
    update_network_option(null, 'mahoee_tracking_geoip_database', sanitize_text_field($_POST['mahoee_tracking_geoip_database']));
    update_network_option(null, 'mahoee_tracking_account_id', sanitize_text_field($_POST['mahoee_tracking_account_id']));
    update_network_option(null, 'mahoee_tracking_license_key', sanitize_text_field($_POST['mahoee_tracking_license_key']));

    // Redirect back to the settings page with a success message
    wp_redirect(add_query_arg('page', 'mahoee-tracking-settings', network_admin_url('admin.php')));
    exit();
    // }
}

add_action('admin_init', 'mahoee_tracking_register_settings');
function mahoee_tracking_register_settings()
{
    register_setting('mahoee_tracking_settings_group', 'mahoee_tracking_geoip_database');
    register_setting('mahoee_tracking_settings_group', 'mahoee_tracking_account_id');
    register_setting('mahoee_tracking_settings_group', 'mahoee_tracking_license_key');

    add_settings_section(
        'mahoee_tracking_settings_section',
        'Configurações da Base de Dados GeoIP',
        'mahoee_tracking_settings_section_callback',
        'mahoee-tracking-settings'
    );

    add_settings_field(
        'mahoee_tracking_geoip_database',
        'URL da Base de Dados GeoIP',
        'mahoee_tracking_geoip_database_callback',
        'mahoee-tracking-settings',
        'mahoee_tracking_settings_section'
    );

    add_settings_field(
        'mahoee_tracking_account_id',
        'MaxMind Account ID',
        'mahoee_tracking_account_id_callback',
        'mahoee-tracking-settings',
        'mahoee_tracking_settings_section'
    );

    add_settings_field(
        'mahoee_tracking_license_key',
        'MaxMind License Key',
        'mahoee_tracking_license_key_callback',
        'mahoee-tracking-settings',
        'mahoee_tracking_settings_section'
    );
}

function mahoee_tracking_settings_section_callback()
{
    echo 'Insira as configurações para baixar a última versão gratuita da base de dados GeoIP.';
}

function mahoee_tracking_geoip_database_callback()
{
    $setting = get_network_option(null, 'mahoee_tracking_geoip_database');
    echo '<input type="text" name="mahoee_tracking_geoip_database" value="' . esc_attr($setting) . '" class="regular-text" />';
}

function mahoee_tracking_account_id_callback()
{
    $setting = get_network_option(null, 'mahoee_tracking_account_id');
    echo '<input type="text" name="mahoee_tracking_account_id" value="' . esc_attr($setting) . '" class="regular-text" />';
}

function mahoee_tracking_license_key_callback()
{
    $setting = get_network_option(null, 'mahoee_tracking_license_key');
    echo '<input type="text" name="mahoee_tracking_license_key" value="' . esc_attr($setting) . '" class="regular-text" />';
}

function mahoee_tracking_download_geoip_data()
{
    // Retrieve URL, Account ID, and License Key
    $url = get_network_option(null, 'mahoee_tracking_geoip_database');
    $account_id = get_network_option(null, 'mahoee_tracking_account_id');
    $license_key = get_network_option(null, 'mahoee_tracking_license_key');
    if (!$url || !$account_id || !$license_key) {
        return false;
    }

    // Initialize cURL session
    $curl = curl_init();
    $filename = 'geoip_database.tar.gz';
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $account_id . ':' . $license_key,
        CURLOPT_HTTPHEADER => array(
            'Content-Disposition: attachment; filename=' . $filename,
        ),
    ));

    // Execute cURL request
    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error_message = curl_error($curl);
        curl_close($curl);
        echo 'Error: ' . $error_message;
        return false;
    }
    $response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    // Check if response code is in the 4xx range (client errors)
    if ($response_code >= 400 && $response_code < 500) {
        echo 'Error: Client Error ' . $response_code;
        return false;
    }

    // Save .tar.gz file
    $upload_dir = wp_upload_dir();
    $tar_gz_file_path = $upload_dir['basedir'] . '/' . $filename;
    if (file_exists($tar_gz_file_path)) {
        unlink($tar_gz_file_path);
    }
    if (file_put_contents($tar_gz_file_path, $response) === false) {
        return false;
    }

    // Extract MMDB file
    $extract_path = $upload_dir['basedir'] . '/geoip_database.mmdb';
    if (file_exists($extract_path)) {
        unlink($extract_path);
    }
    extract_file_with_extension_from_tar_gz($tar_gz_file_path, 'mmdb', $extract_path);

    // Clean and return path to MMDB
    unlink($tar_gz_file_path);
    return $extract_path;
}

add_action('network_admin_menu', 'mahoee_tracking_geoip_file_details');
function mahoee_tracking_geoip_file_details()
{
    add_submenu_page(
        'mahoee-tracking',
        'Detalhes do Arquivo GeoIP',
        'Detalhes do Arquivo GeoIP',
        'manage_network_options',
        'mahoee-tracking-geoip-file',
        'mahoee_tracking_geoip_file_page'
    );
}

function mahoee_tracking_geoip_file_page()
{
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/geoip_database.mmdb';

    echo '<div class="wrap">';
    echo '<h1>Detalhes do Arquivo GeoIP</h1>';
    if (file_exists($file_path)) {
        echo '<p><strong>Localização do Arquivo:</strong> ' . esc_html($file_path) . '</p>';
        echo '<p><strong>Tamanho do Arquivo:</strong> ' . esc_html(size_format(filesize($file_path))) . '</p>';
    } else {
        echo '<p>Nenhum arquivo GeoIP foi baixado ainda.</p>';
    }
    echo '</div>';
}

add_action('network_admin_menu', 'mahoee_tracking_export_menu');
function mahoee_tracking_export_menu()
{
    add_submenu_page(
        'mahoee-tracking',
        'Exportar Dados',
        'Exportar',
        'manage_network_options',
        'mahoee-tracking-export',
        'mahoee_tracking_export_page'
    );
}

function mahoee_tracking_export_page()
{
    echo '<div class="wrap">';
    echo '<h1>Exportar Dados de Visitas</h1>';
    echo '<form method="post" action="">';
    echo '<input type="submit" name="mahoee_tracking_export_csv" class="button button-primary" value="Exportar CSV" />';
    echo '</form>';
    echo '</div>';
}

add_action('admin_init', 'mahoee_tracking_maybe_export_csv');
function mahoee_tracking_maybe_export_csv()
{
    if (isset($_POST['mahoee_tracking_export_csv'])) {
        mahoee_tracking_export_csv();
    }
}

function mahoee_tracking_export_csv()
{
    global $wpdb;
    $table_name = $wpdb->base_prefix . 'mahoee_tracking';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $filename = 'mahoee_tracking_' . date('Y-m-d') . '.csv';

    // Clear buffer if needed
    if (ob_get_length()) {
        ob_end_clean();
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment;filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, array('id', 'site_id', 'ip', 'city', 'state', 'country', 'visit_time'));

    foreach ($results as $row) {
        fputcsv($output, (array) $row);
    }
    fclose($output);
    exit;
}

add_action('network_admin_menu', 'mahoee_tracking_download_geoip_menu');
function mahoee_tracking_download_geoip_menu()
{
    add_submenu_page(
        'mahoee-tracking',
        'Download GeoIP Data',
        'Download GeoIP Data',
        'manage_network_options',
        'mahoee-tracking-download-geoip',
        'mahoee_tracking_download_geoip_page'
    );
}

function mahoee_tracking_download_geoip_page()
{
    if (isset($_POST['download_geoip_data'])) {
        $file_path = mahoee_tracking_download_geoip_data();
        if ($file_path) {
            echo '<div class="updated"><p>Arquivo GeoIP baixado com sucesso para: ' . esc_html($file_path) . '</p></div>';
        } else {
            echo '<div class="error"><p>Falha ao baixar o arquivo GeoIP.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Download GeoIP Data</h1>';
    echo '<form method="post" action="">';
    echo '<input type="submit" name="download_geoip_data" class="button button-primary" value="Download GeoIP Data" />';
    echo '</form>';
    echo '</div>';
}

function extract_file_with_extension_from_tar_gz($tar_gz_path, $extension, $output_file)
{
    $file_found = false;

    try {
        // Open TAR archive
        $phar = new PharData($tar_gz_path);

        // Look for wanted extension in the archive
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === $extension) {

                // Extract the file to the output file
                $file_path = $file->getPathname();
                copy($file_path, $output_file);
                $file_found = true;

                // Stop after finding the first matching file
                break;
            }
        }

        // Throw an exception if no file was found
        if (!$file_found) {
            throw new Exception("No file with extension '$extension' found in the archive.");
        }
    } catch (Exception $e) {
        error_log('Error extracting file: ' . $e->getMessage());
        throw $e;
    }
}
<?php

function mahoee_acme_network_settings_page()
{
    if (!current_user_can('manage_network_options')) {
        return;
    }

    if (isset($_POST['mahoee_acme_save_network_settings'])) {
        check_admin_referer('mahoee_acme_network_settings');
        update_site_option('mahoee_acme_technical_contact', sanitize_text_field($_POST['technical_contact']));
        update_site_option('mahoee_acme_directory_url', esc_url_raw($_POST['directory_url']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $technical_contact = get_site_option('mahoee_acme_technical_contact');
    $directory_url = get_site_option('mahoee_acme_directory_url');
    ?>
<div class="wrap">
    <h1>Certificados da Rede</h1>
    <form method="post" action="">
        <?php wp_nonce_field('mahoee_acme_network_settings');?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="technical_contact">Technical Contact</label></th>
                <td><input name="technical_contact" type="email" id="technical_contact"
                        value="<?php echo esc_attr($technical_contact); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="directory_url">Directory URL</label></th>
                <td><input name="directory_url" type="url" id="directory_url"
                        value="<?php echo esc_attr($directory_url); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php submit_button('Save Settings', 'primary', 'mahoee_acme_save_network_settings');?>
    </form>
</div>
<?php
}
?>
<?php

function mahoee_acme_site_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['mahoee_acme_validate_domain'])) {
        check_admin_referer('mahoee_acme_validate_domain');
        update_option('mahoee_acme_domain', sanitize_text_field($_POST['domain']));

        echo '<div class="updated"><p>Configurações salvas.</p></div>';

        // Request the certificate
        mahoee_acme_request_certificate(sanitize_text_field($_POST['domain']));

        echo '<div class="updated"><p>Validação solicitada.</p></div>';
    }

    $domain = get_option('mahoee_acme_domain');
    $status = get_option('mahoee_acme_status');

    if (isset($_POST['mahoee_acme_get_certificate'])) {
        // Perform the certificate check and download
        mahoee_acme_check_certificate($domain);
    }

    $order = get_option('mahoee_acme_order');
    $token = get_option('mahoee_acme_token');
    $digest = get_option('mahoee_acme_digest');
    $certificate = get_option('mahoee_acme_certificate');
    $private_key = get_option('mahoee_acme_private_key');

    ?>
<div class="wrap">
    <h1>Certificados desse Site</h1>
    <p>
        Aqui você pode solicitar um novo certificado usando um provedor como Let's Encrypt. Primeiro você precisa
        validar seu domínio e depois você solicita a emissão do certificado.
    </p>
    <h2>1. Valide seu Domínio</h2>
    <form method="post" action="">
        <?php wp_nonce_field('mahoee_acme_validate_domain');?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="domain">Domain</label></th>
                <td><input name="domain" type="text" id="domain" value="<?php echo esc_attr($domain); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row">Status</th>
                <td><?php echo esc_html($status); ?></td>
            </tr>
        </table>
        <?php submit_button('Validar Domínio', 'primary', 'mahoee_acme_validate_domain');?>
    </form>
    <h2>2. Solicite o Certificado</h2>
    <?php

    if ($status === 'certificate-issued') {
        echo '<p class="description">Um certificado já foi emitido. Peça para validar novamente para depois solicitar novo certificado.</p>';
    }

    ?>
    <form method="post" action="">
        <?php wp_nonce_field('mahoee_acme_get_certificate');?>
        <table class="form-table">
            <tr>
                <th scope="row">Order</th>
                <td class="code"><?php echo esc_attr($order); ?></td>
            </tr>
            <tr>
                <th scope="row">Token</th>
                <td class="code"><?php echo esc_attr($token); ?></td>
            </tr>
            <tr>
                <th scope="row">Digest</th>
                <td class="code"><?php echo esc_attr($digest); ?></td>
            </tr>
            <tr>
                <th scope="row">Certificate</th>
                <td><textarea class="code" cols="64" rows="10" readonly><?php echo esc_attr($certificate); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">Private Key</th>
                <td><textarea class="code" cols="64" rows="10" readonly><?php echo esc_attr($private_key); ?></textarea>
                </td>
            </tr>
        </table>
        <?php

    if ($status === 'challenge-validated') {
        submit_button('Solicitar Certificado', 'primary', 'mahoee_acme_get_certificate');
    }

    ?>
    </form>
</div>
<?php
}
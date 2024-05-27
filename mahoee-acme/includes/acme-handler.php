<?php

use Afosto\Acme\Client;

function mahoee_acme_request_certificate($domain)
{
    global $filesystem;

    $directory_url = get_site_option('mahoee_acme_directory_url');
    $technical_contact = get_site_option('mahoee_acme_technical_contact');

    update_option('mahoee_acme_status', 'request-started');
    update_option('mahoee_acme_order', '');
    update_option('mahoee_acme_token', '');
    update_option('mahoee_acme_digest', '');

    try {

        // Initialize the ACME client
        $client = new Client([
            'baseUri' => $directory_url,
            'username' => $technical_contact,
            'fs' => $filesystem,
        ]);

        // Create a new order for the domain
        $order = $client->createOrder([$domain]);
        update_option('mahoee_acme_order', $order->getURL());
        update_option('mahoee_acme_status', 'order-created');

        // Authorize the order and validate HTTP challenge
        $authorizations = $client->authorize($order);
        $authorization = reset($authorizations);
        $challenge = $authorization->getHttpChallenge();
        update_option('mahoee_acme_token', $challenge->getToken());
        update_option('mahoee_acme_digest', $authorization->getDigest());
        update_option('mahoee_acme_status', 'challenge-obtained');
        if ($client->validate($challenge, 15)) {
            update_option('mahoee_acme_status', 'challenge-validated');
        } else {
            update_option('mahoee_acme_status', 'challenge-failed');
        }

    } catch (Exception $e) {
        error_log('Error in mahoee_acme_request_certificate: ' . $e->getMessage());
        update_option('mahoee_acme_status', 'error-after-' . get_option('mahoee_acme_status'));
    }
}

function mahoee_acme_check_certificate($domain)
{
    global $filesystem;

    $directory_url = get_site_option('mahoee_acme_directory_url');
    $technical_contact = get_site_option('mahoee_acme_technical_contact');

    $order_url = get_option('mahoee_acme_order');

    // Retrieve the order ID for the domain
    $client = new Client([
        'baseUri' => $directory_url,
        'username' => $technical_contact,
        'fs' => $filesystem,
    ]);
    $order = $client->getOrder($order_url);

    // Check if the client is ready to retrieve the certificate
    if ($order->getStatus() == 'ready') {
        $certificate = $client->getCertificate($order);
        update_option('mahoee_acme_certificate', $certificate->getCertificate());
        update_option('mahoee_acme_private_key', $certificate->getPrivateKey());
        update_option('mahoee_acme_status', 'certificate-issued');
    }
}
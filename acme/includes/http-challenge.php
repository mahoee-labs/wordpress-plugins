<?php

function mahoee_acme_handle_http_challenge()
{
    if (isset($_GET['acme-challenge'])) {
        $challenge = sanitize_text_field($_GET['acme-challenge']);
        $response = get_option('mahoee_acme_http_challenge_' . $challenge);
        if ($response) {
            echo esc_html($response);
            exit;
        }
    }
}

// Function to save HTTP-01 challenge responses
function mahoee_acme_save_http_challenge($challenge, $response)
{
    update_option('mahoee_acme_http_challenge_' . $challenge, $response);
}
<?php

function mahoee_acme_serve_challenge()
{
    $uri = $_SERVER['REQUEST_URI'];
    if (strpos($uri, '/.well-known/acme-challenge/') === 0) {
        // Extract the token from the URL
        $parts = explode('/', $uri);
        $requesed_token = end($parts);

        // Specify the token and corresponding response
        $token = get_option('mahoee_acme_token');
        $digest = get_option('mahoee_acme_digest');
        $response = $token . '.' . $digest;

        if ($token === $requesed_token) {
            // Serve the challenge response
            status_header(200);
            header('Content-Type: application/octet-stream');
            echo $response;
            exit;
        } else {
            // If the token doesn't match, return a 404 response
            status_header(404);
            echo '404 Not Found';
            exit;
        }
    }
}
<?php
// Google OAuth configuration and helper
require_once __DIR__ . '/../vendor/autoload.php';

// TODO: Replace with your credentials from Google Cloud Console
// OAuth 2.0 Client IDs (Web application)
$GOOGLE_CLIENT_ID = getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID';
$GOOGLE_CLIENT_SECRET = getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET';
$GOOGLE_REDIRECT_URI = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/MindCare-AI/auth/google_callback.php';

function google_client(): Google_Client {
    global $GOOGLE_CLIENT_ID, $GOOGLE_CLIENT_SECRET, $GOOGLE_REDIRECT_URI;
    $client = new Google_Client();
    $client->setClientId($GOOGLE_CLIENT_ID);
    $client->setClientSecret($GOOGLE_CLIENT_SECRET);
    $client->setRedirectUri($GOOGLE_REDIRECT_URI);
    $client->setAccessType('offline'); // request refresh token
    $client->setPrompt('consent');
    $client->setIncludeGrantedScopes(true);
    $client->addScope('email');
    $client->addScope('profile');
    return $client;
}

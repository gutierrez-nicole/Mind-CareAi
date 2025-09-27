<?php
// Centralized secure session configuration
// Ensures cookies are HttpOnly, SameSite=Lax, and have a multi-day lifetime
// to keep users logged in until they log out or the session expires.

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $lifetime = 60 * 60 * 24 * 7; // 7 days
    // Ensure server-side session data persists accordingly
    ini_set('session.gc_maxlifetime', (string)$lifetime);

    // Configure cookie parameters before starting the session
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Use a custom session name to avoid collisions
    session_name('mindcare_session');
    session_start();
}

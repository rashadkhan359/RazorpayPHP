<?php

namespace App\Middleware\RateLimiter;

class SessionRateLimiter implements RateLimitStorage {
    public function check($ip, $route, $limit, $window) {
        $now = time();
        $sessionKey = "rate_limit_{$ip}_{$route}";

        // Get the current session data for the IP and route
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // info('Current count before cleanup: ' . count($_SESSION[$sessionKey]));

        // Clean up old records
        $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function ($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        // Debugging after cleanup
        // info('Current count after cleanup: ' . count($_SESSION[$sessionKey]));

        // Count the number of attempts for the given route and IP
        if (count($_SESSION[$sessionKey]) >= $limit) {
            // info("Rate limit exceeded for {$ip} on {$route}. Attempts: " . count($_SESSION[$sessionKey]));
            return false;
        }

        // Log the current attempt
        $_SESSION[$sessionKey][] = $now;

        // Debugging after adding the current attempt
        // info('Attempt logged. Current count: ' . count($_SESSION[$sessionKey]));

        return true;
    }

}

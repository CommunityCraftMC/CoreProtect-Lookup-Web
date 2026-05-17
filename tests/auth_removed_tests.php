<?php

final class AuthRemovedTestFailure extends Exception {}

function assert_auth_not_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new AuthRemovedTestFailure($message . "\nUnexpected: " . $needle);
    }
}

function assert_file_missing($path, $message) {
    if (file_exists($path)) {
        throw new AuthRemovedTestFailure($message . "\nUnexpected file: " . $path);
    }
}

$root = dirname(__DIR__);
$indexSource = file_get_contents($root . '/index.php');
$lookup = file_get_contents($root . '/lookup.php');
$main = file_get_contents($root . '/res/js/main.js');
$configSource = file_get_contents($root . '/config.php');
$css = file_get_contents($root . '/res/css/main.css');

ob_start();
include $root . '/index.php';
$indexRendered = ob_get_clean();

$tests = [
    'index has no login ui or session bootstrap' => function () use ($indexSource, $indexRendered) {
        foreach ([
            'Session.class.php',
            'login-modal',
            'login-form',
            'login-activate',
            'loginRequired',
            'loginUsername',
        ] as $needle) {
            assert_auth_not_contains($needle, $indexSource . $indexRendered, 'index should not include authentication UI/bootstrap');
        }
    },
    'lookup endpoint has no authentication gate' => function () use ($lookup) {
        foreach ([
            'Session.class.php',
            'hasLookupAccess',
            'Access Denied',
            'username',
            'password',
        ] as $needle) {
            assert_auth_not_contains($needle, $lookup, 'lookup.php should not perform authentication');
        }
    },
    'main js has no login flow' => function () use ($main) {
        foreach ([
            '$login',
            'login.php',
            'loginUsername',
            'loginRequired',
            'not logged in',
        ] as $needle) {
            assert_auth_not_contains($needle, $main, 'main.js should not contain authentication flow');
        }
    },
    'config and css have no auth remnants' => function () use ($configSource, $css) {
        foreach ([
            'administrator',
            "'user' =>",
            'Account Configuration',
            'login-username',
            'login-password',
        ] as $needle) {
            assert_auth_not_contains($needle, $configSource . $css, 'config/css should not contain authentication settings');
        }
    },
    'auth files are removed' => function () use ($root) {
        assert_file_missing($root . '/login.php', 'login endpoint should be removed');
        assert_file_missing($root . '/res/php/Session.class.php', 'session class should be removed');
        assert_file_missing($root . '/tests/session_tests.php', 'session tests should be removed');
    },
];

$failures = 0;

foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] " . $name . PHP_EOL;
    } catch (Throwable $e) {
        $failures++;
        echo "[FAIL] " . $name . PHP_EOL . $e->getMessage() . PHP_EOL;
    }
}

if ($failures > 0) {
    exit(1);
}

echo "Auth removal tests passed." . PHP_EOL;

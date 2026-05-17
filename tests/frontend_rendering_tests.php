<?php

final class FrontendRenderingTestFailure extends Exception {}

function assert_render_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new FrontendRenderingTestFailure($message . "\nMissing: " . $needle);
    }
}

function assert_render_not_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new FrontendRenderingTestFailure($message . "\nUnexpected: " . $needle);
    }
}

$main = file_get_contents(__DIR__ . '/../res/js/main.js');

$tests = [
    'frontend renders normalized action groups' => function () use ($main) {
        foreach ([
            'row.actionGroup',
            'row.actionLabel',
            'case "sign"',
            'case "item"',
            'case "inventory"',
            'renderSignTarget',
            'renderItemTarget',
            'renderMetadataList',
            'metadata.blockMetaHex',
            'metadata.itemDataHex',
            'metadata.containerMetadataHex',
            'metadata.containerMetadataDecoded',
            'renderDecodedMetadataList',
            'Possible PHP error output',
            'lookupTimeoutMs',
            'Request timed out',
        ] as $snippet) {
            assert_render_contains($snippet, $main, 'main.js should render using normalized row data');
        }
    },
    'frontend avoids raw db content html assignment' => function () use ($main) {
        foreach ([
            'target2El.innerHTML = row.target',
            '$login.name.html(`Hello',
            'getAlertElement(text, level) {',
        ] as $snippet) {
            assert_render_not_contains($snippet, $main, 'main.js should not assign raw user/db content as HTML');
        }

        assert_render_contains('getAlertElement(title, level)', $main, 'alerts should build DOM with text nodes');
        assert_render_contains('textContent', $main, 'renderer should use textContent for dynamic content');
    },
    'frontend clears stale lookup alerts before request' => function () use ($main) {
        assert_render_contains('$lookup.alert.empty()', $main, 'new lookup should clear stale lookup alerts');
        assert_render_contains('$more.alert.empty()', $main, 'load-more should clear stale load-more alerts');
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

echo "Frontend rendering tests passed." . PHP_EOL;

<?php

final class FrontendActionControlsTestFailure extends Exception {}

function assert_frontend_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new FrontendActionControlsTestFailure($message . "\nMissing: " . $needle);
    }
}

function assert_frontend_not_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new FrontendActionControlsTestFailure($message . "\nUnexpected: " . $needle);
    }
}

function assert_frontend_before($first, $second, $haystack, $message) {
    $firstPosition = strpos($haystack, $first);
    $secondPosition = strpos($haystack, $second);
    if ($firstPosition === false || $secondPosition === false || $firstPosition >= $secondPosition) {
        throw new FrontendActionControlsTestFailure($message);
    }
}

$indexSource = file_get_contents(__DIR__ . '/../index.php');
$main = file_get_contents(__DIR__ . '/../res/js/main.js');
ob_start();
include __DIR__ . '/../index.php';
$indexRendered = ob_get_clean();

$tests = [
    'index renders action controls from PHP metadata' => function () use ($indexSource, $indexRendered) {
        assert_frontend_contains("require_once \"res/php/ActionDefinitions.class.php\"", $indexSource, 'index should load action metadata');
        assert_frontend_contains('renderActionButtonGroup', $indexSource, 'index should render action groups through helper');
        assert_frontend_contains("ActionDefinitions::groups()", $indexSource, 'index should get action groups from PHP metadata');

        foreach ([
            'lookup-a-session-add',
            'lookup-a-session-sub',
            'lookup-a-sign',
            'lookup-a-item',
            'lookup-a-item-add',
            'lookup-a-item-sub',
            'lookup-a-inventory',
        ] as $id) {
            assert_frontend_contains($id, $indexRendered, 'index should render action checkbox ' . $id);
        }
    },
    'index renders home button before header text' => function () use ($indexRendered) {
        assert_frontend_contains('class="btn btn-secondary navbar-home"', $indexRendered, 'home should render as a leading navbar button');
        assert_frontend_contains('class="fa fa-home"', $indexRendered, 'home button should use home icon');
        assert_frontend_before('class="btn btn-secondary navbar-home"', 'class="navbar-brand"', $indexRendered, 'home button should appear before header text');
        assert_frontend_not_contains('<li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>', $indexRendered, 'home should not be duplicated in collapsed links');
    },
    'index renders CommunityCraft rework footer and original credit' => function () use ($indexRendered) {
        assert_frontend_contains('CommunityCraft rework', $indexRendered, 'footer should title this rework to CommunityCraft');
        assert_frontend_contains('2026', $indexRendered, 'footer should show 2026 release year');
        assert_frontend_contains('CoreProtect 23.2', $indexRendered, 'footer should reference supported CoreProtect version');
        assert_frontend_contains('Original CoLWI by', $indexRendered, 'footer should preserve original creator credit');
        assert_frontend_contains('https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web', $indexRendered, 'footer should link CommunityCraft GitHub repo');
        assert_frontend_contains('https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web/issues', $indexRendered, 'footer should link CommunityCraft issues');
    },
    'main js serializes action metadata without duplicated bit constants' => function () use ($main) {
        assert_frontend_contains('const actionDefinitions = config.actions', $main, 'main.js should read action metadata from config');
        assert_frontend_contains('serializeActionGroup', $main, 'main.js should serialize grouped metadata controls');
        assert_frontend_contains("serializeActionGroup('primary')", $main, 'main.js should serialize primary group');
        assert_frontend_contains("serializeActionGroup('messages')", $main, 'main.js should serialize messages group');
        assert_frontend_contains("serializeActionGroup('items')", $main, 'main.js should serialize items group');

        foreach ([
            'const A_BLOCK_MINE',
            'const A_SIGN',
            'const A_ITEM',
            'actionBlockAdd',
            'actionSign',
            'actionItem',
        ] as $snippet) {
            assert_frontend_not_contains($snippet, $main, 'main.js should not duplicate metadata via ' . $snippet);
        }
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

echo "Frontend action control tests passed." . PHP_EOL;

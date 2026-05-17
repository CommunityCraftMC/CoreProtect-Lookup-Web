<?php

require_once __DIR__ . '/../res/php/ActionDefinitions.class.php';

final class ActionDefinitionsTestFailure extends Exception {}

function assert_action_true($condition, $message) {
    if (!$condition) {
        throw new ActionDefinitionsTestFailure($message);
    }
}

function assert_action_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new ActionDefinitionsTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

$tests = [
    'defines all CoreProtect v23.2 action keys' => function () {
        $actions = ActionDefinitions::all();
        $keys = array_keys($actions);

        assert_action_same([
            'block_remove',
            'block_place',
            'click',
            'kill',
            'container_remove',
            'container_add',
            'chat',
            'command',
            'session',
            'session_login',
            'session_logout',
            'username',
            'sign',
            'item',
            'item_add',
            'item_remove',
            'inventory',
            'inventory_add',
            'inventory_remove',
        ], $keys, 'action metadata should expose legacy plus v23.2 action keys in UI order');
    },
    'keeps legacy action bit values stable' => function () {
        $actions = ActionDefinitions::all();

        assert_action_same(0x0001, $actions['block_remove']['bit'], 'block remove bit should stay stable');
        assert_action_same(0x0002, $actions['block_place']['bit'], 'block place bit should stay stable');
        assert_action_same(0x0004, $actions['click']['bit'], 'click bit should stay stable');
        assert_action_same(0x0008, $actions['kill']['bit'], 'kill bit should stay stable');
        assert_action_same(0x0010, $actions['container_remove']['bit'], 'container remove bit should stay stable');
        assert_action_same(0x0020, $actions['container_add']['bit'], 'container add bit should stay stable');
        assert_action_same(0x0040, $actions['chat']['bit'], 'chat bit should stay stable');
        assert_action_same(0x0080, $actions['command']['bit'], 'command bit should stay stable');
        assert_action_same(0x0100, $actions['session']['bit'], 'session bit should stay stable');
        assert_action_same(0x0200, $actions['username']['bit'], 'username bit should stay stable');
    },
    'assigns new action bits after legacy range' => function () {
        $actions = ActionDefinitions::all();

        assert_action_same(0x00020000, $actions['sign']['bit'], 'sign should use first new action bit after existing flags');
        assert_action_same(0x00040000, $actions['item']['bit'], 'item should use dedicated new bit');
        assert_action_same(0x00080000, $actions['inventory']['bit'], 'inventory should use dedicated new bit');
        assert_action_true($actions['sign']['requiresTable'] === 'sign', 'sign should require sign table');
        assert_action_true($actions['item']['requiresTable'] === 'item', 'item should require item table');
    },
    'maps actions to CoreProtect v23.2 selector values' => function () {
        $actions = ActionDefinitions::all();

        assert_action_same([0], $actions['block_remove']['coreProtectActions'], 'block remove should map to action 0');
        assert_action_same([1], $actions['block_place']['coreProtectActions'], 'block place should map to action 1');
        assert_action_same([2], $actions['click']['coreProtectActions'], 'click should map to action 2');
        assert_action_same([3], $actions['kill']['coreProtectActions'], 'kill should map to action 3');
        assert_action_same([4, 11], $actions['inventory']['coreProtectActions'], 'inventory should map to container and item selectors');
        assert_action_same([10], $actions['sign']['coreProtectActions'], 'sign should map to selector 10');
        assert_action_same([11], $actions['item']['coreProtectActions'], 'item should map to selector 11');
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

echo "Action definition tests passed." . PHP_EOL;


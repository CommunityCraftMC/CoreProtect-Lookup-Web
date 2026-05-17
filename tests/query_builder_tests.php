<?php

require_once __DIR__ . '/../res/php/StatementPreparer.class.php';

final class QueryBuilderTestFailure extends Exception {}

function default_request($action) {
    return [
        'a' => (string) $action,
        'b' => '',
        'e' => '',
        't' => '',
        'u' => '',
        'w' => '',
        'x' => '',
        'x2' => '',
        'y' => '',
        'y2' => '',
        'z' => '',
        'z2' => '',
        'keyword' => '',
        'count' => '',
        'offset' => '',
    ];
}

function build_query(array $request, $flags = 0) {
    $prep = new StatementPreparer('co_', $request, 30, 10, $flags);

    return [
        'sql' => normalize_sql($prep->prepareStatementData()),
        'params' => $prep->getParams(),
    ];
}

function normalize_sql($sql) {
    return preg_replace('/\s+/', ' ', trim($sql));
}

function assert_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new QueryBuilderTestFailure($message . "\nMissing: " . $needle . "\nSQL: " . $haystack);
    }
}

function assert_not_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new QueryBuilderTestFailure($message . "\nUnexpected: " . $needle . "\nSQL: " . $haystack);
    }
}

function assert_not_empty($value, $message) {
    if ($value === '' || $value === null || $value === []) {
        throw new QueryBuilderTestFailure($message);
    }
}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new QueryBuilderTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

$tests = [
    'legacy block break filters material and action' => function () {
        $request = default_request(StatementPreparer::A_BLOCK_MINE);
        $request['b'] = 'stone';

        $result = build_query($request, StatementPreparer::FLAG_PRE_BLOCK_NAME);

        assert_contains('FROM `co_block` AS c', $result['sql'], 'block lookup should use co_block');
        assert_contains('c.action IN (0)', $result['sql'], 'block break should filter action 0');
        assert_contains('mm.material IN(?)', $result['sql'], 'material filter should use material map');
        assert_same(['minecraft:stone', 0, 30], $result['params'], 'block break params should include material and default limit');
    },
    'legacy block lookup filters username without malformed subquery' => function () {
        $request = default_request(StatementPreparer::A_BLOCK_PLACE);
        $request['u'] = 'GreenSensei';

        $result = build_query($request);

        assert_contains('u.user IN(?)', $result['sql'], 'user filter should use joined user alias');
        assert_not_contains('SELECT u.rowid WHERE', $result['sql'], 'user filter should not generate a subquery without FROM');
        assert_same(['GreenSensei', 0, 30], $result['params'], 'user filter params should include username and default limit');
    },
    'legacy block lookup filters user uuid params' => function () {
        $request = default_request(StatementPreparer::A_BLOCK_PLACE);
        $request['u'] = '11111111-2222-3333-4444-555555555555';

        $result = build_query($request);

        assert_contains('u.uuid IN(?)', $result['sql'], 'uuid filter should use joined user uuid alias');
        assert_same(['11111111-2222-3333-4444-555555555555', 0, 30], $result['params'], 'uuid filter params should include uuid and default limit');
    },
    'v23.2 metadata flags select modern metadata aliases' => function () {
        $request = default_request(StatementPreparer::A_BLOCK_PLACE | StatementPreparer::A_CONTAINER_IN | StatementPreparer::A_ITEM);
        $flags = StatementPreparer::FLAG_USE_BLOCK_META
            | StatementPreparer::FLAG_USE_BLOCK_BLOCKDATA
            | StatementPreparer::FLAG_USE_CONTAINER_METADATA
            | StatementPreparer::FLAG_USE_ITEM_DATA;

        $result = build_query($request, $flags);

        assert_contains('HEX(c.meta) AS `block_meta`', $result['sql'], 'block metadata flag should select block meta hex');
        assert_contains('HEX(c.blockdata) AS `block_blockdata`', $result['sql'], 'blockdata flag should select blockdata hex');
        assert_contains('HEX(c.metadata) AS `container_metadata`', $result['sql'], 'container metadata flag should select container metadata hex');
        assert_contains('HEX(c.data) AS `item_data`', $result['sql'], 'item metadata flag should select item data hex');
    },
    'legacy container add filters action one' => function () {
        $request = default_request(StatementPreparer::A_CONTAINER_IN);

        $result = build_query($request);

        assert_contains('FROM `co_container` AS c', $result['sql'], 'container lookup should use co_container');
        assert_contains('c.action=1', $result['sql'], 'container add should filter action 1');
        assert_same([0, 30], $result['params'], 'container params should include default limit');
    },
    'legacy container remove filters action zero' => function () {
        $request = default_request(StatementPreparer::A_CONTAINER_OUT);

        $result = build_query($request);

        assert_contains('FROM `co_container` AS c', $result['sql'], 'container lookup should use co_container');
        assert_contains('c.action=0', $result['sql'], 'container remove should filter action 0');
        assert_same([0, 30], $result['params'], 'container params should include default limit');
    },
    'legacy click filters block action two' => function () {
        $request = default_request(StatementPreparer::A_CLICK);

        $result = build_query($request);

        assert_contains('FROM `co_block` AS c', $result['sql'], 'click lookup should use co_block');
        assert_contains('c.action IN (2)', $result['sql'], 'click should filter action 2');
        assert_same([0, 30], $result['params'], 'click params should include default limit');
    },
    'legacy kill filters block action three' => function () {
        $request = default_request(StatementPreparer::A_KILL);

        $result = build_query($request);

        assert_contains('FROM `co_block` AS c', $result['sql'], 'kill lookup should use co_block');
        assert_contains('c.action IN (3)', $result['sql'], 'kill should filter action 3');
        assert_contains('LEFT JOIN `co_entity_map` AS em', $result['sql'], 'kill should join entity map');
        assert_same([0, 30], $result['params'], 'kill params should include default limit');
    },
    'legacy chat lookup uses chat table' => function () {
        $request = default_request(StatementPreparer::A_CHAT);

        $result = build_query($request);

        assert_contains('FROM `co_chat` AS c', $result['sql'], 'chat lookup should use co_chat');
        assert_contains('c.message AS `target`', $result['sql'], 'chat lookup should expose message target');
        assert_same([0, 30], $result['params'], 'chat params should include default limit');
    },
    'legacy command lookup uses command table' => function () {
        $request = default_request(StatementPreparer::A_COMMAND);

        $result = build_query($request);

        assert_contains('FROM `co_command` AS c', $result['sql'], 'command lookup should use co_command');
        assert_contains('c.message AS `target`', $result['sql'], 'command lookup should expose message target');
        assert_same([0, 30], $result['params'], 'command params should include default limit');
    },
    'legacy session lookup includes coordinates' => function () {
        $request = default_request(StatementPreparer::A_SESSION);
        $request['w'] = 'world';
        $request['x'] = '1';
        $request['x2'] = '3';
        $request['y'] = '64';
        $request['y2'] = '70';
        $request['z'] = '-5';
        $request['z2'] = '0';

        $result = build_query($request);

        assert_contains('FROM `co_session` AS c', $result['sql'], 'session lookup should use co_session');
        assert_contains('w.world IN(?)', $result['sql'], 'world filter should use world map');
        assert_contains('x BETWEEN ? AND ?', $result['sql'], 'coordinate filter should be present');
        assert_same(['world', 1, 3, 64, 70, -5, 0, 0, 30], $result['params'], 'session params should preserve filter and limit order');
    },
    'v23.2 session login filters action one' => function () {
        $request = default_request(0x00100000);

        $result = build_query($request);

        assert_contains('FROM `co_session` AS c', $result['sql'], 'session login lookup should use co_session');
        assert_contains('c.action=1', $result['sql'], 'session login should filter action 1');
        assert_same([0, 30], $result['params'], 'session login params should include default limit');
    },
    'v23.2 session logout filters action zero' => function () {
        $request = default_request(0x00200000);

        $result = build_query($request);

        assert_contains('FROM `co_session` AS c', $result['sql'], 'session logout lookup should use co_session');
        assert_contains('c.action=0', $result['sql'], 'session logout should filter action 0');
        assert_same([0, 30], $result['params'], 'session logout params should include default limit');
    },
    'legacy username lookup uses username log table' => function () {
        $request = default_request(StatementPreparer::A_USERNAME);

        $result = build_query($request);

        assert_contains('FROM `co_username_log` AS c', $result['sql'], 'username lookup should use co_username_log');
        assert_contains('c.user AS target', $result['sql'], 'username lookup should expose old username target');
        assert_same([0, 30], $result['params'], 'username params should include default limit');
    },
    'legacy multi table lookup unions block and container' => function () {
        $request = default_request(StatementPreparer::A_BLOCK_PLACE | StatementPreparer::A_CONTAINER_OUT);

        $result = build_query($request);

        assert_contains('UNION ALL', $result['sql'], 'multi-table lookup should use union');
        assert_contains('FROM `co_block` AS c', $result['sql'], 'multi-table lookup should include block table');
        assert_contains('FROM `co_container` AS c', $result['sql'], 'multi-table lookup should include container table');
    },
    'v23.2 sign lookup uses sign table and non-empty text filter' => function () {
        $request = default_request(0x00020000);

        $result = build_query($request);

        assert_not_empty($result['sql'], 'sign lookup should produce SQL');
        assert_contains('FROM `co_sign` AS c', $result['sql'], 'sign lookup should use co_sign');
        assert_contains('c.action=1', $result['sql'], 'sign lookup should filter written signs');
        assert_contains('LENGTH(c.line_1) > 0', $result['sql'], 'sign lookup should ignore empty sign text rows');
        assert_same([0, 30], $result['params'], 'sign params should include default limit');
    },
    'v23.2 chat lookup should support coordinates' => function () {
        $request = default_request(StatementPreparer::A_CHAT);
        $request['w'] = 'world';
        $request['x'] = '1';
        $request['x2'] = '3';
        $request['y'] = '64';
        $request['y2'] = '70';
        $request['z'] = '-5';
        $request['z2'] = '0';
        $result = build_query($request);

        assert_contains('w.world IN(?)', $result['sql'], 'v23.2 chat lookup should support world filter');
        assert_contains('x BETWEEN ? AND ?', $result['sql'], 'v23.2 chat lookup should support coordinate filter');
    },
    'v23.2 command lookup should support coordinates' => function () {
        $request = default_request(StatementPreparer::A_COMMAND);
        $request['w'] = 'world';
        $request['x'] = '1';
        $request['x2'] = '3';
        $request['y'] = '64';
        $request['y2'] = '70';
        $request['z'] = '-5';
        $request['z2'] = '0';
        $result = build_query($request);

        assert_contains('w.world IN(?)', $result['sql'], 'v23.2 command lookup should support world filter');
        assert_contains('x BETWEEN ? AND ?', $result['sql'], 'v23.2 command lookup should support coordinate filter');
    },
    'v23.2 item lookup uses item table and default exclusions' => function () {
        $request = default_request(0x00040000);

        $result = build_query($request);

        assert_not_empty($result['sql'], 'item lookup should produce SQL');
        assert_contains('FROM `co_item` AS c', $result['sql'], 'item lookup should use co_item');
        assert_contains('c.action NOT IN (8,9,10,11,12)', $result['sql'], 'generic item lookup should hide internal item actions');
        assert_contains('c.type=mm.id', $result['sql'], 'item lookup should join modern material id');
        assert_same([0, 30], $result['params'], 'item params should include default limit');
    },
    'v23.2 item add filters pickup and ender remove' => function () {
        $request = default_request(0x00400000);

        $result = build_query($request);

        assert_contains('FROM `co_item` AS c', $result['sql'], 'item add lookup should use co_item');
        assert_contains('c.action IN (3,4)', $result['sql'], 'item add should filter pickup and ender remove actions');
        assert_same([0, 30], $result['params'], 'item add params should include default limit');
    },
    'v23.2 item remove filters drop-like actions' => function () {
        $request = default_request(0x00800000);

        $result = build_query($request);

        assert_contains('FROM `co_item` AS c', $result['sql'], 'item remove lookup should use co_item');
        assert_contains('c.action IN (2,5,6,7)', $result['sql'], 'item remove should filter drop, ender add, throw, and shoot actions');
        assert_same([0, 30], $result['params'], 'item remove params should include default limit');
    },
    'v23.2 inventory lookup should union block container and item' => function () {
        $request = default_request(0x00080000);

        $result = build_query($request);

        assert_not_empty($result['sql'], 'inventory lookup should produce SQL');
        assert_contains('FROM `co_block`', $result['sql'], 'inventory lookup should include block table');
        assert_contains('FROM `co_container`', $result['sql'], 'inventory lookup should include container table');
        assert_contains('FROM `co_item`', $result['sql'], 'inventory lookup should include item table');
        assert_contains('UNION ALL', $result['sql'], 'inventory lookup should union source tables');
    },
];

$todoTests = [];

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

foreach ($todoTests as $name => $test) {
    try {
        $test();
        echo "[TODO-PASS] " . $name . PHP_EOL;
    } catch (Throwable $e) {
        echo "[TODO] " . $name . " - " . $e->getMessage() . PHP_EOL;
    }
}

if ($failures > 0) {
    exit(1);
}

echo "Query builder baseline tests passed." . PHP_EOL;

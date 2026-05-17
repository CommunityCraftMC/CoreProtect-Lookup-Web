<?php

require_once __DIR__ . '/../res/php/LookupRowNormalizer.class.php';

final class LookupRowNormalizerTestFailure extends Exception {}

function assert_row_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new LookupRowNormalizerTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

function java_string_stream_hex($strings) {
    $bytes = "\xAC\xED\x00\x05";
    foreach ($strings as $string) {
        $bytes .= "\x74" . pack('n', strlen($string)) . $string;
    }
    return strtoupper(bin2hex($bytes));
}

$block = LookupRowNormalizer::normalize([
    'rowid' => '12',
    'table' => 'block',
    'time' => '100',
    'user' => 'Alice',
    'uuid' => 'uuid-a',
    'action' => '1',
    'world' => 'world',
    'x' => '0',
    'y' => '64',
    'z' => '-3',
    'target' => 'minecraft:stone',
    'data' => '0',
    'amount' => null,
    'rolled_back' => '0',
    'block_meta' => '0A0B',
    'block_blockdata' => 'DEADBEEF',
]);
assert_row_same(12, $block['id'], 'rowid should normalize to integer id');
assert_row_same('block', $block['source'], 'source should mirror table');
assert_row_same('block', $block['actionGroup'], 'block source should map to block group');
assert_row_same('+Block', $block['actionLabel'], 'block action 1 should label as +Block');
assert_row_same('material', $block['targetType'], 'block target should be material');
assert_row_same(0, $block['x'], 'zero x coordinate should remain int zero');
assert_row_same(-3, $block['z'], 'negative z coordinate should normalize');
assert_row_same(0, $block['rolledBack'], 'rolledBack alias should normalize');
assert_row_same('0A0B', $block['metadata']['blockMetaHex'], 'block metadata should include meta hex');
assert_row_same('DEADBEEF', $block['metadata']['blockDataHex'], 'block metadata should include blockdata hex');

$sign = LookupRowNormalizer::normalize([
    'rowid' => '15',
    'table' => 'sign',
    'time' => '101',
    'user' => 'Bob',
    'uuid' => 'uuid-b',
    'action' => '1',
    'world' => 'world',
    'x' => '1',
    'y' => '65',
    'z' => '2',
    'target' => 'Line 1',
    'data' => null,
    'amount' => null,
    'rolled_back' => null,
    'sign_line_1' => 'Line 1',
    'sign_line_2' => 'Line 2',
    'sign_line_3' => '',
    'sign_line_4' => '',
    'sign_line_5' => null,
    'sign_line_6' => null,
    'sign_line_7' => null,
    'sign_line_8' => null,
    'sign_face' => 'front',
    'sign_waxed' => '1',
    'sign_color' => 'black',
]);
assert_row_same('sign', $sign['actionGroup'], 'sign should map to sign group');
assert_row_same('Sign', $sign['actionLabel'], 'sign should label as Sign');
assert_row_same('sign', $sign['targetType'], 'sign target type should be sign');
assert_row_same(['Line 1', 'Line 2', '', '', null, null, null, null], $sign['metadata']['lines'], 'sign metadata should include all lines');
assert_row_same('front', $sign['metadata']['face'], 'sign metadata should include face');
assert_row_same(1, $sign['metadata']['waxed'], 'sign metadata should cast waxed to int');
assert_row_same('black', $sign['metadata']['color'], 'sign metadata should include color');

$item = LookupRowNormalizer::normalize([
    'id' => '18',
    'table' => 'item',
    'time' => '102',
    'user' => 'Chris',
    'uuid' => 'uuid-c',
    'action' => '3',
    'world' => 'world',
    'x' => '5',
    'y' => '66',
    'z' => '6',
    'target' => 'minecraft:diamond',
    'data' => null,
    'amount' => '4',
    'rolled_back' => '1',
    'item_data' => 'CAFEBABE',
]);
assert_row_same('item', $item['actionGroup'], 'item should map to item group');
assert_row_same('+Item', $item['actionLabel'], 'item action 3 should label as +Item');
assert_row_same('item', $item['targetType'], 'item target type should be item');
assert_row_same(4, $item['amount'], 'amount should normalize to int');
assert_row_same('CAFEBABE', $item['metadata']['itemDataHex'], 'item metadata should include item data hex');

$container = LookupRowNormalizer::normalize([
    'id' => '19',
    'table' => 'container',
    'time' => '103',
    'user' => 'Dana',
    'uuid' => 'uuid-d',
    'action' => '0',
    'world' => 'world',
    'x' => '7',
    'y' => '67',
    'z' => '8',
    'target' => 'minecraft:chest',
    'data' => '0',
    'amount' => '1',
    'rolled_back' => '0',
    'container_metadata' => java_string_stream_hex([
        'meta-type',
        'display-name',
        'lore',
        'ARMOR',
        '{"text":"Decoded Chest Item"}',
        '{"text":"+ ","extra":[{"text":"Protection","extra":[{"text":" IV"}]}]}',
    ]),
]);
assert_row_same('ARMOR', $container['metadata']['containerMetadataDecoded']['metaType'], 'container metadata should decode Java metadata');
assert_row_same('Decoded Chest Item', $container['metadata']['containerMetadataDecoded']['displayName'], 'container metadata should expose decoded display name');

echo "Lookup row normalizer tests passed\n";

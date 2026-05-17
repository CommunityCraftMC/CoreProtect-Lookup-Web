<?php

require_once __DIR__ . '/../res/php/StatementPreparer.class.php';
require_once __DIR__ . '/../res/php/LookupRequest.class.php';

final class LookupRequestTestFailure extends Exception {}

function assert_lookup_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new LookupRequestTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

$request = [
    'a' => (string) StatementPreparer::A_BLOCK_MINE,
    'b' => 'stone, minecraft:dirt',
    'e' => '',
    't' => '123',
    'u' => ' Alice ,Bob ',
    'w' => 'world',
    'x' => '0',
    'x2' => '10',
    'y' => '64',
    'y2' => '70',
    'z' => '-5',
    'z2' => '5',
    'keyword' => ' hello world ',
    'count' => '',
    'offset' => '',
];

$lookup = LookupRequest::fromRequest($request, 30, 10, StatementPreparer::FLAG_PRE_BLOCK_NAME);

assert_lookup_same(StatementPreparer::A_BLOCK_MINE, $lookup->a, 'action should parse as int');
assert_lookup_same(['minecraft:stone', 'minecraft:dirt'], $lookup->b, 'material names should be csv-trimmed and prefixed');
assert_lookup_same(['Alice', 'Bob'], $lookup->u, 'users should be csv-trimmed');
assert_lookup_same(['world'], $lookup->w, 'world should parse as csv array');
assert_lookup_same(0, $lookup->x, 'zero coordinate should remain integer zero');
assert_lookup_same(10, $lookup->x2, 'x2 should parse as int');
assert_lookup_same(-5, $lookup->z, 'negative coordinate should parse as int');
assert_lookup_same(['hello world'], $lookup->keyword, 'keyword should parse without splitting words');
assert_lookup_same(0, $lookup->offset, 'empty offset should default to zero');
assert_lookup_same(30, $lookup->count, 'empty count should use initial count default');

$moreRequest = $request;
$moreRequest['offset'] = '30';
$moreRequest['count'] = '';
$more = LookupRequest::fromRequest($moreRequest, 30, 10);
assert_lookup_same(10, $more->count, 'load-more empty count should use more-count default');

echo "Lookup request tests passed\n";

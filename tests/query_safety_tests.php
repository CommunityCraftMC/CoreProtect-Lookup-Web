<?php

require_once __DIR__ . '/../res/php/QuerySafety.class.php';

final class QuerySafetyTestFailure extends Exception {}

function assert_safety_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new QuerySafetyTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

$config = [
    'max' => 300,
    'maxCoordinateVolume' => 1000000,
    'timeoutSeconds' => 20,
];

$countError = QuerySafety::validateRequest([
    'count' => '500',
], $config);
assert_safety_same('Query too large', $countError['code'], 'count above configured max should be rejected');
assert_safety_same(true, strpos($countError['reason'], 'Limit 500') !== false, 'count error should explain requested limit');

$areaError = QuerySafety::validateRequest([
    'x' => '0',
    'y' => '0',
    'z' => '0',
    'x2' => '1000',
    'y2' => '100',
    'z2' => '1000',
], $config);
assert_safety_same('Query too large', $areaError['code'], 'coordinate volume above configured max should be rejected');
assert_safety_same(true, strpos($areaError['reason'], '101202101') !== false, 'area error should explain volume');

$safe = QuerySafety::validateRequest([
    'count' => '250',
    'x' => '0',
    'y' => '64',
    'z' => '0',
    'x2' => '10',
    'y2' => '70',
    'z2' => '10',
], $config);
assert_safety_same(null, $safe, 'small coordinate query should be allowed');

$defaultAreaError = QuerySafety::validateRequest([
    'x' => '0',
    'y' => '0',
    'z' => '0',
    'x2' => '2000',
    'y2' => '100',
    'z2' => '2000',
], ['max' => 300]);
assert_safety_same('Query too large', $defaultAreaError['code'], 'missing maxCoordinateVolume should still use safe default');

$fatal = QuerySafety::fatalErrorResponse([
    'type' => E_ERROR,
    'message' => 'Maximum execution time of 20 seconds exceeded',
]);
assert_safety_same('Query timed out', $fatal['code'], 'fatal timeout should become a JSON timeout response');

$fatal = QuerySafety::fatalErrorResponse([
    'type' => E_ERROR,
    'message' => 'Allowed memory size exhausted',
]);
assert_safety_same('Server error', $fatal['code'], 'generic fatal should become a JSON server error response');

echo "Query safety tests passed\n";

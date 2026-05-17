<?php

require_once __DIR__ . '/../res/php/SchemaCapabilities.class.php';
require_once __DIR__ . '/../res/php/ActionDefinitions.class.php';

final class SchemaCapabilitiesTestFailure extends Exception {}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new SchemaCapabilitiesTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

class FakeSchemaPDO
{
    private $tables;

    public function __construct($tables) {
        $this->tables = $tables;
    }

    public function query($sql) {
        if (preg_match('/FROM `([^`]+)`/', $sql, $tableMatch) !== 1) {
            throw new Exception('No table in SQL');
        }

        $table = $tableMatch[1];
        if (!array_key_exists($table, $this->tables)) {
            throw new Exception('Missing table ' . $table);
        }

        if (preg_match('/SELECT `([^`]+)`/', $sql, $columnMatch) === 1
                && !in_array($columnMatch[1], $this->tables[$table], true)) {
            throw new Exception('Missing column ' . $columnMatch[1]);
        }

        return true;
    }
}

function schema($withModernTables = true) {
    $tables = [
        'co_block' => ['rowid', 'meta', 'blockdata'],
        'co_container' => ['rowid', 'metadata'],
        'co_chat' => ['rowid', 'wid', 'x', 'y', 'z'],
        'co_command' => ['rowid', 'wid', 'x', 'y', 'z'],
        'co_blockdata_map' => ['rowid'],
    ];

    if ($withModernTables) {
        $tables['co_item'] = ['rowid', 'data'];
        $tables['co_sign'] = ['rowid'];
    }

    return new FakeSchemaPDO($tables);
}

$modern = SchemaCapabilities::detect(schema(), 'co_');
assert_same(true, $modern['tables']['item'], 'modern schema should detect item table');
assert_same(true, $modern['tables']['sign'], 'modern schema should detect sign table');
assert_same(true, $modern['tables']['blockdataMap'], 'modern schema should detect blockdata map table');
assert_same(true, $modern['columns']['blockMeta'], 'modern schema should detect block.meta');
assert_same(true, $modern['columns']['blockBlockdata'], 'modern schema should detect block.blockdata');
assert_same(true, $modern['columns']['containerMetadata'], 'modern schema should detect container.metadata');
assert_same(true, $modern['columns']['itemData'], 'modern schema should detect item.data');
assert_same(true, $modern['columns']['chatCoordinates'], 'modern schema should detect chat coordinates');
assert_same(true, $modern['columns']['commandCoordinates'], 'modern schema should detect command coordinates');

$legacy = SchemaCapabilities::detect(schema(false), 'co_');
assert_same(false, $legacy['tables']['item'], 'legacy schema should mark item table missing');
assert_same(false, $legacy['tables']['sign'], 'legacy schema should mark sign table missing');
assert_same(['Sign'], SchemaCapabilities::unsupportedActionLabels(ActionDefinitions::SIGN, $legacy), 'missing sign table should reject sign action');
assert_same(['Item', '+Item', '-Item', 'Inventory'], SchemaCapabilities::unsupportedActionLabels(
    ActionDefinitions::ITEM | ActionDefinitions::ITEM_ADD | ActionDefinitions::ITEM_REMOVE | ActionDefinitions::INVENTORY,
    $legacy
), 'missing item table should reject item-backed actions');

$legacyBlockOnly = SchemaCapabilities::detect(new FakeSchemaPDO([]), 'co_');
assert_same(false, $legacyBlockOnly['columns']['chatCoordinates'], 'missing chat table should not report chat coordinates');

print "Schema capability tests passed\n";

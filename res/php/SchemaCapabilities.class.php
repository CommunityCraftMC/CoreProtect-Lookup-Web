<?php

require_once __DIR__ . '/ActionDefinitions.class.php';

/**
 * Detects CoreProtect schema features used by lookup generation.
 */
class SchemaCapabilities
{
    public static function detect($pdo, $prefix) {
        return [
            'tables' => [
                'item' => self::tableExists($pdo, $prefix . 'item'),
                'sign' => self::tableExists($pdo, $prefix . 'sign'),
                'blockdataMap' => self::tableExists($pdo, $prefix . 'blockdata_map'),
            ],
            'columns' => [
                'blockMeta' => self::columnExists($pdo, $prefix . 'block', 'meta'),
                'blockBlockdata' => self::columnExists($pdo, $prefix . 'block', 'blockdata'),
                'containerMetadata' => self::columnExists($pdo, $prefix . 'container', 'metadata'),
                'itemData' => self::columnExists($pdo, $prefix . 'item', 'data'),
                'chatCoordinates' => self::columnsExist($pdo, $prefix . 'chat', ['wid', 'x', 'y', 'z']),
                'commandCoordinates' => self::columnsExist($pdo, $prefix . 'command', ['wid', 'x', 'y', 'z']),
            ],
        ];
    }

    public static function unsupportedActionLabels($actionMask, $capabilities) {
        $unsupported = [];

        foreach (ActionDefinitions::all() as $definition) {
            if (($actionMask & $definition['bit']) === 0 || !isset($definition['requiresTable'])) {
                continue;
            }

            if (empty($capabilities['tables'][$definition['requiresTable']])) {
                $unsupported[] = $definition['label'];
            }
        }

        return $unsupported;
    }

    private static function tableExists($pdo, $table) {
        try {
            $pdo->query('SELECT 1 FROM `' . $table . '` LIMIT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function columnsExist($pdo, $table, $columns) {
        foreach ($columns as $column) {
            if (!self::columnExists($pdo, $table, $column)) {
                return false;
            }
        }

        return true;
    }

    private static function columnExists($pdo, $table, $column) {
        try {
            $pdo->query('SELECT `' . $column . '` FROM `' . $table . '` LIMIT 0');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

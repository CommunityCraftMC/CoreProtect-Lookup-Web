<?php

/**
 * Executes prepared statements while preserving scalar parameter types.
 */
class PDOStatementExecutor
{
    public static function execute($statement, $params) {
        foreach (array_values($params) as $index => $value) {
            $statement->bindValue($index + 1, $value, self::paramType($value));
        }

        return $statement->execute();
    }

    private static function paramType($value) {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if ($value === null) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }
}

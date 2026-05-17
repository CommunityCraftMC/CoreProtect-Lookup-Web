<?php

require_once __DIR__ . '/../res/php/PDOStatementExecutor.class.php';

class FakeStatement
{
    public $bindings = [];
    public $executed = false;

    public function bindValue($parameter, $value, $type) {
        $this->bindings[] = [$parameter, $value, $type];
    }

    public function execute() {
        $this->executed = true;
        return true;
    }
}

function assertSameValue($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new RuntimeException($message . ' expected ' . var_export($expected, true)
                . ' got ' . var_export($actual, true));
    }
}

$statement = new FakeStatement();
$result = PDOStatementExecutor::execute($statement, [0, 25, 'minecraft:stone', null, true]);

assertSameValue(true, $result, 'Executor should return statement execution result');
assertSameValue(true, $statement->executed, 'Executor should call execute');
assertSameValue([1, 0, PDO::PARAM_INT], $statement->bindings[0], 'First limit value should bind as int');
assertSameValue([2, 25, PDO::PARAM_INT], $statement->bindings[1], 'Second limit value should bind as int');
assertSameValue([3, 'minecraft:stone', PDO::PARAM_STR], $statement->bindings[2], 'String should bind as string');
assertSameValue([4, null, PDO::PARAM_NULL], $statement->bindings[3], 'Null should bind as null');
assertSameValue([5, true, PDO::PARAM_BOOL], $statement->bindings[4], 'Bool should bind as bool');

echo "PDO statement executor tests passed\n";

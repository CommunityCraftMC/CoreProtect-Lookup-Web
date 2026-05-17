<?php
/**
 * Lookup JSON
 *
 * Returns query results as a JSON file
 *
 * CoreProtect Lookup Web Interface
 * @author Simon Chuu
 * @copyright 2015-2020 Simon Chuu
 * @license MIT License
 * @link https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web
 * @since 1.0.0
 */

require_once 'res/php/StatementPreparer.class.php';
require_once 'res/php/PDOWrapper.class.php';
require_once 'res/php/PDOStatementExecutor.class.php';
require_once 'res/php/SchemaCapabilities.class.php';
require_once 'res/php/LookupRowNormalizer.class.php';
require_once 'res/php/QuerySafety.class.php';
QuerySafety::configureJsonErrors();
$config = require_once 'config.php';

$return = [["time" => microtime(true)]];

/**
 * @param PDO $pdo
 */
function pdoError($pdo) {
    $return[0]["status"] = 2;
    $return[0]["code"] = $pdo->errorInfo()[0];
    $return[0]["driverCode"] = $pdo->errorInfo()[1];
    $return[0]["reason"] = $pdo->errorInfo()[2];
    exit();
}

register_shutdown_function(function () {
    global $return;

    // Set type to application/json
    header('Content-type:application/json;charset=utf-8');

    $fatalError = QuerySafety::fatalErrorResponse(error_get_last());
    if ($fatalError !== null) {
        $return[0] = array_merge($return[0], $fatalError);
    }

    if(!isset($return[0]["status"]))
        $return[0]["status"] = -1;
    $return[0]["duration"] = microtime(true) - $return[0]["time"];
    echo json_encode($return);
});

$checkInputQuery = !isset($_REQUEST['offset']);

$safetyError = QuerySafety::validateRequest($_REQUEST, $config['form']);
if ($safetyError !== null) {
    $return[0] = array_merge($return[0], $safetyError);
    exit();
}

$serverName = isset($_REQUEST['server']) ? $_REQUEST['server'] : null;
if ($serverName == null) {
    $return[0]["status"] = 1;
    $return[0]["code"] = "Request Error";
    $return[0]["reason"] = "No server specified.";
    exit();
}

if (!isset($config['database'][$serverName])) {
    $return[0]["status"] = 1;
    $return[0]["code"] = "Configuration Error";
    $return[0]["reason"] = "The specified server '$serverName' is not configured.";
    exit();
}
$server = $config['database'][$serverName];

$flags = isset($_REQUEST['flags']) ? $_REQUEST['flags'] : ($server['preBlockName'] ? 1 : 0);
$wrapper = new PDOWrapper($server);
$pdo = $wrapper->initPDO();

if (!$pdo) {
    $return[0]["status"] = 1;
    $return[0]["code"] = $wrapper->error()[0];
    $return[0]["reason"] = $wrapper->error()[1];
    exit();
}
QuerySafety::applyRuntimeLimits($pdo, $server, $config['form']);

$capabilities = SchemaCapabilities::detect($pdo, $server['prefix']);
$return[0]['capabilities'] = $capabilities;

if (($flags & StatementPreparer::FLAG_USE_BLOCKDATA_TABLE_DEFINED) === 0) {
    if ($capabilities['tables']['blockdataMap'])
        $flags |= StatementPreparer::FLAG_USE_BLOCKDATA_TABLE_YES;
    else
        $flags |= StatementPreparer::FLAG_USE_BLOCKDATA_TABLE_NO;

    $return[0]['flags'] = $flags;
}
if (!empty($capabilities['columns']['blockMeta']))
    $flags |= StatementPreparer::FLAG_USE_BLOCK_META;
if (!empty($capabilities['columns']['blockBlockdata']))
    $flags |= StatementPreparer::FLAG_USE_BLOCK_BLOCKDATA;
if (!empty($capabilities['columns']['containerMetadata']))
    $flags |= StatementPreparer::FLAG_USE_CONTAINER_METADATA;
if (!empty($capabilities['columns']['itemData']))
    $flags |= StatementPreparer::FLAG_USE_ITEM_DATA;
$return[0]['flags'] = $flags;

$unsupportedActions = SchemaCapabilities::unsupportedActionLabels(
    isset($_REQUEST['a']) ? intval($_REQUEST['a']) : 0,
    $capabilities
);
if (!empty($unsupportedActions)) {
    $return[0]["status"] = 1;
    $return[0]["code"] = "Unsupported database schema";
    $return[0]["reason"] = "This database does not have required CoreProtect table support for: "
        . join(', ', $unsupportedActions) . ".";
    exit();
}

$prep = new StatementPreparer($server['prefix'], $_REQUEST, $config['form']['count'], $config['form']['moreCount'], $flags);


// Check if where parameters exist
if ($checkInputQuery) {
    $checkStr = $prep->prepareCheck();

    if ($checkStr !== '') {
        $check = $pdo->prepare($checkStr);
        if (!$check) {
            pdoError($pdo);
        }

        $w = $prep->getW();
        $u = $prep->getU();
        $m = $prep->getB();
        $e = $prep->getE();

        if (PDOStatementExecutor::execute($check, $prep->getParams())) {
            while($r = $check->fetch(PDO::FETCH_ASSOC)) {
                switch ($r['table']) {
                    case 'world':
                        $params = &$w;
                        $isUser = false;
                        break;
                    case 'user':
                        $params = &$u;
                        $isUser = true;
                        break;
                    case 'material':
                        $params = &$m;
                        $isUser = false;
                        break;
                    case 'entity':
                        $params = &$e;
                        $isUser = false;
                        break;
                    default:
                        continue 2;
                }

                if ($params !== null && (
                    ($key = array_search($r['name'], $params)) !== false
                    || $r['uuid'] !== null && ($key = array_search($r['uuid'], $params)) !== false)
                )
                    unset($params[$key]);
                elseif ($isUser && $e !== null && ($key = array_search($r['name'], $e)) !== false)
                    unset($e[$key]);
            }
        }

        $wSize = is_array($w) && sizeof($w) !== 0;
        $uSize = is_array($u) && sizeof($u) !== 0;
        $mSize = is_array($m) && sizeof($m) !== 0;
        $eSize = is_array($e) && sizeof($e) !== 0;

        if ($wSize || $uSize || $mSize || $eSize) {
            $return[0]["status"] = 1;
            $return[0]["code"] = "Unknown text in"
                . ($wSize ? " 'Worlds'" : '')
                . ($uSize ? " 'Users'" : '')
                . ($mSize ? " 'Materials'" : '')
                . ($eSize ? " 'Entities'" : '');
            $return[0]["reason"]
                = ($wSize ? "Worlds: '" . join("', '", $w) . "';" : '')
                . ($uSize ? "Users: '" . join("', '", $u) . "';" : '')
                . ($mSize ? "Materials: '" . join("', '", $m) . "';" : '')
                . ($eSize ? "Entities: '" . join("', '", $e) . "';" : '');
            exit();
        }
    }
}

// Lookup
$lookup = $pdo->prepare($prep->prepareStatementData());

if (!$lookup) {
    pdoError($pdo);
}

if (PDOStatementExecutor::execute($lookup, $prep->getParams())) {
    $return[0]["status"] = 0;
    if (!isset($_REQUEST['offset']) && $server['mapLink']) $return[0]["mapHref"] = $server['mapLink'];

    $return[1] = [];
    while($r = $lookup->fetch(PDO::FETCH_ASSOC)) {
        $return[1][] = LookupRowNormalizer::normalize($r);
    }
} else {
    $return[0]["status"] = 2;
    $return[0]["code"] = $lookup->errorInfo()[0];
    $return[0]["driverCode"] = $lookup->errorInfo()[1];
    $return[0]["reason"] = $lookup->errorInfo()[2];
    exit();
}

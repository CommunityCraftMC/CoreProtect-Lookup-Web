<?php

/**
 * Guards lookup requests that are likely to be too expensive for live use.
 */
class QuerySafety
{
    public static function configureJsonErrors() {
        ini_set('display_errors', '0');
        ini_set('html_errors', '0');
    }

    public static function applyRuntimeLimits($pdo, $server, $formConfig) {
        $timeout = self::intConfig($formConfig, 'timeoutSeconds', 20);
        if ($timeout > 0) {
            set_time_limit($timeout);

            if (isset($server['type']) && $server['type'] === 'mysql') {
                // MariaDB supports max_statement_time in seconds; MySQL supports max_execution_time in milliseconds.
                @$pdo->query('SET SESSION max_statement_time=' . intval($timeout));
                @$pdo->query('SET SESSION max_execution_time=' . intval($timeout * 1000));
            }
        }
    }

    public static function validateRequest($request, $formConfig) {
        $count = self::requestInt($request, 'count');
        $maxCount = self::intConfig($formConfig, 'max', 0);
        if ($count !== null && $maxCount > 0 && $count > $maxCount) {
            return self::error(
                'Limit ' . $count . ' is above the maximum allowed limit of ' . $maxCount . '. Lower the limit and try again.'
            );
        }

        $maxVolume = self::intConfig($formConfig, 'maxCoordinateVolume', 5000000);
        $volume = self::coordinateVolume($request);
        if ($volume !== null && $maxVolume > 0 && $volume > $maxVolume) {
            return self::error(
                'Coordinate selection covers ' . $volume . ' blocks, above the maximum allowed ' . $maxVolume
                . '. Narrow the area, add time/user/material filters, or ask an admin to raise maxCoordinateVolume.'
            );
        }

        return null;
    }

    public static function fatalErrorResponse($error) {
        if (!is_array($error) || !isset($error['type']) || !self::isFatal($error['type']))
            return null;

        $message = isset($error['message']) ? $error['message'] : 'Fatal PHP error';
        if (stripos($message, 'maximum execution time') !== false) {
            return [
                'status' => 1,
                'code' => 'Query timed out',
                'reason' => 'The lookup exceeded the configured server timeout. Narrow the area, lower the limit, or add filters.',
            ];
        }

        return [
            'status' => 1,
            'code' => 'Server error',
            'reason' => $message,
        ];
    }

    private static function coordinateVolume($request) {
        foreach (['x', 'y', 'z', 'x2', 'y2', 'z2'] as $key) {
            if (self::requestInt($request, $key) === null)
                return null;
        }

        $x1 = self::requestInt($request, 'x');
        $y1 = self::requestInt($request, 'y');
        $z1 = self::requestInt($request, 'z');
        $x2 = self::requestInt($request, 'x2');
        $y2 = self::requestInt($request, 'y2');
        $z2 = self::requestInt($request, 'z2');

        return (abs($x2 - $x1) + 1) * (abs($y2 - $y1) + 1) * (abs($z2 - $z1) + 1);
    }

    private static function error($reason) {
        return [
            'status' => 1,
            'code' => 'Query too large',
            'reason' => $reason,
        ];
    }

    private static function requestInt($request, $key) {
        if (!isset($request[$key]))
            return null;
        $trimmed = trim($request[$key]);
        if ($trimmed === '')
            return null;
        return intval($trimmed);
    }

    private static function intConfig($config, $key, $default) {
        if (!isset($config[$key]) || $config[$key] === null || $config[$key] === '')
            return $default;
        return intval($config[$key]);
    }

    private static function isFatal($type) {
        return in_array($type, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true);
    }
}

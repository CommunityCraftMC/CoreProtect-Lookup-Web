<?php

require_once __DIR__ . '/CoreProtectMetadataDecoder.class.php';

/**
 * Normalizes database rows while preserving the legacy response keys.
 */
class LookupRowNormalizer
{
    public static function normalize($row) {
        if (isset($row["rowid"])) {
            $row["id"] = intval($row["rowid"]);
            unset($row["rowid"]);
        } elseif (isset($row["id"])) {
            $row["id"] = intval($row["id"]);
        }

        self::normalizeInt($row, "time");
        self::normalizeInt($row, "action");
        if (isset($row["world"]) && $row["world"] !== null) {
            self::normalizeInt($row, "x");
            self::normalizeInt($row, "y");
            self::normalizeInt($row, "z");
        }
        self::normalizeInt($row, "amount");
        self::normalizeInt($row, "rolled_back");

        $row["source"] = isset($row["table"]) ? $row["table"] : null;
        $row["actionGroup"] = self::actionGroup($row);
        $row["actionLabel"] = self::actionLabel($row);
        $row["targetType"] = self::targetType($row);
        $row["rolledBack"] = isset($row["rolled_back"]) ? $row["rolled_back"] : null;
        $row["metadata"] = self::metadata($row);

        return $row;
    }

    private static function normalizeInt(& $row, $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null)
            $row[$key] = intval($row[$key]);
    }

    private static function actionGroup($row) {
        if (!isset($row["table"]))
            return null;

        if (strpos($row["table"], "inventory") === 0)
            return "inventory";

        return $row["table"] === "username" ? "username" : $row["table"];
    }

    private static function actionLabel($row) {
        $table = isset($row["table"]) ? $row["table"] : null;
        $action = isset($row["action"]) ? $row["action"] : null;

        if ($table === "block") {
            if ($action === 0) return "-Block";
            if ($action === 1) return "+Block";
            if ($action === 2) return "Click";
            if ($action === 3) return "Kill";
        }
        if ($table === "container") {
            if ($action === 0) return "-Container";
            if ($action === 1) return "+Container";
        }
        if ($table === "session") {
            if ($action === 0) return "-Session";
            if ($action === 1) return "+Session";
            return "Session";
        }
        if ($table === "item") {
            if (in_array($action, [3, 4], true)) return "+Item";
            if (in_array($action, [2, 5, 6, 7], true)) return "-Item";
            return "Item";
        }
        if (strpos($table, "inventory") === 0)
            return "Inventory";
        if ($table === "chat")
            return "Chat";
        if ($table === "command")
            return "Command";
        if ($table === "username")
            return "Username";
        if ($table === "sign")
            return "Sign";

        return null;
    }

    private static function targetType($row) {
        $table = isset($row["table"]) ? $row["table"] : null;

        if ($table === "block")
            return isset($row["action"]) && intval($row["action"]) === 3 ? "entity" : "material";
        if ($table === "container" || $table === "item" || strpos($table, "inventory") === 0)
            return "item";
        if ($table === "chat" || $table === "command")
            return "message";
        if ($table === "username")
            return "username";
        if ($table === "sign")
            return "sign";

        return null;
    }

    private static function metadata($row) {
        $metadata = [];

        if (array_key_exists("block_meta", $row) && $row["block_meta"] !== null && $row["block_meta"] !== "")
            $metadata["blockMetaHex"] = $row["block_meta"];
        if (array_key_exists("block_blockdata", $row) && $row["block_blockdata"] !== null && $row["block_blockdata"] !== "")
            $metadata["blockDataHex"] = $row["block_blockdata"];
        if (array_key_exists("container_metadata", $row) && $row["container_metadata"] !== null && $row["container_metadata"] !== "")
            $metadata["containerMetadataHex"] = $row["container_metadata"];
        if (array_key_exists("item_data", $row) && $row["item_data"] !== null && $row["item_data"] !== "")
            $metadata["itemDataHex"] = $row["item_data"];
        if (isset($metadata["containerMetadataHex"])) {
            $decoded = CoreProtectMetadataDecoder::decodeHex($metadata["containerMetadataHex"]);
            if ($decoded !== null)
                $metadata["containerMetadataDecoded"] = $decoded;
        }
        if (isset($metadata["itemDataHex"])) {
            $decoded = CoreProtectMetadataDecoder::decodeHex($metadata["itemDataHex"]);
            if ($decoded !== null)
                $metadata["itemDataDecoded"] = $decoded;
        }

        if (!isset($row["table"]) || $row["table"] !== "sign")
            return $metadata;

        $metadata["lines"] = [
            self::valueOrNull($row, "sign_line_1"),
            self::valueOrNull($row, "sign_line_2"),
            self::valueOrNull($row, "sign_line_3"),
            self::valueOrNull($row, "sign_line_4"),
            self::valueOrNull($row, "sign_line_5"),
            self::valueOrNull($row, "sign_line_6"),
            self::valueOrNull($row, "sign_line_7"),
            self::valueOrNull($row, "sign_line_8"),
        ];

        if (array_key_exists("sign_face", $row))
            $metadata["face"] = $row["sign_face"];
        if (array_key_exists("sign_waxed", $row))
            $metadata["waxed"] = $row["sign_waxed"] === null ? null : intval($row["sign_waxed"]);
        if (array_key_exists("sign_color", $row))
            $metadata["color"] = $row["sign_color"];

        return $metadata;
    }

    private static function valueOrNull($row, $key) {
        return array_key_exists($key, $row) ? $row[$key] : null;
    }
}

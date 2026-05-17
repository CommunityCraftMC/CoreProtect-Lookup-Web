<?php

/**
 * Decodes the Java-serialized Bukkit item metadata blobs CoreProtect stores.
 *
 * This intentionally does not instantiate Java classes or execute serialized
 * code. It extracts Java serialization string records and builds a safe,
 * best-effort summary for known Bukkit/Guava metadata shapes.
 */
class CoreProtectMetadataDecoder
{
    const TC_STRING = 0x74;
    const TC_LONGSTRING = 0x7C;
    const MAX_BYTES = 65536;
    const MAX_STRINGS = 512;
    const MAX_STRING_BYTES = 32768;

    public static function decodeHex($hex) {
        if (!is_string($hex))
            return null;

        $hex = preg_replace('/\s+/', '', $hex);
        if ($hex === null || $hex === '' || strlen($hex) % 2 !== 0 || preg_match('/^[0-9A-Fa-f]+$/', $hex) !== 1)
            return null;
        if (strlen($hex) / 2 > self::MAX_BYTES)
            return null;
        if (stripos($hex, 'ACED0005') !== 0)
            return null;

        $bytes = hex2bin($hex);
        if ($bytes === false)
            return null;

        $strings = self::extractSerializedStrings($bytes);
        if (empty($strings))
            return null;

        $summary = self::summarizeStrings($strings);
        if (empty($summary))
            return null;

        $summary['format'] = 'java-serialized';
        return $summary;
    }

    private static function extractSerializedStrings($bytes) {
        $strings = [];
        $length = strlen($bytes);

        for ($pos = 4; $pos < $length && count($strings) < self::MAX_STRINGS; $pos++) {
            $tag = ord($bytes[$pos]);
            if ($tag === self::TC_STRING) {
                if ($pos + 3 > $length)
                    continue;
                $stringLength = self::readUnsignedShort($bytes, $pos + 1);
                if ($stringLength > self::MAX_STRING_BYTES || $pos + 3 + $stringLength > $length)
                    continue;
                $strings[] = substr($bytes, $pos + 3, $stringLength);
                $pos += 2 + $stringLength;
            } elseif ($tag === self::TC_LONGSTRING) {
                if ($pos + 9 > $length)
                    continue;
                $stringLength = self::readLongLength($bytes, $pos + 1);
                if ($stringLength === null || $stringLength > self::MAX_STRING_BYTES || $pos + 9 + $stringLength > $length)
                    continue;
                $strings[] = substr($bytes, $pos + 9, $stringLength);
                $pos += 8 + $stringLength;
            }
        }

        return $strings;
    }

    private static function summarizeStrings($strings) {
        $summary = [];
        $metadataStart = self::metadataStart($strings);
        $keys = $metadataStart === null ? [] : self::metadataKeys($strings, $metadataStart);
        $valueStart = $metadataStart === null ? 0 : $metadataStart + count($keys);
        $values = array_slice($strings, $valueStart);
        $cursor = 0;

        if (in_array('meta-type', $keys, true) && isset($values[$cursor]) && self::looksLikeMetaType($values[$cursor])) {
            $summary['metaType'] = $values[$cursor];
            $cursor++;
        }
        if (in_array('display-name', $keys, true) && isset($values[$cursor])) {
            $summary['displayName'] = self::plainText($values[$cursor]);
            $cursor++;
        }

        $lore = in_array('lore', $keys, true) ? self::extractLore($values, $cursor) : [];
        if (!empty($lore))
            $summary['lore'] = $lore;

        $enchants = self::extractEnchants($values, $lore);
        if (!empty($enchants))
            $summary['enchants'] = $enchants;

        $itemFlags = self::extractItemFlags($values);
        if (!empty($itemFlags))
            $summary['itemFlags'] = $itemFlags;

        $publicBukkitValues = self::extractPublicBukkitValues($values);
        if (!empty($publicBukkitValues))
            $summary['publicBukkitValues'] = $publicBukkitValues;

        $trim = self::extractTrim($values);
        if (!empty($trim))
            $summary['trim'] = $trim;

        return $summary;
    }

    private static function metadataStart($strings) {
        for ($i = 0; $i < count($strings); $i++) {
            if ($strings[$i] === 'meta-type')
                return $i;
        }

        return null;
    }

    private static function metadataKeys($strings, $start) {
        $knownKeys = [
            'meta-type' => true,
            'display-name' => true,
            'lore' => true,
            'enchants' => true,
            'stored-enchants' => true,
            'ItemFlags' => true,
            'PublicBukkitValues' => true,
            'trim' => true,
        ];
        $keys = [];

        for ($i = $start; $i < count($strings) && isset($knownKeys[$strings[$i]]); $i++) {
            $keys[] = $strings[$i];
        }

        return $keys;
    }

    private static function extractLore($values, $start) {
        $lore = [];
        for ($i = $start; $i < count($values); $i++) {
            $value = $values[$i];
            if (self::isEnchantId($value) || self::isItemFlag($value) || $value === 'material' || $value === 'pattern')
                break;
            if (self::looksLikePublicBukkitValues($value))
                break;

            $lore[] = self::plainText($value);
        }

        return $lore;
    }

    private static function extractEnchants($values, $lore) {
        $enchants = [];
        $trimValues = self::trimValueIndexes($values);

        for ($i = 0; $i < count($values); $i++) {
            if (!self::isEnchantId($values[$i]) || isset($trimValues[$i]))
                continue;

            $name = self::humanizeMinecraftId($values[$i]);
            $enchants[] = [
                'id' => $values[$i],
                'name' => $name,
                'level' => self::levelFromLore($name, $lore),
            ];
        }

        return $enchants;
    }

    private static function extractItemFlags($values) {
        $flags = [];
        foreach ($values as $value) {
            if (self::isItemFlag($value))
                $flags[] = $value;
        }
        return array_values(array_unique($flags));
    }

    private static function extractPublicBukkitValues($values) {
        foreach ($values as $value) {
            if (!self::looksLikePublicBukkitValues($value))
                continue;

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                return $decoded;
        }

        return [];
    }

    private static function extractTrim($values) {
        $trim = [];
        for ($i = 0; $i < count($values) - 1; $i++) {
            if ($values[$i] === 'material')
                $trim['material'] = $values[$i + 1];
            if ($values[$i] === 'pattern')
                $trim['pattern'] = $values[$i + 1];
        }
        return $trim;
    }

    private static function trimValueIndexes($values) {
        $indexes = [];
        for ($i = 0; $i < count($values) - 1; $i++) {
            if ($values[$i] === 'material' || $values[$i] === 'pattern')
                $indexes[$i + 1] = true;
        }
        return $indexes;
    }

    private static function plainText($value) {
        if (!is_string($value) || $value === '')
            return $value;

        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE)
            return $value;

        return self::jsonText($decoded);
    }

    private static function jsonText($node) {
        if (!is_array($node))
            return '';

        if (array_keys($node) === range(0, count($node) - 1)) {
            $text = '';
            foreach ($node as $child)
                $text .= self::jsonText($child);
            return $text;
        }

        $text = isset($node['text']) && is_string($node['text']) ? $node['text'] : '';
        if (isset($node['extra']) && is_array($node['extra']))
            $text .= self::jsonText($node['extra']);

        return $text;
    }

    private static function levelFromLore($name, $lore) {
        foreach ($lore as $line) {
            if (stripos($line, $name) === false)
                continue;
            if (preg_match('/\b([IVXLCDM]+)\b$/i', trim($line), $match) === 1)
                return self::romanToInt(strtoupper($match[1]));
        }

        return null;
    }

    private static function romanToInt($roman) {
        $values = ['I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100, 'D' => 500, 'M' => 1000];
        $total = 0;
        $previous = 0;

        for ($i = strlen($roman) - 1; $i >= 0; $i--) {
            $value = isset($values[$roman[$i]]) ? $values[$roman[$i]] : 0;
            if ($value < $previous)
                $total -= $value;
            else
                $total += $value;
            $previous = $value;
        }

        return $total;
    }

    private static function humanizeMinecraftId($id) {
        $name = preg_replace('/^minecraft:/', '', $id);
        $words = explode('_', $name);
        foreach ($words as $i => $word)
            $words[$i] = ucfirst($word);
        return join(' ', $words);
    }

    private static function isEnchantId($value) {
        return is_string($value) && preg_match('/^minecraft:[a-z0-9_]+$/', $value) === 1;
    }

    private static function isItemFlag($value) {
        return is_string($value) && strpos($value, 'HIDE_') === 0 && preg_match('/^[A-Z][A-Z0-9_]+$/', $value) === 1;
    }

    private static function looksLikeMetaType($value) {
        return is_string($value) && preg_match('/^[A-Z_]+$/', $value) === 1;
    }

    private static function looksLikePublicBukkitValues($value) {
        return is_string($value)
            && strlen($value) > 1
            && $value[0] === '{'
            && strpos($value, '"text"') === false
            && strpos($value, ':') !== false;
    }

    private static function readUnsignedShort($bytes, $pos) {
        return (ord($bytes[$pos]) << 8) | ord($bytes[$pos + 1]);
    }

    private static function readLongLength($bytes, $pos) {
        $value = 0;
        for ($i = 0; $i < 8; $i++) {
            $value = ($value * 256) + ord($bytes[$pos + $i]);
            if ($value > self::MAX_STRING_BYTES)
                return null;
        }
        return $value;
    }
}

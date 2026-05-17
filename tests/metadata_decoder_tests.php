<?php

require_once __DIR__ . '/../res/php/CoreProtectMetadataDecoder.class.php';

final class MetadataDecoderTestFailure extends Exception {}

function assert_meta_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        throw new MetadataDecoderTestFailure(
            $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true)
        );
    }
}

function java_string_stream_hex($strings) {
    $bytes = "\xAC\xED\x00\x05";
    foreach ($strings as $string) {
        $bytes .= "\x74" . pack('n', strlen($string)) . $string;
    }
    return strtoupper(bin2hex($bytes));
}

$hex = java_string_stream_hex([
    'meta-type',
    'display-name',
    'lore',
    'enchants',
    'ItemFlags',
    'PublicBukkitValues',
    'trim',
    'ARMOR',
    '{"text":"Pad ","extra":[{"text":"van Herboren","color":"#ffd1dc"}],"italic":false}',
    '{"text":"+ ","extra":[{"text":"Unbreaking","extra":[{"text":" V"}]}]}',
    '{"text":"+ ","extra":[{"text":"Protection","extra":[{"text":" IV"}]}]}',
    '',
    '{"text":"Tier: ","extra":[{"text":"Shop Item"}]}',
    'minecraft:protection',
    'minecraft:unbreaking',
    'HIDE_ENCHANTS',
    '{"example:customitemkey":"sp_l26_leggings"}',
    'material',
    'minecraft:emerald',
    'pattern',
    'minecraft:raiser',
]);

$decoded = CoreProtectMetadataDecoder::decodeHex($hex);

assert_meta_same('java-serialized', $decoded['format'], 'decoder should detect Java serialization');
assert_meta_same('ARMOR', $decoded['metaType'], 'decoder should expose metadata type');
assert_meta_same('Pad van Herboren', $decoded['displayName'], 'decoder should flatten JSON text display name');
assert_meta_same(['+ Unbreaking V', '+ Protection IV', '', 'Tier: Shop Item'], $decoded['lore'], 'decoder should flatten lore lines');
assert_meta_same([
    ['id' => 'minecraft:protection', 'name' => 'Protection', 'level' => 4],
    ['id' => 'minecraft:unbreaking', 'name' => 'Unbreaking', 'level' => 5],
], $decoded['enchants'], 'decoder should pair enchantment ids with lore levels');
assert_meta_same(['HIDE_ENCHANTS'], $decoded['itemFlags'], 'decoder should expose item flags');
assert_meta_same(['example:customitemkey' => 'sp_l26_leggings'], $decoded['publicBukkitValues'], 'decoder should decode public Bukkit values JSON');
assert_meta_same(['material' => 'minecraft:emerald', 'pattern' => 'minecraft:raiser'], $decoded['trim'], 'decoder should expose armor trim');

assert_meta_same(null, CoreProtectMetadataDecoder::decodeHex('DEADBEEF'), 'non-Java metadata should return null');

echo "Metadata decoder tests passed\n";

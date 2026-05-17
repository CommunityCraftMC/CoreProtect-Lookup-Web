<?php

/**
 * Parsed lookup request values used by SQL generation.
 */
class LookupRequest
{
    public $a, $t, $x, $y, $z, $x2, $y2, $z2, $count, $offset;
    public $u, $b, $e, $w, $keyword;

    public static function fromRequest(& $req, $count, $moreCount, $flag = 0) {
        $lookup = new self();
        $lookup->offset = self::nonnullInt($req['offset'], 0);
        $lookup->count = self::nonnullInt($req['count'], $lookup->offset == null ? $count : $moreCount);
        $lookup->a = self::nonnullInt($req['a']);
        $lookup->b = self::nonnullArr($req['b']);
        $lookup->e = self::nonnullArr($req['e']);
        $lookup->t = self::nonnullInt($req['t']);
        $lookup->u = self::nonnullArr($req['u']);
        $lookup->w = self::nonnullArr($req['w']);
        $lookup->x = self::nonnullInt($req['x']);
        $lookup->x2 = self::nonnullInt($req['x2']);
        $lookup->y = self::nonnullInt($req['y']);
        $lookup->y2 = self::nonnullInt($req['y2']);
        $lookup->z = self::nonnullInt($req['z']);
        $lookup->z2 = self::nonnullInt($req['z2']);
        $lookup->keyword = self::nonnullArr($req['keyword'], false);

        if ($flag & StatementPreparer::FLAG_PRE_BLOCK_NAME && $lookup->b !== null)
            foreach ($lookup->b as $k => $v)
                if (strpos($v, ':') === false)
                    $lookup->b[$k] = 'minecraft:' . $v;

        return $lookup;
    }

    private static function nonnullArr(& $in, $trimInner = true) {
        if (isset($in)) {
            $trim = trim($in);
            if ($trim !== "") {
                $csv = str_getcsv($trim, ',', '"', '\\');
                if ($trimInner) {
                    foreach ($csv as $k => $v)
                        $csv[$k] = trim($v);
                }
                return $csv;
            }
        }
        return null;
    }

    private static function nonnullInt(& $in, $ifunset = null) {
        if (isset($in)) {
            $trim = trim($in);
            if ($trim !== "")
                return intval($trim);
        }
        return $ifunset;
    }
}

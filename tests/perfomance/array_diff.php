<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 22.04.16
 * Time: 12:20
 */

/**
 * Custom array diff.
 * Compare two arrays
 * @param $b - array 1
 * @param $a - array 2
 * @return array - result array
 */
function flip_isset_diff($b, $a) {
    $at = array_flip($a);
    $d = [];
    foreach ($b as $i)
        if (!isset($at[$i]))
            $d[] = $i;
    return $d;
}

$f1 = file(__DIR__ . '/1s1.log');
$f2 = file(__DIR__ . '/s2.log');

$s = microtime(true);
echo "\nflip_isset_diff\n";
echo "количество: " . count(flip_isset_diff($f1, $f2)) . "\n";
echo "сравнение: " . number_format(microtime(true) - $s, 4) . " ms\n\n";

$s = microtime(true);
echo "\narray_diff\n";
echo "количество: " . count(array_diff($f1, $f2)) . "\n";
echo "сравнение: " . number_format(microtime(true) - $s, 4) . " ms\n\n";
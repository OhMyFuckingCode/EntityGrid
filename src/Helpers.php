<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 10:40
 */

namespace Quextum\EntityGrid;


use Nette\Utils\ArrayHash;

class Helpers
{
    /**
     * Removes empty values recursively
     * @param ArrayHash|array $array
     * @return array
     */
    public static function array_filter_recursive($array):array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (\is_array($value) || $value instanceof ArrayHash) {
                $value = static::array_filter_recursive($value);
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }

}
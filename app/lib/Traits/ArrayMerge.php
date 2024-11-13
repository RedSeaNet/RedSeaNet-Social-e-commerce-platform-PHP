<?php

namespace Redseanet\Lib\Traits;

/**
 * Merge array recursively
 */
trait ArrayMerge
{
    /**
     * Merge array recursively
     *
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function arrayMerge(array $a, array $b)
    {
        foreach ($b as $key => $value) {
            if (is_numeric($key)) {
                $a[] = $value;
            } elseif (isset($a[$key])) {
                $a[$key] = is_array($value) ? $this->arrayMerge($a[$key], $value) : $value;
            } else {
                $a[$key] = $value;
            }
        }
        return $a;
    }
}

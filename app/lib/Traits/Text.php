<?php

namespace Redseanet\Lib\Traits;

use Redseanet\Lib\Bootstrap;

trait Text
{
    public function substring($string, $length, $append = false)
    {
        if ($length <= 0) {
            return '';
        }
        $is_utf8 = false;
        $str1 = @iconv('UTF-8', 'GBK', $string);
        $str2 = @iconv('GBK', 'UTF-8', $str1);
        if ($string == $str2) {
            $is_utf8 = true;
            $string = $str1;
        }
        $newstr = '';
        for ($i = 0; $i < $length; $i++) {
            if (!empty($string[$i])) {
                $newstr .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
        }
        if ($is_utf8) {
            $newstr = @iconv('GBK', 'UTF-8', $newstr);
        }
        if ($append && $newstr != $string) {
            $newstr .= $append;
        }
        return $newstr;
    }
}

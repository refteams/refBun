<?php

namespace presentkim\makepluginplus\util;

class Utils{

    /**
     * @param Object[] $list
     *
     * @return string[]
     */
    public static function listToPairs(array $list) : array{
        $pairs = [];
        $size = sizeOf($list);
        for ($i = 0; $i < $size; ++$i) {
            $pairs["{%$i}"] = $list[$i];
        }
        return $pairs;
    }
}
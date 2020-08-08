<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\tool\builder\util;

class Utils{
    /**
     * @param string $originalCode
     *
     * @return string
     */
    public static function codeOptimize(string $originalCode) : string{
        $ignoreBeforeList = [
            T_SL,               // <<
            T_NS_SEPARATOR,     // \\
            T_DOUBLE_COLON,     // ::
            T_OBJECT_OPERATOR,  // ->
            T_FUNCTION          // function
        ];
        $tokens = token_get_all($originalCode);
        $stripedCode = "";
        for($i = 0, $count = count($tokens); $i < $count; $i++){
            if(!is_array($tokens[$i])){
                $stripedCode .= $tokens[$i];
                continue;
            }

            if($tokens[$i][0] === T_LOGICAL_OR){
                $tokens[$i][1] = "||";
            }elseif($tokens[$i][0] === T_LOGICAL_AND){
                $tokens[$i][1] = "&&";
            }elseif($tokens[$i][0] === T_STRING){
                $beforeIndex = $i - 1;
                $before = null;
                while($token = $tokens[$beforeIndex] ?? false){
                    --$beforeIndex;
                    if(!in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])){
                        $before = $token[0];
                        break;
                    }
                }
                if($before === null || !in_array($before, $ignoreBeforeList)){
                    if(defined("\\" . $tokens[$i][1])){
                        $tokens[$i][1] = "\\" . $tokens[$i][1];
                    }elseif(function_exists("\\" . $tokens[$i][1]) && isset($tokens[$i + 1]) && $tokens[$i + 1] === "("){
                        $tokens[$i][1] = "\\" . $tokens[$i][1];
                    }
                }
            }
            $stripedCode .= $tokens[$i][1];
        }
        return $stripedCode;
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    public static function removeDirectory(string $directory) : bool{
        $files = array_diff(scandir($directory), [".", ".."]);
        foreach($files as $file){
            $fileName = "{$directory}/{$file}";
            if(is_dir($fileName)){
                Utils::removeDirectory($fileName);
            }else{
                unlink($fileName);
            }
        }
        return rmdir($directory);
    }
}

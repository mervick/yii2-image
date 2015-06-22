<?php

namespace mervick\image\helpers;

class Image
{
    /**
     * Load image from file.
     * @param string $file File path
     * @param string|null $driver Driver class name
     */
    public static function load($file, $driver=null)
    {
        $driver = $driver ?: '\\mervick\\image\\drivers\\GD';
        return new $driver($file);
    }
}
<?php

namespace mervick\image;

/**
 * Class Image
 * @package mervick\image
 * @author Andrey Izman
 */
class Image
{
    // Resizing constants
    const WIDTH   = 'width';
    const HEIGHT  = 'height';
    const AUTO    = 'auto';
    const INVERSE = 'inverse';
    const PRECISE = 'precise';
    const ADAPT   = 'adapt';
    const CROP    = 'crop';

    // Flipping constants
    const HORIZONTAL = 'horizontal';
    const VERTICAL   = 'vertical';


    /**
     * Load image from file.
     * @param string $file File path
     * @param string|null $driver [optional] Driver class name
     * @param boolean $throwsErrors [optional] Show errors
     * @return Driver
     */
    public static function load($file, $driver = null, $throwsErrors = true)
    {
        $driver = $driver ?: '\\mervick\\image\\drivers\\GD';
        return new $driver($file, $throwsErrors);
    }
}
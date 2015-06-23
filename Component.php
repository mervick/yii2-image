<?php

namespace mervick\image;

/**
 * Class Component
 * @package mervick\image
 * @author Andrey Izman
 */
class Component extends \yii\base\Component
{
    /**
     * @var string Driver
     */
    public $driver = '\\mervick\\image\\drivers\\GD';

    /**
     * @var boolean
     */
    public $throwErrors = true;


    /**
     * Load image from file
     * @param string $file
     * @return Driver
     */
    public function load($file)
    {
        return Image::load($file, $this->driver, $this->throwErrors);
    }
}
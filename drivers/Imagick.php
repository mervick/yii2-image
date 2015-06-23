<?php

namespace mervick\image\drivers;

use mervick\image\Driver;
use mervick\image\Image;
use yii\base\InvalidParamException;

/**
 * Imagick driver.
 * @package mervick\image\drivers
 * @author Andrey Izman
 */
class Imagick extends Driver
{
    /**
     * @var Imagick
     */
    private $im;


    /**
     * Checks if Imagick extension is loaded.
     * @return boolean
     * @throws \ErrorException
     */
    public static function isLoaded()
    {
        if (!extension_loaded('imagick')) {
            throw new \ErrorException('Imagick extension not loaded. Check your server configuration.');
        }
        return true;
    }

    /**
     * Open the image.
     * @param string $filename
     * @param bool $throwsErrors
     * @throws InvalidParamException
     * @throws \ErrorException
     */
    public function __construct($filename, $throwsErrors = true)
    {
        static $isLoaded;

        if (!isset($isLoaded)) {
            $isLoaded = self::isLoaded();
        }

        parent::__construct($filename, $throwsErrors);

        $this->im = new \Imagick;
        $this->im->readImage($this->filename);

        if (!$this->im->getImageAlphaChannel()) {
            $this->im->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
        }
    }

}
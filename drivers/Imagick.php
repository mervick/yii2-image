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
     * @param boolean $throwsErrors
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

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->im->clear();
        $this->im->destroy();
    }

    /**
     * Resize the image.
     * @param integer $width
     * @param integer $height
     */
    protected function _resize($width, $height)
    {
        if ($this->im->scaleImage($width, $height)) {
            $this->width = $this->im->getImageWidth();
            $this->height = $this->im->getImageHeight();
        }
    }


    /**
     * Adaptation the image.
     * @param int $width
     * @param int $height
     * @param int $offset_x
     * @param int $offset_y
     */
    protected function _adapt($width, $height, $offset_x, $offset_y)
    {
        $image = new \Imagick();
        $image->newImage($width, $height, 'none');
        $image->compositeImage($this->im, \Imagick::COMPOSITE_ADD, $offset_x, $offset_y);
        $this->im->clear();
        $this->im->destroy();
        $this->im = $image;
        $this->width = $image->getImageWidth();
        $this->height = $image->getImageHeight();
    }

}
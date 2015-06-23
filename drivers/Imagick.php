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

    /**
     * Crop the image.
     * @param int $width
     * @param int $height
     * @param int $offset_x
     * @param int $offset_y
     */
    protected function _crop($width, $height, $offset_x, $offset_y)
    {
        if ($this->im->cropImage($width, $height, $offset_x, $offset_y)) {
            $this->width = $this->im->getImageWidth();
            $this->height = $this->im->getImageHeight();
            $this->im->setImagePage($this->width, $this->height, 0, 0);
        }
    }

    /**
     * Rotate the image.
     * @param int $degrees
     */
    protected function _rotate($degrees)
    {
        if ($this->im->rotateImage(new \ImagickPixel('transparent'), $degrees)) {
            $this->width = $this->im->getImageWidth();
            $this->height = $this->im->getImageHeight();
            $this->im->setImagePage($this->width, $this->height, 0, 0);
        }
    }

    /**
     * Flip the image.
     * @param string $direction
     */
    protected function _flip($direction)
    {
        $func = $direction === Image::HORIZONTAL ? 'flopImage' : 'flipImage';
        $this->im->$func();
    }

    /**
     * Sharpe the image.
     * @param integer $amount
     */
    protected function _sharpen($amount)
    {
        $amount = ($amount < 5) ? 5 : $amount;
        $amount = ($amount * 3.0) / 100;
        $this->im->sharpenImage(0, $amount);
    }
}
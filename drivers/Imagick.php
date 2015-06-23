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
     * Sharp the image.
     * @param integer $amount
     */
    protected function _sharpen($amount)
    {
        $amount = ($amount < 5) ? 5 : $amount;
        $amount = ($amount * 3.0) / 100;
        $this->im->sharpenImage(0, $amount);
    }

    /**
     * Reflection.
     * @param integer $height
     * @param integer $opacity
     * @param boolean $fade_in
     */
    protected function _reflection($height, $opacity, $fade_in)
    {
        $reflection = $this->im->clone();
        $reflection->flipImage();
        $reflection->cropImage($this->width, $height, 0, 0);
        $reflection->setImagePage($this->width, $height, 0, 0);
        $direction = array('transparent', 'black');

        if ($fade_in) {
            $direction = array_reverse($direction);
        }

        $fade = new \Imagick;
        $fade->newPseudoImage($reflection->getImageWidth(), $reflection->getImageHeight(),
            vsprintf('gradient:%s-%s', $direction));

        $reflection->compositeImage($fade, \Imagick::COMPOSITE_DSTOUT, 0, 0);
        $reflection->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $opacity / 100, \Imagick::CHANNEL_ALPHA);

        $image = new \Imagick;
        $image->newImage($this->width, $this->height + $height, new \ImagickPixel);

        $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
        $image->setColorspace($this->im->getColorspace());

        if ($image->compositeImage($this->im, \Imagick::COMPOSITE_SRC, 0, 0) &&
            $image->compositeImage($reflection, \Imagick::COMPOSITE_OVER, 0, $this->height)) {
            $this->im = $image;
            $this->width = $this->im->getImageWidth();
            $this->height = $this->im->getImageHeight();
        }
    }

    /**
     * Watermark.
     * @param Driver $image
     * @param integer $offset_x
     * @param integer $offset_y
     * @param integer $opacity
     */
    protected function _watermark(Driver $image, $offset_x, $offset_y, $opacity)
    {
        $watermark = new \Imagick;
        $watermark->readImageBlob($image->render(), $image->filename);

        if ($watermark->getImageAlphaChannel() !== \Imagick::ALPHACHANNEL_ACTIVATE) {
            $watermark->setImageAlphaChannel(\Imagick::ALPHACHANNEL_OPAQUE);
        }

        if ($opacity < 100) {
            $watermark->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $opacity / 100, \Imagick::CHANNEL_ALPHA);
        }

        $this->im->compositeImage($watermark, \Imagick::COMPOSITE_DISSOLVE, $offset_x, $offset_y);
    }

    /**
     * Fill the image background.
     * @param integer $r
     * @param integer $g
     * @param integer $b
     * @param integer $opacity
     */
    protected function _background($r, $g, $b, $opacity)
    {
        $color = sprintf('rgb(%d, %d, %d)', $r, $g, $b);

        $background = new \Imagick;
        $background->newImage($this->width, $this->height, new \ImagickPixel($color));

        if ( ! $background->getImageAlphaChannel()) {
            $background->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);
        }
        $background->setImageBackgroundColor(new \ImagickPixel('transparent'));
        $background->evaluateImage(\Imagick::EVALUATE_MULTIPLY, $opacity / 100, \Imagick::CHANNEL_ALPHA);
        $background->setColorspace($this->im->getColorspace());

        if ($background->compositeImage($this->im, \Imagick::COMPOSITE_DISSOLVE, 0, 0)) {
            $this->im = $background;
        }
    }
}
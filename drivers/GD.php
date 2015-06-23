<?php

namespace mervick\image\drivers;

use mervick\image\Driver;
use mervick\image\Image;
use yii\base\InvalidParamException;

/**
 * GD driver.
 * @package mervick\image\drivers
 * @author Andrey Izman
 */
class GD extends Driver
{
    /**
     * @var resource Image resource
     */
    protected $image;


    /**
     * Checks if GD extension is loaded.
     * @return boolean
     * @throws \ErrorException
     */
    public static function isLoaded()
    {
        if ((!extension_loaded('gd') && !extension_loaded('gd2')) || !function_exists('gd_info')) {
            throw new \ErrorException('GD extension not loaded. Check your server configuration.');
        }

        if (!defined('GD_VERSION')) {
            preg_match('/\d+\.\d+(?:\.\d+)?/', gd_info()['GD Version'], $m);
            define('GD_VERSION', $m[0]);
        }

        if (version_compare(GD_VERSION, '2.0.1', '<')) {
            throw new \ErrorException(sprintf('Image driver `GD` requires GD library version 2.0.1 or greater, you have %s', GD_VERSION));
        }

        return true;
    }

    /**
     * Read image.
     * @param string $filename
     * @param boolean $throwsErrors
     * @throws InvalidParamException
     */
    public function __construct($filename, $throwsErrors = true)
    {
        static $isLoaded;
        if (!isset($isLoaded)) {
            $isLoaded = self::isLoaded();
        }

        parent::__construct($filename, $throwsErrors);

        $create_func = 'imagecreatefrom' . $this->getFormat();
        $this->image = $create_func($filename);

        if (!$this->image) {
            $this->error = sprintf('Bad image format: "%s"', $filename);
            if ($throwsErrors) {
                throw new InvalidParamException($this->error);
            }
        } else {
            imagesavealpha($this->image, true);
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
    }

    /**
     * Create empty image.
     * @param integer $width
     * @param integer $height
     * @return resource
     */
    protected function create($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Get image format by extension or by $type
     * @param null $extension
     * @param null $quality
     * @param null $type
     * @return string
     */
    private function getFormat($extension = null, &$quality = null, &$type = null)
    {
        if (empty($extension)) {
            $extension = image_type_to_extension($this->type, false);
        }

        $extension = strtolower($extension);

        if (!in_array($extension, ['jpeg', 'gif', 'png'])) {
            $extension = 'jpeg';
        }

        switch ($extension) {
            case 'jpeg':
                $type = IMAGETYPE_JPEG;
                $quality = $quality >= 0 ? min($quality, 100) : 75;
                break;
            case 'gif':
                $type = IMAGETYPE_GIF;
                $quality = null;
                break;
            case 'png':
                $type = IMAGETYPE_PNG;
                $quality = $quality >= 0 ? min($quality, 9) : 9;
                break;
        }

        return $extension;
    }

    /**
     * Resize image.
     * @param int $width
     * @param int $height
     */
    protected function _resize($width, $height)
    {
        $orig_width = $this->width;
        $orig_height = $this->height;

        if ($width > ($this->width / 2) && $height > ($this->height / 2))
        {
            while ($orig_width / 2 > round($width  * 1.1) && $orig_height / 2 > round($height * 1.1))
            {
                $orig_width /= 2;
                $orig_height /= 2;
            }

            $image = $this->create($orig_width, $orig_height);

            if (imagecopyresized($image, $this->image, 0, 0, 0, 0, $orig_width, $orig_height, $this->width, $this->height))
            {
                imagedestroy($this->image);
                $this->image = $image;
            }
        }

        $image = $this->create($width, $height);

        if (imagecopyresampled($image, $this->image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height))
        {
            imagedestroy($this->image);
            $this->image = $image;
            $this->width  = imagesx($image);
            $this->height = imagesy($image);
        }
    }

    /**
     * Adaptation the image.
     * @param integer $width Image width
     * @param integer $height Image height
     * @param integer $offset_x Offset from left
     * @param integer $offset_y Offset from top
     */
    protected function _adapt($width, $height, $offset_x, $offset_y)
    {
        $image = $this->image;
        $this->image = $this->_create($width, $height);
        imagealphablending($this->image, false);
        $col = imagecolorallocatealpha($this->image, 0, 255, 0, 127);
        imagefilledrectangle($this->image, 0, 0, $width, $height, $col);
        imagealphablending($this->image, true);
        imagecopy($this->image, $image, $offset_x, $offset_y, 0, 0, $this->width, $this->height);
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
        $this->width = $width;
        $this->height = $height;
        imagedestroy($image);
    }

    /**
     * Crop the image.
     * @param integer $width New width
     * @param integer $height New height
     * @param integer $offset_x Offset from left
     * @param integer $offset_y Offset from top
     */
    protected function _crop($width, $height, $offset_x, $offset_y)
    {
        $image = $this->create($width, $height);

        if (imagecopyresampled($image, $this->image, 0, 0, $offset_x, $offset_y, $width, $height, $width, $height)) {
            imagedestroy($this->image);
            $this->image = $image;
            $this->width  = imagesx($image);
            $this->height = imagesy($image);
        }
    }

    /**
     * Rotate the image.
     * @param integer $degrees
     */
    protected function _rotate($degrees)
    {
        $transparent = imagecolorallocatealpha($this->_image, 0, 255, 0, 127);
        $image = imagerotate($this->image, 360 - $degrees, $transparent, true);
        imagesavealpha($image, true);
        $width = imagesx($image);
        $height = imagesy($image);
        if (imagecopymerge($this->image, $image, 0, 0, 0, 0, $width, $height, 100)) {
            imagedestroy($this->image);
            $this->image = $image;
            $this->width  = $width;
            $this->height = $height;
        }
    }

    /**
     * Flip the image.
     * @param string $direction May be Image::HORIZONTAL or Image::VERTICAL
     */
    protected function _flip($direction)
    {
        $image = $this->_create($this->width, $this->height);

        if ($direction === Image::HORIZONTAL) {
            for ($x = 0; $x < $this->width; $x++) {
                imagecopy($image, $this->image, $x, 0, $this->width - $x - 1, 0, 1, $this->height);
            }
        } else {
            for ($y = 0; $y < $this->height; $y++) {
                imagecopy($image, $this->image, 0, $y, 0, $this->height - $y - 1, $this->width, 1);
            }
        }

        imagedestroy($this->image);
        $this->image = $image;
    }
}
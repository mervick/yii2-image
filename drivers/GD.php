<?php

namespace mervick\image\drivers;

use mervick\image\Image;
use yii\base\InvalidParamException;

/**
 * GD driver.
 * @package mervick\image\drivers
 * @author Andrey Izman
 */
class GD extends Image
{
    /**
     * @var resource Image resource
     */
    private $image;


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
     * @param boolean $throwErrors
     * @throws InvalidParamException
     * @throws \ErrorException
     */
    public function __construct($filename, $throwErrors = true)
    {
        static $isLoaded;

        if (!isset($isLoaded)) {
            $isLoaded = self::isLoaded();
        }

        parent::__construct($filename, $throwErrors);

        $create_func = 'imagecreatefrom' . $this->getFormat();
        $this->image = $create_func($this->filename);

        if (!$this->image) {
            $this->error = sprintf('Bad image format: "%s"', $this->filename);
            if ($throwErrors) {
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
    private function create($width, $height)
    {
        $image = imagecreatetruecolor($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
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
        $this->image = $this->create($width, $height);
        imagealphablending($this->image, false);
        $col = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
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
        $transparent = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
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
        $image = $this->create($this->width, $this->height);

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

    /**
     * Sharpen the image.
     * @param integer $amount
     */
    protected function _sharpen($amount)
    {
        // Amount should be in the range of 18-10
        $amount = round(abs(-18 + ($amount * 0.08)), 2);
        // Gaussian blur matrix
        $matrix = [
            [-1, -1, -1],
            [-1, $amount, -1],
            [-1, -1, -1],
        ];
        // Perform the sharpen
        if (imageconvolution($this->image, $matrix, $amount - 8, 0)) {
            // Reset the width and height
            $this->width  = imagesx($this->image);
            $this->height = imagesy($this->image);
        }
    }

    /**
     * Reflection the image.
     * @param integer $height
     * @param integer $opacity
     * @param boolean $fade_in
     */
    protected function _reflection($height, $opacity, $fade_in)
    {
        $opacity = round(abs(($opacity * 127 / 100) - 127));
        $stepping = (127 - ($opacity < 127 ? $opacity : 0)) / max($height, 1);
        $reflection = $this->create($this->width, $this->height + $height);

        imagecopy($reflection, $this->image, 0, 0, 0, 0, $this->width, $this->height);

        for ($offset = 0; $height >= $offset; $offset++)
        {
            $src_y = $this->height - $offset - 1;
            $dst_y = $this->height + $offset;

            if ($fade_in) {
                $dst_opacity = round($opacity + ($stepping * ($height - $offset)));
            } else {
                $dst_opacity = round($opacity + ($stepping * $offset));
            }

            $line = $this->create($this->width, 1);
            imagecopy($line, $this->image, 0, 0, 0, $src_y, $this->width, 1);
            imagefilter($line, IMG_FILTER_COLORIZE, 0, 0, 0, $dst_opacity);
            imagecopy($reflection, $line, 0, $dst_y, 0, 0, $this->width, 1);
        }

        imagedestroy($this->image);
        $this->image = $reflection;

        $this->width  = imagesx($reflection);
        $this->height = imagesy($reflection);
    }

    /**
     * Watermark.
     * @param Image $watermark
     * @param integer $offset_x
     * @param integer $offset_y
     * @param integer $opacity
     */
    protected function _watermark(Image $watermark, $offset_x, $offset_y, $opacity)
    {
        $overlay = imagecreatefromstring($watermark->render());
        imagesavealpha($overlay, true);
        $width  = imagesx($overlay);
        $height = imagesy($overlay);

        if ($opacity < 100)  {
            $opacity = round(abs(($opacity * 127 / 100) - 127));
            $color = imagecolorallocatealpha($overlay, 127, 127, 127, $opacity);
            imagelayereffect($overlay, IMG_EFFECT_OVERLAY);
            imagefilledrectangle($overlay, 0, 0, $width, $height, $color);
        }
        imagealphablending($this->image, true);
        imagecopy($this->image, $overlay, $offset_x, $offset_y, 0, 0, $width, $height);
        imagedestroy($overlay);
    }

    /**
     * Fill image background.
     * @param integer $r
     * @param integer $g
     * @param integer $b
     * @param integer $opacity
     */
    protected function _background($r, $g, $b, $opacity)
    {
        $opacity = round(abs(($opacity * 127 / 100) - 127));
        $background = $this->create($this->width, $this->height);
        $color = imagecolorallocatealpha($background, $r, $g, $b, $opacity);
        imagefilledrectangle($background, 0, 0, $this->width, $this->height, $color);
        imagealphablending($background, true);
        imagecopy($background, $this->image, 0, 0, 0, 0, $this->width, $this->height);
        imagedestroy($this->image);
        $this->image = $background;
    }

    /**
     * Save to file.
     * @param string $filename
     * @param integer|null $quality
     * @return boolean
     */
    protected function _save($filename, $quality)
    {
        $format = $this->getFormat(pathinfo($filename, PATHINFO_EXTENSION), $quality, $type);
        $save_func = "image{$format}";
        $status = isset($quality) ? $save_func($this->image, $filename, $quality) : $save_func($this->image, $filename);

        if ($status) {
            $this->type = $type;
            $this->mime = image_type_to_mime_type($type);
        }

        return $status;
    }

    /**
     * Render image to data string.
     * @param string $type
     * @param integer $quality
     * @return string
     */
    protected function _render($type, $quality)
    {
        $format = $this->getFormat($type, $quality);
        $save_func = "image{$format}";
        ob_start();
        isset($quality) ? $save_func($this->image, null, $quality) : $save_func($this->image);
        return ob_get_clean();
    }
}
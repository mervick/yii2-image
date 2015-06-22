<?php

namespace mervick\image;

use Yii;
use yii\base\InvalidParamException;

/**
 * Class Driver
 * @package mervick\image
 * @author Andrey Izman
 */
abstract class Driver
{
    /**
     * @var string file path
     */
    public $filename;

    /**
     * @var integer width
     */
    public $width;

    /**
     * @var integer height
     */
    public $height;

    /**
     * @var integer image type constant, e.g., `IMAGETYPE_XXX`
     */
    public $type;

    /**
     * @var string mime-type
     */
    public $mime;

    /**
     * @var string|null Error
     */
    public $error;


    /**
     * Constructor. Opens an image.
     * @param string $filename File path
     * @param boolean $throwsErrors [optional] If true will be throws exceptions
     * @throws \InvalidParamException
     */
    public function __construct($filename, $throwsErrors = true)
    {
        $filename = Yii::getAlias($filename);

        if (!is_readable($filename)) {
            $this->error = sprintf('Enable to read file: "%s"', $filename);
            if ($throwsErrors) {
                throw new \InvalidParamException($this->error);
            }
            return;
        }

        try {
            $info = getimagesize($filename);
        } catch (\Exception $e) {
            // Ignore
        }

        if (empty($info) || empty($info[0]) || empty($info[1])) {
            $this->error = sprintf('Bad image format: "%s"', $filename);
            if ($throwsErrors) {
                throw new \InvalidParamException($this->error);
            }
            return;
        }

        $this->filename = $filename;
        $this->width  = intval($info[0]);
        $this->height = intval($info[1]);
        $this->type   = $info[2];
        $this->mime   = image_type_to_mime_type($this->type);
    }

    /**
     * Resize the image to the given size.

     * @param integer $width New width
     * @param integer $height New height
     * @param string $master [optional] Master dimension. Default is 'auto'
     * @return Driver
     */
    final public function resize($width, $height, $master = Image::AUTO)
    {
        if (empty($width) && empty($height)) {
            return $this;
        }

        switch ($master) {
            case Image::CROP:
                if (empty($width) || empty($height)) {
                    return $this->resize($width, $height, Image::AUTO);
                }

                $master = $this->width / $this->height > $width / $height ? Image::HEIGHT : Image::WIDTH;
                $this->resize($width, $height, $master);

                if ($this->width !== $width || $this->height !== $height) {
                    $offset_x = round(($this->width - $width) / 2);
                    $offset_y = round(($this->height - $height) / 2);
                    $this->crop($width, $height, $offset_x, $offset_y);
                }
                return $this;
                break;

            case Image::ADAPT:
                $width = max(round(!empty($width) ? $width : $this->width * $height / $this->height, 1));
                $height = max(round(!empty($height) ? $height : $this->height * $width / $this->width, 1));
                if ($width / $height !== $this->width / $this->height) {
                    $bg_width = $this->width;
                    $bg_height = $this->height;
                    $offset_x = $offset_y = 0;

                    if ($width / $height > $this->width / $this->height) {
                        $bg_width = floor($this->height * $width / $height);
                        $offset_x = abs(floor(($bg_width - $this->width) / 2));
                    } else {
                        $bg_height = floor($this->width * $height / $width);
                        $offset_y = abs(floor(($bg_height - $this->height) / 2));
                    }

                    $this->_adapt($bg_width, $bg_height, $offset_x, $offset_y);
                }
                break;

            case Image::INVERSE:
                $master = ($this->width / $width) > ($this->height / $height) ? Image::HEIGHT : Image::WIDTH;
                break;

            case Image::PRECISE:
                if ($width / $height > $this->width / $this->height) {
                    $height = $this->height * $width / $this->width;
                } else {
                    $width = $this->width * $height / $this->height;
                }
                break;
        }

        switch ($master) {
            case Image::WIDTH:
                if (!empty($width)) {
                    $height = null;
                    $master = Image::AUTO;
                }
                break;

            case Image::HEIGHT:
                if (!empty($height)) {
                    $width = null;
                    $master = Image::AUTO;
                }
                break;
        }

        if (empty($width)) {
            $master = Image::HEIGHT;
        } elseif (empty($height)) {
            $master = Image::WIDTH;
        } elseif ($master === Image::AUTO) {
            $master = ($this->width / $width) > ($this->height / $height) ? Image::WIDTH : Image::HEIGHT;
        }

        switch ($master) {
            case Image::WIDTH:
                $height = $this->height * $width / $this->width;
                break;
            case Image::HEIGHT:
                $width = $this->width * $height / $this->height;
                break;
        }

        $this->_resize(max(round($width), 1), max(round($height), 1));

        return $this;
    }

    /**
     * Resize the image.
     * @param integer $width
     * @param integer $height
     */
    abstract protected function _resize($width, $height);

    /**
     * Adapt the image.
     * @param integer $width
     * @param integer $height
     * @param integer $offset_x
     * @param integer $offset_y
     */
    abstract protected function _adapt($width, $height, $offset_x, $offset_y);

    /**
     * Rotate the image.
     * @param integer $degrees
     * @return Driver
     */
    final public function rotate($degrees)
    {
        $degrees = intval($degrees);

        while ($degrees < 0) {
            $degrees += 360;
        }

        $degrees = $degrees % 360;

        if ($degrees > 0) {
            $this->_rotate($degrees);
        }

        return $this;
    }

    /**
     * Rotate the image using driver.
     * @param integer $degrees
     */
    abstract protected function _rotate($degrees);

    /**
     * Flip the image along the horizontal or vertical axis.
     * @param string $direction May be Image::HORIZONTAL, Image::VERTICAL
     * @return Driver
     */
    final public function flip($direction)
    {
        $this->_flip($direction === Image::HORIZONTAL ? Image::HORIZONTAL : Image::VERTICAL);
        return $this;
    }

    /**
     * Flip the image.
     * @param string $direction
     */
    abstract protected function _flip($direction);

    /**
     * Crop the image.
     * @param integer $width
     * @param integer  $height
     * @param integer|null $offset_x
     * @param integer|null $offset_y
     * @return $this
     */
    final public function crop($width, $height, $offset_x = null, $offset_y = null)
    {
        if ($width > $this->width) {
            $width = $this->width;
        }
        if ($height > $this->height) {
            $height = $this->height;
        }

        if ($offset_x === null) {
            // Center
            $offset_x = round(($this->width - $width) / 2);
        } elseif ($offset_x === true) {
            // Float right
            $offset_x = $this->width - $width;
        } elseif ($offset_x < 0) {
            // Offset right
            $offset_x = $this->width - $width + $offset_x;
        }

        if ($offset_y === null) {
            // Middle
            $offset_y = round(($this->height - $height) / 2);
        } elseif ($offset_y === true) {
            // Float bottom
            $offset_y = $this->height - $height;
        } elseif ($offset_y < 0) {
            // Offset bottom
            $offset_y = $this->height - $height + $offset_y;
        }

        $max_width  = $this->width  - $offset_x;
        $max_height = $this->height - $offset_y;

        if ($width > $max_width) {
            $width = $max_width;
        }

        if ($height > $max_height) {
            $height = $max_height;
        }

        $this->_crop($width, $height, $offset_x, $offset_y);

        return $this;
    }

    /**
     * Crop the image.
     * @param integer $width
     * @param integer $height
     * @param integer $offset_x
     * @param integer $offset_y
     */
    abstract protected function _crop($width, $height, $offset_x, $offset_y);

}
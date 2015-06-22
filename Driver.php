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

    /**
     * Sharpen the image.
     * @param integer $amount
     * @return Driver
     */
    final public function sharpen($amount)
    {
        $this->_sharpen(min(max($amount, 1), 100));
        return $this;
    }

    /**
     * Sharpen the image.
     * @param integer $amount
     */
    abstract protected function _sharpen($amount);

    /**
     * Add a reflection to the image. The most opaque part of the reflection
     * will be equal to the opacity setting and fade out to full transparent.
     * Alpha transparency is preserved.
     *
     *     // Create a 50 pixel reflection that fades from 0-100% opacity
     *     $image->reflection(50);
     *
     *     // Create a 50 pixel reflection that fades from 100-0% opacity
     *     $image->reflection(50, 100, true);
     *
     *     // Create a 50 pixel reflection that fades from 0-60% opacity
     *     $image->reflection(50, 60, true);
     *
     * [!!] By default, the reflection will be go from transparent at the top
     * to opaque at the bottom.
     *
     * @param integer $height reflection height
     * @param integer $opacity reflection opacity: 0-100
     * @param boolean $fade_in true to fade in, false to fade out
     * @return Driver
     */
    public function reflection($height = null, $opacity = 100, $fade_in = false)
    {
        if ($height === null || $height > $this->height)  {
            $height = $this->height;
        }
        $opacity = min(max($opacity, 0), 100);
        $this->_reflection($height, $opacity, $fade_in);

        return $this;
    }

    /**
     * Add a reflection to the image.
     * @param integer $height reflection height
     * @param integer $opacity reflection opacity: 0-100
     * @param boolean $fade_in true to fade in, false to fade out
     */
    abstract protected function _reflection($height, $opacity, $fade_in);

    /**
     * Add a watermark to the image with a specified opacity.
     * Alpha transparency will be preserved.
     *
     * If no offset is specified, the center of the axis will be used.
     * If an offset of true is specified, the bottom of the axis will be used.
     *
     *     // Add a watermark to the bottom right of the image
     *     $mark = Image::factory('upload/watermark.png');
     *     $image->watermark($mark, true, true);
     *
     * @param Driver $watermark
     * @param integer $offset_x Offset from the left
     * @param integer $offset_y Offset from the top
     * @param integer $opacity Opacity of watermark: 1-100
     * @return Driver
     */
    public function watermark(Driver $watermark, $offset_x = null, $offset_y = null, $opacity = 100)
    {
        if ($offset_x === null) {
            // Center the X offset
            $offset_x = round(($this->width - $watermark->width) / 2);
        } elseif ($offset_x === true) {
            // Bottom the X offset
            $offset_x = $this->width - $watermark->width;
        } elseif ($offset_x < 0) {
            // Set the X offset from the right
            $offset_x = $this->width - $watermark->width + $offset_x;
        }

        if ($offset_y === null) {
            // Center the Y offset
            $offset_y = round(($this->height - $watermark->height) / 2);
        } elseif ($offset_y === true) {
            // Bottom the Y offset
            $offset_y = $this->height - $watermark->height;
        } elseif ($offset_y < 0) {
            // Set the Y offset from the bottom
            $offset_y = $this->height - $watermark->height + $offset_y;
        }
        $opacity = min(max($opacity, 1), 100);
        $this->_watermark($watermark, $offset_x, $offset_y, $opacity);

        return $this;
    }

    /**
     * Add a watermark to the image.
     * @param Driver $image
     * @param integer $offset_x
     * @param integer $offset_y
     * @param integer $opacity
     */
    abstract protected function _watermark(Driver $image, $offset_x, $offset_y, $opacity);

    /**
     * Set the background color of an image. This is only useful for images
     * with alpha transparency.
     *
     *     // Make the image background black
     *     $image->background('#000');
     *
     *     // Make the image background black with 50% opacity
     *     $image->background('#000', 50);
     *
     * @param string $color Hexadecimal color
     * @param integer $opacity Background opacity: 0-100
     * @return Driver
     */
    public function background($color, $opacity = 100)
    {
        if ($color{0} === '#') {
            $color = substr($color, 1);
        }

        if (strlen($color) == 3) {
            $color = preg_replace('/./', '$0$0', $color);
        }

        list ($r, $g, $b) = array_map('hexdec', str_split($color, 2));

        $opacity = min(max($opacity, 0), 100);
        $this->_background($r, $g, $b, $opacity);

        return $this;
    }

    /**
     * Fill the background of the image.
     * @param integer $r
     * @param integer $g
     * @param integer $b
     * @param integer $opacity
     */
    abstract protected function _background($r, $g, $b, $opacity);


    /**
     * Save the image. If the filename is omitted, the original image will
     * be overwritten.
     *
     *     // Save the image as a PNG
     *     $image->save('/path/to/cool.png');
     *
     *     // Overwrite the original image
     *     $image->save();
     *
     * [!!] If the file exists, but is not writable, an exception will be thrown.
     *
     * [!!] If the file does not exist, and the directory is not writable, an
     * exception will be thrown.
     *
     * @param string|null $filename Image file name
     * @param integer|null $quality Quality 1-100 for JPEG, 0-9 for PNG
     * @return boolean
     * @throws \ErrorException
     */
    final public function save($filename = null, $quality = null)
    {
        if (empty($filename)) {
            // Overwrite the file
            $filename = $this->filename;
        } else {
            $filename = Yii::getAlias($filename);
        }

        if (is_file($filename)) {
            if (!is_writable($filename)) {
                throw new \ErrorException(sprintf('Enable to write file: %s', $filename));
            }
        } else {
            $path = realpath(pathinfo($filename, PATHINFO_DIRNAME));
            if (!is_dir($path)) {
                if (!mkdir($path, 0777, true)) {
                    throw new \ErrorException(sprintf('Unable to make dir: %s', $path));
                }
            }
        }

        return $this->_save($filename, $quality);
    }

    /**
     * Save image to file.
     * @param string $filename
     * @param int|null $quality
     */
    abstract protected function _save($filename, $quality);

    /**
     * Render the image and return the binary string.
     *
     *     // Render the image at 50% quality
     *     $data = $image->render(NULL, 50);
     *
     *     // Render the image as a PNG
     *     $data = $image->render('png');
     *
     * @param string|null $type
     * @param integer|null $quality
     * @return string
     */
    public function render($type = null, $quality = null)
    {
        if ($type === null) {
            $type = image_type_to_extension($this->type, null);
        }

        return $this->_render($type, $quality);
    }

    /**
     * Render the image.
     * @param string $type
     * @param integer $quality
     * @return strng
     */
    abstract protected function _render($type, $quality);
}
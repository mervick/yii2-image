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
     * @param string $filename
     * @param boolean $throwsErrors
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

}
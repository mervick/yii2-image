<?php

namespace mervick\image;

use Yii;
use yii\base\Component as BaseComponent;
use yii\base\ErrorException;


class Component extends BaseComponent
{
    /**
     * @var string Driver
     */
    public $driver = '\\mervick\\image\\drivers\\GD';


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param string $file
     * @return \mervick\image\drivers\ImageDriver
     */
    public function load($file)
    {
        return Image::load($file, $this->driver);
    }
}
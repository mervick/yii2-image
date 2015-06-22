<?php

namespace mervick\image;

use Yii;
use yii\base\Component;
use yii\base\ErrorException;
use mervick\image\drivers;


class Image extends Component
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
}
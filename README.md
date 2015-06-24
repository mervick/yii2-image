# yii2-image 

Yii2 Framefork Extension for Image Manipulation.  
Forked from [yurkinx/yii2-image](https://github.com/yurkinx/yii2-image)

## Installation


Add to your composer.json:  
```php
"require": 
{
    "mervick/yii2-image": "~1.0"
}
```
after that exec `php composer.phar update`  

## Usage 

### Simple:
```php

$driver = \mervick\image\drivers\GD::classname(); // or `\mervick\image\drivers\Imagick::classname()`
/* @var $image \mervick\image\drivers\GD */
$image = \mervick\image\Image::load('@path/to/file', $driver);
$image->resize($width, $height, 'crop');
$image->save(); 
// or $image->save('@path/to/file2.png', $quality);

```
### Component:

configuration, `main.php`:
```php
'components' => [
    'image' => [
        'class' => 'mervick\\image\\Component',
        'driver' => 'mervick\\image\\drivers\\Imagick',
    ],
],
```
usage:
```php
/* @var $image \mervick\image\Driver */
$image = Yii::$app->image->load('@path/to/file');
$image->flip('vertical')->save('@path/to/file2.jpeg', $quality);

```

### Resize constants:
```php
    const WIDTH   = 'width';
    const HEIGHT  = 'height';
    const AUTO    = 'auto';
    const INVERSE = 'inverse';
    const PRECISE = 'precise';
    const ADAPT   = 'adapt';
    const CROP    = 'crop';
```
###Flipping constants
```php
    const HORIZONTAL = 'horizontal';
    const VERTICAL   = 'vertical';
```

## License
BSD-3-Clause






# yii2-image 
[![Analytics](https://ga-beacon.appspot.com/UA-65295275-1/yii2-image)](https://github.com/igrigorik/ga-beacon)

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

### Simple
```php
$driver = \mervick\image\drivers\GD::classname(); // or `\mervick\image\drivers\Imagick::classname()`
/* @var $image \mervick\image\Image */
$image = \mervick\image\Image::load('@path/to/file', $driver);
$image->resize($width, $height, 'crop');
$image->save(); 
// or $image->save('@path/to/file2.png', $quality);

```
### Using component

configure in `main.php`:
```php
'components' => [
    'image' => [
        'class' => 'mervick\\image\\Component',
        'driver' => 'mervick\\image\\drivers\\Imagick',
    ],
],
```
usage
```php
/* @var $image \mervick\image\Image */
$image = Yii::$app->image->load('@path/to/file');
$image->flip('vertical')->save('@path/to/file2.jpeg', $quality);

```

## Api
```php
/**
 * Resize the image to the given size.
 * @param integer $width New width
 * @param integer $height New height
 * @param string $master [optional] Master dimension. Default is 'auto'
 * @return Image
 */
public function resize($width, $height, $master = Image::AUTO);

/**
 * Rotate the image.
 * @param integer $degrees
 * @return Image
 */
public function rotate($degrees);

/**
 * Flip the image along the horizontal or vertical axis.
 * @param string $direction May be Image::HORIZONTAL, Image::VERTICAL
 * @return Image
 */
public function flip($direction);

/**
 * Crop the image.
 * @param integer $width
 * @param integer  $height
 * @param integer|null $offset_x
 * @param integer|null $offset_y
 * @return Image
 */
public function crop($width, $height, $offset_x = null, $offset_y = null);

/**
 * Sharpen the image.
 * @param integer $amount
 * @return Image
 */
public function sharpen($amount);

/**
 * Add a reflection to the image.
 * @param integer $height reflection height
 * @param integer $opacity reflection opacity: 0-100
 * @param boolean $fade_in true to fade in, false to fade out
 * @return Image
 */
public function reflection($height = null, $opacity = 100, $fade_in = false);

/**
 * Add a watermark to the image with a specified opacity.
 * @param Image $watermark
 * @param integer $offset_x Offset from the left
 * @param integer $offset_y Offset from the top
 * @param integer $opacity Opacity of watermark: 1-100
 * @return Image
 */
public function watermark(Image $watermark, $offset_x = null, $offset_y = null, $opacity = 100);
 
/**
 * Fill the background color of the image.
 * @param string $color Hexadecimal color
 * @param integer $opacity Background opacity: 0-100
 * @return Image
 */
public function background($color, $opacity = 100);

/**
 * Save the image. If the filename is omitted, the original image will
 * be overwritten.
 * @param string|null $filename Image file name
 * @param integer|null $quality Quality 1-100 for JPEG, 0-9 for PNG
 * @return boolean
 * @throws \ErrorException
 */
public function save($filename = null, $quality = null);

/**
 * Render the image.
 * @param string|null $type
 * @param integer|null $quality
 * @return string binary string
 */
public function render($type = null, $quality = null);
```

### Image constants
```php
    // resize contants
    const WIDTH   = 'width';
    const HEIGHT  = 'height';
    const AUTO    = 'auto';
    const INVERSE = 'inverse';
    const PRECISE = 'precise';
    const ADAPT   = 'adapt';
    const CROP    = 'crop';
    
    // Flipping constants 
    const HORIZONTAL = 'horizontal';
    const VERTICAL   = 'vertical';
```

## License
[BSD-3-Clause](http://opensource.org/licenses/BSD-3-Clause)






<?php
namespace App\File\Image;

use App\File\File;

function gzdecode($data){
  $g=tempnam(APPLICATION_PATH . '/../../data/temp', 'gzip');
  @file_put_contents($g,$data);
  ob_start();
  readgzfile($g);
  $d=ob_get_clean();
  unlink($g);
  return $d;
}


class Gd
{
    protected static $_fontsDir = '',
                     $_defaultFont = null;

    protected $from = '',
              $fromType,
              $baseExt, // Реализовано только для создания объекта из файла
              $im = false,
              $wid = 0, 
              $hei = 0;
    
    const FROM_NONE = 0,
          FROM_FILE = 1,
          FROM_BYTES = 2,
          FROM_GD_IMAGE = 3,
          FROM_BASE64 = 4,
          FROM_BASE64_GZIP = 5,
          FROM_GZIP = 6,
          FROM_STRUCT = 7;
          
    const EXT_PNG = 'png',
          EXT_GIF = 'gif',
          EXT_JPG = 'jpg';
    
    /**
     * @param mixed $from
     * @param int $fromType
     */
    public function __construct($from, $fromType = self::FROM_FILE)
    {
        $this->fromType = $fromType;
        
        if ($fromType == self::FROM_NONE) {
            /**
             * @todo Переделать на Exception?
             */
            return false;
        }

        if ($fromType == self::FROM_STRUCT) {
            if (!is_array($from)) {
                $from = array();
            }
            $width = max(@$from['width'], 1);
            $height = max(@$from['height'], 1);

            $this->im = @imagecreatetruecolor($width, $height);
            $this->_prepare();

            if (is_int(@$from['color'])) {
                $fillColor = $from['color'];
            } elseif (count(@$from['color'] ?: array()) == 3) {
                $r = array_shift($from['color']);
                $g = array_shift($from['color']);
                $b = array_shift($from['color']);
                $fillColor = $this->getColor($r, $g, $b);
            } else {
                $fillColor = $this->getColor(255, 255, 255);
            }
            imagefill($this->im, 0, 0, $fillColor);

            return false;
        }

        if ($fromType == self::FROM_GD_IMAGE) {
            if (!$from || gettype($from) != 'resource' || get_resource_type($from) != 'gd') {
                throw new \Exception('Source data is not an GD image.');
            }
            $w = imagesx($from);
            $h = imagesy($from);
            $this->im = @imagecreatetruecolor($w, $h);
            $this->_prepare();
            $fillColor = $this->getColor(255, 255, 255, 127);
            imagefill($this->im, 0, 0, $fillColor);
            @imagecopy($this->im, $from, 0, 0, 0, 0, $w, $h);
            return false;
        }

        if ($fromType == self::FROM_BASE64) {
            $from = base64_decode($from);
            if (!$from) {
                throw new \Exception('Wrong Base64 image');
            }
            $fromType = self::FROM_BYTES;
        }

        if ($fromType == self::FROM_BASE64_GZIP) {
            $from = base64_decode($from);
            if (!$from) {
                throw new \Exception('Wrong Base64 GZipped image');
            }
            $fromType = self::FROM_GZIP;
        }

        if ($fromType == self::FROM_GZIP) {
            if (!function_exists('gzdecode')) {
                throw new \Exception('ZLib gzdecode function doesn\'t exists');
            }
            $from = gzdecode($from);
            if (!$from) {
                throw new \Exception('Wrong GZipped image');
            }
            $fromType = self::FROM_BYTES;
        }

        if ($fromType == self::FROM_BYTES) {
            $this->im = @imagecreatefromstring($from);
            if (!$this->im) {
                throw new \Exception('Source byte array is not an image.');
            }
            $this->_prepare();
            return false;
        }

        if ($fromType != self::FROM_FILE) {
            throw new \Exception('Unknown source type. Use '.__CLASS__.'::FROM_* constants.');
        }

        if (!is_file($from) || (!list(,,$type) = @getimagesize($from))) {
            throw new \Exception('Wrong or missing source image file.');
        }

        switch ($type)
        {
          case 1:
              $this->im = imagecreatefromgif($from);
              $this->baseExt = self::EXT_GIF;
              break;
          case 2:
              $this->im = imagecreatefromjpeg($from);
              $this->baseExt = self::EXT_JPG;
              break;
          case 3:
              $this->im = imagecreatefrompng($from);
              $this->baseExt = self::EXT_PNG;
              break;
          default:
              $this->im = false;
              return false;
        }

        $this->_prepare();
        $this->from = $from;
    }

    public function __destruct()
    {
        if ($this->im) {
            imagedestroy($this->im);
        }
    }

    private function _prepare()
    {
        /**
         * @todo Переделать на Exception
         */
        if (!$this->im) {
            return;
        }
        imageantialias($this->im, true);
        $this->wid = imagesx($this->im);
        $this->hei = imagesy($this->im);
    }

    /**
     * @param int $width
     * @param int $height
     * @return Gd
     */
    public static function create($width, $height, $color = array())
    {
        $config = array('width' => $width,
                        'height' => $height,
                        'color' => $color);
        return new self($config, self::FROM_STRUCT);
    }

    public static function setFontsDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        self::$_fontsDir = realpath($dir);
        return true;
    }

    public static function setDefaultFont($font)
    {
        self::_loadFont($font);
        self::$_defaultFont = $font;
    }

    public function getWidth()
    {
        if (!$this->im) {
            return null;
        }
        return $this->wid;
    }

    public function getHeight()
    {
        if (!$this->im) {
            return null;
        }
        return $this->hei;
    }

    /**
    * @return Gd
    */
    public function cloneImage()
    {
        if (!$this->im) {
            return null;
        }

        return new Gd($this->im, self::FROM_GD_IMAGE);
    }

    protected function _output($ext, $quality = 90)
    {
        if (!$this->im) {
            return false;
        }

        ob_start();
        switch ($ext)
        {
          case self::EXT_GIF:
            imagegif($this->im);
            break;

          case self::EXT_JPG:
          case "jpeg":
            imagejpeg($this->im, '', $quality);
            break;

          case self::EXT_PNG:
            imagepng($this->im);
            break;

          default:
            ob_end_clean();
            return false;
        }

        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    public function save($to, $quality = 90)
    {
        if (!$this->im) {
            return false;
        }

        $ext = pathinfo($to, PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = $this->baseExt;
            $to .= '.' . $ext;
        }
        File::createDirForFileRecursive($to);

        $result = false;
        switch ($ext)
        {
            case self::EXT_GIF:
                $result = imagegif($this->im, $to);
                break;
            case self::EXT_JPG:
            case "jpeg":
                $result = imagejpeg($this->im, $to, $quality);
                break;
            case self::EXT_PNG:
                $result = imagepng($this->im, $to);
                break;
            default:
                throw new \Exception('Wrong extension ' . print_r($ext, true));
        }

        if (!$result) {
            throw new \Exception('Error while saving to "' . $to . '"');
        }

        return $to;
    }


    public function show($ext, $quality = 90, \Zend\Http\Response $response = null, $headersFilename = null, $mtime = null)
    {
        $output = $this->_output($ext, $quality);
        if ($output === false) {
            return false;
        }

        if ($headersFilename === null && $this->fromType == self::FROM_FILE) {
			$info = pathinfo($this->from);
            if ($info) {
                $headersFilename = @$info['basename'];
            }
        }
        if ($headersFilename) {
            $headersFilename = iconv('CP1251', 'UTF-8', $headersFilename);
        }
        if ($response instanceof \Zend\Http\Response) {
            $response->setHeader('Content-type', 'image/' . $ext)
                     ->setBody($output);
            if ($headersFilename) {
                $response->setHeader('Content-Disposition', 'filename="'.$headersFilename.'"');
            }
            if ($mtime) {
                $response->setHeader('Last-Modified', date('D, d M Y H:i:s T', $mtime));
            }
        } else {
            header('Content-type: image/' . $ext);
            if ($headersFilename) {
                header('Content-Disposition: filename="'.$headersFilename.'"');
            }
            if ($mtime) {
                header('Last-Modified: ' . date('D, d M Y H:i:s T', $mtime));
            }
            echo $output;
        }
        return true;
    }


    public function base64($ext, $quality = 90, $inlineImage = false)
    {
        $output = $this->_output($ext, $quality);
        if ($output === false) {
            return false;
        }
        $output = base64_encode($output);
        if ($inlineImage) {
            return 'data:image/' . $ext . ';base64,' . $output;
        } else {
            return $output;
        }
    }

    public function getColor($r, $g, $b, $alpha = 0)
    {
        if (!$this->im) {
            return $this;
        }

        if ($alpha) {
            return imagecolorallocatealpha($this->im, $r, $g, $b, $alpha);
        } else {
            return imagecolorallocate($this->im, $r, $g, $b);
        }
    }

    /**
    * @param int $nw
    * @param int $nh
    * @return Gd
    */
    public function resize($nw, $nh)
    {
        if (!$this->im) {
            return $this;
        }

        $im = &$this->im;
        $w = $this->getWidth();
        $h = $this->getHeight();
        $kf = $w/$h;
        if (!$nw && $nh) {
            $nw = floor($nh * $kf);
        } else if($nw && !$nh) {
            $nh = floor($nw / $kf);
        }
        $new = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($new, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($im);
        $this->im = &$new;
        $this->_prepare();
        return $this;
    }

    /**
    * @param int $maxw
    * @param int $maxh
    * @return Gd
    */
    public function reduce($maxw, $maxh)
    {
        if (!$this->im) {
            return $this;
        }

        $im = &$this->im;
        $nw = $w = $this->getWidth();
        $nh = $h = $this->getHeight();
        if ($maxw >= $w && $maxh >= $h) {
            return $this;
        }
        if ($nw > $maxw) {
            $nw = $maxw;
            $k = $nw / $w;
            $nh = round($k * $h);
        }
        if ($nh > $maxh) {
            $nh = $maxh;
            $k = $nh / $h;
            $nw = round($k * $w);
        }
        return $this->resize($nw, $nh);
    }

    /**
    * @param int $x
    * @param int $y
    * @param int $w
    * @param int $h
    * @return Gd
    */
    public function crop($x, $y, $w, $h)
    {
        if (!$this->im) {
            return $this;
        }

        $x = round($x);
        $y = round($y);
        $w = round($w);
        $h = round($h);

        $im = &$this->im;
        $ow = $this->getWidth();
        $oh = $this->getHeight();
        if ($x >= $ow) {
            $x = $ow - 1;
        }
        if ($y >= $oh) {
            $y = $oh - 1;
        }
        if ($x + $w > $ow) {
            $w = $ow - $x;
        }
        if ($y + $h > $oh) {
            $h = $oh - $y;
        }
        $new = imagecreatetruecolor($w, $h);
        imagecopyresampled($new, $im, 0, 0, $x, $y, $w, $h, $w, $h);
        imagedestroy($im);
        $this->im = &$new;
        $this->_prepare();
        return $this;
    }

    /**
    * @param int $x
    * @param int $y
    * @param int $w
    * @param int $h
    * @return Gd
    */
    public function cropPercents($x, $y, $w, $h)
    {
        if (!$this->im) {
            return $this;
        }

        $im = &$this->im;
        $ow = $this->getWidth();
        $oh = $this->getHeight();
        return $this->crop($ow * $x / 100, $oh * $y / 100, $ow * $w / 100, $oh * $h / 100);
    }

    /**
     * Пропорционально изменяет размеры изображения,
     * подгоняя по большему и обрезая по меньшему
     *
     * @param int $w
     * @param int $h
     * @return Gd
     */
    public function resizeAndCrop($w, $h)
    {
        if (!$this->im) {
            return $this;
        }

        $kNew = $w / $h;
        $kOld = $this->getWidth() / $this->getHeight();
        if ($kNew > $kOld) {
            $wNew = $w;
            $hNew = $wNew / $kOld;
        } else {
            $hNew = $h;
            $wNew = $hNew * $kOld;
        }
        $wNew = round($wNew);
        $hNew = round($hNew);
        if ($wNew != $this->getWidth() || $hNew != $this->getHeight()) {
            $this->resize($wNew, $hNew);
        }

        $x = round(($wNew - $w) / 2);
        $y = round(($hNew - $h) / 2);
        if ($x || $y) {
            $this->crop($x, $y, $w, $h);
        }

        return $this;
    }

    public function paste($img, $x, $y)
    {
        if (!$this->im) {
            return $this;
        }

        if ($img instanceof Gd) {
            $w = $img->getWidth();
            $h = $img->getHeight();
            $img = &$img->im;
        } elseif (gettype($img) == 'resource' && get_resource_type($img) == 'gd') {
            $w = imagesx($img);
            $h = imagesy($img);
        } else {
            /**
             * @todo Переделать на Exception
             */
            return $this;
        }

        imagecopy($this->im, $img, $x, $y, 0, 0, $w , $h);

        return $this;
    }

    protected static function _loadFont($font)
    {
        if (!$font && self::$_defaultFont !== null) {
            $font = self::$_defaultFont;
        }
        if (substr($font, 0, -4) != '.ttf') {
            $font .= '.ttf';
        }
        $font = self::$_fontsDir . '/' . $font;
        if (!is_file($font)) {
            throw new Exception('Wrong font name or fonts dir wasn\'t set.');
        }
        return $font;
    }
    
    public function drawText($text, $x, $y, $color, $size, $font = null, $angle = 0)
    {
        if (!$this->im) {
            return $this;
        }
        
        $font = $this->_loadFont($font);
        imagettftext($this->im, $size, $angle, $x, $y, $color, $font, $text);
        return $this;
    }

    /**
     * Returns an array with 8 elements representing four points making the bounding box:
     * 0: lower left corner, X position
     * 1: lower left corner, Y position
     * 2: lower right corner, X position
     * 3: lower right corner, Y position
     * 4: upper right corner, X position
     * 5: upper right corner, Y position
     * 6: upper left corner, X position
     * 7: upper left corner, Y position
     * 
     * @param string $text
     * @param int $size
     * @param string $font
     * @param int $angle
     * @return array|bool 
     */
    public function measureText($text, $size, $font = null, $angle = 0)
    {
        if (!$this->im) {
            return false;
        }
     
        $font = $this->_loadFont($font);
        return imagettfbbox($size, $angle, $font, $text);
    }
    
    /**
     * Returns an array with width and height of the rectangle which can contain text
     * 
     * @param string $text
     * @param int $size
     * @param string $font
     * @param int $angle
     * @return array|bool 
     */
    public function measureTextRectangle($text, $size, $font = null, $angle = 0)
    {
        if (!$this->im) {
            return false;
        }
        
        $bounds = $this->measureText($text, $size, $font, $angle);
        if (!$bounds) {
            return false;
        }
        
        $x = min($bounds[0], $bounds[2], $bounds[4], $bounds[6]);
        $y = min($bounds[1], $bounds[3], $bounds[5], $bounds[7]);
        $w = max($bounds[0], $bounds[2], $bounds[4], $bounds[6]) - $x;
        $h = max($bounds[1], $bounds[3], $bounds[5], $bounds[7]) - $y;
        return array($w, $h);
    }

    public function insertInImage($image, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        imagecopymerge($this->im, $image, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct);
    }

    public function getGdObject()
    {
        return $this->im;
    }
}
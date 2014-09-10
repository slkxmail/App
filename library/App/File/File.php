<?php
namespace App\File;

class File
{
    const SOURCE_TYPE_LOCAL = 1;
    const SOURCE_TYPE_HTTP  = 2;
    
    protected $_sourceType;
    protected $_fullPath;
    protected $_dirName;
    protected $_baseName;
    protected $_fileName;
    protected $_ext;
    protected $_size = null;
    protected $_mtime = null;
    
    /**
    * @param string $path Путь к файлу
    * @return App_File
    */
    public function __construct($path)
    {
        if (!is_scalar($path) || !$path) {
            throw new Exception('Wrong path');
        }
		if (strpos($path, 'http://') === 0) {
			$headersUrl = get_headers($path);
			if (strpos(reset($headersUrl), '200') === false) {
				throw new Exception('File "' . $path . '" not found');
			}
			foreach ($headersUrl as $header) {
                if (strpos(strtolower($header), 'last-modified:') === 0) {
                    $val = trim(substr($header, strpos($header, ':')));
                    $this->_mtime = strtotime($val);
                }
				/*if (strpos(strtolower($header), 'content-length:') === 0) {
					$this->_size = end(explode(' ', $header));
					break;
				}*/
			}
            if (!$this->_mtime) {
                $this->_mtime = false;
            }
			$this->_size = false;
            
            $this->_sourceType = self::SOURCE_TYPE_HTTP;
		} else {
			if (!is_file($path)){
				throw new Exception('File "' . $path . '" not found');
			}
			if (!is_readable($path)) {
				throw new Exception('Can\'t access "' . $path . '"');
			}
            $this->_sourceType = self::SOURCE_TYPE_LOCAL;
		}
        $this->_initPathInfo($path);
    }
    
    protected function _initPathInfo($path)
    {
        if ($this->_sourceType == self::SOURCE_TYPE_HTTP) {
			$this->_fullPath = $path;
            $explodeSlash = explode('/', $path);
			$this->_baseName = array_pop($explodeSlash);
			$this->_dirName = implode('/', $explodeSlash);
            
            $explodeDot = explode('.', $this->_baseName);
			$this->_ext = array_pop($explodeDot);
            $this->_fileName = implode('.', $explodeDot);
        } else {
			$path = realpath($path);
			$info = pathinfo($path);
            $this->_fileName = @$info['filename'];
			$this->_baseName = @$info['basename'];
			$this->_dirName = @$info['dirname'];
			$this->_ext = @$info['extension'] ?: '';
			$this->_fullPath = $path;
        }
    }
    
    protected function _dropInfo()
    {
        $this->_size = null;
        $this->_mtime = null;
    }
    
    /**
     * Создает новый пустой файл
     * 
     * @param string $path Путь к файлу
     * @param bool $checkExists Если проверять существование файла, и он есть - кинет исключение, не проверять - затрет содержимое
     * @return App_File
     */
    public static function create($path, $checkExists = true)
    {
        if ($checkExists && file_exists($path)) {
            throw new Exception('File "' . $path . '" already exists');
        }
        self::createDirForFileRecursive($path);
        $file = @fopen($path, 'w');
        if (!$file) {
            throw new Exception('Can\'t create file "' . $path . '"');
        }
        fclose($file);
        return new self($path);
    }
    
    /**
    * Полный путь к файлу
    * Например, /home/user/file.ext
    * 
    * @return string
    */
    public function getFullPath()
    {
        return $this->_fullPath;
    }
    
    /**
    * Путь к родительской директории
    * Например, /home/user
    * 
    * @return string
    */
    public function getDirName()
    {
        return $this->_dirName;
    }
    
    /**
    * Имя файла полное
    * Например, file.ext
    * 
    * @return string
    */
    public function getBaseName()
    {
        return $this->_baseName;
    }
    
    /**
    * Имя файла без расширения
    * Например, file
    * 
    * @return string
    */
    public function getFileName()
    {
        return $this->_fileName;
    }
    
    /**
    * Расширение файла
    * Например, ext
    * 
    * @return string
    */
    public function getExt()
    {
        return strtolower($this->_ext);
    }
    
    /**
    * Размер файла
    * 
    * @return float|bool
    */
    public function getSize()
    {
        if ($this->_size === null) {
            $this->_size = self::calcSize($this->getFullPath());
        }
        return $this->_size;
    }
    
    /**
    * Дата изменения
    * 
    * @return int|bool
    */
    public function getModifiedTime()
    {
        if ($this->_mtime === null) {
            $this->_mtime = filemtime($this->getFullPath());
        }
        return $this->_mtime;
    }
    
    /**
     * Тип источника файла
     * 
     * @return int App_File::SOURCE_TYPE_*
     */
    public function getSourceType()
    {
        return $this->_sourceType;
    }
    
    /**
     * Локальный файл
     * 
     * @return bool
     */
    public function isSourceTypeLocal()
    {
        return $this->_sourceType == self::SOURCE_TYPE_LOCAL;
    }
    
    /**
     * Файл загружен по HTTP
     * 
     * @return bool
     */
    public function isSourceTypeHttp()
    {
        return $this->_sourceType == self::SOURCE_TYPE_HTTP;
    }
    
    /**
     * Задать содержимое файла
     * 
     * @param string $contents 
     */
    public function setContents($contents)
    {
        if ($this->getSourceType() != self::SOURCE_TYPE_LOCAL) {
            throw new Exception('Setting contents supported for local files only.');
        }
        $saveResult = @file_put_contents($this->getFullPath(), $contents);
        if (!$saveResult) {
            throw new Exception('Can\'t save file "' . $this->getFullPath() . '"');
        }
        $this->_dropInfo();
    }
    
    /**
     * Задать содержимое файла из Base64 строки
     * 
     * @param string $contentsBase64
     */
    public function setContentsFromBase64($contentsBase64)
    {
        $fileBinary = base64_decode($contentsBase64, true);
        if (!$fileBinary) {
            throw new Exception('Wrong base64 string');
        }
        return $this->setContents($fileBinary);
    }
    
    /**
     * Проверить не изменен ли файл с последней загрузки
     * Если не изменен, установить заголовок
     * 
     * @param Zend_Controller_Request_Http $request
     * @param Zend_Controller_Response_Http $response
     * @return bool 
     */
    public function checkIfNotModified(Zend_Controller_Request_Http $request, Zend_Controller_Response_Http $response = null)
    {
        $mtime = $this->getModifiedTime();
        if ($mtime) {
            $ifModSince = strtotime($request->getHeader('If-Modified-Since'));
            if ($ifModSince >= $mtime) {
                if ($response) {
                    $response->setHttpResponseCode(304);
                } else {
                    header($protocol.' 304 Not Modified');
                }
                return true;
            }
        }
        return false;
    }
    
    /**
     * Отдать файл в поток
     * 
     * @param Zend_Controller_Request_Http $request Объект запроса для поддержки докачки и кеша 304
     * @param string $headersFileName Отображаемое имя файла или null - оставить оргинальное имя
     * @param string $mime MIME-тип или null - определить по расширению
     * @param bool $isAttachment Как вложение - отобразит в браузере окно сохранения файла
     * @param bool $sendMTime Отдавать дату последней модификации
     * @return void
     */
    public function output($request = null, $headersFileName = null, $mime = 'application/octet-stream', $isAttachment = true, $sendMTime = false)
    {
        set_time_limit(0);
     
        $path = $this->getFullPath();
        $fsize = $this->getSize();

        $fd = @fopen($path, 'rb');
        if ($fsize && $request instanceof Zend_Controller_Request_Http && $range = $request->getServer('HTTP_RANGE')) {
            $range = str_replace('bytes=', '', $range);
            $t = explode('-', $range);
            $range = @$t[0] ?: 0;
            //$end = @$t[1] ?: $fsize;

            if (!empty($range)) {
                fseek($fd, $range);
            }
        } else {
            $range = 0;
        }

        $protocol = 'HTTP/1.1';
        if ($request instanceof Zend_Controller_Request_Http && $request->getServer('SERVER_PROTOCOL')) {
            $protocol = $request->getServer('SERVER_PROTOCOL');
        }
        
        //ob_end_clean();
        
        $mtime = false;
        if ($sendMTime) {
            $mtime = $this->getModifiedTime();
        }
        
        //$headersFileNameConv = iconv('CP1251', 'UTF-8', $headersFileName ?: $this->getBaseName());
        $headersFileNameConv = $headersFileName ?: $this->getBaseName();

        if ($isAttachment) {
            header('Content-Disposition: attachment; filename="' . $headersFileNameConv . '"');
        }
        //header('Content-Disposition: ' . ($isAttachment ? 'attachment' : 'inline') . '; filename="' . $headersFileNameConv . '"');

        if (!$mime) {
            $mime = $this->getFileMimeTypeByExt();
        }
        header('Content-Disposition: attachment; filename="' . $headersFileNameConv . '"');
        header('Content-Type: ' . $mime . '; name="' . $headersFileNameConv . '"');
        
        if ($mtime) {
            $ifModSince = strtotime($request->getHeader('If-Modified-Since'));
            if ($ifModSince >= $mtime) {
                header($protocol.' 304 Not Modified');
                exit;
            }
        }
        
        if ($range) {
            header($protocol.' 206 Partial Content');
        } else {
            header($protocol.' 200 OK');
        }

        if ($mtime) {
            header('Last-Modified: ' . date('D, d M Y H:i:s T', $mtime));
        }
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        if ($fsize !== false) {
            if ($range) {
                header('Content-Length: '.($fsize - $range));
                header("Content-Range: bytes ".$range."-".($fsize - 1).'/'.$fsize);
            } else {
                header('Content-Length: '.$fsize);
            }
        }

        ob_end_clean();
        flush();

        //fpassthru($fd);
        
        while (!feof($fd) && !connection_status()) {
            echo fread($fd, 1048576);
            flush();
        }
        
        fclose($fd);

        exit;

        /*
        ob_end_clean();
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . iconv('CP1251', 'UTF-8', basename($path)) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        $fsize = App_File::getSize($path, true);
        if ($fsize !== false) {
            header('Content-Length: ' . $fsize);
        }
        ob_clean();
        flush();
        readfile($path);
        exit;
        */
    }


    /**
     * Отдать файл в поток как вложение - отобразит в браузере окно сохранения файла
     *
     * @param Zend_Controller_Request_Http $request Объект запроса для поддержки докачки и кеша 304
     * @param string $headersFileName Отображаемое имя файла или null - оставить оргинальное имя
     * @param bool $sendMTime Отдавать дату последней модификации
     * @return void
     */
    public function outputAsAttach($request = null, $headersFileName = null, $sendMTime = false)
    {
        $this->output($request, $headersFileName, $this->getFileMimeType(), true, $sendMTime);
    }

    /**
     * Отдать файл в поток как встроенный - откроет в браузере
     *
     * @param Zend_Controller_Request_Http $request Объект запроса для поддержки докачки и кеша 304
     * @param string $headersFileName Отображаемое имя файла или null - оставить оргинальное имя
     * @param bool $sendMTime Отдавать дату последней модификации
     * @return void
     */
    public function outputAsInline($request = null, $headersFileName = null, $sendMTime = false)
    {
        $this->output($request, $headersFileName, $this->getFileMimeType(), false, $sendMTime);
    }
    
    /**
     * @return string
     */
    public function base64()
    {
        return base64_encode(file_get_contents($this->getFullPath()));
    }
    
    /**
     * Удалить файл
     * 
     * @return void
     */
    public function unlink()
    {
        if ($this->getSourceType() != self::SOURCE_TYPE_LOCAL) {
            throw new Exception('Unlink supported for local files only.');
        }
        if (!@unlink($this->getFullPath())) {
            throw new Exception('Can\'t unlink "' . $this->getFullPath() . '"');
        }
    }
    
    /**
     * Переименовать/переместить файл
     * 
     * @param string $newName Новое имя файла
     * @param bool $relativeCurrentPath Новое имя относительно текущего пути
     * @param bool $overwrite Перезаписать файл
     * @return void
     */
    public function rename($newName, $relativeCurrentPath = false, $overwrite = false)
    {
        if ($this->getSourceType() != self::SOURCE_TYPE_LOCAL) {
            throw new Exception('Rename supported for local files only.');
        }
        if ($relativeCurrentPath) {
            $newName = $this->getDirName() . DIRECTORY_SEPARATOR . $newName;
        }
        if (file_exists($newName)) {
            if ($newName == $this->getFullPath()) {
                return true;
            }
            if (is_dir($newName)) {
                throw new Exception('Dir "' . $newName . '" already exists');
            }
            if ($overwrite) {
                if (!unlink($newName)) {
                    throw new Exception('Can\'t overwrite "' . $newName . '"');
                }
            } else {
                throw new Exception('File "' . $newName . '" exists');
            }
        }
        self::createDirForFileRecursive($newName);
        if (!rename($this->getFullPath(), $newName)) {
            throw new Exception('Can\'t rename "' . $this->getFullPath() . '" to "' . $newName . '"');
        }
        $this->_initPathInfo($newName);
    }
    
    /**
     * @param string $algo Name of selected hashing algorithm (i.e. "sha1", "md5", "sha256", "haval160,4", etc..) 
     * @return string
     */
    public function getHash($algo = 'sha1')
    {
        if ($this->getSourceType() != self::SOURCE_TYPE_LOCAL) {
            throw new Exception('Hash calc supported for local files only.');
        }
        return hash_file($algo, $this->getFullPath());
    }

    /**
     * Получить MIME-тип файла
     * @return string
     */
    public function getFileMimeType()
    {
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $filename     = $this->getFullPath();
        $fileMimeType = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $fileMimeType;
    }


    /**
     * Получить MIME-тип файла по его разрешению
     * @return string
     */
    public function getFileMimeTypeByExt()
    {
        switch ($this->getExt()) {
            case 'jpg':
                $mime = 'image/jpeg';
                break;
            case 'jpeg':
            case 'png':
            case 'gif':
                $mime = 'image/' . $this->getExt();
                break;
            default:
                $mime = 'application/octet-stream';
        }
        return $mime;
    }



    
    /**
    * Размер файла
    * 
    * @param string $path
    * @return float|mixed
    */
    public static function calcSize($path)
    {
        $fsize = @filesize($path);
        if ($fsize === false) {
            return false;
        }
        /*if ($fsize < 0) {
            if (!$asString) {
                return false;
            }
            return sprintf("%u", @filesize($path));
        } elseif ($asString) {
            $fsize = (string)$fsize;
        }*/
        return self::_uint32($fsize);
    }

    /**
    * Преобразовать int32 в uint32
    * 
    * @param int $value
    * @return float
    */
    protected static function _uint32($value)
    {
        $value = (float)$value;
        if ($value < 0) {
            $value = (float)(PHP_INT_MAX*2) + $value + 2;
        }
        return (float)$value;
    }
    
    public static function createDirRecursive($path) 
    {
        if (is_dir($path)) {
            return;
        }
        $res = false;
        if ($path) {
            $res = mkdir($path, 0777, true);
        }
        if (!$res) {
            throw new Exception('Can\'t create folder "' . $path . '"');
        }
    }
    
    public static function createDirForFileRecursive($path)
    {
        return self::createDirRecursive(dirname($path));
    }
}
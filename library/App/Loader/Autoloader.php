<?php

namespace App\Loader;

class Autoloader
{
    const RULE_TYPE_PREFIX = 'prefix';
    
    const RULE_TYPE_POSTFIX = 'postfix';
    
    const RULE_TYPE_REGEX = 'regex';
    
    /**
     * @var Autoloader Singleton instance
     */
    protected static $_instance;

    protected static $_cacheIncludeFile = null;
    
    protected static $_cacheIncludeFileAppended = false;

    //protected static $_cacheFile = null;
    
    protected $_rules = array();

    private $classMap = array();
    
    /**
     * Retrieve singleton instance
     *
     * @return Autoloader
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * Registers instance with spl_autoload stack
     *
     * @return Autoloader
     */
    protected function __construct()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
        $this->_internalAutoloader = array($this, '_autoload');
    }

    /**
     * @return Autoloader
     */
    public function registerAutoload()
    {
        return self::getInstance();
    }

    /**
    * Добавить правила загрузки
    * 
    * В ini файле может выглядеть, например, так:
    * resources.autoloader.enabled = true
    * resources.autoloader.rules.Zend.rule = "Zend_"
    * resources.autoloader.rules.Zend.path = LIBRARY_PATH
    * resources.autoloader.rules.Zend.type = prefix
    * // resources.autoloader.rules.Zend.cache_to_file = true отключено
    * 
    * @param array $rules
    * @return Autoloader
    */
    public function addRules($rules)
    {
        foreach ($rules as $rule) {
            if (!$rule || !is_array($rule)) {
                continue;
            }
            $this->addRule($rule['rule'], $rule['path'], @$rule['type'] ?: self::RULE_TYPE_PREFIX, @$rule['cache_to_file'] ?: false);
        }
        return $this;
    }

    /**
     * Добавить правило загрузки
     *
     * @see Autoloader::addRules()
     *
     * @param string $rule
     * @param string $path
     * @param string $type
     * @throws App_Exception
     * @return Autoloader
     */
    public function addRule($rule, $path, $type = self::RULE_TYPE_PREFIX/*, $cacheToFile = false*/)
    {
        if ($path) {
            $path = realpath($path);
        }

        if (!$rule) {
            throw new App_Exception('Empty rule');
        }
        if (!in_array($type, array(self::RULE_TYPE_POSTFIX, self::RULE_TYPE_PREFIX, self::RULE_TYPE_REGEX))) {
            throw new App_Exception('Wrong type');
        }

        if ($type == self::RULE_TYPE_POSTFIX) {
            $length = -strlen($rule);
        } elseif ($type == self::RULE_TYPE_PREFIX) {
            $length = strlen($rule);
        } else {
            $length = 0;
        }

        $this->_rules[] = array('rule'   => $rule,
                                'path'   => $path,
                                'type'   => $type,
                                'length' => $length);
        return $this;
    }

    /**
     *
     *
     * @param array $classmapArray
     * @return Autoloader
     */
    public function addClassmap(array $classmapArray = array())
    {
        $this->classMap = array_merge($this->classMap, $classmapArray);
        return $this;
    }

    /**
     *
     *
     * @param array $classmapArray
     * @return Autoloader
     */
    public function setClassmap(array $classmapArray = array())
    {
        $this->classMap = $classmapArray;
        return $this;
    }

    /**
     * @param null $classname
     * @return Autoloader
     */
    public function clearClassmap($classname = null)
    {
        if (!$classname) {
            $this->classMap = array();
        } elseif (isset($this->classMap[$classname])) {
            unset($this->classMap[$classname]);
        }

        return $this;
    }

    public static function load($class)
    {
        return self::autoload($class);
    }

    /**
     * Загрузка класса
     *
     * @param       $class
     * @param array $rule
     * @return bool
     */
    private static function _load($class, array $rule = array())
    {
        $requiredFile = (isset($rule['path']) && !empty($rule['path']) ? ($rule['path'] . DIRECTORY_SEPARATOR) : '') . str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class) . '.php';

        if (!is_file($requiredFile)) {
            $found = false;
            $includePaths = explode(PATH_SEPARATOR, get_include_path());
            foreach ($includePaths as &$includePath) {
                $_requiredFile = realpath($includePath) . DIRECTORY_SEPARATOR . $requiredFile;
                if (is_file($_requiredFile)) {
                    $requiredFile = $_requiredFile;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                return false;
            }
        }

        require_once $requiredFile;
        return true;
    }
    
    /**
     * Autoload a class
     *
     * @param  string $class
     * @return bool
     */
    public static function autoload($class)
    {
        $self = self::getInstance();

        if (isset($self->classMap[$class])) {
            require_once $self->classMap[$class];
            return true;
        }

        $requiredFile = null;

        foreach ($self->_rules as &$rule) {
            switch ($rule['type']) {
                case self::RULE_TYPE_PREFIX:
                    if (substr($class, 0, $rule['length']) == $rule['rule']){
                        if (self::_load($class, $rule)) {
                            return true;
                        }
                    }
                    break;
                case self::RULE_TYPE_POSTFIX:
                    if (substr($class, $rule['length']) == $rule['rule']){
                        if (self::_load($class, $rule)) {
                            return true;
                        }
                    }
                    break;
                case self::RULE_TYPE_REGEX:
                    if (preg_match($rule['rule'], $class)) {
                        if (self::_load($class, $rule)) {
                            return true;
                        }
                    }
                    break;
            }
        }

        return self::_load($class);
    }
    
    public static function setCacheIncludeFile($filename)
    {
        self::$_cacheIncludeFile = $filename;
        
        if (is_file($filename)) {
            $file = file_get_contents($filename);
            if (strpos($file, '<?php') !== 0) {
                self::_clearIncludeFile();
                return;
            }
            set_error_handler(function() {
                self::_clearIncludeFile();
            }, E_ERROR);
            try {
                require_once $filename;
            } catch (Exception $ex) {
                self::_clearIncludeFile();
                throw $ex;
            }
            restore_error_handler();
        }
    }

    public static function getCacheIncludeFile()
    {
        return self::$_cacheIncludeFile;
    }

    /**
     * Append an include_once statement to the class file cache
     *
     * @param  string $incFile
     * @return void
     */
    protected static function _appendIncludeFile($incFile)
    {
        if (!self::getCacheIncludeFile()) {
            return;
        }
        
        $fileEmpty = false;
        if (!is_file(self::getCacheIncludeFile()) || filesize(self::getCacheIncludeFile()) < 6) {
            $fileEmpty = true;
        }
        
        if ($fileEmpty && self::$_cacheIncludeFileAppended) {
            self::setCacheIncludeFile('');
            return;
        }
        self::$_cacheIncludeFileAppended = true;
        
        $file = fopen(self::getCacheIncludeFile(), $fileEmpty ? 'w' : 'a');
        if ($fileEmpty) {
            fputs($file, "<?php\n");
        }
        fputs($file, "\ninclude_once '$incFile';");
        fclose($file);
    }
    
    /**
     * Clear the class file cache
     * 
     * @return void
     */
    protected static function _clearIncludeFile()
    {
        if (!self::getCacheIncludeFile()) {
            return;
        }
        file_put_contents(self::getCacheIncludeFile(), "<?php\n");
    }
}
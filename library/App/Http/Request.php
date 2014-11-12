<?php

namespace App\Http;

class Request
{
    /**
     * Scheme for http
     *
     */
    const SCHEME_HTTP  = 'http';

    /**
     * Scheme for https
     *
     */
    const SCHEME_HTTPS = 'https';

    private $params;
    private $rawBody;

    /**
     * _GET clone
     *
     * @var array
     */
    private $get = array();

    /**
     * _POST clone
     *
     * @var array
     */
    private $post = array();

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
    }

    /**
     * Return the parameter container responsible for query parameters or a single query parameter
     *
     * @param string|null           $name            Parameter name to retrieve, or null to get the whole container.
     * @param mixed|null            $default         Default value to use when the parameter is missing.
     * @return \Zend\Stdlib\ParametersInterface|mixed
     */
    public function getQuery($name = null, $default = null)
    {
        if ($name === null) {
            return $this->get;
        }

        return isset($this->get[$name]) ? $this->get[$name] : $default;
    }

    /**
     * @return bool
     */
    public function isPost()
    {
        return !empty($_POST);
    }

    public function getPost($name = null, $default = null)
    {
        if ($name === null) {
            return $_POST;
        }

        return isset($_POST[$name])&&!empty($_POST[$name]) ? $_POST[$name] : $default;
    }

    public function getEnv($name = null, $default = null)
    {
        if ($name === null) {
            return $_ENV;
        }

        return isset($_ENV[$name]) ? $_ENV[$name] : $default;
    }

    /**
     * Retrieve a member of the $_COOKIE superglobal
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a member of the $_SERVER superglobal
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Set a userland parameter
     *
     * Uses $key to set a userland parameter. If $key is an alias, the actual
     * key will be retrieved and used to set the parameter.
     *
     * @param mixed $key
     * @param mixed $value
     * @return \App\Http\Request
     */
    public function setParam($key, $value)
    {
        $this->params[(string)$key] = $value;
        return $this;
    }

    /**
     * Retrieve a parameter
     *
     * Retrieves a parameter from the instance. Priority is in the order of
     * userland parameters (see {@link setParam()}), $_GET, $_POST. If a
     * parameter matching the $key is not found, null is returned.
     *
     * If the $key is an alias, the actual key aliased will be used.
     *
     * @param mixed $key
     * @param mixed $default Default value to use if key not found
     * @return mixed
     */
    public function getParam($key = null, $default = null)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return $default;
    }

    /**
     * Set parameters
     *
     * Set one or more parameters. Parameters are set as userland parameters,
     * using the keys specified in the array.
     *
     * @param array $params
     * @return \App\Http\Request
     */
    public function setParams(array $params)
    {
        foreach ($params as $key => $value) {
            $this->setParam($key, $value);
        }
        return $this;
    }

    public function getParamAsCascade($key = null, $default = null)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        if (isset($this->post[$key])) {
            return $this->post[$key];
        }

        if (isset($this->get[$key])) {
            return $this->get[$key];
        }

        return $default;
    }
    public function getRequestUri()
    {
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            // IIS with Microsoft Rewrite Module
            $requestUri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            // IIS with ISAPI_Rewrite
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (
            // IIS7 with URL Rewrite: make sure we get the unencoded url (double slash problem)
            isset($_SERVER['IIS_WasUrlRewritten'])
            && $_SERVER['IIS_WasUrlRewritten'] == '1'
            && isset($_SERVER['UNENCODED_URL'])
            && $_SERVER['UNENCODED_URL'] != ''
        ) {
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // Http proxy reqs setup request uri with scheme and host [and port] + the url path, only use url path
            $schemeAndHttpHost = $this->getScheme() . '://' . $this->getHttpHost();
            if (strpos($requestUri, $schemeAndHttpHost) === 0) {
                $requestUri = substr($requestUri, strlen($schemeAndHttpHost));
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0, PHP as CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUri = '';
        }

        return $requestUri;
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return boolean
     */
    public function isXmlHttpRequest()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * Is this a Flash request?
     *
     * @return boolean
     */
    public function isFlashRequest()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }

    /**
     * Is https secure request
     *
     * @return boolean
     */
    public function isSecure()
    {
        return ($this->getScheme() === self::SCHEME_HTTPS);
    }

    /**
     * Return the raw body of the request, if present
     *
     * @return string|false Raw body, or false if not present
     */
    public function getRawBody()
    {
        if (null === $this->rawBody) {
            $body = file_get_contents('php://input');

            if (strlen(trim($body)) > 0) {
                $this->rawBody = $body;
            } else {
                $this->rawBody = false;
            }
        }
        return $this->rawBody;
    }

    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws \Zend\Http\Exception\InvalidArgumentException
     */
    public function getHeader($header)
    {
        if (empty($header)) {
            throw new \Zend\Http\Exception\InvalidArgumentException('An HTTP header name is required');
        }

        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (isset($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers[$header])) {
                return $headers[$header];
            }
            $header = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) == $header) {
                    return $value;
                }
            }
        }

        return false;
    }

    /**
     * Get the request URI scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->getServer('HTTPS') == 'on') ? self::SCHEME_HTTPS : self::SCHEME_HTTP;
    }

    /**
     * Get the HTTP host.
     *
     * "Host" ":" host [ ":" port ] ; Section 3.2.2
     * Note the HTTP Host header is not the same as the URI host.
     * It includes the port while the URI host doesn't.
     *
     * @return string
     */
    public function getHttpHost()
    {
        $host = $this->getServer('HTTP_HOST');
        if (!empty($host)) {
            return $host;
        }

        $scheme = $this->getScheme();
        $name   = $this->getServer('SERVER_NAME');
        $port   = $this->getServer('SERVER_PORT');

        if(null === $name) {
            return '';
        }
        elseif (($scheme == self::SCHEME_HTTP && $port == 80) || ($scheme == self::SCHEME_HTTPS && $port == 443)) {
            return $name;
        } else {
            return $name . ':' . $port;
        }
    }

    /**
     * Get the client's IP addres
     *
     * @param  boolean $checkProxy
     * @return string
     */
    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } elseif ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }

        return $ip;
    }
}

<?php

namespace App\Http;

use App\Http\Exception\ErrorException;
use App\Http\Header\Cookie;

class Response
{
    const STATUS_OK    = 'ok';

    const STATUS_ERROR = 'error';

    /**
     * Body content
     * @var array
     */
    protected $_body = null;

    /**
     * Exception stack
     * @var \Exception[]|array
     */
    protected $_exceptions = array();

    /**
     * Array of headers. Each header is an array with keys 'name' and 'value'
     * @var array
     */
    protected $_headers = array();

    /**
     * Array of raw headers. Each header is a single string, the entire header to emit
     * @var array
     */
    protected $_headersRaw = array();

    /**
     * HTTP response code to use in headers
     * @var int
     */
    protected $_httpResponseCode = 200;

    /**
     * Flag; is this response a redirect?
     * @var boolean
     */
    protected $_isRedirect = false;

    /**
     * Whether or not to render exceptions; off by default
     * @var boolean
     */
    protected $_renderExceptions = false;

    /**
     * Flag; if true, when header operations are called after headers have been
     * sent, an exception will be raised; otherwise, processing will continue
     * as normal. Defaults to true.
     *
     * @see canSendHeaders()
     * @var boolean
     */
    public $headersSentThrowsException = true;

    /**
     * Статус операции (не путать с Response Code) используется в ответе для Ajax
     *
     * @see http://github.com/esteit/edu/js/ajax_response.md
     * @var string
     */
    public $status = self::STATUS_OK;

    /**
     * Расширенный статус операции ['notice', 'warning' etc] используется в ответе для Ajax
     *
     * @see http://github.com/esteit/edu/js/ajax_response.md
     * @var string
     */
    private $statusExtended;

    /**
     * @param string $status
     * @return \App_Controller_Response_Abstract
     */
    public function setStatus($status)
    {
        switch (strtolower($status)) {
            case self::STATUS_OK:
                $this->status = self::STATUS_OK;
                break;
            default:
                $this->status = self::STATUS_ERROR;
        }

        return $this;
    }

    /**
     * Получить значение статуса
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isStatusError()
    {
        return $this->getStatus() == self::STATUS_ERROR;
    }

    /**
     * @return bool
     */
    public function isStatusOk()
    {
        return $this->getStatus() == self::STATUS_OK;
    }

    /**
     * Установить статус
     *
     * @param bool $isError
     * @return $this
     */
    public function setIsError($isError = true)
    {
        $this->setStatus((bool)$isError ?  self::STATUS_ERROR : self::STATUS_OK);
        return $this;
    }

    /**
     * Установить расширенный статус
     *
     * @param string $statusExtended
     * @return $this
     */
    public function setStatusExtended($statusExtended)
    {
        $this->statusExtended = (string)$statusExtended;
        return $this;
    }

    /**
     * Получить значение расширенного статуса
     *
     * @return string
     */
    public function getStatusExtended()
    {
        return $this->statusExtended;
    }

    /**
     * Set a header
     *
     * If $replace is true, replaces any headers already defined with that
     * $name.
     *
     * @param string $name
     * @param string $value
     * @param boolean $replace
     * @return $this
     */
    public function setHeader($name, $value, $replace = false)
    {
        $this->canSendHeaders(true);
        $name  = $this->_normalizeHeader($name);
        $value = (string) $value;

        if ($replace) {
            foreach ($this->_headers as $key => $header) {
                if ($name == $header['name']) {
                    unset($this->_headers[$key]);
                }
            }
        }

        $this->_headers[] = array(
            'name'    => $name,
            'value'   => $value,
            'replace' => $replace
        );

        return $this;
    }

    /**
     * Set redirect URL
     *
     * Sets Location header and response code. Forces replacement of any prior
     * redirects.
     *
     * @param string $url
     * @param int $code
     * @return Zend_Controller_Response_Abstract
     */
    public function setRedirect($url, $code = 301)
    {
        $this->canSendHeaders(true);
        $this->setHeader('Location', $url, true)
            ->setHttpResponseCode($code);

        $this->_isRedirect = true;
        return $this;
    }

    /**
     * Is this a redirect?
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->_isRedirect;
    }

    /**
     * Return array of headers; see {@link $_headers} for format
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * Clear headers
     *
     * @return $this
     */
    public function clearHeaders()
    {
        $this->_headers = array();
        return $this;
    }

    /**
     * Clears the specified HTTP header
     *
     * @param  string $name
     * @return Zend_Controller_Response_Abstract
     */
    public function clearHeader($name)
    {
        if (!count($this->_headers)) {
            return $this;
        }

        foreach ($this->_headers as $index => $header) {
            if ($name == $header['name']) {
                unset($this->_headers[$index]);
            }
        }

        return $this;
    }

    /**
     * Set raw HTTP header
     *
     * Allows setting non key => value headers, such as status codes
     *
     * @param string $value
     * @return Zend_Controller_Response_Abstract
     */
    public function setRawHeader($value)
    {
        $this->canSendHeaders(true);
        if ('Location' == substr($value, 0, 8)) {
            $this->_isRedirect = true;
        }
        $this->_headersRaw[] = (string) $value;
        return $this;
    }

    /**
     * Retrieve all {@link setRawHeader() raw HTTP headers}
     *
     * @return array
     */
    public function getRawHeaders()
    {
        return $this->_headersRaw;
    }

    /**
     * Clear all {@link setRawHeader() raw HTTP headers}
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function clearRawHeaders()
    {
        $this->_headersRaw = array();
        return $this;
    }

    /**
     * Clears the specified raw HTTP header
     *
     * @param  string $headerRaw
     * @return Zend_Controller_Response_Abstract
     */
    public function clearRawHeader($headerRaw)
    {
        if (! count($this->_headersRaw)) {
            return $this;
        }

        $key = array_search($headerRaw, $this->_headersRaw);
        if ($key !== false) {
            unset($this->_headersRaw[$key]);
        }

        return $this;
    }

    /**
     * Clear all headers, normal and raw
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function clearAllHeaders()
    {
        return $this->clearHeaders()
            ->clearRawHeaders();
    }

    /**
     * Set HTTP response code to use with headers
     *
     * @param int $code
     * @throws Exception\ErrorException
     * @return $this
     */
    public function setHttpResponseCode($code)
    {
        if (!is_int($code) || (100 > $code) || (599 < $code)) {
            throw new ErrorException('Invalid HTTP response code');
        }

        if ((300 <= $code) && (307 >= $code)) {
            $this->_isRedirect = true;
        } else {
            $this->_isRedirect = false;
        }

        $this->_httpResponseCode = $code;
        return $this;
    }

    /**
     * Retrieve HTTP response code
     *
     * @return int
     */
    public function getHttpResponseCode()
    {
        return $this->_httpResponseCode;
    }

    /**
     * Can we send headers?
     *
     * @param boolean $throw Whether or not to throw an exception if headers have been sent; defaults to false
     * @throws Exception\ErrorException
     * @return boolean
     */
    public function canSendHeaders($throw = false)
    {
        $ok = headers_sent($file, $line);
        if ($ok && $throw && $this->headersSentThrowsException) {
            throw new ErrorException('Cannot send headers; headers already sent in ' . $file . ', line ' . $line);
        }

        return !$ok;
    }

    /**
     * Send all headers
     *
     * Sends any headers specified. If an {@link setHttpResponseCode() HTTP response code}
     * has been specified, it is sent with the first header.
     *
     * @return Zend_Controller_Response_Abstract
     */
    public function sendHeaders()
    {
        // Only check if we can send headers if we have headers to send
        if (count($this->_headersRaw) || count($this->_headers) || (200 != $this->_httpResponseCode)) {
            $this->canSendHeaders(true);
        } elseif (200 == $this->_httpResponseCode) {
            // Haven't changed the response code, and we have no headers
            return $this;
        }

        $httpCodeSent = false;

        foreach ($this->_headersRaw as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header, true, $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header);
            }
        }

        foreach ($this->_headers as $header) {
            if (!$httpCodeSent && $this->_httpResponseCode) {
                header($header['name'] . ': ' . $header['value'], $header['replace'], $this->_httpResponseCode);
                $httpCodeSent = true;
            } else {
                header($header['name'] . ': ' . $header['value'], $header['replace']);
            }
        }

        if (!$httpCodeSent) {
            header('HTTP/1.1 ' . $this->_httpResponseCode);
            $httpCodeSent = true;
        }

        return $this;
    }

    /**
     * Set body content
     *
     * @param string $content
     * @return $this
     */
    public function setBody($content)
    {
        $this->_body = $content;

        return $this;
    }

    /**
     * Clear body array
     *
     * @return $this
     */
    public function clearBody()
    {
        $this->_body = null;
        return $this;
    }

    /**
     * Return the body content
     *
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Echo the body segments
     *
     * @return void
     */
    public function outputBody()
    {
        echo (string)$this->getBody();
    }

    /**
     * Register an exception with the response
     *
     * @param Exception $e
     * @return Zend_Controller_Response_Abstract
     */
    public function setException(Exception $e)
    {
        $this->_exceptions[] = $e;
        return $this;
    }

    /**
     * Retrieve the exception stack
     *
     * @return array|\Exception[]
     */
    public function getException()
    {
        return $this->_exceptions;
    }

    /**
     * Has an exception been registered with the response?
     *
     * @return boolean
     */
    public function isException()
    {
        return !empty($this->_exceptions);
    }

    /**
     * Does the response object contain an exception of a given type?
     *
     * @param  string $type
     * @return boolean
     */
    public function hasExceptionOfType($type)
    {
        foreach ($this->_exceptions as $e) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the response object contain an exception with a given message?
     *
     * @param  string $message
     * @return boolean
     */
    public function hasExceptionOfMessage($message)
    {
        /** @var $e \Exception */
        foreach ($this->_exceptions as $e) {
            if ($message == $e->getMessage()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the response object contain an exception with a given code?
     *
     * @param  int $code
     * @return boolean
     */
    public function hasExceptionOfCode($code)
    {
        $code = (int) $code;
        foreach ($this->_exceptions as $e) {
            if ($code == $e->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve all exceptions of a given type
     *
     * @param  string $type
     * @return false|array
     */
    public function getExceptionByType($type)
    {
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($e instanceof $type) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Retrieve all exceptions of a given message
     *
     * @param  string $message
     * @return false|array
     */
    public function getExceptionByMessage($message)
    {
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($message == $e->getMessage()) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Retrieve all exceptions of a given code
     *
     * @param mixed $code
     * @return mixed
     */
    public function getExceptionByCode($code)
    {
        $code       = (int) $code;
        $exceptions = array();
        foreach ($this->_exceptions as $e) {
            if ($code == $e->getCode()) {
                $exceptions[] = $e;
            }
        }

        if (empty($exceptions)) {
            $exceptions = false;
        }

        return $exceptions;
    }

    /**
     * Whether or not to render exceptions (off by default)
     *
     * If called with no arguments or a null argument, returns the value of the
     * flag; otherwise, sets it and returns the current value.
     *
     * @param boolean $flag Optional
     * @return boolean
     */
    public function renderExceptions($flag = null)
    {
        if (null !== $flag) {
            $this->_renderExceptions = $flag ? true : false;
        }

        return $this->_renderExceptions;
    }

    /**
     * Send the response, including all headers, rendering exceptions if so
     * requested.
     *
     * @return void
     */
    public function sendResponse()
    {
        $this->sendHeaders();

        if ($this->isException() && $this->renderExceptions()) {
            $exceptions = '';
            foreach ($this->getException() as $e) {
                $exceptions .= $e->__toString() . "\n";
            }
            echo $exceptions;
            return;
        }

        $this->outputBody();
    }

    /**
     * Add Set-Cookie header to response
     *
     * @param Cookie $cookie
     * @return $this
     */
    public function setCookie(Cookie $cookie)
    {
        $this->setHeader('Set-Cookie', $cookie->toHeaderString());

        return $this;
    }

    /**
     * Normalize a header name
     *
     * Normalizes a header name to X-Capitalized-Names
     *
     * @param  string $name
     * @return string
     */
    protected function _normalizeHeader($name)
    {
        $filtered = str_replace(array('-', '_'), ' ', (string) $name);
        $filtered = ucwords(strtolower($filtered));
        $filtered = str_replace(' ', '-', $filtered);
        return $filtered;
    }

    /**
     * Magic __toString functionality
     *
     * Proxies to {@link sendResponse()} and returns response value as string
     * using output buffering.
     *
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->sendResponse();
        return ob_get_clean();
    }
}

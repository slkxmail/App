<?php

namespace App\Mvc\Block\Response;

abstract class AbstractResponse
{
    const STATUS_OK = 'ok';

    const STATUS_ERROR = 'error';

    const DATA_TYPE_JSON = 'json';

    const DATA_TYPE_JSONP = 'jsonp';

    const DATA_TYPE_HTML = 'html';

    const DATA_TYPE_XML = 'xml';

    const DATA_TYPE_SCRIPT = 'script';

    const DATA_TYPE_TEXT = 'text';

    const FILE_TYPE_CSS = 'css';

    const FILE_TYPE_JS = 'js';

    /**
     * @var array
     */
    private $fileArray = array();

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var string
     */
    private $status = self::STATUS_OK;

    /**
     * @var string
     */
    private $statusExtended = '';

    /**
     * @var array
     */
    private $data = array();

    /**
     * @var bool
     */
    private $isReload = false;

    /**
     * @var string
     */
    private $redirectUrl = '';

    /**
     * @var string
     */
    private $forwardUrl = '';

    /**
     * @var string
     */
    private $message = '';

    /**
     * @var array
     */
    private $messageArray = array();

    /**
     * @var string
     */
    private $error = '';

    /**
     * @var array
     */
    private $errorArray = array();

    /**
     * @var string
     */
    private $dataType = self::DATA_TYPE_JSON;

    /**
     * @param string $status
     * @return \App_Controller_Response_Abstract
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return App_Controller_Response_Abstract
     */
    public function setIsError($isError = true)
    {
        $this->setStatus($isError ?  self::STATUS_ERROR : self::STATUS_OK);
        return $this;
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
     * @param string $statusExtended
     * @return \App_Controller_Response_Abstract
     */
    public function setStatusExtended($statusExtended)
    {
        $this->statusExtended = $statusExtended;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatusExtended()
    {
        return $this->statusExtended;
    }

    /**
     * @param $error
     *
     * @return App_Controller_Response_Abstract
     */
    public function setError($error)
    {
        $this->error = $error;
        $this->setIsError(true);

        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param array $errorArray
     * @return \App_Controller_Response_Abstract
     */
    public function setErrorArray(array $errorArray)
    {
        $this->errorArray = $errorArray;
        $this->setIsError(true);

        return $this;
    }

    /**
     * @return array
     */
    public function getErrorArray()
    {
        return $this->errorArray;
    }

    /**
     * Добавить ошибку
     *
     * @param string $error
     * @param string|null $code
     *
     * @return \App_Controller_Response_Abstract
     */
    public function addError($error, $code = null)
    {
        $this->errorArray[$code] = $error;
        return $this;
    }

    /**
     * @param string $message
     * @return \App_Controller_Response_Abstract
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param array $messageArray
     * @return \App_Controller_Response_Abstract
     */
    public function setMessageArray(array $messageArray)
    {
        $this->messageArray = $messageArray;
        return $this;
    }

    /**
     * @return array
     */
    public function getMessageArray()
    {
        return $this->messageArray;
    }

    /**
     * Добавить сообщение
     *
     * @param string $message
     *
     * @return \App_Controller_Response_Abstract
     */
    public function addMessage($message)
    {
        $this->messageArray[] = $message;
        return $this;
    }

    /**
     * @param string $redirect
     * @return \App_Controller_Response_Abstract
     */
    public function setRedirectUrl($redirect)
    {
        $this->redirectUrl = $redirect;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $forwardUrl
     * @return \App_Controller_Response_Abstract
     */
    public function setForwardUrl($forwardUrl)
    {
        $this->forwardUrl = $forwardUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getForwardUrl()
    {
        return $this->forwardUrl;
    }

    /**
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param $url
     * @param string $type
     *
     * @return App_Controller_Response_Abstract
     */
    public function addFile($url, $type = self::FILE_TYPE_CSS)
    {
        $this->fileArray[$type][] = $url;
        return $this;
    }

    /**
     * @param array $fileArray
     * @return $this
     */
    public function setFileArray($fileArray)
    {
        $this->fileArray = $fileArray;
        return $this;
    }

    /**
     * @return array
     */
    public function getFileArray()
    {
        return $this->fileArray;
    }

    /**
     * @param boolean $isReload
     * @return \App_Controller_Response_Abstract
     */
    public function setIsReload($isReload)
    {
        $this->isReload = $isReload;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsReload()
    {
        return $this->isReload;
    }

    /**
     * @param array $data
     * @return \App_Controller_Response_Abstract
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Добавить параметр
     *
     * @param string $name - имя параметра
     * @param mixed $value - значение параметра
     *
     * @return \App_Controller_Response_Abstract
     */
    public function addDataParam($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Получить параметр
     *
     * @param string $name - имя параметра
     * @param mixed $default - значение по-умолчанию
     *
     * @return mixed|null
     */
    public function getDataParamByName($name, $default = null)
    {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    /**
     * @param string $content
     * @return \App_Controller_Response_Abstract
     */
    public function setContent($content)
    {
        $this->content = (string) $content;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Add Set-Cookie header to response
     *
     * @param App_Http_Cookie $cookie
     * @return App_Controller_Response_Http
     */
    public function setCookie(App_Http_Cookie $cookie)
    {
        $this->setHeader('Set-Cookie', $cookie->toHeaderString());

        return $this;
    }

}
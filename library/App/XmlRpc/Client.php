<?php
namespace App\XmlRpc;

class Client extends \Zend\XmlRpc\Client
{
    /**
     * @deprecated
     * @todo delete
     */
    const XMLRPC_QUERY_NAME_KEY     = 'query_name';
    const XMLRPC_QUERY_DEFAULT_NAME = 'za-kolbaskoi';
                                               
    const XMLRPC_REQUEST_FAST = 'request_fast';
    const XMLRPC_REQUEST_SLOW = 'request_slow';

    protected static $_queueAdapter = null;
    
    protected static $_fastRequestTimeout = 10;
    protected static $_slowRequestTimeout = 120;

    /**
     * Create a new XML-RPC client to a remote server
     *
     * @param  string $server      Full address of the XML-RPC service
     *                             (e.g. http://time.xmlrpc.com/RPC2)
     * @param  \Zend\Http\Client $httpClient HTTP Client to use for requests
     * @return void
     */
    public function __construct($server, \Zend\Http\Client $httpClient = null)
    {
        parent::__construct($server, $httpClient);
        
        $this->setSkipSystemLookup();
    }
    
    /**
     * Send an XML-RPC request to the service (for a specific method)
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @param  string $requestType Type of the request \App\XmlRpc\Client::XMLRPC_REQUEST_*
     * @return mixed
     * @throws Zend_XmlRpc_Client_FaultException
     */
    public function call($method, $params=array(), $requestType = \App\XmlRpc\Client::XMLRPC_REQUEST_FAST)
    {
        $timeout = self::$_fastRequestTimeout;
        if ($requestType == self::XMLRPC_REQUEST_SLOW) {
            $timeout = self::$_slowRequestTimeout;            
        }
        
        $this->getHttpClient()->setOptions(array('timeout' => $timeout));
        
        return parent::call($method, $params);
    }
    
    /**
     * Добавляем запрос в очередь.
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @param  string Name of the queue
     * @return boolean Встал ли запрос в очередь
     */
    public function queue($method, $params=array(), $queueName = self::XMLRPC_QUERY_DEFAULT_NAME)
    {
        try {
            return self::getQueueAdapter()->addRequest($this->serverAddress, $queueName, $method, $params);
        } catch (Exception $ex) { }
        
        return false;
    }

    /**
     * Установить адаптер очереди
     *
     * @param \App\XmlRpc\Queue\Adapter\AdapterInterface $adapter
     * @return void
     */
    public static function setQueueAdapter(\App\XmlRpc\Queue\Adapter\AdapterInterface $adapter = null)
    {
        if (!$adapter) {
            $adapter = new \App\XmlRpc\Queue\Adapter\Mysql();
        }
        
        self::$_queueAdapter = $adapter;        
    }

    /**
     *
     * @return \App\XmlRpc\Queue\Adapter\AdapterInterface
     */
    public static function getQueueAdapter()
    {
        if (!self::$_queueAdapter) {
            self::setQueueAdapter();
        }
        
        return self::$_queueAdapter;
    }

    public static function getQueryNameFromParams($params)
    {
        if (is_array($params) && array_key_exists(self::XMLRPC_QUERY_NAME_KEY, $params)) {
            return $params[self::XMLRPC_QUERY_NAME_KEY];
        } else {
            return self::XMLRPC_QUERY_DEFAULT_NAME;
        }
    }

    /**
    * Установить тайм-аут для быстрых запросов
    * 
    * @param int $seconds
    */
    public static function setFastRequestTimeout($seconds)
    {
        self::$_fastRequestTimeout = $seconds;
    }
    
    /**
    * Установить тайм-аут для медленных запросов
    * 
    * @param int $seconds
    */
    public static function setSlowRequestTimeout($seconds)
    {
        self::$_slowRequestTimeout = $seconds;
    }
    
    /**                              
    * Тайм-аут для быстрых запросов  
    * 
    * @return int Тайм-аут в секундах
    */
    public static function getFastRequestTimeout()
    {
        return self::$_fastRequestTimeout;
    }
    
    /**                              
    * Тайм-аут для медленных запросов  
    * 
    * @return int Тайм-аут в секундах
    */
    public static function getSlowRequestTimeout()
    {
        return self::$_slowRequestTimeout;
    }
    
    /*
    public function sendFailedRequestToEmail($response) {
         try {
            $mail = new Zend_Mail('UTF-8');
            $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
            $mail->setFrom('nobody@zappstore.ru', 'Склад');                
            $mail->setBodyText("Ошибка" . print_r($response, true) . print_r($response->getFault(), true));
            $mail->addTo('alexander.perov@inamerica.ru');
            $mail->setSubject("Ошибка запроса");
            $mail->setType(Zend_Mime::MULTIPART_ALTERNATIVE);                
            $mail->send();
        } catch (Exception $e) {}
    }
    
    public function doRequest($request, $response = null)
    {
        // устанавливаем задачу
        $xml = $request->__toString();
        //$method = $this->_lastMethod;
        $method = $request->getMethod();
        $url = $this->getHttpClient()->getUri();
        if($url === null) {
            $url = $this->_serverAddress;
        }

        $queueId = 0;
        if ($method!='system.methodSignature') {
            $taskId = RequestTaskModel::getInstance()->setTask($url, $method, $xml);
            $queueId = RequestQueueModel::getInstance()->addRequestQueue($url, $method, $xml);
        }
        
        try {
            parent::doRequest($request, $response);
            if ($method!='system.methodSignature') {
                if ($this->_lastResponse->isFault()) {
                    $this->sendFailedRequestToEmail($this->_lastResponse);
                    RequestTaskModel::getInstance()->setTaskFail($taskId, $this->_lastResponse);
                } else {
                    RequestTaskModel::getInstance()->setTaskComplite($taskId, $this->_lastResponse);
                    if ($queueId > 0) {
                        RequestQueueModel::getInstance()->deleteRequestQueue($queueId);
                    }
                }
            }
            
        } catch (Exception $e) {
            if ($method != 'system.methodSignature') {
                $this->sendFailedRequestToEmail($this->_lastResponse);
                RequestTaskModel::getInstance()->setTaskFail($taskId, $this->_lastResponse);
            }
            throw $e;
        }
    }*/
}

<?php

namespace App\XmlRpc\Client;

/**
 * Description of BlackHole
 *
 * @author Mad
 */
class BlackHole extends \App\XmlRpc\Client
{
    public function __construct($server = '', Zend_Http_Client $httpClient = null)
    { }
    
    public function call($method, $params = array(), $requestType = App_XmlRpc_Client::XMLRPC_REQUEST_FAST)
    {
        return array();
    }
    
    public function queue($method, $params = array(), $queueName = App_XmlRpc_Client::XMLRPC_QUERY_DEFAULT_NAME)
    {
        return 1;
    }
}

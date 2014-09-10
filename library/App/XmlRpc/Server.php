<?php

namespace App\XmlRpc;

use App\ServiceManager\ServiceManager;
use App\Http\Request;
use App\Http\Response;
use App\Mvc\View;
#use App\XmlRpc\Service\BaseService;
#use Model\ProjectModel;

class Server
{
    private $config;

    private $serviceManager;

    protected $request;
    protected $response;
    protected $project;

    /**
     * Сервер
     * @var \Zend\XmlRpc\Server
     */
    private $server;

    /**
     * Список не логируемых методов
     *
     * @var array of string
     */
    protected static $_notLoggingMethods = array('system.methodsignature');

    /**
     * Список методов c нелогируемыми запросами
     *
     * @var array of string
     */
    protected static $_notLoggingRequestMethods = array();
    /**
     * Список методов с нелогируемыми ответами
     *
     * @var array of string
     */
    protected static $_notLoggingResponseMethods = array();

    protected static $_emptyXmlRequest;
    protected static $_emptyXmlResponse;


    public function __construct($config, ServiceManager $serviceManager)
    {
        $this->config         = $config;
        $this->serviceManager = $serviceManager;
        $this->request        = $serviceManager->get('Request');
        $this->response       = $serviceManager->get('Response');

        $this->server         = new \Zend\XmlRpc\Server();
        $this->server->setReturnResponse(true);

        if (array_key_exists('service', $config) && is_array($config['service']) && !empty ($config['service'])) {
            foreach ($config['service'] as $k => $v) {
                try {
                $this->server->setClass($k, $v);
                //$this->server->setClass('App\XmlRpc\Service\Cargoitem', 'cargoitem');
                } catch (\Exception $e) {
                    print_r($e->getMessage());
                    die;
                }
            }
        }
    }

    /**
     * Get the request object
     *
     * @return \App\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response object
     *
     * @return \App\Http\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get the response object
     *
     * @ return \Model\Entity\ProjectEntity|mixed
     * @return mixed
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return \App\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Static method for quick and easy initialization of the Application.
     *
     * If you use this init() method, you cannot specify a service with the
     * name of 'ApplicationConfig' in your service manager config. This name is
     * reserved to hold the array from application.config.php.
     *
     * @param array $configuration
     * @throws \App\Exception\InvalidArgumentException
     * @return \App\Mvc\Application
     */
    public static function init($configuration = array())
    {
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
        $serviceManager = new ServiceManager(new \Zend\ServiceManager\Config($smConfig));

        return $serviceManager->get('application');
    }

    public function run()
    {
/*
        $data = array(
            'project_id' => 'WRHS',
            'service' => 'cargoitem',
            'method' => 'import',
            'request' => ''
        );

        $logAddResult = \Model\LogXmlrpcServerModel::getInstance()->add($data);
print_r($logAddResult->getErrors());
        die;
        */
        $logAddResult = null;
        $method = null;
        ob_start();
        $request = $this->getRequest();
        try {
            $xmlRequest = $request->getRawBody();
            if (empty($xmlRequest)) {
                $xmlRequest = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<methodCall><methodName>cargoitem.import</methodName><params><param><value><struct><member><name>item_fid</name><value><string>EBTD_P654326</string></value></member><member><name>source_id</name><value><string>EBTD</string></value></member><member><name>warehouse_code</name><value><string>usa</string></value></member><member><name>country_code</name><value><string>RU</string></value></member><member><name>delivery_type</name><value><string>SPSR</string></value></member><member><name>weight</name><value><int>11386</int></value></member><member><name>_cargo_item_declaration</name><value><array><data><value><struct><member><name>name</name><value><string>Test t-short</string></value></member><member><name>article</name><value><string>123312a</string></value></member><member><name>color</name><value><string>white</string></value></member><member><name>size</name><value><string>xxx-large</string></value></member><member><name>count</name><value><int>2</int></value></member><member><name>weight</name><value><int>11386</int></value></member><member><name>shop</name><value><string>amazon.com</string></value></member><member><name>url</name><value><string>http://amazon.com/goods/1?id2</string></value></member><member><name>price</name><value><double>123.40000000000001</double></value></member><member><name>commission</name><value><double>12.4</double></value></member><member><name>means_of_payment</name><value><string>visa*342</string></value></member><member><name>category</name><value><string>cloting</string></value></member><member><name>descr</name><value><string>Simple t-short</string></value></member></struct></value><value><struct><member><name>name</name><value><string>Test t-short 2</string></value></member><member><name>article</name><value><string>123313b</string></value></member><member><name>color</name><value><string>white</string></value></member><member><name>size</name><value><string>xxx-large</string></value></member><member><name>count</name><value><int>2</int></value></member><member><name>weight</name><value><int>11386</int></value></member><member><name>shop</name><value><string>amazon.com</string></value></member><member><name>url</name><value><string>http://amazon.com/goods/1?id2</string></value></member><member><name>price</name><value><double>123.40000000000001</double></value></member><member><name>commission</name><value><double>12.4</double></value></member><member><name>means_of_payment</name><value><string>visa*342</string></value></member><member><name>category</name><value><string>cloting</string></value></member><member><name>descr</name><value><string>Simple t-short</string></value></member></struct></value></data></array></value></member></struct></value></param></params></methodCall>';
            }
            $parts = null;
            if (preg_match("#\<methodName\>\s*([a-z0-9_]+)\.([a-z0-9_]+)\s*\<\/methodName\>#is", $xmlRequest, $parts)) {
                $method = @$parts[1] . '.' . @$parts[2];
            }

            if (strtolower($method) == 'system.fake') { // don't execute fake request
                throw new \Exception('Fake request');
            }

            $key = $this->getRequest()->getQuery('key');
            $project = \Model\ProjectModel::getInstance()->getByKey($key);

            if ($project->exists()) {
                BaseService::setProject($project);
            }

            $logRequest = !in_array(strtolower($method), self::$_notLoggingMethods);
            if ($logRequest && $method) {
                $data = array(
                    'project_id' => $project->getId(),
                    'service' => $parts[1],
                    'method' => $parts[2],
                    'request' => !in_array(strtolower($method), self::$_notLoggingRequestMethods) ?
                        $xmlRequest : self::$_emptyXmlRequest
                );

                $logAddResult = \Model\LogXmlrpcServerModel::getInstance()->add($data);
                //$a = $logAddResult instanceof \Model\Result\Result;
            }
            $response = $this->server->handle();

        } catch (\Exception $ex) {
            $response = (string)$this->server->fault('Server error', 1500);
            echo "\n\nEXCEPTION: ". $ex->getMessage() . "\n";
            echo $ex->getTraceAsString() . "\n\n";
        }
        $output = ob_get_contents();
        ob_end_clean();
        ob_start();

        try {
            if ($logAddResult instanceof \Model\Result\Result && $logAddResult->getResult()) {
                $logId = $logAddResult->getResult();
                $methparts = explode('.', $method);
                $msec = round((microtime(true) - $this->getTimeStart()) * 1000);

                $updateCond = \Model\LogXmlrpcServerModel::getInstance()->getCond()->where(array('id' => $logId));

                $logXmlrpcServerArray = array(
                    //'response' => !in_array(strtolower($method), self::$_notLoggingResponseMethods) ? $response : self::$_emptyXmlResponse,
                    'response' => $response,
                    'output' => $output,
                    'work_msec' => $msec);
                if ($msec > 1000) {
                    //\Model\LogXmlrpcServerModel::getInstance()->reconnect(); нужен реконект
                }
                $logUpdResult = \Model\LogXmlrpcServerModel::getInstance()->update($logXmlrpcServerArray, $updateCond);
                if($logUpdResult->isError()){
                    echo "\n\nLogXmlrpcServer update failed\n";
                    echo $logUpdResult->getErrors(true)->toString() . "\n\n";
                    echo "Output:\n" . $output;
                }
            } else {
                echo "\n\nLogXmlrpcServer add failed\n";
                print_r($logAddResult, true);
                //echo $logAddResult->getErrors(true)->toString() . "\n\n";
                echo "Output:\n" . $output;
            }
        } catch (\Exception $e) {
            echo "\n\n" . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n";
        }

        $output2 = ob_get_contents();
/*
        if ($output2) {
            $file = Zend_Registry::get('dir')->tmp . '/front_services_exception.txt';
            @file_put_contents($file, $output2);
            @chmod($file, 0664);
            //App_Logger_Db::log($output2, Zend_Log::ERR);
        }
*/
        ob_end_clean();

        // Пишем в логи для xml-rpc сервера
        echo $response;
    }

    public function getTimeStart()
    {
        if (!array_key_exists('time_start', $GLOBALS)) {
            $GLOBALS['time_start'] = time();
        }
        return $GLOBALS['time_start'];
    }
}
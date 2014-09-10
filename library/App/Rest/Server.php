<?php
namespace App\Rest;

use App\ServiceManager\ServiceManager;
use App\Http\Response;

class Server{

    private $config;
    private $serviceManager;

//    private $server = null;

    public function __construct($config, ServiceManager $serviceManager)
    {
        $this->config         = $config;
        $this->serviceManager = $serviceManager;
        $this->request        = $serviceManager->get('Request');
        $this->response        = $serviceManager->get('Response');


        //$request_method = strtolower($_SERVER['REQUEST_METHOD']);
        //$data = array();
        /*
        echo '<pre>';
        $r = new \App\Http\Request();
        print_r($r->getRequestUri());
        print_r($r);
        print_r($_SERVER);
        //print_r($this->request);
        */
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
        // Роутим
       $params = $this->route();

        if ($params === false) {
            $params = array(
                'controller' => 'error',
                'action'     => '404'
            );
        }

        $this->getRequest()->setParams($params);

        // Диспетчим данные
        $this->dispatch($this->getRequest(), $this->getResponse());

        $body = $this->getResponse()->getBody();
        if (!is_array($body)) {
            $body = array($body);
        }

        return @$_GET['callback'] . "(" .json_encode($body) . ")";
    }

    /**
     * @return array|bool
     */
    protected function route()
    {
        if (!isset($this->config['routes'])) {
            return false;
        }

        $routes = $this->config['routes'];
        list($path) = explode('?', $this->getRequest()->getRequestUri(), 2);

        $path = trim(urldecode($path), '/ ');

        $return = false;

        foreach ($routes as $routeName => $routeParams) {
            $regex = '#^' . trim($routeParams['route'], ' \\\/') . '$#i';
            $res   = preg_match($regex, $path, $values);

            if ($res === 0) {
                continue;
            }

            $map      = $routeParams['map'];
            $defaults = $routeParams['defaults'];

            // array_filter_key()? Why isn't this in a standard PHP function set yet? :)
            // Этключено для поддержки ассоциативных имен в регекспах
            /*foreach ($values as $i => $value) {
                if (!is_int($i) || $i === 0) {
                    unset($values[$i]);
                }
            }*/

            $values   = $this->_getMappedValues($map, $values);
            $defaults = $this->_getMappedValues($map, $defaults, false, true);
            $return   = $values + $defaults;

            $return['_route_name'] = $routeName;

            if (!isset($routeParams['block'])) {
                $return['block'] = $routeName;
            } else {
                $return['block'] = $routeParams['block'];
            }

            if (!isset($routeParams['layout'])) {
                $return['layout'] = 'default';
            } else {
                $return['layout'] = $routeParams['layout'];
            }
            break;
        }
        return $return;
    }

    public function dispatch(Request $request, Response $response)
    {
        $controllerParam = $request->getParam('controller', 'Error');
        $actionParam     = $request->getParam('action', 'error404');

        $controllerClass = 'Application\\Controller\\' . implode('', array_map('ucfirst', explode('-', $controllerParam))) . 'Controller';
        $actionMethod    = implode('', array_map('ucfirst', explode('-', $actionParam)))     . 'Action';
        $actionMethod    = strtolower(substr($actionMethod, 0, 1)) . substr($actionMethod, 1);

        if (!class_exists($controllerClass, true)) {
            $controllerClass = 'Application\\Controller\\ErrorController';
            $actionMethod = 'error404Action';
            $request->setParams(array('action' => '404', 'controller' => 'error'));
        }
//echo $controllerClass; die;
        /** @var $controller \App\Rest\Server\AbstractAction */
        $controller = new $controllerClass($request, $response);

        $classMethods = get_class_methods($controller);
        if (!in_array($actionMethod, get_class_methods($controller))) {
            $controllerClass = 'Application\\Controller\\ErrorController';
            $actionMethod = 'error404Action';
            // controller => 'name', action => 'name', param => 'name'
            $request->setParams(array('action' => '404', 'controller' => 'error'));

            /** @var $controller \App\Rest\Server\AbstractAction */
            $controller = new $controllerClass($request, $response);
        }

        if (in_array('preDispatch', $classMethods)) {
            $controller->preDispatch();
        }

        if (!$controller->getBreakRun() && empty($forward)) {

            $forward = $controller->getForward();

            if (!empty($forward)) {
                $request->setParams($forward);
                $controller->removeForward();

                return $this->dispatch($request, $response);
            }

            $actionResponse = $controller->$actionMethod();

            if (in_array('postDispatch', $classMethods)) {
                $controller->postDispatch($actionResponse);

            }
        }


//        print_r($response); echo 123123; die;
        return $response;
    }


    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Maps numerically indexed array values to it's associative mapped counterpart.
     * Or vice versa. Uses user provided map array which consists of index => name
     * parameter mapping. If map is not found, it returns original array.
     *
     * Method strips destination type of keys form source array. Ie. if source array is
     * indexed numerically then every associative key will be stripped. Vice versa if reversed
     * is set to true.
     *
     * @param array    $map
     * @param  array   $values   Indexed or associative array of values to map
     * @param  boolean $reversed False means translation of index to association. True means reverse.
     * @param  boolean $preserve Should wrong type of keys be preserved or stripped.
     * @return array   An array of mapped values
     */
    protected function _getMappedValues(array $map, $values, $reversed = false, $preserve = false)
    {
        if (count($map) == 0) {
            return $values;
        }

        $return = array();

        foreach ($values as $key => $value) {
            if (is_int($key) && !$reversed) {
                if (array_key_exists($key, $map)) {
                    $index = $map[$key];
                } elseif (false === ($index = array_search($key, $map))) {
                    $index = $key;
                }
                $return[$index] = $values[$key];
            } elseif ($reversed) {
                $index = $key;
                if (!is_int($key)) {
                    if (array_key_exists($key, $map)) {
                        $index = $map[$key];
                    } else {
                        $index = array_search($key, $map, true);
                    }
                }
                if (false !== $index) {
                    $return[$index] = $values[$key];
                }
            } elseif ($preserve) {
                $return[$key] = $value;
            }
        }

        return $return;
    }

}
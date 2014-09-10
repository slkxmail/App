<?php

namespace App\Navigation;

class Page extends AbstractContainer
{
    private $options = array();

    public function __construct($options = array())
    {
        $this->setOptions($options);
        $this->init();
    }

    /**
     * Sets whether page should be considered active or not
     *
     * @param  bool $active [optional] whether page should be
     *                      considered active or not. Default is true.
     *
     * @return Page fluent interface, returns self
     */
    public function setActive($active = true)
    {
        $this['active'] = (bool) $active;
        return $this;
    }

    /**
     * Returns whether page should be considered active or not
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default is
     *                          false.
     * @return bool             whether page should be considered active
     */
    public function isActive($recursive = false)
    {
        if (!isset($this['active']) && !$this['active'] && $recursive) {
            /** @var $this Page[] */
            foreach ($this as $page) {
                if ($page->isActive(true)) {
                    return true;
                }
            }
            return false;
        }

        return $this->active;
    }

    /**
     * Proxy to isActive()
     *
     * @param  bool $recursive  [optional] whether page should be considered
     *                          active if any child pages are active. Default
     *                          is false.
     *
     * @return bool             whether page should be considered active
     */
    public function getActive($recursive = false)
    {
        return $this->isActive($recursive);
    }

    /**
     * @param $label
     * @return \App\Navigation\Page
     */
    public function setLabel($label)
    {
        $this['label'] = (string)$label;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return isset($this['label']) ? $this['label'] : '';
    }

    /**
     * @param $class
     * @return \App\Navigation\Page
     */
    public function setClass($class)
    {
        $this['class'] = (string)strtolower($class);
        return $this;
    }

    /**
     * @param $class
     * @return Page
     */
    public function addClass($class)
    {
        $classList = array_map('trim', explode(' ', $this->getClass()));

        if (!in_array($class, $classList)) {
            $classList[] = $class;
        }

        return $this->setClass(implode(' ', $classList));
    }

    /**
     * @param $target
     * @return Page
     */
    public function setTarget($target)
    {
        $this['target'] = (string)$target;
        return $this;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return isset($this['target']) ? $this['target'] : '';
    }

    /**
     * @param $title
     * @return Page
     */
    public function setTitle($title)
    {
        $this['title'] = (string)$title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return isset($this['title']) ? $this['title'] : '';
    }


    /**
     * @return string
     */
    public function getClass()
    {
        return isset($this['class']) ? $this['class'] : '';
    }

    public function setRoute($route)
    {
        $this['route'] = (string)$route;
        return $this;
    }

    public function getRoute()
    {
        return isset($this['route']) ? $this['route'] : '';
    }

    /**
     * @param array $routeParams
     * @return Page
     */
    public function setRouteParams(array $routeParams = null)
    {
        if (empty($routeParams)) {
            $routeParams = array();
        }

        $this['route_params'] = $routeParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return isset($this['route_params']) ? $this['route_params'] : array();
    }

    /**
     * @param $href
     * @return \App\Navigation\Page
     */
    public function setHref($href)
    {
        $this['href'] = (string)$href;
        return $this;
    }

    /**
     * @throws \App\Exception\ErrorException
     * @return string
     */
    public function getHref()
    {
        if (isset($this['href'])) {
            return $this['href'];
        } elseif ($this->getRoute()) {
            if (!$this->getUrlBuilder()) {
                throw new \App\Exception\ErrorException('Route builder not defined');
            }

            return $this->getUrlBuilder()->url($this->getRoute(), $this->getRouteParams());
        } else {
            return '';
        }
    }

    /**
     *
     * @param $order
     * @return Page
     * @throws ErrorException
     */
    public function setOrder($order)
    {
        if (is_string($order)) {
            $temp = (int) $order;
            if ($temp < 0 || $temp > 0 || $order == '0') {
                $order = $temp;
            }
        }

        if (null !== $order && !is_int($order)) {
            throw new ErrorException(
                'Invalid argument: $order must be an integer or null, ' .
                    'or a string that casts to an integer'
            );
        }

        $this['order'] = (int)$order;

        if (isset($this->parent)) {
            $this->parent->notifyOrderUpdate();
        }

        return $this;
    }

    public function getOrder()
    {
        if (!isset($this['order'])) {
            return 0;
        }

        return $this['order'];
    }

    /**
     * @param array $options
     * @return Page
     * @throws \App\Exception\ErrorException
     */
    public function setOptions(array $options)
    {
        if (!is_array($options)) {
            throw new \App\Exception\ErrorException('Options must be an array');
        }

        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Initializes page (used by subclasses)
     *
     * @return void
     */
    protected function init()
    { }

    /**
     * @param $array
     * @return Page
     */
    public static function fromArray($array)
    {
        return new Page($array);
    }

    /**
     * Sets parent container
     *
     * @param  AbstractContainer $parent [optional] new parent to set.
     *                           Default is null which will set no parent.
     * @throws Exception\InvalidArgumentException
     * @return AbstractPage fluent interface, returns self
     */
    public function setParent(AbstractContainer $parent = null)
    {
        if ($parent === $this) {
            throw new Exception\InvalidArgumentException(
                'A page cannot have itself as a parent'
            );
        }

        // return if the given parent already is parent
        if ($parent === $this->parent) {
            return $this;
        }

        // remove from old parent
        if (null !== $this->parent) {
            $this->parent->removePage($this);
        }

        // set new parent
        $this->parent = $parent;

        // add to parent if page and not already a child
        if (null !== $this->parent && !$this->parent->hasPage($this, false)) {
            $this->parent->addPage($this);
        }

        return $this;
    }

    /**
     * Returns parent container
     *
     * @return AbstractContainer|null  parent container or null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Sets the given property
     *
     * If the given property is native (id, class, title, etc), the matching
     * set method will be used. Otherwise, it will be set as a custom property.
     *
     * @param  string $property property name
     * @param  mixed  $value    value to set
     * @return AbstractPage fluent interface, returns self
     * @throws Exception\InvalidArgumentException if property name is invalid
     */
    public function set($property, $value)
    {
        if (!is_string($property) || empty($property)) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $property must be a non-empty string'
            );
        }

        $method = 'set' . static::normalizePropertyName($property);

        if ($method != 'setOptions' && method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->options[$property] = $value;
        }

        return $this;
    }

    /**
     * Returns the value of the given property
     *
     * If the given property is native (id, class, title, etc), the matching
     * get method will be used. Otherwise, it will return the matching custom
     * property, or null if not found.
     *
     * @param  string $property property name
     * @return mixed            the property's value or null
     * @throws Exception\InvalidArgumentException if property name is invalid
     */
    public function get($property)
    {
        if (!is_string($property) || empty($property)) {
            throw new Exception\InvalidArgumentException(
                'Invalid argument: $property must be a non-empty string'
            );
        }

        $method = 'get' . static::normalizePropertyName($property);

        if (method_exists($this, $method)) {
            return $this->$method();
        } elseif (isset($this->options[$property])) {
            return $this->options[$property];
        }

        return null;
    }

    /**
     * @return string
     */
    public final function getHashCode()
    {
        return spl_object_hash($this);
    }

    /**
     * Normalizes a property name
     *
     * @param  string $property  property name to normalize
     * @return string            normalized property name
     */
    protected static function normalizePropertyName($property)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
    }

    // Magic overloads:

    /**
     * Sets a custom property
     *
     * Magic overload for enabling <code>$page->propname = $value</code>.
     *
     * @param  string $name  property name
     * @param  mixed  $value value to set
     * @return void
     * @throws Exception\InvalidArgumentException if property name is invalid
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Returns a property, or null if it doesn't exist
     *
     * Magic overload for enabling <code>$page->propname</code>.
     *
     * @param  string $name property name
     * @return mixed        property value or null
     * @throws Exception\InvalidArgumentException if property name is invalid
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Checks if a property is set
     *
     * Magic overload for enabling <code>isset($page->propname)</code>.
     *
     * Returns true if the property is native (id, class, title, etc), and
     * true or false if it's a custom property (depending on whether the
     * property actually is set).
     *
     * @param  string $name property name
     * @return bool whether the given property exists
     */
    public function __isset($name)
    {
        $method = 'get' . static::normalizePropertyName($name);
        if (method_exists($this, $method)) {
            return true;
        }

        return isset($this->properties[$name]);
    }

    /**
     * Unsets the given custom property
     *
     * Magic overload for enabling <code>unset($page->propname)</code>.
     *
     * @param  string $name property name
     * @return void
     * @throws Exception\InvalidArgumentException  if the property is native
     */
    public function __unset($name)
    {
        $method = 'set' . static::normalizePropertyName($name);
        if (method_exists($this, $method)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Unsetting native property "%s" is not allowed',
                    $name
                )
            );
        }

        if (isset($this->properties[$name])) {
            unset($this->properties[$name]);
        }
    }

    /**
     * Magic overload: Proxy calls to finder methods
     *
     * Examples of finder calls:
     * <code>
     * // METHOD                    // SAME AS
     * $nav->findByLabel('foo');    // $nav->findOneBy('label', 'foo');
     * $nav->findOneByLabel('foo'); // $nav->findOneBy('label', 'foo');
     * $nav->findAllByClass('foo'); // $nav->findAllBy('class', 'foo');
     * </code>
     *
     * @param  string $method             method name
     * @param  array  $arguments          method arguments
     * @throws ErrorException
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        $result = preg_match('/(get)(.+)/', $method, $match);
        if (!$result) {
            throw new ErrorException(sprintf(
                'Bad method call: Unknown method %s::%s',
                get_called_class(),
                $method
            ));
        }
        return $this->get(self::CamelCaseToUnderscore($match[2]));

    }

    public static function CamelCaseToUnderscore($name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
    }
}
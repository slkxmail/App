<?php

namespace App\Mvc\Block;

use App\Exception\InvalidArgumentException;

class Block extends \ArrayIterator
{
    const TYPE_REAL    = 'real';
    const TYPE_PSEUDO  = 'pseudo';
    const TYPE_DEFAULT = 'real';

    /**
     * Имя блока
     *
     * @var string
     */
    protected $name;

    /**
     * @var null
     */
    protected $pos = null;

    /**
     * @var null
     */
    protected $show = null;

    /**
     * @var null
     */
    protected $type = null;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * Переменные
     * @var array
     */
    protected $params = array();

    /**
     * Оберточный тег
     *
     * @var array|null
     */
    protected $wrapper_tag = null;

    /**
     * Оберточный блок
     *
     * @var array|null
     */
    protected $wrapper_block = null;

    /**
     * Оберточный тег для дочерних элементов
     *
     * @var array|null
     */
    protected $inner_tag = null;

    /**
     * @var string|null
     */
    protected $controller;

    /**
     * @var string|null
     */
    protected $action;


    /**
     * @var string
     */
    protected $partial;

    /**
     * Рендерить ли элемент автоматически при рендеринге родителя
     *
     * @var
     */
    protected $autorender = true;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $wrapperContent;

    /**
     * Роли которым доступен блок
     *
     * @var array
     */
    protected $roleAllow = array();

    /**
     * Роли которым блок недоступен
     *
     * @var array
     */
    protected $roleDeny = array();


    protected $isDirtyIndex = true;
    /**
     * Список CSS файлов
     *
     * Формат
     *  array(
     *       'media_type' => array(
     *           'name' => 'file'
     *      )
     * )
     *
     * @var array
     */
    protected $css = array();

    /**
     * Список JS файлов
     *
     * @var array
     */
    protected $js = array();

    public function __construct(array $spec)
    {
        $methods = get_class_methods($this);

        foreach ($spec as $key => $value) {
            $method = 'set' . ucfirst($key);

            if ($key == 'param') {
                $this->setParams($value);
            } elseif ($key == 'block') {
                $this->setBlocks($value);
            } elseif ($key == 'inner_tag') {
                $this->setInnerTag($value);
            } elseif ($key == 'wrapper_tag') {
                $this->setWrapperTag($value);
            } elseif ($key == 'wrapper_content') {
                $this->setWrapperContent($value);
            } elseif ($key == 'wrapper_block') {
                $this->setWrapperBlock($value);
            } elseif ($key == 'role_allow') {
                $this->setRoleAllow($value);
            } elseif ($key == 'role_deny') {
                $this->setRoleDeny($value);
            } elseif (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
    }

    /**
     * @param string $wrapperContent
     * @return $this
     */
    public function setWrapperContent($wrapperContent)
    {
        $this->wrapperContent = $wrapperContent;
        return $this;
    }

    /**
     * @param array $roleAllow
     * @return $this
     */
    public function setRoleAllow(array $roleAllow = array())
    {
        $this->roleAllow = $roleAllow;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoleAllow()
    {
        return $this->roleAllow;
    }

    /**
     * @param array $roleDeny
     * @return $this
     */
    public function setRoleDeny(array $roleDeny = array())
    {
        $this->roleDeny = $roleDeny;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoleDeny()
    {
        return $this->roleDeny;
    }

    /**
     * Разрешено ли роле видеть блок
     *
     * @param $role
     * @return bool
     */
    public function isRoleAllowed($role)
    {
        $role = strtolower(trim($role));

        $allowed = true;
        if ($roleAllowed = $this->getRoleAllow()) {
            if (!in_array($role, $roleAllowed)) {
                $allowed = false;
            }

            if (!$allowed && in_array('guest', $roleAllowed)) {
                $allowed = true;
            }
        }

        if ($roleDeny = $this->getRoleDeny()) {
            if (in_array($role, $roleDeny)) {
                $allowed = false;
            }
        }

        return $allowed;
    }

    /**
     * @return string
     */
    public function getWrapperContent()
    {
        return $this->wrapperContent;
    }

    /**
     * @param array $wrapper_block
     * @return $this
     */
    public function setWrapperBlock(array $wrapper_block = null)
    {
        $this->wrapper_block = $wrapper_block;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getWrapperBlock()
    {
        return $this->wrapper_block;
    }

    /**
     * @param boolean $autorender
     * @return $this;
     */
    public function setAutorender($autorender)
    {
        $this->autorender = (bool) $autorender;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getAutorender()
    {
        return $this->autorender;
    }


    /**
     *
     * @param array|null $wrapper_tag
     * @return $this
     */
    public function setWrapperTag(array $wrapper_tag = null)
    {
        $this->wrapper_tag = $wrapper_tag;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getWrapperTag()
    {
        return $this->wrapper_tag;
    }

    /**
     * @param array|null $inner_tag
     * @return $this
     */
    public function setInnerTag(array $inner_tag = null)
    {
        $this->inner_tag = $inner_tag;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getInnerTag()
    {
        return $this->inner_tag;
    }


    /**
     * @param null|string $controller
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param null|string $action
     * @return $this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = (string)$content;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $partial
     * @return $this
     */
    public function setPartial($partial)
    {
        $this->partial = $partial;
        return $this;
    }

    /**
     * @return string
     */
    public function getPartial()
    {
        return $this->partial;
    }

    /**
     * @param $css
     * @return $this
     */
    public function setCss($css)
    {
        $this->css = $css;
        return $this;
    }

    /**
     * @param $media
     * @return array
     */
    public function getCss($media = null)
    {
        if ($media === null) {
            return $this->css;
        }

        if (isset($this->css[$media])) {
            return $this->css[$media];
        }

        return array();
    }

    /**
     * @param $js
     * @return $this
     */
    public function setJs($js)
    {
        $this->js = $js;
        return $this;
    }

    /**
     * @return array
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * @param mixed $block
     * @throws \App\Exception\InvalidArgumentException
     * @return $this
     */
    public function addBlock($block)
    {
        if (is_array($block)) {
            $block = new Block($block);
        } elseif (!$block instanceof Block) {
            throw new InvalidArgumentException('Block must be instance of Block or an array');
        }

        $this[$block->getName()] = $block;
        $this->isDirtyIndex = true;
        return $this;
    }

    /**
     * @param array $blocks
     * @return $this
     */
    public function setBlocks(array $blocks)
    {
        foreach ($blocks as $block) {
            $this->addBlock($block);
        }

        return $this;
    }

    /**
     * @param $blockName
     * @internal param Block $block
     * @return $this
     */
    public function getBlock($blockName)
    {
        if (isset($this[$blockName])) {
            return $this[$blockName];
        }
        return false;
    }

    /**
     * Сортирует блоки по позиции
     *
     * @return bool
     */
    private function sortBlockArray()
    {
        if (!$this->isDirtyIndex) {
            return false;
        }

        $this->uasort(function(Block $a, Block $b) {
            return ($a->getPos() < $b->getPos()) ? -1 : 1;
        });
        $this->isDirtyIndex = false;
        return true;
    }

    /**
     * @return array|Block[]
     */
    public function getBlocks()
    {
        if ($this->isDirtyIndex) {
            $this->sortBlockArray();
        }

        return $this->getArrayCopy();
    }

    /**
     * @return array
     */
    public function getBlocksAsArray()
    {
        if ($this->isDirtyIndex) {
            $this->sortBlockArray();
        }

        /** @var $blocks Block[]|array */
        $blocks = $this->getArrayCopy();

        $result = array();
        foreach ($blocks as $block) {
            $result[$block->getName()] = $block->toArray();
        }

        return $result;
    }

    /**
     * @param null $blockName
     * @return array
     */
    public function getBlockAsArray($blockName = null)
    {
        if ($blockName === null) {
            $this->getBlocksAsArray();
        } elseif ($block = $this->getBlock($blockName)) {
            /** @var Block $block */
            return $block->toArray();
        }

        return array();
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $pos
     */
    public function setPos($pos = null)
    {
        $this->pos = is_null($pos) ? null : (int) $pos;
    }

    /**
     * @return null|integer
     */
    public function getPos()
    {
        return $this->pos;
    }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = (string)$label;
        return $this;

    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $show
     * @return $this
     */
    public function setShow($show)
    {
        $this->show = $show;
        return $this;
    }

    /**
     * Показывать ли элемент
     *
     * @param bool $default
     * @return bool|mixed
     */
    public function getShow($default = true)
    {
        if ($this->show === null) {
            return $default;
        }

        return (bool)$this->show;
    }

    /**
     * Получить тип блока
     *
     * @return mixed|string
     */
    public function getType()
    {
        return ($this->type === null) ? self::TYPE_DEFAULT : $this->type;
    }

    /**
     * @param $param
     * @param $value
     * @return $this
     */
    public function setParam($param, $value)
    {
        $this->params[$param] = $value;
        return $this;
    }

    /**
     * Получить значение, так же можно использовать xpath
     *
     * @param string $param
     * @param null $default
     * @return array|null
     */
    public function getParam($param = null, $default = null)
    {
        if (is_null($param)) {
            return $this->getParams();
        } elseif (isset($this->params[$param]['value'])) {
            return $this->params[$param]['value'];
        } elseif (strpos($param, '/') !== false) {
            $xpath = explode('/', trim($param, ' /'));

            $vars = $this->params;
            foreach ($xpath as $param) {
                if (isset($vars[$param])) {
                    $vars = $vars[$param];
                } else {
                    return $default;
                }
            }

            if (isset($vars['value'])) {
                return $vars['value'];
            } else {
                return $default;
            }
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return (array)$this->params;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params = array())
    {
        $this->params = array();
        return $this->addParams($params);
    }

    public function addParam($param, $value)
    {
        return $this->setParam($param, $value);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function addParams(array $params = array())
    {
        foreach ($params as $param => $value) {
            $this->setParam($param, $value);
        }

        return $this;
    }

    /**
     * @param null $param
     * @return $this
     */
    public function removeParam($param = null)
    {
        if (is_null($param)) {
            return $this->clearParams();
        }

        if (isset($this->params[$param])) {
            unset($this->params[(string)$param]);
        } elseif (strpos($param, '/') !== false) {
            $xpath = explode('/', trim($param, ' /'));
            eval('unset($this->params[\''. implode('\'][\'', $xpath) . '\']);');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearParams()
    {
        $this->params = array();
        return $this;
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $result = array(
            'name'        => $this->getName(),
            'pos'         => $this->getPos(),
            'show'        => $this->getShow(),
            'type'        => $this->getType(),
            'label'       => $this->getLabel(),
            'autorender'  => $this->getAutorender(),
            'role_allow'  => $this->getRoleAllow(),
            'role_deny'   => $this->getRoleDeny(),

            'controller'  => $this->getController(),
            'action'      => $this->getAction(),

            'partial'     => $this->getPartial(),
            'content'     => $this->getContent(),
            'wrapper_content' => $this->getWrapperContent(),

            'wrapper_tag' => $this->getWrapperTag(),
            'inner_tag'   => $this->getInnerTag(),
            'param'       => $this->getParams(),
            'block'       => $this->getBlocksAsArray(),
            'css'         => $this->getCss(),
            'js'          => $this->getJs()
        );

        if ($wrapperBlock = $this->getWrapperBlock()) {
            $result['wrapper_block'] = $this->getWrapperBlock();

            if (isset($result['wrapper_block']['block']) && is_array($result['wrapper_block']['block'])) {
                foreach ($result['wrapper_block']['block'] as $k => $block) {
                    $result['wrapper_block']['block'][$k] = $block->toArray();
                }
            }
        }

        return $result;
    }
}
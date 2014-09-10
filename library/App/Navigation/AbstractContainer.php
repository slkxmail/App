<?php

namespace App\Navigation;

use Countable;
use RecursiveIterator;
use RecursiveIteratorIterator;
use Traversable;
use App\Exception\ErrorException;
use App\Mvc\UrlBuilder\UrlBuilder;

abstract class AbstractContainer extends \ArrayIterator implements Countable, RecursiveIterator
{
    /**
     * @var AbstractContainer
     */
    protected $parent;

    /**
     * Whether index is dirty and needs to be re-arranged
     *
     * @var bool
     */
    protected $dirtyIndex = true;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @return \App\Mvc\UrlBuilder\UrlBuilder
     * @throws \App\Exception\ErrorException
     */
    public function getUrlBuilder()
    {
        if (!$this->urlBuilder && $this->parent) {
            $this->urlBuilder = $this->parent->getUrlBuilder();
        }

        if (!$this->urlBuilder instanceof UrlBuilder) {
            throw new ErrorException('Url builder not defined');
        }

        return $this->urlBuilder;
    }

    /**
     * @param \App\Mvc\UrlBuilder\UrlBuilder $urlBuilder
     * @return AbstractContainer
     */
    public function setUrlBuilder(UrlBuilder $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
        return $this;
    }

    public function addPage($page)
    {
        if (is_array($page)) {
            $page = Page::fromArray($page);
        }

        if (!$page instanceof Page) {
            throw new ErrorException('Page must be instance of App\Navigation\Page or an array');
        }

        $hash = $page->getHashCode();

        if (isset($this[$hash])) {
            return $this;
        }

        $this->dirtyIndex = true;
        $page->setParent($this);
        $this[$hash] = $page;

        return $this;
    }

    public function notifyOrderUpdate()
    {
        $this->dirtyIndex = true;
    }

    /**
     * Отсортировать
     */
    public  function sort()
    {
        if (!$this->dirtyIndex) {
            return $this;
        }

        $sort = function(Page $a, Page $b) {
            return $a->getOrder() > $b->getOrder();
        };

        $this->uasort($sort);
        $this->dirtyIndex = false;

        return $this;
    }

    /**
     * @param $pages
     * @return AbstractContainer
     * @throws \App\Exception\ErrorException
     */
    public function addPages($pages)
    {
        if (!is_array($pages) && !$pages instanceof Traversable) {
            throw new ErrorException(
                'Invalid argument: $pages must be an array, an '
                    . 'instance of Traversable or an instance of '
                    . 'Zend\Navigation\AbstractContainer'
            );
        }

        if ($pages instanceof AbstractContainer) {
            $pages = iterator_to_array($pages);
        }

        foreach ($pages as $page) {
            $this->addPage($page);
        }

        return $this;
    }

    /**
     * Sets pages this container should have, removing existing pages
     *
     * @param  array $pages pages to set
     * @return AbstractContainer fluent interface, returns self
     */
    public function setPages(array $pages)
    {
        $this->removePages();
        return $this->addPages($pages);
    }

    /**
     * Returns pages in the container
     *
     * @return array  array of Page\AbstractPage instances
     */
    public function getPages()
    {
        return $this->getArrayCopy();
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function removePage(Page $page)
    {
        $hash = $page->getHashCode();

        if (isset($this[$hash])) {
            unset($this[$hash]);
            return true;
        }

        return false;
    }

    /**
     * Checks if the container has the given page
     *
     * @param \App\Navigation\Page|\App\Navigation\Page\AbstractPage $page      page to look for
     * @param  bool                                                  $recursive [optional] whether to search recursively.
     *                                                                          Default is false.
     * @return bool whether page is in container
     */
    public function hasPage(Page $page, $recursive = false)
    {
        $hash = $page->getHashCode();
        if (isset($this[$hash])) {
            return true;
        } elseif ($recursive) {
            /** @var $this Page[] */
            foreach ($this as $childPage) {
                if ($childPage->hasPage($page, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Removes all pages in container
     *
     * @return AbstractContainer fluent interface, returns self
     */
    public function removePages()
    {
        foreach ($this as $k => $v) {
            unset($this[$k]);
        }

        return $this;
    }

    /**
     * @param AbstractContainer $parent
     * @return AbstractContainer
     */
    public function setParent(AbstractContainer $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->count() > 0;
    }

    /**
     * @return AbstractContainer|mixed|\RecursiveIterator
     */
    public function getChildren()
    {
        return $this->current();
    }

    /**
     * Returns a child page matching $property == $value, or null if not found
     *
     * @param  string $property        name of property to match against
     * @param  mixed  $value           value to match property against
     * @return Page\AbstractPage|null  matching page or null
     */
    public function findOneBy($property, $value)
    {
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        /** @var $page Page */
        foreach ($iterator as $page) {
            if ($page->get($property) == $value) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Returns all child pages matching $property == $value, or an empty array
     * if no pages are found
     *
     * @param  string $property  name of property to match against
     * @param  mixed  $value     value to match property against
     * @return array  array containing only Page\AbstractPage instances
     */
    public function findAllBy($property, $value)
    {
        $found = array();

        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        /** @var $page Page */
        foreach ($iterator as $page) {
            if ($page->get($property) == $value) {
                $found[] = $page;
            }
        }

        return $found;
    }

    /**
     * Returns page(s) matching $property == $value
     *
     * @param  string $property  name of property to match against
     * @param  mixed  $value     value to match property against
     * @param  bool   $all       [optional] whether an array of all matching
     *                           pages should be returned, or only the first.
     *                           If true, an array will be returned, even if not
     *                           matching pages are found. If false, null will
     *                           be returned if no matching page is found.
     *                           Default is false.
     * @return Page\AbstractPage|null  matching page or null
     */
    public function findBy($property, $value, $all = false)
    {
        if ($all) {
            return $this->findAllBy($property, $value);
        }

        return $this->findOneBy($property, $value);
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
     * @throws \App\Exception\ErrorException
     * @return
     */
    public function __call($method, $arguments)
    {
        $result = preg_match('/(find(?:One|All)?By)(.+)/', $method, $match);
        if (!$result) {
            throw new ErrorException(sprintf(
                'Bad method call: Unknown method %s::%s',
                get_called_class(),
                $method
            ));
        }
        return $this->{$match[1]}($match[2], $arguments[0]);

    }

    // RecursiveIterator interface:

    /**
     * Returns current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return Page
     */
    public function current()
    {
        $this->sort();
        return parent::current();
    }

    /**
     * Returns hash code of current page
     *
     * Implements RecursiveIterator interface.
     *
     * @return string  hash code of current page
     */
    public function key()
    {
        $this->sort();
        return parent::key();
    }

    /**
     * Moves index pointer to next page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function next()
    {
        $this->sort();
        parent::next();
    }

    /**
     * Sets index pointer to first page in the container
     *
     * Implements RecursiveIterator interface.
     *
     * @return void
     */
    public function rewind()
    {
        $this->sort();
        parent::rewind();
    }

    /**
     * Checks if container index is valid
     *
     * Implements RecursiveIterator interface.
     *
     * @return bool
     */
    public function valid()
    {
        $this->sort();
        return parent::valid();
    }
}
<?php

namespace App\Form;

use ArrayAccess;
use Traversable;

class Factory
{
    /**
     * Create an element, fieldset, or form
     *
     * Introspects the 'type' key of the provided $spec, and determines what
     * type is being requested; if none is provided, assumes the spec
     * represents simply an element.
     *
     * @param  array|Traversable $spec
     * @throws Exception\DomainException
     * @return Element
     */
    public function create($spec)
    {
        $spec = $this->validateSpecification($spec, __METHOD__);
        $type = isset($spec['type']) ? $spec['type'] : 'App\Form\Element';

        /** @var $element Element */
        if (class_exists($type)) {
            $element = new $type();

            if ($element instanceof Form) {
                return $this->configureForm($element, $spec);
            }

            if ($element instanceof Fieldset) {
                return $this->configureFieldset($element, $spec);
            }

            if ($element instanceof Element) {
                return $this->configureElement($element, $spec);
            }
        }

        throw new Exception\DomainException(sprintf(
            '%s expects the $spec["type"] to implement one of %s, %s, or %s; received %s',
            __METHOD__,
            'Zend\Form\Element',
            'Zend\Form\Fieldset',
            'Zend\Form\Form',
            $type
        ));
    }

    /**
     * Create an element
     *
     * @param  array $spec
     * @return Element
     */
    public function createElement($spec)
    {
        if (!isset($spec['type'])) {
            $spec['type'] = 'App\Form\Element';
        }

        return $this->create($spec);
    }

    /**
     * Create a fieldset
     *
     * @param  array $spec
     * @return Element
     */
    public function createFieldset($spec)
    {
        if (!isset($spec['type'])) {
            $spec['type'] = 'App\Form\Fieldset';
        }

        return $this->create($spec);
    }

    /**
     * Create a form
     *
     * @param  array $spec
     * @return Element
     */
    public function createForm($spec)
    {
        if (!isset($spec['type'])) {
            $spec['type'] = 'App\Form\Form';
        }

        return $this->create($spec);
    }

    /**
     * Configure an element based on the provided specification
     *
     * Specification can contain any of the following:
     * - type: the Element class to use; defaults to \Zend\Form\Element
     * - name: what name to provide the element, if any
     * - options: an array, Traversable, or ArrayAccess object of element options
     * - attributes: an array, Traversable, or ArrayAccess object of element
     *   attributes to assign
     *
     * @param \App\Form\Element|\App\Form\Element $element
     * @param  array|Traversable|ArrayAccess               $spec
     * @return Element
     */
    public function configureElement(Element $element, $spec)
    {
        $spec = $this->validateSpecification($spec, __METHOD__);

        $name       = isset($spec['name'])       ? $spec['name']       : null;
        $options    = isset($spec['options'])    ? $spec['options']    : null;
        $attributes = isset($spec['attributes']) ? $spec['attributes'] : null;

        if ($name !== null && $name !== '') {
            $element->setName($name);
        }

        if (is_array($options) || $options instanceof Traversable || $options instanceof ArrayAccess) {
            $element->setOptions($options);
        }

        if (is_array($attributes) || $attributes instanceof Traversable || $attributes instanceof ArrayAccess) {
            $element->setAttributes($attributes);
        }

        return $element;
    }

    /**
     * Configure a fieldset based on the provided specification
     *
     * Specification can contain any of the following:
     * - type: the Fieldset class to use; defaults to \App\Form\Fieldset
     * - name: what name to provide the fieldset, if any
     * - options: an array, Traversable, or ArrayAccess object of element options
     * - attributes: an array, Traversable, or ArrayAccess object of element
     *   attributes to assign
     * - elements: an array or Traversable object where each entry is an array
     *   or ArrayAccess object containing the keys:
     *   - flags: (optional) array of flags to pass to Fieldset::add()
     *   - spec: the actual element specification, per {@link configureElement()}
     *
     * @param \App\Form\Fieldset $fieldset
     * @param  array|Traversable|ArrayAccess                 $spec
     * @return Fieldset
     */
    public function configureFieldset(Fieldset $fieldset, $spec)
    {
        $spec     = $this->validateSpecification($spec, __METHOD__);
        $fieldset = $this->configureElement($fieldset, $spec);

        if (isset($spec['elements'])) {
            $this->prepareAndInjectElements($spec['elements'], $fieldset, __METHOD__);
        }

        if (isset($spec['fieldsets'])) {
            $this->prepareAndInjectFieldsets($spec['fieldsets'], $fieldset, __METHOD__);
        }

        return $fieldset;
    }

    /**
     * Configure a form based on the provided specification
     *
     * @param  Form                  $form
     * @param  array|Traversable|ArrayAccess  $spec
     * @return Form
     */
    public function configureForm(Form $form, $spec)
    {
        $spec = $this->validateSpecification($spec, __METHOD__);
        $form = $this->configureFieldset($form, $spec);

        return $form;
    }

    /**
     * Validate a provided specification
     *
     * Ensures we have an array, Traversable, or ArrayAccess object, and returns it.
     *
     * @param  array|Traversable|ArrayAccess $spec
     * @param  string $method Method invoking the validator
     * @return array|ArrayAccess
     * @throws Exception\InvalidArgumentException for invalid $spec
     */
    protected function validateSpecification($spec, $method)
    {
        if (is_array($spec)) {
            return $spec;
        }

        if ($spec instanceof Traversable) {
            $spec = \Zend\Stdlib\ArrayUtils::iteratorToArray($spec);
            return $spec;
        }

        if (!$spec instanceof \ArrayAccess) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array, or object implementing Traversable or ArrayAccess; received "%s"',
                $method,
                (is_object($spec) ? get_class($spec) : gettype($spec))
            ));
        }

        return $spec;
    }

    /**
     * Takes a list of element specifications, creates the elements, and injects them into the provided fieldset
     *
     * @param  array|Traversable|ArrayAccess $elements
     * @param  Fieldset $fieldset
     * @param  string $method Method invoking this one (for exception messages)
     * @return void
     */
    protected function prepareAndInjectElements($elements, Fieldset $fieldset, $method)
    {
        $elements = $this->validateSpecification($elements, $method);

        foreach ($elements as $elementSpecification) {
            $flags = isset($elementSpecification['flags']) ? $elementSpecification['flags'] : array();
            $spec  = isset($elementSpecification['spec'])  ? $elementSpecification['spec']  : array();

            if (!isset($spec['type'])) {
                $spec['type'] = 'App\Form\Element';
            }

            /** @var $element Fieldset */
            $element = $this->create($spec);
            $fieldset->add($element, $flags);
        }
    }

    /**
     * Takes a list of fieldset specifications, creates the fieldsets, and injects them into the master fieldset
     *
     * @param  array|Traversable|ArrayAccess                 $fieldsets
     * @param \App\Form\Fieldset $masterFieldset
     * @param  string                                        $method Method invoking this one (for exception messages)
     * @return void
     */
    public function prepareAndInjectFieldsets($fieldsets, Fieldset $masterFieldset, $method)
    {
        $fieldsets = $this->validateSpecification($fieldsets, $method);

        foreach ($fieldsets as $fieldsetSpecification) {
            $flags = isset($fieldsetSpecification['flags']) ? $fieldsetSpecification['flags'] : array();
            $spec  = isset($fieldsetSpecification['spec'])  ? $fieldsetSpecification['spec']  : array();

            $fieldset = $this->createFieldset($spec);
            $masterFieldset->add($fieldset, $flags);
        }
    }
}

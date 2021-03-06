<?php
/**
 * PHP version 5.5
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 *
 * @copyright Comunidad PHP Puebla 2015 (http://www.comunidadphppuebla.com)
 */
namespace EasyForms;

use Assert\Assertion;
use EasyForms\Elements\File;
use EasyForms\Elements\Element;
use EasyForms\View\FormView;

class Form
{
    /** @var array */
    protected $attributes = [];

    /** @var Element[] */
    protected $elements = [];

    /** @var array */
    protected $values = [];

    /**
     * @param string $name
     * @return Element
     */
    protected function get($name)
    {
        Assertion::keyExists($this->elements, $name);

        return $this->elements[$name];
    }

    /**
     * @param $name
     * @return boolean
     */
    protected function has($name)
    {
        return isset($this->elements[$name]);
    }

    /**
     * @param Element $element
     * @return Form
     */
    public function add(Element $element)
    {
        if ($element instanceof File) {
            $this->enableFileUploads();
        }

        $this->elements[$element->name()] = $element;

        return $this;
    }

    /**
     * Set the correct content type for this form to allow file uploads
     */
    protected function enableFileUploads()
    {
        $this->attributes['enctype'] = 'multipart/form-data';
    }

    /**
     * @return array
     */
    public function values()
    {
        array_map(function (Element $element) use (&$values) {
            $this->values[$element->name()] = $element->value();
        }, $this->elements);

        return $this->values;
    }

    /**
     * @param array $values
     */
    public function submit(array $values)
    {
        $this->populate($values);
    }

    /**
     * Populate the form elements with its corresponding value
     *
     * @param array $values
     */
    protected function populate(array $values)
    {
        foreach ($values as $name => $value) {
            $this->has($name) && $this->get($name)->setValue($value);
        }
    }

    /**
     * @param array $messages
     */
    public function setErrorMessages(array $messages)
    {
        foreach ($messages as $name => $message) {
            $this->has($name) && $this->get($name)->setMessages($message);
        }
    }

    /**
     * @return FormView
     */
    public function buildView()
    {
        $view = new FormView($this->elements);
        $view->attributes = $this->attributes;

        return $view;
    }
}

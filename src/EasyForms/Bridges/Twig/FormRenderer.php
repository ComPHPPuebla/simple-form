<?php
/**
 * PHP version 5.5
 *
 * This source file is subject to the license that is bundled with this package in the file LICENSE.
 *
 * @copyright Comunidad PHP Puebla 2015 (http://www.comunidadphppuebla.com)
 */
namespace EasyForms\Bridges\Twig;

use EasyForms\Bridges\Twig\Exception\BlockNotFoundException;
use EasyForms\View\ElementView;
use EasyForms\View\FormView;
use Twig_Environment as Environment;
use Twig_Template as Template;

class FormRenderer
{
    /** @var Environment */
    protected $environment;

    /** @var string[] */
    protected $paths;

    /** @var Template[] */
    protected $themes = [];

    /** @var Template[] */
    protected $cache = [];

    /**
     * @param Environment $environment
     * @param array $themePaths
     */
    public function __construct(Environment $environment, array $themePaths)
    {
        $this->environment = $environment;
        $this->paths = $themePaths;
    }

    /**
     * @param Template $theme
     */
    public function addTheme(Template $theme)
    {
        $this->themes[] = $theme;
    }

    /**
     * @param string $block
     * @return Template
     * @throws BlockNotFoundException
     */
    protected function searchThemeFor($block)
    {
        $this->loadThemes();

        if (isset($this->cache[$block])) {
            return $this->cache[$block];
        }

        foreach ($this->themes as $theme) {
            if ($theme = $this->tryToCache($theme, $block)) {
                break; // Block is defined in this template
            }
        }

        if (!$theme) {
            throw new BlockNotFoundException("Block '$block' is not defined in the templates of this theme.");
        }

        return $theme;
    }

    /**
     * @param Template $theme
     * @param string $block
     * @return Template | null
     */
    protected function tryToCache(Template $theme, $block)
    {
        if ($theme->hasBlock($block)) {
            $this->cache[$block] = $theme;

            return $theme;
        }
    }

    /**
     * Lazy load all the registered themes
     */
    protected function loadThemes()
    {
        foreach ($this->paths as $path) {
            $this->loadThemeForPath($path);
        }
    }

    /**
     * @param string $path
     */
    protected function loadThemeForPath($path)
    {
        if (!isset($this->themes[$path])) {
            $template = $this->environment->loadTemplate($path);
            $this->themes[$path] = $template;
            $template->getParent([]) && $this->themes[$template->getParent([])->getTemplateName()] = $template->getParent([]);
        }
    }

    /**
     * @param ElementView $element
     * @param array $options {
     *     @var array   $attr       This element HTML attributes
     *     @var string  $label      The display name for this element
     *     @var array   $label_attr The HTML attributes for this element's label
     * }
     * @return string
     */
    public function renderRow(ElementView $element, array $options = [])
    {
        $attr = $this->mergeAttributes($element, $options);
        isset($options['options']) && $this->overrideBlocks($element, $options['options']);
        $opt = $this->mergeOptions($element, $options);

        return $this->renderBlock($element->rowBlock, array_merge([
            'element' => $element,
            'valid' => $element->valid,
            'attr' => $attr,
            'options' => $opt,
        ], $options));
    }

    /**
     * @param ElementView $element
     * @param array $options
     * @return array
     */
    protected function mergeAttributes(ElementView $element, array &$options)
    {
        $attr = isset($options['attr']) ? array_merge($element->attributes, $options['attr']) : $element->attributes;
        unset($options['attr']);

        return $attr;
    }

    /**
     * @param ElementView $element
     * @param array $options
     * @return array
     */
    protected function mergeOptions(ElementView $element, array &$options)
    {
        $opt = isset($options['options']) ? array_merge($element->options, $options['options']) : $element->options;
        unset($options['options']);

        return $opt;
    }

    /**
     * @param ElementView $element
     * @param array $options
     */
    public function overrideBlocks(ElementView $element, array &$options)
    {
        isset($options['block']) && $element->block = $options['block'];
        unset($options['block']);

        isset($options['row_block']) && $element->rowBlock = $options['row_block'];
        unset($options['row_block']);
    }

    /**
     * @param ElementView $element
     * @param array $attributes
     * @param array $options
     * @return string
     */
    public function renderElement(ElementView $element, $attributes = [], array $options = [])
    {
        $this->overrideBlocks($element, $options);

        return $this->renderBlock($element->block, [
            'element' => $element,
            'attr' => array_merge($element->attributes, $attributes),
            'value' => $element->value,
            'options' => array_merge($element->options, $options),
            'choices' => $element->choices,
        ]);
    }

    /**
     * @param FormView $form
     * @param array $attributes
     * @return string
     */
    public function renderFormStart(FormView $form, array $attributes = [])
    {
        return $this->renderBlock('form_start', [
            'attr' => array_merge($form->attributes, $attributes),
        ]);
    }

    /**
     * @return string
     */
    public function renderFormEnd()
    {
        return $this->renderBlock('form_end');
    }

    /**
     * @param ElementView $element
     * @param string $label
     * @param string $id
     * @param array $attributes
     * @return string
     */
    public function renderLabel(ElementView $element, $label, $id, array $attributes = [])
    {
        $id && $attributes['for'] = $id;

        return $this->renderBlock('label', [
            'label' => $label,
            'attr' => $attributes,
            'is_required' => $element->isRequired,
        ]);
    }

    /**
     * @param ElementView $element
     * @return string
     */
    public function renderErrors(ElementView $element)
    {
        return $this->renderBlock('errors', [
            'errors' => $element->messages,
        ]);
    }

    /**
     * @param string $name
     * @param array $vars
     * @return string
     */
    protected function renderBlock($name, array $vars = [])
    {
        $theme = $this->searchThemeFor($name);

        ob_start();

        $theme->displayBlock($name, $vars);

        return ob_get_clean();
    }
}

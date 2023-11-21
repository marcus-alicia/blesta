<?php
namespace Blesta\Core\Util\Widgets;
use Blesta\Core\Util\Input\Fields\InputFields;
use \Html;
use \Language;

/**
 * Abstract Widget
 *
 * @package blesta
 * @subpackage blesta.core.Util.Widgets
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractWidget extends Html
{
    /**
     * @var string The string to use as the end of line character, "\n" by default
     */
    protected $eol = "\n";
    /**
     * @var bool Whether or not to return output from various widget methods
     */
    protected $return_output = false;
    /**
     * @var array Buttons that should be displayed within the window
     */
    protected $widget_buttons = [];
    /**
     * @var array An array of style sheet attributes to be rendered into the DOM
     */
    protected $style_sheets = [];
    /**
     * @var string How to render the widget. Options include:
     *
     *  - full The entire widget (default)
     *  - inner The content only (everything excluding the nav and title)
     *  - content_section The full content including nav (everything exluding box frame and title section)
     *  - common_box_content The content only (full_content excluding the nav)
     */
    protected $render;
    /**
     * @var array $nav An array of navigation elements
     */
    protected $nav = [];
    /**
     * @var string $nav_type Sets the navigation type:
     *
     *  - links
     *  - tabs
     *  - pills
     */
    protected $nav_type = 'links';
    /**
     * @var array $link_buttons An array of link buttons
     */
    protected $link_buttons = [];
    /**
     * @var InputFilters A list of input fields to display in the widget filtering form
     */
    protected $filters;
    /**
     * @var bool Whether to show the filter content on page load
     */
    protected $show_filters;
    /**
     * @var bool Whether to submit filters and reload widget via ajax
     */
    protected $ajax_filtering;
    /**
     * @var string The HTML to display either in or as the widget filtering form
     */
    protected $filter_html = '';
    /**
     * @var string The uri to which the widget filtering form should be submitted
     */
    protected $filter_uri = '';
    /**
     * @var string The widget header link
     */
    protected $header_link = '';

    /**
     * Sets navigation links within the widget
     *
     * @param array $link A multi-dimensional array of tab info including:
     *
     *  - name The name of the link to be displayed
     *  - current True if this element is currently active
     *  - attributes An array of attributes to set for this link (e.g. array('href'=>"#"))
     * @param string $type the type of links being set (optional) (links or tabs, default links)
     */
    public function setLinks(array $link, $type = 'links')
    {
        $this->nav = $link;
        $this->nav_type = $type;
    }

    /**
     * Sets navigation buttons along with AbstractWidget::setLinks(). This method may
     * only be used in addition with AbstractWidget::setLinks()
     *
     * @param array $link_buttons A multi-dimensional array of button links including:
     *
     *  - name The name of the button link to be displayed
     *  - icon The class name of the icon to display before the name
     *  - attributes An array of attributes to set for this button link (e.g. array('href'=>"#"))
     */
    public function setLinkButtons(array $link_buttons)
    {
        $this->link_buttons = $link_buttons;
    }

    /**
     * Set a widget button to be displayed in the title bar of the widget
     *
     * @param string|array $button The widget button attributes
     */
    public function setWidgetButton($button)
    {
        $this->widget_buttons[] = $button;
    }

    /**
     * Sets a style sheet to be linked into the document
     *
     * @param string $path the web path to the style sheet
     * @param array An array of attributes to set for this element
     */
    public function setStyleSheet($path, array $attributes = null)
    {
        $default_attributes = ['media' => 'screen', 'type' => 'text/css', 'rel' => 'stylesheet', 'href' => $path];
        $attributes = array_merge((array)$attributes, $default_attributes);

        $this->style_sheets[] = $attributes;
    }

    /**
     * Sets a list of widget filters
     *
     * @param InputFields $filters A list of input fields to display in the widget filtering form
     * @param string $uri The uri for the widget filtering form action
     * @param bool $show_filters Whether to show the filtering form on page load
     */
    public function setFilters(InputFields $filters, $uri, $show_filters = false)
    {
        $this->filters = $filters;
        $this->filter_uri = $uri;
        $this->show_filters = $show_filters;
    }

    /**
     * Sets HTML for the widget filtering form
     *
     * @param string $html The uri for the widget filtering form action
     * @param bool $show_filters Whether to show the filtering form on page load
     */
    public function setFilterHtml($html, $show_filters = false)
    {
        $this->filter_html = $html;
        $this->show_filters = $show_filters;
    }

    /**
     * Sets the link for the widget header
     *
     * @param string $link The link for the widget header
     */
    public function setHeaderLink($link)
    {
        $this->header_link = $link;
    }

    /**
     * Sets whether to submit filters and reload widget via ajax
     *
     * @param bool $ajax Whether to submit filters and reload widget via ajax
     */
    public function setAjaxFiltering($ajax = true)
    {
        $this->ajax_filtering = $ajax;
    }

    /**
     * Set whether to return $output generated by these methods, or to echo it out instead
     *
     * @param bool $return True to return output from these widget methods, false to echo results instead
     */
    public function setOutput($return)
    {
        $this->return_output = $return;
    }

    /**
     * Handles whether to output or return $html
     *
     * @param string $html The HTML to output/return
     * @return string The HTML given, void if output enabled
     */
    protected function output($html)
    {
        if ($this->return_output) {
            return $html;
        }
        echo $html;
    }

    /**
     *  Add the filter form toggle button to the list of widget links
     */
    protected function setFilterLink()
    {
        // Set the filter form toggle button
        if (isset($this->filters) || $this->filter_html != '') {
            if ($this->nav_type == 'tabs') {
                $this->setWidgetButton([
                    'class' => 'filter-toggle',
                    'title' => Language::_('Widget.toggle_filters', true)
                ]);
            } else {
                if (!is_array($this->link_buttons)) {
                    $this->link_buttons = [];
                }

                $this->link_buttons[] = [
                    'icon' => 'fas fa-filter',
                    'name' => '',
                    'attributes' => [
                        'class' => 'filter-toggle btn btn-sm btn-light',
                        'title' => Language::_('Widget.toggle_filters', true)
                    ]
                ];
            }
        }
    }
}

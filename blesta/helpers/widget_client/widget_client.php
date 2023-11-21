<?php
use Blesta\Core\Util\Input\Fields\InputField;
use Blesta\Core\Util\Widgets\AbstractWidget;
/**
 * Simplifies the creation of widgets for the client interface
 *
 * @package blesta
 * @subpackage blesta.helpers.widget_client
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class WidgetClient extends AbstractWidget
{
    /**
     * @var bool True if to automatically start the widget body
     */
    private $auto_start_body = false;
    /**
     * @var bool True if the body of the widget is open
     */
    private $body_open = false;
    /**
     * @var bool True if the footer of the widget is open
     */
    private $footer_open = false;

    /**
     * Initializes the client widget helper
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Session']);
        Language::setLang($this->Session->read('blesta_language'));
        Language::loadLang('widget_client');
    }

    /**
     * Clear this widget, making it ready to produce the next widget
     */
    public function clear()
    {
        $this->widget_buttons = [];
        $this->nav = [];
        $this->nav_type = 'links';
        $this->link_buttons = [];
        $this->style_sheets = [];
        $this->render = 'full';
        $this->body_open = false;
        $this->footer_open = false;
        $this->auto_start_body = false;
        $this->filters = null;
        $this->filter_html = '';
        $this->filter_uri = '';
        $this->show_filters = null;
        $this->ajax_filtering = null;
    }

    /**
     * Sets whether or not the body panel should start when WidgetClient::create() is called
     *
     * @param bool $auto_start True to auto begin the widget body when WidgetClient::create() is called
     */
    public function autoStartBody($auto_start)
    {
        $this->auto_start_body = $auto_start;
    }

    /**
     * Creates the widget with the given title and attributes
     *
     * @param string $title The title to display for this widget
     * @param array $attributes An list of attributes to set for this widget's primary container
     * @param string $render How to render the widget. Options include:
     *
     *  - full The entire widget (default)
     *  - inner_content (everything but the title)
     * @return mixed An HTML string containing the widget, void if the string is output automatically
     */
    public function create($title = null, array $attributes = null, $render = null)
    {
        // Don't output until this section is completely built
        $output = $this->setOutput(true);

        $this->render = ($render == null ? 'full' : $render);
        $default_attributes = ['class' => 'card card-blesta content_section'];

        // Set the attributes, don't allow overwriting the default class, concat instead
        if (isset($attributes['class']) && isset($default_attributes['class'])) {
            $attributes['class'] .= ' ' . $default_attributes['class'];
        }
        $attributes = array_merge($default_attributes, (array)$attributes);

        // Add the filter form toggle button to the list of widget links
        $this->setFilterLink();

        // Set the widget id
        if (isset($attributes['id'])) {
            $this->id = $attributes['id'];
        } elseif ($title !== null) {
            $this->id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $attributes['id'] = $this->id;
        }

        $html = null;
        $html .= $this->buildStyleSheets();
        // Render container and heading
        if ($this->render == 'full') {
            $html .= '
                <div' . $this->buildAttributes($attributes) . '>
                    <div class="card-header">
                        ' . $this->_($title, true) . $this->buildWidgetButtons() . '
                    </div>
                    <div class="card-content">';
        }

        if (($this->render == 'full' || $this->render == 'inner_content') && $this->auto_start_body) {
            $html .= $this->startBody(false);
            $html .= $this->buildFilters(false);
        }

        // Restore output setting
        $this->setOutput($output);

        return $this->output($html);
    }

    /**
     * End the widget, closing an loose ends
     *
     * @return mixed An HTML string ending the widget, void if the string is output automatically
     */
    public function end()
    {
        // Don't output until this section is completely built
        $output = $this->setOutput(true);

        $html = null;
        $html .= $this->endBody(false);
        $html .= $this->endFooter(false);

        if ($this->render == 'full') {
            // Close container
            $html .= '
                    </div>
                </div>';
        }

        // Restore output setting
        $this->setOutput($output);

        return $this->output($html);
    }

    /**
     * Start the widget body
     *
     * @param bool $output True to output the body, false to return it
     * @return mixed An HTML string beginning the widget body, void if the string is output automatically
     */
    public function startBody($output = true)
    {
        $this->body_open = true;

        $panel_nav = $this->buildNav() . $this->buildLinkButtons();

        $html = '
            <div class="card-body">';
        if ($panel_nav != '') {
            $html .= '
                <div class="card-nav">
                    ' . $panel_nav . '
                    <div class="clearfix"></div>
                </div>';
        }

        if ($output) {
            $this->setOutput(false);
        }
        return $this->output($html);
    }

    /**
     * End the widget body
     *
     * @param bool $output True to output the body, false to return it
     * @return mixed An HTML string ending the widget body, void if the string is output automatically
     */
    public function endBody($output = true)
    {
        if ($this->body_open) {
            $this->body_open = false;
            if ($output) {
                $this->setOutput(false);
            }

            return $this->output('</div>' . $this->eol);
        }
        return null;
    }

    /**
     * Start the widget footer
     *
     * @param bool $output True to output the footer, false to return it
     * @return mixed An HTML string beginning the widget footer, void if the string is output automatically
     */
    public function startFooter($output = true)
    {
        $this->footer_open = true;
        if ($output) {
            $this->setOutput(false);
        }

        return $this->output('<div class="card-footer">' . $this->eol);
    }

    /**
     * End the widget footer
     *
     * @param bool $output True to output the footer, false to return it
     * @return mixed An HTML string ending the widget footer, void if the string is output automatically
     */
    public function endFooter($output = true)
    {
        if ($this->footer_open) {
            $this->footer_open = false;
            if ($output) {
                $this->setOutput(false);
            }

            return $this->output('</div>' . $this->eol);
        }
        return null;
    }

    /**
     * Creates the window buttons that appear in the title bar of the widget
     *
     * @return string An HTML string containing the window buttons
     */
    private function buildWidgetButtons()
    {
        $html = null;
        if (!empty($this->widget_buttons)) {
            $html .= '<div class="btn-group float-right">';

            $defaults = [
                'class' => 'btn btn-sm btn-secondary',
                'href' => '#'
            ];

            foreach ($this->widget_buttons as $button) {
                $attributes = array_merge($defaults, (array)$button);
                unset($attributes['icon']);
                $icon = isset($button['icon']) ? $button['icon'] : 'fas fa-cog';
                $html .= '<a' . $this->buildAttributes($attributes) . '><i class="' . $this->_($icon, true)
                    . '"></i></a>';
            }

            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Builds the nav for this widget
     *
     * @return mixed A string of HTML, or void if HTML is output automatically
     */
    private function buildNav()
    {
        $html = null;

        $nav_elements = $this->buildNavElements();
        if ($nav_elements) {
            $html .= '
                <div class="float-left">
                    ' . $nav_elements . '
                </div>' . $this->eol;
        }

        return $html;
    }

    /**
     * Builds the nav elements for this widget
     *
     * @return string A string of HTML
     */
    private function buildNavElements()
    {
        if (empty($this->nav)) {
            return null;
        }

        $nav_class = null;
        switch ($this->nav_type) {
            default:
            case 'links':
                $nav_class = 'card-links';
                break;
            case 'tabs':
                $nav_class = 'nav nav-tabs';
                break;
            case 'pills':
                $nav_class = 'nav nav-pills';
                break;
        }

        $html = '<ul class="' . $nav_class . '">' . $this->eol;
        $i = 0;
        foreach ($this->nav as $element) {
            // Set attributes on the anchor element
            $a_attr = '';
            if (isset($element['attributes'])) {
                $a_attr = $this->buildAttributes($element['attributes']);
            }

            // Set attributes on the list element
            $li_attr = '';
            if ($i == 0 || isset($element['current']) || isset($element['highlight'])) {
                $li_attr = $this->buildAttributes(
                    [
                        'class' => $this->concat(
                            ' ',
                            ((isset($element['current']) ? $element['current'] : null) ? 'active' : ''),
                            ((isset($element['highlight']) ? $element['highlight'] : null) && !(isset($element['current']) ? $element['current'] : null)
                                ? 'highlight'
                                : '')
                        )
                    ]
                );
            }

            $html .= '<li' . $li_attr . '><a' . $a_attr . '>' . (isset($element['name']) ? $element['name'] : null) . '</a></li>' . $this->eol;

            $i++;
        }
        $html .= '</ul>' . $this->eol;

        return $html;
    }

    /**
     * Builds link buttons for use with link navigation
     *
     * @param array $attributes A set of link button HTML attributes (optional)
     * @return string A string of HTML
     */
    private function buildLinkButtons(array $attributes = null)
    {
        $default_attributes = ['class' => 'btn btn-sm btn-light'];
        // Override default attributes
        $attributes = array_merge($default_attributes, (array)$attributes);

        $html = null;
        if (!empty($this->link_buttons)) {
            $html = '<div class="float-right">' . $this->eol;
            foreach ($this->link_buttons as $element) {
                $icon = ['class' => isset($element['icon']) ? $element['icon'] : 'fas fa-plus-circle'];
                $element['attributes'] = array_merge(
                    $attributes,
                    (array)(isset($element['attributes']) ? $element['attributes'] : [])
                );
                $html .= '<a' . $this->buildAttributes($element['attributes']) . '><i' . $this->buildAttributes($icon)
                    . '></i> ' . $this->_($element['name'], true) . '</a>' . $this->eol;
            }
            $html .= '</div>' . $this->eol;
        }

        return $html;
    }

    /**
     * Builds the markup to link style sheets into the DOM using jQuery
     *
     * @return string A string of HTML
     */
    private function buildStyleSheets()
    {
        $html = '';
        if (is_array($this->style_sheets) && !empty($this->style_sheets)) {
            $html .= '<script type="text/javascript">' . $this->eol;
            foreach ($this->style_sheets as $style) {
                $attributes = '';
                $i = 0;
                foreach ($style as $key => $value) {
                    $attributes .= ($i++ > 0 ? ',' . $this->eol : '') . $key . ': "' . $value . '"';
                }

                $html .= '$(document).blestaSetHeadTag("link", { ' . $attributes . ' });' . $this->eol;
            }
            $html .= $this->eol . '</script>';
        }

        return $html;
    }

    /**
     * Builds the filter form for this widget
     *
     * @param bool $output True to output the body, false to return it
     * @return string Html for the widget filtering form
     */
    public function buildFilters($output = true)
    {
        Loader::loadHelpers($this, ['Form']);
        $this->Form->setOutput(true);
        $html = '';
        if (isset($this->filters)) {
            // Wrap filter form in an inner div to ensure proper styling

            $filter_fields = $this->filters->getFields();

            $html .= $this->Form->create(
                $this->filter_uri,
                [
                    'style' => ($this->show_filters ? '' : 'display: none;'),
                    'class' => 'widget_filter_form'
                ]
            );
            $html .= '<div class="card p-3">';

            // Set any hidden fields
            foreach ($filter_fields as $index => $field) {
                foreach ($field->fields as $input) {
                    if (isset($input->params['attributes']['class'])) {
                        $input->params['attributes']['class'] .= ' input-sm';
                    }

                    if ($input->type == 'fieldHidden') {
                        $html .= call_user_func_array([$this->Form, $input->type], $input->params);
                        unset($filter_fields[$index]);
                        continue 2;
                    }
                }
            }

            // Set the input field list
            $i = 1;
            $html .= '<div class="row">';
            foreach ($filter_fields as $field) {
                $html .= $this->buildFilter($field);

                if ($i % 4 == 0) {
                    $html .= '</div><div class="row">';
                }

                $i++;
            }
            $html .= '<div class="col-md-12">';
            // Add the submit button
            $html .= $this->Form->fieldSubmit(
                'submit',
                Language::_('WidgetClient.submit', true),
                ['class' => 'btn btn-light float-right']
            );
            $html .= '</div>';
            $html .= '</div>';

            // Set custom HTML inside the from
            $html .= $this->filter_html;

            $html .= '</div>';
            $html .= $this->Form->end();

            // Add html/js to submit form for all ajax links
            $html .= '
                <script type="text/javascript">
                    $(document).ready(function () {
                        $("' . (isset($this->id) ? '#'. $this->id : '') . '.card").on("click", "a.ajax", function(e) {
                            e.preventDefault();
                            var form = $(this).parents(".card").find("form.widget_filter_form");

                            form.find("input").each(function() {
                                var data_value = $(this).data("value");

                                if (data_value != undefined) {
                                    $(this).attr("value", data_value).val(data_value);
                                }
                            });

                            form.find("select").each(function() {
                                var data_value = $(this).data("value");

                                if (data_value != undefined) {
                                    $(this).val(data_value);
                                }
                            });

                            var action = $(form).attr("action");
                            $(form).attr("action", $(this).attr("href"));
                            $(form).submit();
                            $(form).attr("action", action);
                            return false;
                        });
                    });
                </script>';
            $html .= $this->filters->getHtml();
        } else {
            $html = $this->filter_html;
        }

        // Add html/js to toggle filter form
        $html .= '
            <script type="text/javascript">
                $(document).ready(function () {
                    $("' . (isset($this->id) ? '#'. $this->id . ' ' : '') . '.filter-toggle").click(function () {
                        $(this).parents(".card").find("form.widget_filter_form").toggle();
                    });
                });
            </script>';

        // Add html/js for submitting filters via ajax
        if ($this->ajax_filtering) {
            $html .= $this->ajaxFilteringHtml();
        }

        if ($output) {
            $this->setOutput(false);
        }
        return $this->output($html);
    }

    /**
     * Build the filter input/label/tooltips for a given field
     *
     * @param InputField $field The InputField to build from
     * @return string The html for the input field
     */
    private function buildFilter(InputField $field)
    {
        $html = '<div class="col-md-3 form-group">';

        // Draw the primary label/field
        $field->params['attributes']['class'] = (array)(isset($field->params['attributes']['class']) ? $field->params['attributes']['class'] : []);
        $field->params['attributes']['class'][] = 'control-label';
        $html .= call_user_func_array([$this->Form, $field->type], $field->params);

        // Draw each tooltip
        foreach ($field->fields as $input) {
            // Collect all tooltips to be displayed at the end
            if ($input->type == 'tooltip') {
                $html .= '<a href="#" data-toggle="tooltip" data-html="true" title="'
                    . $this->_($input->params['message'], true) . '">
                        <i class="fas fa-question-circle text-primary"></i>
                    </a>';
            }
        }

        foreach ($field->fields as $input) {
            if (($input->type == 'fieldCheckbox' || $input->type == 'fieldRadio') && isset($input->label)) {
                $html .= '<div class="' . $this->safe($input->type == 'fieldCheckbox' ? 'checkbox' : 'radio') . '">
                        <label>'
                            . call_user_func_array([$this->Form, $input->type], $input->params)
                            . $this->_($input->label->params['name'])
                        . '</label>
                    </div>';
            } else {
                $input->params['attributes']['class'] = (array)(isset($input->params['attributes']['class']) ? $input->params['attributes']['class'] : []);
                $input->params['attributes']['class'][] = 'form-control';

                if ($input->type == 'fieldSelect') {
                    $input->params['attributes']['data-value'] =
                        isset($input->params['selected_value']) ? $input->params['selected_value'] : '';
                } else {
                    $input->params['attributes']['data-value'] =
                        isset($input->params['value']) ? $input->params['value'] : '';
                }

                if ($input->type == 'fieldTextarea') {
                    $input->params['attributes']['rows'] = 5;
                }

                if ($input->type != 'tooltip') {
                    $html .= call_user_func_array([$this->Form, $input->type], $input->params);
                }

                // Show form field's secondary label
                if (isset($input->label)) {
                    $html .= call_user_func_array([$this->Form, 'label'], $input->label->params);
                }
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get the html/js for submitting filtering forms via ajax
     *
     * @return string The ajax html/js
     */
    private function ajaxFilteringHtml()
    {
        return '
            <script type="text/javascript">
                $(document).ready(function () {
                    $("form.widget_filter_form").submit(function(event) {
                        event.preventDefault();
                        var that = this;
                        if ($(this).blestaDisableFormSubmission($(this))) {
                            $(this).blestaRequest("POST", $(this).attr("action"), $(this).serialize(),
                                // On success
                                function(data) {
                                    if (data.hasOwnProperty("replacer") && data.hasOwnProperty("content")) {
                                        $(that).parents(".card").find(data.replacer).html(data.content);
                                    }
                                },
                                null,
                                {dataType: "json", complete: function() { $("form.widget_filter_form").blestaEnableFormSubmission($("form.widget_filter_form")); }}
                            );
                        }
                    });
                });
            </script>';
    }
}

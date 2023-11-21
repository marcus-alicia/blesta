<?php
use Blesta\Core\Util\Input\Fields\InputField;
use Blesta\Core\Util\Widgets\AbstractWidget;
/**
 * Simplifies the creation of widget interfaces
 *
 * @package blesta
 * @subpackage blesta.helpers.widget
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Widget extends AbstractWidget
{
    /**
     * @var string The URI to fetch when requesting the badge value for this widget
     */
    private $badge_uri = null;
    /**
     * @var string The badge value to display for this widget
     */
    private $badge_value = null;
    /**
     * @var array Mapping for widget button classes to their font awesome icons
     */
    private $widget_buttons_map = [
        'filter-toggle' => ['default' => 'fas fa-filter', 'toggled' => 'fas fa-filter'],
        'arrow' => ['default' => 'fas fa-caret-up', 'toggled' => 'fas fa-caret-down'],
        'setting' => ['default' => 'fas fa-cog', 'toggled' => 'fas fa-cog'],
        'full_screen' => ['default' => 'fas fa-expand-arrows-alt', 'toggled' => 'fas fa-compress-arrows-alt']
    ];

    /**
     * Initializes the widget helper
     */
    public function __construct()
    {
        Loader::loadComponents($this, ['Session']);
        Language::setLang($this->Session->read('blesta_language'));
        Language::loadLang('widget');
    }

    /**
     * Clear this widget, making it ready to produce the next widget
     */
    public function clear()
    {
        $this->nav = null;
        $this->nav_type = 'tabs';
        $this->link_buttons = null;
        $this->badge_uri = null;
        $this->widget_buttons = [];
        $this->style_sheets = [];
        $this->render = 'full';
        $this->filters = null;
        $this->filter_html = '';
        $this->filter_uri = '';
        $this->show_filters = null;
        $this->ajax_filtering = null;
    }

    /**
     * Sets navigation tabs within the widget
     *
     * @param array $tabs A multi-dimensional array of tab info including:
     *
     *  - name The name of the tab to be displayed
     *  - current True if this element is currently active
     *  - attributes An array of attributes to set for this tab (e.g. array('href'=>"#"))
     */
    public function setTabs(array $tabs)
    {
        $this->nav = $tabs;
        $this->nav_type = 'tabs';
    }

    /**
     * Sets the URI to request when fetching a badge value for this widget
     *
     * @param string $uri The URI to request for the badge value for this widget
     */
    public function setBadgeUri($uri)
    {
        $this->badge_uri = $uri;
    }

    /**
     * Sets the badge value to appear on this widget, any thing other than null will be displayed
     *
     * @param string $value The value of the badge to be displayed
     */
    public function setBadgeValue($value = null)
    {
        $this->badge_value = $value;
    }

    /**
     * Creates the widget with the given title and attributes
     *
     * @param string $title The title to display for this widget
     * @param array $attributes An list of attributes to set for this widget's primary container
     * @param string $render How to render the widget. Options include:
     *
     *  - full The entire widget
     *  - content_section The full content including nav (everything excluding box frame and title section)
     *  - common_box_content The content only (full_content excluding the nav)
     * @return mixed An HTML string containing the widget, void if the string is output automatically
     */
    public function create($title = null, array $attributes = null, $render = null)
    {
        // Don't output until this section is completely built
        $output = $this->setOutput(true);

        $this->render = ($render == null ? 'full' : $render);

        $default_attributes = ['class' => 'common_box'];

        // Set the attributes, don't allow overwriting the default class, concat instead
        if (isset($attributes['class']) && isset($default_attributes['class'])) {
            $attributes['class'] .= ' ' . $default_attributes['class'];
        }
        $attributes = array_merge($default_attributes, (array)$attributes);

        // Set the badge URI to be displayed
        $badge_uri = $this->badge_uri != ''
            ? '<input type="hidden" name="badge_uri" value="' . $this->_($this->badge_uri, true) . '" />'
            : '';

        // Add the filter form toggle button to the list of widget links
        $this->setFilterLink();

        // Set the widget id
        if (isset($attributes['id'])) {
            $this->id = $attributes['id'];
        } elseif ($title !== null) {
            $this->id = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $attributes['id'] = $this->id;
        }

        // Control which sections are rendered
        $html = '';
        $html .= $this->buildStyleSheets();
        if ($this->render == 'full') {
            $html .= '
				<section' . $this->buildAttributes($attributes) . '>
					' . $badge_uri . '
					<div class="common_box_header">
				        <h2>'
				            . (!empty($this->header_link) ? '<a class="common_box_header_link" href="' . $this->header_link . '">' : '')
				                . '<span>' . $this->_($title, true) . '</span>'
                                . $this->buildBadge() . $this->buildWidgetButtons()
                            . (!empty($this->header_link) ? '</a>' : '')
				        . '</h2>
					</div>
					<div class="common_box_inner">
						<div class="content_section">';
        }

        // Only render nav and common_box_content container if set to do so
        if ($this->render == 'full' || $this->render == 'content_section') {
            $html .= $this->buildNav();
            $html .= $this->buildFilters();
            $html .= '<div class="common_box_content">';
        }

        // Restore output setting
        $this->setOutput($output);

        return $this->output($html);
    }

    /**
     * Sets a row for this widget to be displayed.
     *
     * @param string $left An HTML string to set on the left of the row
     * @param string $right An HTML string to set on the right of the row
     * @return mixed An HTML string containing the row, void if the string is output automatically
     */
    public function setRow($left = null, $right = null)
    {
        $html = '<div class="line">
			<div class="left_section">' . $left . '</div>
			<div class="right_section">' . $right . '</div>
		</div>';

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

        $html = '';

        // Handle special case where links were used as nav
        if ($this->render == 'full' && (!empty($this->nav) || !empty($this->link_buttons))) {
            $html .= '</div>';
        } // end div.inner or div.tabs_content

        if ($this->render == 'full' || $this->render == 'content_section') {
            $html .= '
							</div>
						</div>';
        }
        if ($this->render == 'full') {
            $html .= '
					</div>
					<div class="shadow"></div>
				</section>';
        }

        // Restore output setting
        $this->setOutput($output);

        return $this->output($html);
    }

    /**
     * Creates the window buttons that appear in the title bar of the widget
     *
     * @return string An HTML string containing the window buttons
     */
    private function buildWidgetButtons()
    {
        $num_widget_buttons = count($this->widget_buttons);

        $buttons = '';
        for ($i = 0; $i < $num_widget_buttons; $i++) {
            if (is_array($this->widget_buttons[$i])) {
                $attributes = $this->widget_buttons[$i];
            } else {
                $attributes = ['href' => '#', 'class' => $this->widget_buttons[$i]];
            }

            // Create the icons
            $icons = '';
            if (array_key_exists('class', $attributes)) {
                $icons = $this->buildIcons($attributes['class']);
            }

            $buttons .= '<a' . $this->buildAttributes($attributes) . '>' . $icons . '</a>';
        }
        return $buttons;
    }

    /**
     * Creates a font awesome icon from the given class
     *
     * @param string $class The class name(s) for an icon
     * @return string An HTML string containing the icon
     */
    private function buildIcon($class)
    {
        $html = '';
        $class = trim($class);

        if (!empty($class)) {
            $html = '<i class="' . $this->_($class, true) . ' fa-fw"></i>';
        }

        return $html;
    }

    /**
     * Creates font awesome icons for widget buttons
     *
     * @param string $class The class name(s) for a single button
     * @return string An HTML string containing the icons
     */
    private function buildIcons($class)
    {
        $html = '';
        $icons = [];
        $classes = explode(' ', $class);

        // Choose the first class that matches a widget button as the icon
        foreach ($classes as $class_name) {
            if (array_key_exists(trim($class_name), $this->widget_buttons_map)) {
                $icons = $this->widget_buttons_map[trim($class_name)];
                break;
            }
        }

        if (!empty($icons)) {
            // The toggled icon is hidden by default
            $icons['toggled'] .= ' hidden';

            $html = $this->buildIcon($icons['default']) . $this->buildIcon($icons['toggled']);
        }

        return $html;
    }

    /**
     * Creates the badge value to appear next to the title of the widget
     *
     * @return string An HTML string containing the badge value
     */
    private function buildBadge()
    {
        $html = '';
        if ($this->badge_value !== null) {
            $html = '<strong class="badge badge-danger">' . $this->_($this->badge_value, true) . '</strong>';
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
        if (empty($this->nav) && empty($this->link_buttons)) {
            return null;
        }

        if ($this->nav_type == 'tabs' && !empty($this->nav)) {
            $html = '
				<div class="tabs_row">
					<div class="tabs_nav"><a href="#" class="prev">&nbsp;</a><a href="#" class="next">&nbsp;</a></div>
					<div class="tab_slider">
						' . $this->buildNavElements() . '
					</div>
				</div>
				<div class="tabs_content">' . $this->eol;
        } elseif (!empty($this->nav) || !empty($this->link_buttons)) {
            $html = '
				<div class="inner">
					<div class="links_row">
						' . $this->buildNavElements() . '
						' . $this->buildLinkButtons() . '
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

        $html = '<ul>' . $this->eol;
        $i = 0;
        if (is_array($this->nav)) {
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
                                ($i == 0 ? 'first' : ''),
                                ((isset($element['current']) ? $element['current'] : null) ? 'current' : ''),
                                (
                                    (isset($element['highlight']) ? $element['highlight'] : null) && !(isset($element['current']) ? $element['current'] : null)
                                        ? 'highlight'
                                        : ''
                                )
                            )
                        ]
                    );
                }

                $html .= '<li' . $li_attr . '><a' . $a_attr . '>'
                    . (isset($element['name']) ? $element['name'] : null)
                    . '</a></li>' . $this->eol;

                $i++;
            }
            $html .= '</ul>' . $this->eol;
        }

        return $html;
    }

    /**
     * Builds the filter form for this widget
     *
     * @return string Html for the widget filtering form
     */
    private function buildFilters()
    {
        Loader::loadHelpers($this, ['Form']);
        $this->Form->setOutput(true);
        $html = '';
        if (isset($this->filters)) {
            // Wrap filter form in an inner div to ensure proper styling
            if ($this->nav_type == 'tabs') {
                $html .= '<div class="inner">';
            }

            $filter_fields = $this->filters->getFields();

            $html .= $this->Form->create(
                $this->filter_uri,
                [
                    'style' => ($this->show_filters ? '' : 'display: none;'),
                    'class' => 'widget_filter_form'
               ]
            );
            $html .= '<div class="pad">';

            // Set any hidden fields
            foreach ($filter_fields as $index => $field) {
                foreach ($field->fields as $input) {
                    if ($input->type == 'fieldHidden') {
                        $html .= call_user_func_array([$this->Form, $input->type], $input->params);
                        unset($filter_fields[$index]);
                        continue 2;
                    }
                }
            }

            // Set the input field list
            $i = 1;
            $html .= '<ul class="row">';
            foreach ($filter_fields as $field) {
                $html .= $this->buildFilter($field);

                if ($i % 4 == 0) {
                    $html .= '</ul><ul class="row">';
                }

                $i++;
            }

            // Set custom HTML inside the from
            $html .= '</ul>' . $this->filter_html;

            // Add the submit button
            $html .= $this->Form->fieldSubmit(
                'submit',
                Language::_('Widget.submit', true),
                ['class' => 'btn btn-default pull-right']
            );
            $html .= '</div>';
            $html .= $this->Form->end();

            if ($this->nav_type == 'tabs') {
                $html .= '</div>';
            }

            // Add html/js to submit form for all ajax links
            $html .= '
                <script type="text/javascript">
                    $(document).ready(function () {
                        $("' . (isset($this->id) ? '#'. $this->id : '') . '.common_box").on("click", "a.ajax", function(e) {
                            e.preventDefault();
                            var form = $(this).parents(".common_box").find("form.widget_filter_form");

                            form.find("input").each(function() {
                                var data_value = $(this).data("value");

                                if (data_value != undefined && data_value !== "" && $(this).val == "") {
                                    $(this).attr("value", data_value).val(data_value);
                                }
                            });

                            form.find("select").each(function() {
                                var data_value = $(this).data("value");

                                if (data_value != undefined && data_value !== "" && $(this).val == "") {
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
                        $(this).parents(".common_box").find("form.widget_filter_form").toggle();
                    });
                });
            </script>';

        // Add html/js for submitting filters via ajax
        if ($this->ajax_filtering) {
            $html .= $this->ajaxFilteringHtml();
        }


        return $html;
    }

    /**
     * Build the filter input/label/tooltips for a given field
     *
     * @param InputField $field The InputField to build from
     * @return string The html for the input field
     */
    private function buildFilter(InputField $field)
    {
        $html = '<li class="col-md-3">';
        $tooltips = [];
        foreach ($field->fields as $input) {
            // Collect all tooltips to be displayed for the field
            if ($input->type == 'tooltip') {
                $tooltips[] = $input;
            }
        }

        // Draw the primary label/field
        $html .= call_user_func_array(
            [$this->Form, $field->type],
            array_merge(
                (array)$field->params,
                (!empty($tooltips) ? ['attributes' => ['class' => 'inline']] : [])
            )
        );

        // Draw each form field associated with this label
        foreach ($field->fields as $input) {
            // Collect all tooltips to be displayed at the end
            if ($input->type == 'tooltip') {
                continue;
            }

            // Display a tooltip after the label if there is a label or the field is not a checkbox/radio
            $params = [];
            if (!empty($tooltips)
                && (!empty($field->params['name']) || !in_array($input->type, ['fieldCheckbox', 'fieldRadio']))
            ) {
                $params = (!in_array($input->type, ['fieldCheckbox', 'fieldRadio'])
                    ? ['attributes' => ['class' => 'block']]
                    : []);

                foreach ($tooltips as $tooltip) {
                    $html .= '<span class="tooltip block">'
                            . Language::_('AppController.tooltip.text', true)
                            . '<div>' . $this->_($tooltip->params['message'], true) . '</div>'
                        . '</span>';
                }

                // Radio/checkbox lists should break at a new line
                if (in_array($input->type, ['fieldCheckbox', 'fieldRadio'])) {
                    $html .= '<br />';
                }

                // Reset tooltips, they've already been displayed
                $tooltips = [];
            }

            // Display the form input field
            if ($input->type == 'fieldSelect') {
                $input->params['attributes']['data-value'] =
                    isset($input->params['selected_value']) ? $input->params['selected_value'] : '';
            } else {
                $input->params['attributes']['data-value'] =
                    isset($input->params['value']) ? $input->params['value'] : '';
            }

            $html .= call_user_func_array(
                [$this->Form, $input->type],
                array_merge((array)$input->params, $params)
            );

            // Draw the form field's secondary label if checkbox or radio item
            if (($input->type == 'fieldCheckbox' || $input->type == 'fieldRadio') && isset($input->label)) {
                if (isset($input->label->params['attributes']['class'])) {
                    if (is_array($input->label->params['attributes']['class'])) {
                        $input->label->params['attributes']['class'][] = 'inline';
                    } else {
                        $input->label->params['attributes']['class'] .= ' inline';
                    }
                } else {
                    $input->label->params['attributes']['class'] = 'inline';
                }

                $html .= call_user_func_array([$this->Form, 'label'], $input->label->params);
            }
        }

        // Display tooltips at the end of the field if any exist
        foreach ($tooltips as $tooltip) {
            $html .= '<span class="tooltip">'
                    . Language::_('AppController.tooltip.text', true)
                    . '<div>' . $this->_($tooltip->params['message'], true) . '</div>'
                . '</span>';
        }
        $html .= '</li>';
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
                                        $(that).parents(".common_box").find(data.replacer).html(data.content);
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

    /**
     * Builds link buttons for use with link navigation
     *
     * @return string A string of HTML
     */
    private function buildLinkButtons()
    {
        $default_attributes = ['class' => 'btn btn-default pull-right btn-sm'];

        $html = '';
        if (is_array($this->link_buttons)) {
            foreach ($this->link_buttons as $element) {
                // Set the attributes, don't allow overwriting the default class, concat instead
                if (isset($element['attributes']['class']) && isset($default_attributes['class'])) {
                    $element['attributes']['class'] .= ' ' . $default_attributes['class'];
                }
                $element['attributes'] = array_merge($default_attributes, (array)$element['attributes']);
                $icon = (array_key_exists('icon', $element) ? $element['icon'] : '');

                $html .= '<a' . $this->buildAttributes($element['attributes']) . '>'
                    . $this->buildIcon($icon)
                    . ' <span>'
                    . $this->_($element['name'], true)
                    . '</span></a>' . $this->eol;
            }
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
                //$html .= "$('head').append('<link" . $this->buildAttributes($style) . " />');";
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
                        'class' => 'filter-toggle',
                        'title' => Language::_('Widget.toggle_filters', true)
                    ]
                ];
            }
        }
    }
}

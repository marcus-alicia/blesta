<?php
namespace Blesta\Core\Util\PackageOptions;
use \Configure;
use \stdClass;
use \Language;
use \Loader;
use \View;

class Logic
{
    private $triggers = [];
    private $evaluations = [];
    private $defaults = [];
    private $option_condition_sets = [];
    private $container_selector = 'html';

    public function __construct()
    {
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Initialize the main view
        $this->view = new View('partial_packageoption_js');
        Loader::loadHelpers($this->view, ['Form', 'Html']);
    }

    /**
     * Adds a package option condition set to the current list
     *
     * @param stdClass $condition_set A package option condition set
     * @see \PackageOptionConditionSets::get()
     */
    public function addPackageOptionConditionSet(stdClass $condition_set)
    {
        // Add some aliases
        $option_id = $condition_set->option_id;
        foreach ($condition_set->option_value_ids as $option_value_id) {
            // Create a set of conditions for this option
            if (!isset($this->evaluations[$option_id])) {
                $this->evaluations[$option_id] = [];
            }

            // Create a set of conditions for this option value
            if (!isset($this->evaluations[$option_id][$option_value_id])) {
                $this->evaluations[$option_id][$option_value_id] = [];
            }

            // Set the target element selector for this condition set
            $element_selector = $this->getElementSelector($condition_set->option, $option_value_id);
            $this->evaluations[$option_id][$option_value_id][$condition_set->id] = [
                'target_element' => $element_selector
            ];

            // Set defaults in the list
            foreach ($condition_set->option->values as $value) {
                if ($option_value_id == $value->id && $value->default != 0) {
                    $this->defaults[$element_selector] = $condition_set->option->type == 'quantity'
                        ? $value->default
                        : $value->id;
                    break;
                }
            }

            foreach (($condition_set->conditions ?? []) as $condition) {
                if (is_array($condition->value_id)) {
                    $condition_value_ids = $condition->value_id;
                } else {
                    $condition_value_ids = [$condition->value_id];
                }

                // Create the condition to be evaluated
                $evaluation = [];
                $evaluation['hide_on_disable'] = $condition_set->option_group->hide_options == '1';
                $evaluation['trigger_selectors'] = [];
                foreach ($condition_value_ids as $condition_value_id) {
                    $evaluation['trigger_selectors'][] = $this->getElementSelector(
                        $condition->triggering_option,
                        $condition_value_id
                    );
                }
                $evaluation['operator'] = $condition->operator;
                $evaluation['values'] = $condition->operator == 'in'
                    ? explode(',', $condition->value)
                    : [$condition->value];

                foreach ($condition->triggering_option->values as $trigger_value) {
                    if ($condition->triggering_option->type === 'checkbox') {
                        continue;
                    }

                    if (in_array($trigger_value->id, $condition_value_ids) && !empty($trigger_value->value)) {
                        $evaluation['values'][] = $trigger_value->value;

                        break;
                    }
                }

                // Keep a track of which options to evaluate when a triggering option is changed
                $trigger_element = '*[name=\'configoptions[' . $condition->trigger_option_id . ']\']';
                if (!isset($this->triggers[$trigger_element][$option_id][$option_value_id])) {
                    $this->triggers[$trigger_element][$option_id][$option_value_id] = true;
                }

                $this->evaluations[$option_id][$option_value_id][$condition_set->id][$condition->id] = $evaluation;
            }
        }

        $this->option_condition_sets[] = $condition_set;
    }

    /**
     * Gets the appropriate element selector for an option and value
     *
     * @param stdClass $option The option for which to get a selector
     * @param int $value_id The value for which to get a selector
     * @return string The element selector
     */
    private function getElementSelector($option, $value_id)
    {
        $element_selecter =  '*[name=\'configoptions[' . $option->id . ']\']';
        foreach ($option->values as $value) {
            if ($value->id == $value_id) {
                if ($option->type === 'select') {
                    $element_selecter .= ' option[value=\'' . $value->value . '\'] ';
                } elseif ($option->type === 'radio') {
                    $element_selecter .= '[value=\'' . $value->value . '\'] ';
                }

                break;
            }
        }
        return $element_selecter;
    }

    /**
     * Replaces the current condition list with a list of package option condition sets
     *
     * @param array $condition_sets A list of objects each representing a package option condition set
     * @see \PackageOptionConditionSets::getAll()
     */
    public function setPackageOptionConditionSets(array $condition_sets)
    {
        $this->option_condition_sets = [];
        $this->evaluations = [];
        foreach ($condition_sets as $condition_set) {
            $this->addPackageOptionConditionSet($condition_set);
        }
    }

    /**
     * Sets the selector for the element
     *
     * @param string $container_selector
     */
    public function setOptionContainerSelector($container_selector)
    {
        $this->container_selector = $container_selector;
    }

    /**
     * Creates JS for configurable option display logic
     *
     * @param string $view_dir The directory in which to to look for the partial_packageoption_js.pdt file
     * @return string The JS script tag
     */
    public function getJavascript($view_dir = null)
    {
        if ($view_dir === null) {
            $this->view->setDefaultView(APPDIR);
        }
        $this->view->set('evaluations', json_encode($this->evaluations, JSON_PRETTY_PRINT));
        $this->view->set('triggers', json_encode($this->triggers, JSON_PRETTY_PRINT));
        $this->view->set('defaults', json_encode($this->defaults, JSON_PRETTY_PRINT));
        $this->view->set('container_selector', $this->container_selector);
        $this->view->set('maximum_evaluations', Configure::get('Blesta.max_config_option_logic_evaluations'));

        if ($view_dir !== null) {
            $this->view->setView(null, $view_dir);
        }

        return $this->view->fetch();
    }

    /**
     * Validates a list of configurable option data given the current condition set evaluations
     *
     * @param array $vars A list of submitted configurable option data to validate
     */
    public function validate(array $vars)
    {
        // Autoload the language file
        Language::loadLang(
            'logic',
            Configure::get('Blesta.language'),
            COREDIR . 'Util' . DS . 'PackageOptions' . DS . 'language' . DS
        );

        // Load helpers
        Loader::loadHelpers($this, ['Form']);

        $errors = [];
        $accepted_option_ids = [];

        // Check each condidition set against the submitted values
        // If even one condition set is true for a submitted field/value, then the value is valid regardless of how
        // the other condition sets for the for the field evaluate
        foreach ($this->option_condition_sets as $condition_set) {
            // Skip evaluation if the option has already been accepted or the option was not submitted
            if (isset($accepted_option_ids[$condition_set->option->id]) || !isset($vars[$condition_set->option->id])) {
                continue;
            }

            // Skip evaluation if this condition set doesn't apply to the "Option Value" that was submitted
            if (!$this->submittedValueCoveredByConditionSet($vars, $condition_set)) {
                continue;
            }

            // Start by assuming the submitted value is valid
            $accept_field = true;

            // Check each condition in the set and mark it invalid if any fail
            foreach ($condition_set->conditions as $condition) {
                // Get the option used to validate the submitted field (aka the trigger option)
                $trigger_option = $condition->triggering_option;

                // Get the value that was submitted for the trigger option
                $submitted_trigger_value = isset($vars[$trigger_option->id])
                    ? $vars[$trigger_option->id]
                    : null;

                // Get the value to compare against the one submitted for the trigger option
                $condition_value = $condition->value;

                // Override the value to compare against if the condition depends on a non empty "Option Value"
                foreach ($trigger_option->values as $value) {
                    if ($value->id == $condition->value_id && $value->value !== '' && $value->value !== null) {
                        $condition_value = $value->value;
                    }
                }

                // Assemble a list of valid values based on id if $condition->value_id is an array
                if (is_array($condition->value_id)) {
                    $valid_values = [];
                    foreach ($trigger_option->values as $value) {
                        if (in_array($value->id, $condition->value_id) 
                            && $value->value !== ''
                            && $value->value !== null
                        ) {
                            $valid_values[] = $value->value;
                        }
                    }
                    $condition->value = implode(',', $valid_values);
                }

                // Evaluate the condition
                switch ($condition->operator) {
                    case 'in':
                        $accept_field = in_array($submitted_trigger_value, explode(',', $condition->value));
                        break;
                    case '=':
                        $accept_field = $submitted_trigger_value !== null
                            && ($submitted_trigger_value == $condition_value);
                        break;
                    case '>':
                        $accept_field = $submitted_trigger_value !== null
                            && $submitted_trigger_value > $condition_value;
                        break;
                    case '<':
                        $accept_field = $submitted_trigger_value !== null
                            && $submitted_trigger_value < $condition_value;
                        break;
                    case '!=':
                        $accept_field = $submitted_trigger_value === null
                            || $submitted_trigger_value != $condition_value;
                        break;
                }

                if (!$accept_field) {
                    break;
                }
            }

            if (!$accept_field) {
                $errors[$condition_set->option->id] = [
                    'invalid' => Language::_(
                        'Util.packageoptions.logic.invalid_option',
                        true,
                        $condition_set->option->label
                    )
                ];
            } else {
                $accepted_option_ids[$condition_set->option->id] = $condition_set->option->id;
                unset($errors[$condition_set->option->id]);
            }
        }

        return $errors;
    }

    /**
     * Check if this condition set applies to the "Option Value" that was submitted
     *
     * @param array $vars A list of submitted configurable options
     * @param array $condition_set A condition set
     * @return boolean
     * @see PackageOptionConditionSets::get()
     */
    private function submittedValueCoveredByConditionSet($vars, $condition_set)
    {
        // Get the values of the configurable option from the condition set
        $option_values = $this->Form->collapseObjectArray(
            $condition_set->option->values,
            'value',
            'id'
        );

        // Skip to the next condition set if the option was not submitted
        if (!isset($vars[$condition_set->option_id])) {
            return false;
        }

        // Get the target value (the value submitted for the field being validated)
        $submitted_value = $vars[$condition_set->option_id];

        // Get the actual value of the "Option Values" being validated
        $condition_set_values = [];
        foreach ($condition_set->option_value_ids as $option_value_id) {
            if (array_key_exists($option_value_id, $option_values)) {
                $condition_set_values[] = $option_values[$option_value_id];
            }
        }

        // Evaluate if:
        // - This is a text field which has no "Option Values"
        // - This is a non-text field and the submitted "Option Value" is the one evaluated by this condition set
        if (empty($condition_set_values) || in_array($submitted_value, $condition_set_values)) {
            return true;
        }

        return false;
    }
}

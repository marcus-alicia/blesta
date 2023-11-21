<?php
class FormRules {
    public function __construct()
    {
        Loader::loadComponents($this, ['Input']);
        Language::loadLang(
            'form_rules',
            null,
            dirname(__FILE__) . DS . '..' . DS . 'language' . DS
        );
    }

    /**
     * Validates the submitted vars for the given action
     *
     * @param string $action The action for which to validate vars
     * @param array $vars The submitted input vars to validate
     * @return bool True if the input vars were successfully validated, false otherwise
     */
    public function validate($action, array $vars)
    {
        if (method_exists($this, $action)) {
            $this->Input->setRules($this->{$action}());
            return $this->Input->validates($vars);
        } else {
            return true;
        }
    }

    /**
     * Return all validation errors encountered
     *
     * @return mixed Boolean false if no errors encountered, an array of errors otherwise
     */
    public function errors()
    {
        if (is_object($this->Input) && $this->Input instanceof Input) {
            return $this->Input->errors();
        }
    }

    /**
     * Gets a list of input validation rules for the general action
     *
     * @return array A list of input validation rules
     */
    protected function general()
    {
        return [
            'name' => [
                'format' => [
                    'rule' => ['matches', '/^[a-z0-9_\s]+$/i'],
                    'message' => Language::_('FormRules.general.name.format', true)
                ]
            ],
        ];
    }

    /**
     * Gets a list of input validation rules for the modulebasic action
     *
     * @return array A list of input validation rules
     */
    protected function modulebasic()
    {
        $rules = [
            'module_row' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulebasic.module_row.empty', true)
                ]
            ],
            'module_row_plural' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulebasic.module_row_plural.empty', true)
                ]
            ],
            'module_group' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulebasic.module_group.empty', true)
                ]
            ],
            'authors[][name]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulebasic.authors[][name].empty', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the modulefields action
     *
     * @return array A list of input validation rules
     */
    protected function modulefields()
    {
        $rules = [];
        $array_fields = ['module_rows', 'service_fields', 'package_fields'];
        foreach ($array_fields as $array_field) {
            $rules += [
                $array_field . '[][name]' => [
                    'format' => [
                        'if_set' => true,
                        'rule' => ['matches', '/^([a-z0-9]+_?)*[a-z0-9]+$/'],
                        'message' => Language::_('FormRules.modulefields.' .  $array_field . '[][name].format', true)
                    ]
                ],
                $array_field . '[][label]' => [
                    'empty' => [
                        'if_set' => true,
                        'rule' => 'isEmpty',
                        'negate' => true,
                        'message' => Language::_('FormRules.modulefields.' .  $array_field . '[][label].empty', true)
                    ]
                ],
                $array_field . '[][type]' => [
                    'valid' => [
                        'if_set' => true,
                        'rule' => ['in_array', $this->getFieldTypes()],
                        'message' => Language::_('FormRules.modulefields.' .  $array_field . '[][type].valid', true)
                    ]
                ],
                $array_field . '[][name_key]' => [
                    'valid' => [
                        'if_set' => true,
                        'rule' => ['in_array', ['true', 'false']],
                        'message' => Language::_('FormRules.modulefields.' .  $array_field . '[][name_key].valid', true)
                    ]
                ],
            ];
        }

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the modulefeatures action
     *
     * @return array A list of input validation rules
     */
    protected function modulefeatures()
    {
        $rules = [
            'service_tabs[][method_name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-zA-Z]+$/'],
                    'message' => Language::_('FormRules.modulefeatures.service_tabs[][method_name].format', true)
                ]
            ],
            'service_tabs[][label]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulefeatures.service_tabs[][label].empty', true)
                ]
            ],
            'service_tabs[][level]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['staff', 'client']],
                    'message' => Language::_('FormRules.modulefeatures.service_tabs[][level].valid', true)
                ]
            ],
            'cron_tasks[][name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z]+_?)*[a-z]+$/'],
                    'message' => Language::_('FormRules.modulefeatures.cron_tasks[][name].format', true)
                ]
            ],
            'cron_tasks[][label]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.modulefeatures.cron_tasks[][label].empty', true)
                ]
            ],
            'cron_tasks[][type]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['time', 'interval']],
                    'message' => Language::_('FormRules.modulefeatures.cron_tasks[][type].valid', true)
                ]
            ],
            'cron_tasks[][time]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [
                        function ($time, $type) {
                            if ($type == 'time') {
                                return preg_match('/^[0-9]{1,2}:[0-9]{1,2}(:[0-9]{1,2})?$/', $time);
                            } else {
                                return is_numeric($time);
                            }
                        },
                        ['_linked' => 'cron_tasks[][type]'],
                    ],
                    'message' => Language::_('FormRules.modulefeatures.cron_tasks[][time].format', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the pluginbasic action
     *
     * @return array A list of input validation rules
     */
    protected function pluginbasic()
    {
        $rules = [
            'authors[][name]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.pluginbasic.authors[][name].empty', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the plugindatabase action
     *
     * @return array A list of input validation rules
     */
    protected function plugindatabase()
    {
        $rules = [
            'tables[][name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z]+_?)*[a-z]+$/'],
                    'message' => Language::_('FormRules.plugindatabase.tables[][name].format', true)
                ]
            ],
            'tables[][columns][][name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z]+_?)*[a-z]+$/'],
                    'message' => Language::_('FormRules.plugindatabase.tables[][columns][][name].format', true)
                ]
            ],
            'tables[][columns][][type]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['INT', 'TINYINT', 'VARCHAR', 'TEXT', 'DATETIME', 'ENUM']],
                    'message' => Language::_('FormRules.plugindatabase.tables[][columns][][type].valid', true)
                ]
            ],
            'tables[][columns][][length]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => [
                        function ($length, $type) {
                            if (in_array($type, ['TEXT', 'DATETIME'])) {
                                return empty($length);
                            } elseif ($type == 'ENUM') {
                                return preg_match("/^(('([a-z]+_?)+'),)*('([a-z]+_?)+'){1}$/", $length);
                            } else {
                                return is_numeric($length);
                            }
                        },
                        ['_linked' => 'tables[][columns][][type]'],
                    ],
                    'message' => Language::_('FormRules.plugindatabase.tables[][columns][][length].empty', true)
                ]
            ],
            'tables[][columns][][nullable]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('FormRules.plugindatabase.tables[][columns][][nullable].valid', true)
                ]
            ],
            'tables[][columns][][primary]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['true', 'false']],
                    'message' => Language::_('FormRules.plugindatabase.tables[][columns][][primary].valid', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the pluginintegrations action
     *
     * @return array A list of input validation rules
     */
    protected function pluginintegrations()
    {
        $rules = [
            'actions[][location]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', $this->getActionLocations()],
                    'message' => Language::_('FormRules.pluginintegrations.actions[][location].valid', true)
                ]
            ],
            'actions[][controller]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z]+_?)*[a-z]+$/'],
                    'message' => Language::_('FormRules.pluginintegrations.actions[][controller].format', true)
                ]
            ],
            'actions[][action]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z]+_?)*[a-z]+$/'],
                    'message' => Language::_('FormRules.pluginintegrations.actions[][action].format', true)
                ]
            ],
            'actions[][name]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.pluginintegrations.actions[][name].empty', true)
                ]
            ],
            'events[][event]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.pluginintegrations.events[][event].empty', true)
                ]
            ],
            'events[][callback]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-zA-Z]+$/'],
                    'message' => Language::_('FormRules.pluginintegrations.events[][callback].format', true)
                ]
            ],
            'cards[][level]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['client', 'staff']],
                    'message' => Language::_('FormRules.pluginintegrations.cards[][level].valid', true)
                ]
            ],
            'cards[][callback]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[a-zA-Z]+$/'],
                    'message' => Language::_('FormRules.pluginintegrations.cards[][callback].format', true)
                ]
            ],
            'cards[][label]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.pluginintegrations.cards[][empty].format', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the pluginfeatures action
     *
     * @return array A list of input validation rules
     */
    protected function pluginfeatures()
    {
        return $this->modulefeatures();
    }

    /**
     * Gets a list of input validation rules for the merchantbasic action
     *
     * @return array A list of input validation rules
     */
    protected function merchantbasic()
    {
        return [
            'authors[][name]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.merchantbasic.authors[][name].empty', true)
                ]
            ],
            'currencies[][code]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => Language::_('FormRules.merchantbasic.currencies.format', true)
                ]
            ],
        ];
    }

    /**
     * Gets a list of input validation rules for the merchantfields action
     *
     * @return array A list of input validation rules
     */
    protected function merchantfields()
    {
        $rules = [
            'fields[][name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z0-9]+_?)*[a-z0-9]+$/'],
                    'message' => Language::_('FormRules.merchantfields.fields[][name].format', true)
                ]
            ],
            'fields[][label]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.merchantfields.fields[][label].empty', true)
                ]
            ],
            'fields[][type]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', $this->getFieldTypes()],
                    'message' => Language::_('FormRules.merchantfields.fields[][type].valid', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the merchantfeatures action
     *
     * @return array A list of input validation rules
     */
    protected function merchantfeatures()
    {
        return [];
    }

    /**
     * Gets a list of input validation rules for the nonmerchantbasic action
     *
     * @return array A list of input validation rules
     */
    protected function nonmerchantbasic()
    {
        return [
            'authors[][name]' => [
                'empty' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.nonmerchantbasic.authors[][name].empty', true)
                ]
            ],
            'currencies[][code]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => Language::_('FormRules.nonmerchantbasic.currencies.format', true)
                ]
            ],
        ];
    }

    /**
     * Gets a list of input validation rules for the nonmerchantfields action
     *
     * @return array A list of input validation rules
     */
    protected function nonmerchantfields()
    {
        $rules = [
            'fields[][name]' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['matches', '/^([a-z0-9]+_?)*[a-z0-9]+$/'],
                    'message' => Language::_('FormRules.nonmerchantfields.fields[][name].format', true)
                ]
            ],
            'fields[][label]' => [
                'empty' => [
                    'if_set' => true,
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('FormRules.nonmerchantfields.fields[][label].empty', true)
                ]
            ],
            'fields[][type]' => [
                'valid' => [
                    'if_set' => true,
                    'rule' => ['in_array', $this->getFieldTypes()],
                    'message' => Language::_('FormRules.nonmerchantfields.fields[][type].valid', true)
                ]
            ],
        ];

        return $rules;
    }

    /**
     * Gets a list of input validation rules for the nonmerchantfeatures action
     *
     * @return array A list of input validation rules
     */
    protected function nonmerchantfeatures()
    {
        return [];
    }

    /**
     * Gets a list of field types
     *
     * @return A list of field types
     */
    protected function getFieldTypes()
    {
        return [
            'Text',
            'Textarea',
            'Checkbox'
        ];
    }

    /**
     * Gets a list of action locations
     *
     * @return A list of action locations
     */
    protected function getActionLocations()
    {
        return [
            'nav_primary_staff',
            'nav_secondary_staff',
            'action_staff_client',
            'nav_primary_client',
            'widget_client_home',
            'widget_staff_home',
            'widget_staff_client',
            'widget_staff_billing',
        ];
    }
}
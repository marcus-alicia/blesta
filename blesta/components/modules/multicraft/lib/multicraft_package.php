<?php
/**
 * Multicraft Package actions
 *
 * @package blesta
 * @subpackage blesta.components.modules.multicraft.lib
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MulticraftPackage
{
    /**
     * Initialize
     */
    public function __construct()
    {
        // Load required components
        Loader::loadComponents($this, ['Input']);
    }

    /**
     * Retrieves a list of Input errors, if any
     */
    public function errors()
    {
        return $this->Input->errors();
    }

    /**
     * Fetches the module keys usable in email tags
     *
     * @return array A list of module email tags
     */
    public function getEmailTags()
    {
        return [];
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function add(array $vars = null)
    {
        // Set missing checkboxes
        $checkboxes = [
            'user_jar',
            'user_name',
            'user_schedule',
            'user_ftp',
            'user_visibility',
            'autostart',
            'create_ftp'
        ];
        foreach ($checkboxes as $checkbox) {
            if (empty($vars['meta'][$checkbox])) {
                $vars['meta'][$checkbox] = '0';
            }
        }

        // Set rules to validate input data
        $this->Input->setRules($this->getRules($vars));

        // Build meta data to return
        $meta = [];
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = [
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                ];
            }
        }
        return $meta;
    }

    /**
     * Retrieves a list of JAR directories
     *
     * @param array A key/value array of JAR directories and their names
     */
    public function getJarDirectories()
    {
        return [
            'daemon' => Language::_('MulticraftPackage.package_fields.jardir_daemon', true),
            'server' => Language::_('MulticraftPackage.package_fields.jardir_server', true),
            'server_base' => Language::_('MulticraftPackage.package_fields.jardir_server_base', true)
        ];
    }

    /**
     * Retrieves a list of default roles
     *
     * @param array A key/value array of default roles and their names
     */
    public function getDefaultRoles()
    {
        return [
            '0' => Language::_('MulticraftPackage.package_fields.default_level_0', true),
            '10' => Language::_('MulticraftPackage.package_fields.default_level_10', true),
            '20' => Language::_('MulticraftPackage.package_fields.default_level_20', true),
            '30' => Language::_('MulticraftPackage.package_fields.default_level_30', true)
        ];
    }

    /**
     * Retrieves a list of server visibility options
     *
     * @param array A key/value array of visibility options and their names
     */
    public function getServerVisibilityOptions()
    {
        return [
            '0' => Language::_('MulticraftPackage.package_fields.server_visibility_0', true),
            '1' => Language::_('MulticraftPackage.package_fields.server_visibility_1', true),
            '2' => Language::_('MulticraftPackage.package_fields.server_visibility_2', true)
        ];
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields
     *  to render as well as any additional HTML markup to include
     */
    public function getFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        // Set the server name
        $server_name = $fields->label(
            Language::_('MulticraftPackage.package_fields.server_name', true),
            'multicraft_server_name'
        );
        $server_name->attach(
            $fields->fieldText(
                'meta[server_name]',
                (isset($vars->meta['server_name']) ? $vars->meta['server_name'] : 'Minecraft Server'),
                ['id' => 'multicraft_server_name']
            )
        );
        $fields->setField($server_name);

        // Set the player slots
        $players = $fields->label(Language::_('MulticraftPackage.package_fields.players', true), 'multicraft_players');
        $players->attach(
            $fields->fieldText(
                'meta[players]',
                (isset($vars->meta['players']) ? $vars->meta['players'] : null),
                ['id' => 'multicraft_players']
            )
        );
        $fields->setField($players);

        // Set the memory (in MB)
        $memory = $fields->label(Language::_('MulticraftPackage.package_fields.memory', true), 'multicraft_memory');
        $memory->attach(
            $fields->fieldText(
                'meta[memory]',
                (isset($vars->meta['memory']) ? $vars->meta['memory'] : null),
                ['id' => 'multicraft_memory']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.memory', true));
        $memory->attach($tooltip);
        $fields->setField($memory);

        // Set the JAR file to use
        $jar = $fields->label(Language::_('MulticraftPackage.package_fields.jarfile', true), 'multicraft_jarfile');
        $jar->attach(
            $fields->fieldText(
                'meta[jarfile]',
                (isset($vars->meta['jarfile']) ? $vars->meta['jarfile'] : null),
                ['id' => 'multicraft_jarfile']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.jarfile', true));
        $jar->attach($tooltip);
        $fields->setField($jar);

        // Set the JAR file to use
        $jardir = $fields->label(Language::_('MulticraftPackage.package_fields.jardir', true), 'multicraft_jardir');
        $jardir->attach(
            $fields->fieldSelect(
                'meta[jardir]',
                $this->getJarDirectories(),
                (isset($vars->meta['jardir']) ? $vars->meta['jardir'] : null),
                ['id' => 'multicraft_jardir']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.jardir', true));
        $jardir->attach($tooltip);
        $fields->setField($jardir);

        // Set the JAR owner
        $label = $fields->label(Language::_('MulticraftPackage.package_fields.user_jar', true), 'multicraft_user_jar');
        $user_jar = $fields->fieldCheckbox(
            'meta[user_jar]',
            '1',
            (isset($vars->meta['user_jar']) ? $vars->meta['user_jar'] : null) == '1',
            ['id' => 'multicraft_user_jar'],
            $label
        );
        $blank_label = $fields->label('');
        $blank_label->attach($user_jar);
        $fields->setField($blank_label);

        // Set whether the owner can set the name
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_name', true),
            'multicraft_user_name'
        );
        $user_name = $fields->fieldCheckbox(
            'meta[user_name]',
            '1',
            (isset($vars->meta['user_name']) ? $vars->meta['user_name'] : null) == '1',
            ['id' => 'multicraft_user_name'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_name', true));
        $blank_label = $fields->label('');
        $blank_label->attach($user_name);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set whether the owner can schedule tasks
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_schedule', true),
            'multicraft_user_schedule'
        );
        $user_schedule = $fields->fieldCheckbox(
            'meta[user_schedule]',
            '1',
            (isset($vars->meta['user_schedule']) ? $vars->meta['user_schedule'] : null) == '1',
            ['id' => 'multicraft_user_schedule'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_schedule', true));
        $blank_label = $fields->label('');
        $blank_label->attach($user_schedule);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set whether the owner can give others FTP access
        $label = $fields->label(Language::_('MulticraftPackage.package_fields.user_ftp', true), 'multicraft_user_ftp');
        $user_ftp = $fields->fieldCheckbox(
            'meta[user_ftp]',
            '1',
            (isset($vars->meta['user_ftp']) ? $vars->meta['user_ftp'] : null) == '1',
            ['id' => 'multicraft_user_ftp'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_ftp', true));
        $blank_label = $fields->label('');
        $blank_label->attach($user_ftp);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set whether the owner can set visibility
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.user_visibility', true),
            'multicraft_user_visibility'
        );
        $user_visibility = $fields->fieldCheckbox(
            'meta[user_visibility]',
            '1',
            (isset($vars->meta['user_visibility']) ? $vars->meta['user_visibility'] : null) == '1',
            ['id' => 'multicraft_user_visibility'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.user_visibility', true));
        $blank_label = $fields->label('');
        $blank_label->attach($user_visibility);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set the Default Role to use
        $default_level = $fields->label(
            Language::_('MulticraftPackage.package_fields.default_level', true),
            'multicraft_default_level'
        );
        $default_level->attach(
            $fields->fieldSelect(
                'meta[default_level]',
                $this->getDefaultRoles(),
                (isset($vars->meta['default_level']) ? $vars->meta['default_level'] : '10'),
                ['id' => 'multicraft_default_level']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.default_level', true));
        $default_level->attach($tooltip);
        $fields->setField($default_level);

        // Set whether the server autostarts
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.autostart', true),
            'multicraft_autostart'
        );
        $autostart = $fields->fieldCheckbox(
            'meta[autostart]',
            '1',
            (isset($vars->meta['autostart']) ? $vars->meta['autostart'] : null) == '1',
            ['id' => 'multicraft_autostart'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.autostart', true));
        $blank_label = $fields->label('');
        $blank_label->attach($autostart);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set whether the server autostarts
        $label = $fields->label(
            Language::_('MulticraftPackage.package_fields.create_ftp', true),
            'multicraft_create_ftp'
        );
        $create_ftp = $fields->fieldCheckbox(
            'meta[create_ftp]',
            '1',
            (isset($vars->meta['create_ftp']) ? $vars->meta['create_ftp'] : null) == '1',
            ['id' => 'multicraft_create_ftp'],
            $label
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.create_ftp', true));
        $blank_label = $fields->label('');
        $blank_label->attach($create_ftp);
        $blank_label->attach($tooltip);
        $fields->setField($blank_label);

        // Set the server visibility
        $server_visibility = $fields->label(
            Language::_('MulticraftPackage.package_fields.server_visibility', true),
            'multicraft_server_visibility'
        );
        $server_visibility->attach(
            $fields->fieldSelect(
                'meta[server_visibility]',
                $this->getServerVisibilityOptions(),
                (isset($vars->meta['server_visibility']) ? $vars->meta['server_visibility'] : '1'),
                ['id' => 'multicraft_server_visibility']
            )
        );
        // Add tooltip
        $tooltip = $fields->tooltip(Language::_('MulticraftPackage.package_fields.tooltip.server_visibility', true));
        $server_visibility->attach($tooltip);
        $fields->setField($server_visibility);

        return $fields;
    }

    /**
     * Builds and returns the rules required to add/edit a package
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRules(array $vars)
    {
        $rules = [
            'meta[server_name]' => [
                'format' => [
                    'rule' => 'isEmpty',
                    'negate' => true,
                    'message' => Language::_('MulticraftPackage.!error.meta[server_name].format', true)
                ]
            ],
            'meta[players]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('MulticraftPackage.!error.meta[players].format', true)
                ]
            ],
            'meta[memory]' => [
                'format' => [
                    'rule' => ['matches', '/^[0-9]+$/'],
                    'message' => Language::_('MulticraftPackage.!error.meta[memory].format', true)
                ]
            ],
            'meta[jardir]' => [
                'format' => [
                    'rule' => ['in_array', array_keys($this->getJarDirectories())],
                    'message' => Language::_('MulticraftPackage.!error.meta[jardir].format', true)
                ]
            ],
            'meta[user_jar]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_jar].format', true)
                ]
            ],
            'meta[user_name]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_name].format', true)
                ]
            ],
            'meta[user_schedule]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_schedule].format', true)
                ]
            ],
            'meta[user_ftp]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_ftp].format', true)
                ]
            ],
            'meta[user_visibility]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[user_visibility].format', true)
                ]
            ],
            'meta[default_level]' => [
                'format' => [
                    'rule' => ['in_array', array_keys($this->getDefaultRoles())],
                    'message' => Language::_('MulticraftPackage.!error.meta[default_level].format', true)
                ]
            ],
            'meta[autostart]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[autostart].format', true)
                ]
            ],
            'meta[create_ftp]' => [
                'format' => [
                    'rule' => ['in_array', ['0', '1']],
                    'message' => Language::_('MulticraftPackage.!error.meta[create_ftp].format', true)
                ]
            ],
            'meta[server_visibility]' => [
                'format' => [
                    'rule' => ['in_array', array_keys($this->getServerVisibilityOptions())],
                    'message' => Language::_('MulticraftPackage.!error.meta[server_visibility].format', true)
                ]
            ]
        ];

        return $rules;
    }
}

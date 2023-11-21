
	<ul>{{array:fields}}
        <li>
            <?php
            $this->Form->label($this->_('{{class_name}}.meta.{{fields.name}}', true), '{{fields.name}}'{{if:fields.tooltip:}});{{else:fields.tooltip}}, ['class' => 'inline']);
            ?>
            <span class="tooltip inline-block"><?php $this->_("AppController.tooltip.text");?><div><?php $this->_("{{class_name}}.meta.tooltip_{{fields.name}}");?></div></span>
            <?php{{endif:fields.tooltip}}{{if:fields.type:Checkbox}}
            $this->Form->field{{fields.type}}('{{fields.name}}', 'true', (isset($meta['{{fields.name}}']) ? $meta['{{fields.name}}'] : null) == 'true', ['id' => '{{fields.name}}']);{{else:fields.type}}
            $this->Form->field{{fields.type}}('{{fields.name}}', (isset($meta['{{fields.name}}']) ? $meta['{{fields.name}}'] : null), ['id' => '{{fields.name}}', 'class' => 'block']);{{endif:fields.type}}
            ?>
        </li>{{array:fields}}
	</ul>

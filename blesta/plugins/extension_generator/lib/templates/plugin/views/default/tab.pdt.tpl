{{array:service_tabs}}
    <div id="{{service_tabs.snake_case_name}}">
        <h4><?php $this->_('{{class_name}}.{{service_tabs.method_name}}.heading');?></h4>

        <?php
        $this->Form->create(
//// {{if:service_tabs.level:staff}}$this->base_uri . 'services/manage/' . (isset($service_id) ? $service_id : null) . '/{{service_tabs.snake_case_name}}/'{{else:service_tabs.level}}$this->base_uri . 'clients/serviceTab/' . (isset($client_id) ? $client_id : null) . '/' . (isset($service_id) ? $service_id : null) . '/{{service_tabs.snake_case_name}}/'{{endif:service_tabs.level}}
        );
        ?>
        <div class="col-md-12">
            <p>This is a very simple service management tab, created by the extension generator.  The content
                of this file can be modified at <?php echo PLUGINDIR . '{{snake_case_name}}' . DS . 'views' . DS . 'default' . DS . '{{service_tabs.snake_case_name}}.pdt';?>.
                Included here is a basic form that can be submitted and is handled by the {{service_tabs.method_name}}() method in <?php echo PLUGINDIR . '{{snake_case_name}}' . DS . '{{snake_case_name}}_plugin.php';?>
            </p>
            <div class="button_row">
                <button class="btn btn-default pull-right">
                    <i class="fas fa-edit"></i> <?php $this->_('{{class_name}}.{{service_tabs.method_name}}.submit');?>
                </button>
            </div>
        </div>
        <?php
        $this->Form->end();
        ?>

    </div>
{{array:service_tabs}}
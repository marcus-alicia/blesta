{{array:actions}}        <?php
        echo (isset($message) ? $message : null);

////        For a live example of a widget like this see plugins/support_manager/views/default/admin_tickets.pdt
////
////
////
////        $links = [
////            ['name' => $this->_('{{actions.controller_class}}.{{actions.action}}.category_active', true) . ' <span>(' . (isset($status_count['active']) ? $this->Html->safe($status_count['active'], true) : null) . ')</span>', 'current' => ((isset($status) ? $status : null) == 'active' ? true : false), 'attributes' => ['href' => $this->base_uri . 'plugin/{{snake_case_name}}/{{actions.controller}}/{{actions.action}}/active/', 'class' => 'ajax']],
////        ];
////        $link_buttons = [
////            [
////                'icon' => 'fas fa-plus',
////                'name' => '',
////                'attributes' => [
////                    'title' => $this->_('{{actions.controller_class}}.{{actions.action}}.categorylink_createticket', true),
////                    'href' => $this->Html->safe($this->base_uri . 'plugin/{{snake_case_name}}/{{actions.controller}}/{{actions.action}}/add/')
////                ]
////            ]
////        ];
////
        $this->Widget->clear();
////        $this->Widget->setStyleSheet($this->view_dir . 'css/styles.css');
////        $this->Widget->setLinks($links);
////        $this->Widget->setLinkButtons($link_buttons);
////        $this->Widget->setFilters((isset($filters) ? $filters : null), $this->Html->safe($this->base_uri . 'plugin/{{snake_case_name}}/{{actions.controller}}/{{actions.action}}/'), !empty($filter_vars));
////        $this->Widget->setAjaxFiltering();
////        $this->Widget->create($this->_('{{actions.controller_class}}.{{actions.action}}.boxtitle', true), ['id'=>'{{actions.controller}}'], (isset($render_section) ? $render_section : null));
        $this->Widget->create($this->_('{{actions.controller_class}}.{{actions.action}}.boxtitle', true), ['id'=>'{{actions.controller}}']);
        $this->Form->create($this->base_uri . 'plugin/{{snake_case_name}}/{{actions.controller}}/{{actions.action}}/', ['id' => '{{actions.action}}']);
        ?>

        <div class="col-md-12">
            <p>This is a very simple action page, created by the extension generator.  The content
                of this file can be modified at <?php echo PLUGINDIR . '{{snake_case_name}}' . DS . 'views' . DS . 'default' . DS . '{{actions.controller}}{{if:actions.action:index}}{{else:actions.action}}_{{actions.action}}{{endif:actions.action}}.pdt';?>.
                Included here is a basic form that can be submitted and is handled by the {{actions.action}}() method in <?php echo PLUGINDIR . '{{snake_case_name}}' . DS . 'controllers' . DS . '{{actions.controller}}.php';?>
            </p>
            <div class="button_row">
                <button class="btn btn-default pull-right">
                    <i class="fas fa-edit"></i> <?php $this->_('{{actions.controller_class}}.{{actions.action}}.submit');?>
                </button>
            </div>
        </div>
        <?php
////        // Here we have an example table that lists "objects"
////        if ((isset($objects) ? $objects : false) && ($num_objects = count($objects)) > 0) {
////        ?><!--
////        <table class="table" id="ticket_list">
////            <tr class="heading_row">
////            Enter <td> fields here.  Often these are used for sorting. An example looks like the following.
////                <td><span><a href="<?php
////                    echo $this->Html->safe($this->base_uri . '{{actions.controller}}/{{actions.action}}/' . (isset($status) ? $status : null) . '/?sort=id_code&order=' . ($sort == 'id_code' ? $negate_order : $order));
////                    ?>" class="ajax<?php
////                    echo $this->Html->safe($sort == 'id_code' ? ' ' . $order : '');
////                    ?>"><?php
////                    $this->_('{{actions.controller_class}}.{{actions.action}}.heading_field');
////                    ?></a></span>
////                 </td>
////            </tr>
////             --><?php
////            // Display all objects
////            $i = 0;
////            foreach ($objects as $object) {
////            ?><!--
////            <tr<?php
////                echo ($i++ % 2 == 1) ? ' class="odd_row"' : '';
////                ?>>
////            </tr>
////            --><?php
////            }
////            unset($i);
////            ?><!--
////        </table>
////        --><?php
////            // Set pagination
////            $this->Pagination->build();
////        } else {
////        ?><!--
////        <div class="empty_section">
////            <div class="empty_box">
////                <?php
////                $this->_('{{actions.controller_class}}.{{actions.action}}.no_results');
////                ?>
////            </div>
////        </div>
////        --><?php
////        }
////
        $this->Form->end();
        $this->Widget->end();
{{array:actions}}
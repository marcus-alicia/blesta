        <?php
        $this->Widget->clear();
        $this->Widget->setLinkButtons([]);
        $this->Widget->create($this->_('{{class_name}}.edit_row.box_title', true));
        ?>
        <div class="inner">
            <?php
            $this->Form->create();
            ?>
            <div class="pad">
                <ul>{{array:module_rows}}
                    <li>
                        <?php
                        $this->Form->label($this->_('{{class_name}}.row_meta.{{module_rows.name}}', true), '{{module_rows.name}}'{{if:module_rows.tooltip:}});{{else:module_rows.tooltip}}, ['class' => 'inline']);
                        ?>
                        <span class="tooltip inline-block"><?php $this->_("AppController.tooltip.text");?><div><?php $this->_("{{class_name}}.row_meta.tooltip.{{module_rows.name}}");?></div></span>
                        <?php{{endif:module_rows.tooltip}}{{if:module_rows.type:Checkbox}}
                        $this->Form->field{{module_rows.type}}('{{module_rows.name}}', 'true', (isset($vars->{{module_rows.name}}) ? $vars->{{module_rows.name}} : null) == 'true', ['id' => '{{module_rows.name}}']);{{else:module_rows.type}}
                        $this->Form->field{{module_rows.type}}('{{module_rows.name}}', (isset($vars->{{module_rows.name}}) ? $vars->{{module_rows.name}} : null), ['id' => '{{module_rows.name}}', 'class' => 'block']);{{endif:module_rows.type}}
                        ?>
                    </li>{{array:module_rows}}
                </ul>
            </div>{{if:code_examples:1}}<?php{{endif:code_examples}}
////// Many modules will include the ability to set nameservers.  Following is a table to do that.
////// Be sure to uncomment the JS below and visit the {{snake_case_name}}.php language file to
////// uncomment the language definitions for this table
////?><!--
////            <div class="title_row">
////                <h3><?php $this->_('{{class_name}}.edit_row.name_servers_title'); ?></h3>
////            </div>
////            <div class="pad">
////                <div class="links_row">
////                    <a class="btn btn-default pull-right btn-sm ns_row_add" href="#"><i class="fas fa-plus"></i> <span><?php $this->_('{{class_name}}.edit_row.name_server_btn'); ?></span></a>
////                </div>
////                <table class="table">
////                    <thead>
////                        <tr class="heading_row">
////                            <td><?php $this->Form->label($this->_('{{class_name}}.edit_row.name_server_col', true)); ?></td>
////                            <td><?php $this->Form->label($this->_('{{class_name}}.edit_row.name_server_host_col', true)); ?></td>
////                            <td class="last"></td>
////                        </tr>
////                    </thead>
////                    <tbody>
////                        <?php
////                        $num_servers = count((isset($vars->name_servers) ? $vars->name_servers : []));
////                        for ($i = 0; $i < max(2, $num_servers); $i++) {
////                            ?>
////                        <tr class="ns_row<?php
////                            echo ($i % 2 == 1) ? ' odd_row' : '';
////                            ?>">
////                            <td><?php
////                                $this->_('{{class_name}}.edit_row.name_server', false, '<span>' . ($i + 1) . '</span>'); ?>
////                            </td>
////                            <td><?php
////                                $this->Form->fieldText('name_servers[]', (isset($vars->name_servers[$i]) ? $vars->name_servers[$i] : null)); ?>
////                            </td>
////                            <td><a href="#" class="manage ns_row_remove"><?php $this->_('{{module_rows.name}}.edit_row.remove_name_server'); ?></a></td>
////                        </tr>
////                        <?php
////                        }
////                        ?>
////                    </tbody>
////                </table>
////            </div>
////--><?php
////
////// Modules also occasionally include a notes field to give more details on the module row (server info and such).
////// Visit the {{snake_case_name}}.php language file to uncomment the language definition for this field
////?><!--
////            <div class="title_row">
////                <h3><?php $this->_('{{class_name}}.edit_row.notes_title'); ?></h3>
////            </div>
////            <div class="pad">
////                <ul>
////                    <li>
////                        <?php
////                        $this->Form->fieldTextarea('notes', (isset($vars->notes) ? $vars->notes : null));
////                        ?>
////                    </li>
////                </ul>
////            </div>
////-->

            <div class="button_row">
                <?php
                $this->Form->fieldSubmit('save', $this->_('{{class_name}}.edit_row.edit_btn', true), ['class' => 'btn btn-primary pull-right']);
                ?>
            </div>
            <?php
            $this->Form->end();
            ?>
        </div>
        <?php
        $this->Widget->end();
        ?>{{if:code_examples:1}}<?php{{endif:code_examples}}
////
////// This is the supporting JS for the nameserver table
////?><!--
////<script type="text/javascript">
////$(document).ready(function() {
////    // Add a row
////    $('.ns_row_add').click(function() {
////        var fields = $('tr.ns_row:first').clone(true);
////        $(fields).find('input').val('');
////        $('td:first span', fields).text($('tr.ns_row').length+1);
////        $('tr.ns_row:last').after(fields);
////        updateNsRows();
////        return false;
////    });
////    // Remove a row
////    $('.ns_row_remove').click(function() {
////        if ($('tr.ns_row').length > 1) {
////            $(this).closest('tr').remove();
////            // Reorder the counts for these rows
////            $i=1;
////            $('tr.ns_row').each(function() {
////                $('td:first span', this).text($i++);
////            });
////        }
////        updateNsRows();
////        return false;
////    });
////});
////
////// Zebra-stripe name server rows
////function updateNsRows() {
////    var i = 0;
////    $('tr.ns_row').each(function() {
////        if (i++%2 == 1)
////            $(this).addClass('odd_row');
////        else
////            $(this).removeClass('odd_row');
////    });
////}
////</script>
////-->

/**
 * Mass Mailer jQuery extension
 *
 * @copyright Copyright (c) 2016, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
(function($) {

  $(document).ready(function() {
    $(this).MassMailerBindToggleServiceFilters();
    $(this).MassMailerBindToggleServiceType();
    $(this).MassMailerBindSetPackages();
    $(this).MassMailerBindMovePackages();
    $(this).MassMailerBindModuleRows();
    $(this).MassMailerBindFilterSubmit();
    $(this).MassMailerBindWysiwyg();

    $('#email .tab_content').blestaTabbedContent();
  });

  $.fn.extend({
    /**
     * Toggles service filter options
     */
    MassMailerBindToggleServiceFilters: function() {
      toggleServices();
      $('#filter_services').on('change', function() {
        toggleServices();
      });

      function toggleServices() {
        if ($('#filter_services').prop('checked')) {
          $('#service_filters').show();
        } else {
          $('#service_filters').hide();
        }
      }
    },
    /**
     * Toggles the package/module options
     */
    MassMailerBindToggleServiceType: function() {
      toggleServiceType();
      $('.service_parent_type').on('change', function() {
        toggleServiceType();
      });

      function toggleServiceType() {
        var type = $('.service_parent_type:checked').val();

        if (type === 'module') {
          $('#package_options').hide();
          $('#module_options').show();
        } else {
          $('#package_options').show();
          $('#module_options').hide();
        }
      }
    },
    /**
     * Updates the package multi-select with packages from
     * the selected package group
     */
    MassMailerBindSetPackages: function() {
      setAvailablePackages();
      $('#package_group').change(function() {
        // Remove all available packages
        $('#pool').append($('#available option'));

        setAvailablePackages();
      });

      function setAvailablePackages() {
        // Show the available items for this group
        var selected_group_id = $('#package_group option:selected').val();

        // Select all packages
        if (selected_group_id == '') {
          $('#available').append($('#pool option'));
        } else {
          // Select specific group packages
          $('#available').append($('#pool option.group_' + selected_group_id));
        }
      }
    },
    /**
     * Moves packages from available to assigned
     */
    MassMailerBindMovePackages: function() {
      // Move packages from right to left
      $('.move_left').click(function() {
          // Move right to left
          $('#available option:selected').appendTo($('#packages'));
          return false;
      });
      // Move packages from left to right
      $('.move_right').click(function() {
          $('#packages option:selected').appendTo($('#available'));
          return false;
      });
    },
    /**
     * Loads module rows based on selected module
     */
    MassMailerBindModuleRows: function() {
      $('#module_id').on('change', function() {
        if ($(this).val() == '') {
          // Remove all options
          clearModuleRows();
        } else {
          // Fetch the options
          fetchModuleRows();
        }
      });

      function fetchModuleRows() {
        // Determine the URI based on the form's URI
        var plugin_uri = $('#module_id').closest('form').attr('action');
        plugin_uri = plugin_uri.replace(/(\/plugin\/mass_mailer)(.*)/i, '')
          + '/plugin/mass_mailer/admin_filter/modulerows/'
          + $('#module_id').val();

        var module_id = $('#module_id').val();

        $(document).blestaRequest(
          'GET',
          plugin_uri,
          null,
          function(data) {
            // Remove previous rows
            clearModuleRows();

            // Add new ones
            $.each(data, function(index, value) {
              // Each option is selected by default
              var option = new Option(value, index);
              option.selected = true;

              $('#module_rows').append(option);
            });
          },
          null,
          {dataType:'json'}
        );
      }

      function clearModuleRows() {
        $('#module_rows option').remove();
      }
    },
    /**
     * Performs pre-submit from modifications
     */
    MassMailerBindFilterSubmit: function() {
      $('#mass_mailer_filters').on('submit', function() {
        // Ensure all selected packages are selected
        $('#packages option').prop('selected', 'selected');
      });
    },
    /**
     * Makes the WYSIWYG available to the view
     */
    MassMailerBindWysiwyg: function() {
      var options = {};

      if ($('#html').length) {
        // Set the language for the WYSIWYG
        var language = $('#html').data('language');
        if (language && language.length) {
          options.language = language;
        }

        $('#html').blestaBindWysiwygEditor(options);
      }
    }
  });
})(jQuery);

{{array:controllers}}
<?php
/**
 * {{name}} {{controllers.snake_case_name}} controller
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{controllers.class_name}} extends {{class_name}}Controller
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->structure->set('page_title', Language::_('{{controllers.class_name}}.index.page_title', true));
    }{{array:controllers.actions}}

    /**
     * Returns the view for a list of extensions
     */
    public function {{controllers.actions.action}}()
    {
////        // This statement used to load a model from the plugin to this
////        // object making it accessible via $this->{{class_name}}Objects
////        $this->uses(['{{class_name}}.{{class_name}}Objects']);
////
////        // Set current page of results
////        $page = (isset($this->get[1]) ? (int) $this->get[1] : 1);
////        $sort = (isset($this->get['sort']) ? $this->get['sort'] : 'date_updated');
////        $order = (isset($this->get['order']) ? $this->get['order'] : 'desc');
////
////        $objects = $this->{{class_name}}Objects->getList(
////            ['company_id' => Configure::get('Blesta.company_id')],
////            $page, [$sort => $order]
////        );
////        $total_results = $this->{{class_name}}Objects->getListCount(['company_id' => Configure::get('Blesta.company_id')]);
////
////        $this->set('objects', $objects);
////        $this->set('sort', $sort);
////        $this->set('order', $order);
////        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
////
////        // Overwrite default pagination settings
////        $settings = array_merge(
////            Configure::get('Blesta.pagination'),
////            [
////                'total_results' => $total_results,
////                'uri' => $this->base_uri . 'plugin/{{snake_case_name}}/{{controllers.actions.controller}}/{{controllers.actions.action}}/[p]/',
////                'params' => ['sort' => $sort, 'order' => $order],
////            ]
////        );
////        $this->setPagination($this->get, $settings);
////
////        // This helps with sorting list results in a widget.  It lets the page reload via ajax
////        // while only updating the table items and not the whole page
////        return $this->renderAjaxWidgetIfAsync(
////            isset($this->get['sort']) ? true : (isset($this->get[1]) || isset($this->get[0]) ? false : null)
////        );
        return $this->renderAjaxWidgetIfAsync(false);
    }{{array:controllers.actions}}
}{{array:controllers}}

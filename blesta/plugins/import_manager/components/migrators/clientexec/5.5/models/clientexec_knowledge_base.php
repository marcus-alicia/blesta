<?php
/**
 * Generic Clientexec Knowledge Base Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecKnowledgeBase
{
    /**
     * ClientexecKnowledgeBase constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all knowledge base categories.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('kb_categories')->getStatement()->fetchAll();
    }

    /**
     * Get all knowledge base articles from an specific category.
     *
     * @param mixed $category_id
     * @return mixed The result of the sql transaction
     */
    public function getArticles($category_id)
    {
        return $this->remote->select()->from('kb_articles')->where('categoryid', '=', $category_id)->getStatement()->fetchAll();
    }
}

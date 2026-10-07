<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Menu\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->columns([
            'item_count' => new \Zend_Db_Expr($this->getItemCountSql())
        ]);

        return $this;
    }

    public function getItemCountSql(): string
    {
        $tableCount = '(SELECT COUNT(*) FROM ' . $this->getTable('panth_megamenu_item') .
            ' WHERE menu_id = main_table.menu_id)';

        return '(CASE WHEN ' . $tableCount . ' = 0 AND JSON_VALID(main_table.items_json)' .
            ' THEN COALESCE(JSON_LENGTH(main_table.items_json), 0)' .
            ' ELSE ' . $tableCount . ' END)';
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'item_count') {
            $this->getSelect()->where(
                $this->getConnection()->prepareSqlCondition($this->getItemCountSql(), $condition)
            );
            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            if ($item->getData('item_count') == 0 && $item->getData('items_json')) {
                $itemsJson = $item->getData('items_json');
                $items = json_decode($itemsJson, true);
                if (is_array($items)) {
                    $item->setData('item_count', count($items));
                }
            }
        }

        return $this;
    }
}

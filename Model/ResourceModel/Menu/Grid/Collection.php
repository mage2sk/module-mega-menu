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
            'item_count' => new \Zend_Db_Expr(
                '(SELECT COUNT(*) FROM ' . $this->getTable('panth_megamenu_item') .
                ' WHERE menu_id = main_table.menu_id)'
            )
        ]);

        return $this;
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

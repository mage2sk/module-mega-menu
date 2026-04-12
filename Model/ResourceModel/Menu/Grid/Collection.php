<?php
/**
 * Menu Grid Collection
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Menu\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        // Add item count as a subquery from panth_megamenu_item table
        // If items_json is used instead, we'll calculate it in _afterLoad
        $this->getSelect()->columns([
            'item_count' => new \Zend_Db_Expr(
                '(SELECT COUNT(*) FROM ' . $this->getTable('panth_megamenu_item') .
                ' WHERE menu_id = main_table.menu_id)'
            )
        ]);

        return $this;
    }

    /**
     * After load processing - calculate item count from JSON if needed
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        foreach ($this->_items as $item) {
            // If item_count is 0 but items_json exists, count JSON items
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

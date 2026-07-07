<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Menu;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\MegaMenu\Model\Menu as MenuModel;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'menu_id';

    protected $_eventPrefix = 'panth_megamenu_menu_collection';

    protected $_eventObject = 'menu_collection';

    protected function _construct()
    {
        $this->_init(MenuModel::class, MenuResource::class);
        $this->_map['fields']['menu_id'] = 'main_table.menu_id';
        $this->_map['fields']['store_id'] = 'store_table.store_id';
    }

    public function addStoreFilter($storeId, bool $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($storeId, $withAdmin);
            $this->setFlag('store_filter_added', true);
        }
        return $this;
    }

    protected function performAddStoreFilter($storeId, bool $withAdmin = true)
    {
        if ($storeId instanceof \Magento\Store\Model\Store) {
            $storeId = [$storeId->getId()];
        }

        if (!is_array($storeId)) {
            $storeId = [$storeId];
        }

        if ($withAdmin) {
            $storeId[] = 0;
        }

        $this->addFilter('store_id', ['in' => $storeId], 'public');
    }

    protected function _renderFiltersBefore()
    {
        $this->joinStoreRelationTable();
        parent::_renderFiltersBefore();
        return $this;
    }

    protected function joinStoreRelationTable()
    {
        if (!$this->getFlag('store_table_joined')) {
            $this->getSelect()->joinLeft(
                ['store_table' => $this->getTable(MenuResource::STORE_TABLE)],
                'main_table.menu_id = store_table.menu_id',
                []
            )->group('main_table.menu_id');

            $this->setFlag('store_table_joined', true);
        }
    }

    public function addActiveFilter()
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function addIdentifierFilter(string $identifier)
    {
        $this->addFieldToFilter('identifier', $identifier);
        return $this;
    }

    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        parent::load($printQuery, $logQuery);

        $this->loadStoreIds();

        return $this;
    }

    protected function loadStoreIds()
    {
        if ($this->getSize()) {
            $menuIds = $this->getColumnValues('menu_id');

            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getTable(MenuResource::STORE_TABLE))
                ->where('menu_id IN (?)', $menuIds);

            $storeRelations = $connection->fetchAll($select);

            $storeIdsByMenu = [];
            foreach ($storeRelations as $relation) {
                $menuId = $relation['menu_id'];
                if (!isset($storeIdsByMenu[$menuId])) {
                    $storeIdsByMenu[$menuId] = [];
                }
                $storeIdsByMenu[$menuId][] = $relation['store_id'];
            }

            foreach ($this as $menu) {
                $menuId = $menu->getId();
                if (isset($storeIdsByMenu[$menuId])) {
                    $menu->setData('store_ids', $storeIdsByMenu[$menuId]);
                } else {
                    $menu->setData('store_ids', []);
                }
            }
        }
    }
}

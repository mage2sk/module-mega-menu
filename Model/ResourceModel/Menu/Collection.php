<?php
/**
 * Menu Collection
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Menu;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\MegaMenu\Model\Menu as MenuModel;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'menu_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_menu_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'menu_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(MenuModel::class, MenuResource::class);
        $this->_map['fields']['menu_id'] = 'main_table.menu_id';
        $this->_map['fields']['store_id'] = 'store_table.store_id';
    }

    /**
     * Add store filter to collection
     *
     * @param int|array $storeId
     * @param bool $withAdmin
     * @return $this
     */
    public function addStoreFilter($storeId, bool $withAdmin = true)
    {
        if (!$this->getFlag('store_filter_added')) {
            $this->performAddStoreFilter($storeId, $withAdmin);
            $this->setFlag('store_filter_added', true);
        }
        return $this;
    }

    /**
     * Perform store filter
     *
     * @param int|array $storeId
     * @param bool $withAdmin
     * @return void
     */
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

    /**
     * Join store relation table
     *
     * @return $this
     */
    protected function _renderFiltersBefore()
    {
        $this->joinStoreRelationTable();
        parent::_renderFiltersBefore();
        return $this;
    }

    /**
     * Join store relation table
     *
     * @return void
     */
    protected function joinStoreRelationTable()
    {
        if (!$this->getFlag('store_table_joined')) {
            // Use LEFT JOIN instead of INNER JOIN to include menus without store relations
            // This fixes the issue where menus with no store relations would not be loaded
            $this->getSelect()->joinLeft(
                ['store_table' => $this->getTable(MenuResource::STORE_TABLE)],
                'main_table.menu_id = store_table.menu_id',
                []
            )->group('main_table.menu_id');

            $this->setFlag('store_table_joined', true);
        }
    }

    /**
     * Add active filter
     *
     * @return $this
     */
    public function addActiveFilter()
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    /**
     * Add identifier filter
     *
     * @param string $identifier
     * @return $this
     */
    public function addIdentifierFilter(string $identifier)
    {
        $this->addFieldToFilter('identifier', $identifier);
        return $this;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        parent::load($printQuery, $logQuery);

        // Load store IDs for each menu
        $this->loadStoreIds();

        return $this;
    }

    /**
     * Load store IDs for all menus
     *
     * @return void
     */
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

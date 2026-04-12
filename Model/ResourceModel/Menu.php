<?php
/**
 * Menu Resource Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Menu extends AbstractDb
{
    /**
     * Store relation table
     */
    const STORE_TABLE = 'panth_megamenu_store';

    /**
     * @param Context $context
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('panth_megamenu_menu', 'menu_id');
    }

    /**
     * Process menu data before save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        // Convert store IDs array to string for storage
        if ($object->hasData('store_ids') && is_array($object->getData('store_ids'))) {
            $object->setData('store_ids', implode(',', $object->getData('store_ids')));
        }

        return parent::_beforeSave($object);
    }

    /**
     * Save store relation
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $this->saveStoreRelation($object);
        return parent::_afterSave($object);
    }

    /**
     * Load store relation
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $this->loadStoreRelation($object);
        return parent::_afterLoad($object);
    }

    /**
     * Delete store relation on menu delete
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $this->deleteStoreRelation($object);
        return parent::_beforeDelete($object);
    }

    /**
     * Save store relation
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function saveStoreRelation(AbstractModel $object)
    {
        $menuId = $object->getId();
        $storeIds = $object->getStoreIds();

        if (!is_array($storeIds)) {
            $storeIds = $storeIds ? explode(',', $storeIds) : [];
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);

        // Delete old relations
        $connection->delete($table, ['menu_id = ?' => $menuId]);

        // Insert new relations
        if (!empty($storeIds)) {
            $data = [];
            foreach ($storeIds as $storeId) {
                $data[] = [
                    'menu_id' => $menuId,
                    'store_id' => $storeId
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load store relation
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function loadStoreRelation(AbstractModel $object)
    {
        $menuId = $object->getId();
        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);

        $select = $connection->select()
            ->from($table, 'store_id')
            ->where('menu_id = ?', $menuId);

        $storeIds = $connection->fetchCol($select);
        $object->setData('store_ids', $storeIds);
    }

    /**
     * Delete store relation
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function deleteStoreRelation(AbstractModel $object)
    {
        $menuId = $object->getId();
        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);

        $connection->delete($table, ['menu_id = ?' => $menuId]);
    }

    /**
     * Load menu by identifier and store
     *
     * @param string $identifier
     * @param int|null $storeId
     * @return array
     */
    public function loadByIdentifier(string $identifier, ?int $storeId = null): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['main' => $this->getMainTable()])
            ->where('main.identifier = ?', $identifier)
            ->where('main.is_active = ?', 1);

        if ($storeId !== null) {
            $select->joinLeft(
                ['store' => $this->getTable(self::STORE_TABLE)],
                'main.menu_id = store.menu_id',
                []
            )->where('store.store_id IN (?)', [0, $storeId]);
        }

        $select->limit(1);

        return $connection->fetchRow($select) ?: [];
    }
}

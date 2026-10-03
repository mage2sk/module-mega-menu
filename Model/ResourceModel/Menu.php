<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Menu extends AbstractDb
{
    const STORE_TABLE = 'panth_megamenu_store';

    public function __construct(
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('panth_megamenu_menu', 'menu_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->hasData('store_ids') && is_array($object->getData('store_ids'))) {
            $object->setData('store_ids', implode(',', $object->getData('store_ids')));
        }

        return parent::_beforeSave($object);
    }

    protected function _afterSave(AbstractModel $object)
    {
        $this->saveStoreRelation($object);
        return parent::_afterSave($object);
    }

    protected function _afterLoad(AbstractModel $object)
    {
        $this->loadStoreRelation($object);
        return parent::_afterLoad($object);
    }

    protected function _beforeDelete(AbstractModel $object)
    {
        $this->deleteStoreRelation($object);
        return parent::_beforeDelete($object);
    }

    protected function saveStoreRelation(AbstractModel $object)
    {
        $menuId = $object->getId();
        $storeIds = $object->getStoreIds();

        if (!is_array($storeIds)) {
            $storeIds = $storeIds ? explode(',', $storeIds) : [];
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);

        $connection->delete($table, ['menu_id = ?' => $menuId]);

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

    protected function deleteStoreRelation(AbstractModel $object)
    {
        $menuId = $object->getId();
        $connection = $this->getConnection();
        $table = $this->getTable(self::STORE_TABLE);

        $connection->delete($table, ['menu_id = ?' => $menuId]);
    }

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

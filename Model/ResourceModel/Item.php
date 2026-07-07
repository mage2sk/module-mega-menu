<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Item extends AbstractDb
{
    public function __construct(
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('panth_megamenu_item', 'item_id');
    }

    protected function _beforeSave(AbstractModel $object)
    {
        $this->calculatePathAndLevel($object);

        return parent::_beforeSave($object);
    }

    protected function _beforeDelete(AbstractModel $object)
    {
        $this->deleteChildren($object);
        return parent::_beforeDelete($object);
    }

    protected function calculatePathAndLevel(AbstractModel $object)
    {
        $parentId = $object->getParentId();

        if ($parentId) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getMainTable(), ['path', 'level'])
                ->where('item_id = ?', $parentId);

            $parent = $connection->fetchRow($select);

            if ($parent) {
                $parentPath = $parent['path'];
                $parentLevel = (int)$parent['level'];

                $object->setLevel($parentLevel + 1);

                if ($object->getId()) {
                    $object->setPath($parentPath . '/' . $object->getId());
                } else {
                    $object->setData('_parent_path', $parentPath);
                }
            } else {
                $object->setLevel(0);
                $object->setParentId(null);
            }
        } else {
            $object->setLevel(0);
        }

        if (!$parentId && $object->getId()) {
            $object->setPath((string)$object->getId());
        }
    }

    protected function _afterSave(AbstractModel $object)
    {
        if (!$object->getOrigData('item_id') && $object->getId()) {
            $parentPath = $object->getData('_parent_path');

            if ($parentPath) {
                $path = $parentPath . '/' . $object->getId();
            } else {
                $path = (string)$object->getId();
            }

            $connection = $this->getConnection();
            $connection->update(
                $this->getMainTable(),
                ['path' => $path],
                ['item_id = ?' => $object->getId()]
            );

            $object->setPath($path);
        }

        if ($object->dataHasChangedFor('parent_id') || $object->dataHasChangedFor('item_id')) {
            $this->updateChildrenPaths($object);
        }

        return parent::_afterSave($object);
    }

    protected function updateChildrenPaths(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $itemId = $object->getId();

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('parent_id = ?', $itemId);

        $children = $connection->fetchAll($select);

        foreach ($children as $child) {
            $childPath = $object->getPath() . '/' . $child['item_id'];
            $childLevel = $object->getLevel() + 1;

            $connection->update(
                $this->getMainTable(),
                [
                    'path' => $childPath,
                    'level' => $childLevel
                ],
                ['item_id = ?' => $child['item_id']]
            );

            $childObject = new \Magento\Framework\DataObject([
                'item_id' => $child['item_id'],
                'path' => $childPath,
                'level' => $childLevel
            ]);
            $this->updateChildrenPaths($childObject);
        }
    }

    protected function deleteChildren(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $itemId = $object->getId();

        $connection->delete(
            $this->getMainTable(),
            [
                'path LIKE ?' => $object->getPath() . '/%',
                'item_id != ?' => $itemId
            ]
        );
    }

    public function reindexPositions(int $menuId, ?int $parentId = null)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['item_id'])
            ->where('menu_id = ?', $menuId);

        if ($parentId === null) {
            $select->where('parent_id IS NULL');
        } else {
            $select->where('parent_id = ?', $parentId);
        }

        $select->order('position ASC');

        $items = $connection->fetchCol($select);

        $position = 0;
        foreach ($items as $itemId) {
            $connection->update(
                $this->getMainTable(),
                ['position' => $position],
                ['item_id = ?' => $itemId]
            );
            $position++;
        }
    }

    public function moveItem(int $itemId, ?int $newParentId, int $position)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('item_id = ?', $itemId);

        $item = $connection->fetchRow($select);

        if (!$item) {
            return;
        }

        $oldParentId = $item['parent_id'];
        $menuId = $item['menu_id'];

        $connection->update(
            $this->getMainTable(),
            [
                'parent_id' => $newParentId,
                'position' => $position
            ],
            ['item_id = ?' => $itemId]
        );

        $itemObject = new \Magento\Framework\DataObject($item);
        $itemObject->setId($itemId);
        $itemObject->setParentId($newParentId);
        $this->calculatePathAndLevel($itemObject);

        $connection->update(
            $this->getMainTable(),
            [
                'path' => $itemObject->getPath(),
                'level' => $itemObject->getLevel()
            ],
            ['item_id = ?' => $itemId]
        );

        $this->updateChildrenPaths($itemObject);

        if ($oldParentId !== $newParentId) {
            $this->reindexPositions($menuId, $oldParentId);
        }

        $this->reindexPositions($menuId, $newParentId);
    }

    public function getMaxPosition(int $menuId, ?int $parentId = null): int
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), 'MAX(position)')
            ->where('menu_id = ?', $menuId);

        if ($parentId === null) {
            $select->where('parent_id IS NULL');
        } else {
            $select->where('parent_id = ?', $parentId);
        }

        $maxPosition = $connection->fetchOne($select);

        return $maxPosition !== false ? (int)$maxPosition : -1;
    }

    public function getChildrenIds(int $itemId, bool $recursive = false): array
    {
        $connection = $this->getConnection();

        if ($recursive) {
            $select = $connection->select()
                ->from($this->getMainTable(), 'path')
                ->where('item_id = ?', $itemId);

            $path = $connection->fetchOne($select);

            if (!$path) {
                return [];
            }

            $select = $connection->select()
                ->from($this->getMainTable(), 'item_id')
                ->where('path LIKE ?', $path . '/%');

            return $connection->fetchCol($select);
        } else {
            $select = $connection->select()
                ->from($this->getMainTable(), 'item_id')
                ->where('parent_id = ?', $itemId);

            return $connection->fetchCol($select);
        }
    }
}

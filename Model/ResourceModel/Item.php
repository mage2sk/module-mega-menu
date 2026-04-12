<?php
/**
 * Menu Item Resource Model with Nested Set Implementation
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class Item extends AbstractDb
{
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
        $this->_init('panth_megamenu_item', 'item_id');
    }

    /**
     * Process item data before save
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        // Calculate path and level
        $this->calculatePathAndLevel($object);
        
        return parent::_beforeSave($object);
    }

    /**
     * Process before delete - delete children
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeDelete(AbstractModel $object)
    {
        $this->deleteChildren($object);
        return parent::_beforeDelete($object);
    }

    /**
     * Calculate path and level for item
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function calculatePathAndLevel(AbstractModel $object)
    {
        $parentId = $object->getParentId();

        if ($parentId) {
            // Get parent item
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getMainTable(), ['path', 'level'])
                ->where('item_id = ?', $parentId);

            $parent = $connection->fetchRow($select);

            if ($parent) {
                $parentPath = $parent['path'];
                $parentLevel = (int)$parent['level'];

                $object->setLevel($parentLevel + 1);
                
                // Path will be updated after save when we have the ID
                if ($object->getId()) {
                    $object->setPath($parentPath . '/' . $object->getId());
                } else {
                    // For new items, path will be set in _afterSave
                    $object->setData('_parent_path', $parentPath);
                }
            } else {
                // Parent not found, make it root level
                $object->setLevel(0);
                $object->setParentId(null);
            }
        } else {
            // Root level item
            $object->setLevel(0);
        }

        // Set path for root items or new items
        if (!$parentId && $object->getId()) {
            $object->setPath((string)$object->getId());
        }
    }

    /**
     * Update path after insert
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        // Update path for newly created items
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

        // Update children paths if parent changed
        if ($object->dataHasChangedFor('parent_id') || $object->dataHasChangedFor('item_id')) {
            $this->updateChildrenPaths($object);
        }

        return parent::_afterSave($object);
    }

    /**
     * Update children paths recursively
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function updateChildrenPaths(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $itemId = $object->getId();

        // Get all direct children
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('parent_id = ?', $itemId);

        $children = $connection->fetchAll($select);

        foreach ($children as $child) {
            $childPath = $object->getPath() . '/' . $child['item_id'];
            $childLevel = $object->getLevel() + 1;

            // Update child
            $connection->update(
                $this->getMainTable(),
                [
                    'path' => $childPath,
                    'level' => $childLevel
                ],
                ['item_id = ?' => $child['item_id']]
            );

            // Recursively update grandchildren
            $childObject = new \Magento\Framework\DataObject([
                'item_id' => $child['item_id'],
                'path' => $childPath,
                'level' => $childLevel
            ]);
            $this->updateChildrenPaths($childObject);
        }
    }

    /**
     * Delete all children items
     *
     * @param AbstractModel $object
     * @return void
     */
    protected function deleteChildren(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $itemId = $object->getId();

        // Delete all items where path starts with current item's path
        $connection->delete(
            $this->getMainTable(),
            [
                'path LIKE ?' => $object->getPath() . '/%',
                'item_id != ?' => $itemId
            ]
        );
    }

    /**
     * Reindex item positions within parent
     *
     * @param int $menuId
     * @param int|null $parentId
     * @return void
     */
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

    /**
     * Move item to new position
     *
     * @param int $itemId
     * @param int|null $newParentId
     * @param int $position
     * @return void
     */
    public function moveItem(int $itemId, ?int $newParentId, int $position)
    {
        $connection = $this->getConnection();

        // Get current item data
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('item_id = ?', $itemId);

        $item = $connection->fetchRow($select);

        if (!$item) {
            return;
        }

        $oldParentId = $item['parent_id'];
        $menuId = $item['menu_id'];

        // Update parent_id and position
        $connection->update(
            $this->getMainTable(),
            [
                'parent_id' => $newParentId,
                'position' => $position
            ],
            ['item_id = ?' => $itemId]
        );

        // Recalculate path and level
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

        // Update children paths
        $this->updateChildrenPaths($itemObject);

        // Reindex positions in old parent
        if ($oldParentId !== $newParentId) {
            $this->reindexPositions($menuId, $oldParentId);
        }

        // Reindex positions in new parent
        $this->reindexPositions($menuId, $newParentId);
    }

    /**
     * Get maximum position for parent
     *
     * @param int $menuId
     * @param int|null $parentId
     * @return int
     */
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

    /**
     * Get item children IDs
     *
     * @param int $itemId
     * @param bool $recursive
     * @return array
     */
    public function getChildrenIds(int $itemId, bool $recursive = false): array
    {
        $connection = $this->getConnection();

        if ($recursive) {
            // Get current item path
            $select = $connection->select()
                ->from($this->getMainTable(), 'path')
                ->where('item_id = ?', $itemId);

            $path = $connection->fetchOne($select);

            if (!$path) {
                return [];
            }

            // Get all descendants
            $select = $connection->select()
                ->from($this->getMainTable(), 'item_id')
                ->where('path LIKE ?', $path . '/%');

            return $connection->fetchCol($select);
        } else {
            // Get direct children only
            $select = $connection->select()
                ->from($this->getMainTable(), 'item_id')
                ->where('parent_id = ?', $itemId);

            return $connection->fetchCol($select);
        }
    }
}

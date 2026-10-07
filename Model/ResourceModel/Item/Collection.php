<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\MegaMenu\Model\Item as ItemModel;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'item_id';

    protected $_eventPrefix = 'panth_megamenu_item_collection';

    protected $_eventObject = 'item_collection';

    protected function _construct()
    {
        $this->_init(ItemModel::class, ItemResource::class);
    }

    public function addMenuFilter(int $menuId)
    {
        $this->addFieldToFilter('menu_id', $menuId);
        return $this;
    }

    public function addParentFilter(?int $parentId)
    {
        if ($parentId === null) {
            $this->addFieldToFilter('parent_id', ['null' => true]);
        } else {
            $this->addFieldToFilter('parent_id', $parentId);
        }
        return $this;
    }

    public function addActiveFilter()
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function addLevelFilter(int $level)
    {
        $this->addFieldToFilter('level', $level);
        return $this;
    }

    public function addPathFilter(string $path)
    {
        $this->addFieldToFilter('path', ['like' => $path . '%']);
        return $this;
    }

    public function setPositionOrder(string $direction = 'ASC')
    {
        $this->setOrder('position', $direction);
        return $this;
    }

    public function toTree(?int $parentId = null): array
    {
        $items = [];
        $itemsById = [];

        foreach ($this->getItems() as $item) {
            $itemsById[$item->getId()] = $item;
            $item->setChildren([]);
        }

        foreach ($itemsById as $item) {
            $currentParentId = $item->getParentId();

            if ($currentParentId === $parentId || ($parentId === null && $currentParentId === null)) {
                $items[] = $item;
            } elseif (isset($itemsById[$currentParentId])) {
                $parent = $itemsById[$currentParentId];
                $children = $parent->getChildren();
                $children[] = $item;
                $parent->setChildren($children);
            }
        }

        return $items;
    }

    public function toHierarchicalArray(?int $parentId = null): array
    {
        $tree = $this->toTree($parentId);
        return $this->itemsToArray($tree);
    }

    protected function itemsToArray(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $itemData = $item->getData();

            if ($item->hasChildren()) {
                $itemData['children'] = $this->itemsToArray($item->getChildren());
            }

            $result[] = $itemData;
        }

        return $result;
    }

    public function loadMenuTree(int $menuId, ?int $parentId = null, bool $activeOnly = true): array
    {
        $this->addMenuFilter($menuId);

        if ($activeOnly) {
            $this->addActiveFilter();
        }

        $this->setPositionOrder();
        $this->load();

        return $this->toTree($parentId);
    }

    public function addRootLevelFilter()
    {
        $this->addFieldToFilter('level', 0);
        return $this;
    }

    public function addDescendantsFilter(int $itemId)
    {
        $this->getSelect()
            ->where('path LIKE ?', '%/' . $itemId . '/%')
            ->orWhere('path LIKE ?', '%/' . $itemId);

        return $this;
    }

    public function loadByLevel(int $menuId, int $level, ?int $parentId = null)
    {
        $this->addMenuFilter($menuId);
        $this->addLevelFilter($level);

        if ($parentId !== null) {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getMainTable(), 'path')
                ->where('item_id = ?', $parentId);

            $parentPath = $connection->fetchOne($select);

            if ($parentPath) {
                $this->addPathFilter($parentPath);
            }
        }

        return $this;
    }
}

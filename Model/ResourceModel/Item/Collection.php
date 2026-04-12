<?php
/**
 * Menu Item Collection
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\MegaMenu\Model\Item as ItemModel;
use Panth\MegaMenu\Model\ResourceModel\Item as ItemResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'item_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_item_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'item_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ItemModel::class, ItemResource::class);
    }

    /**
     * Add menu filter
     *
     * @param int $menuId
     * @return $this
     */
    public function addMenuFilter(int $menuId)
    {
        $this->addFieldToFilter('menu_id', $menuId);
        return $this;
    }

    /**
     * Add parent filter
     *
     * @param int|null $parentId
     * @return $this
     */
    public function addParentFilter(?int $parentId)
    {
        if ($parentId === null) {
            $this->addFieldToFilter('parent_id', ['null' => true]);
        } else {
            $this->addFieldToFilter('parent_id', $parentId);
        }
        return $this;
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
     * Add level filter
     *
     * @param int $level
     * @return $this
     */
    public function addLevelFilter(int $level)
    {
        $this->addFieldToFilter('level', $level);
        return $this;
    }

    /**
     * Add path filter
     *
     * @param string $path
     * @return $this
     */
    public function addPathFilter(string $path)
    {
        $this->addFieldToFilter('path', ['like' => $path . '%']);
        return $this;
    }

    /**
     * Set position order
     *
     * @param string $direction
     * @return $this
     */
    public function setPositionOrder(string $direction = 'ASC')
    {
        $this->setOrder('position', $direction);
        return $this;
    }

    /**
     * Build tree structure
     *
     * @param int|null $parentId
     * @return array
     */
    public function toTree(?int $parentId = null): array
    {
        $items = [];
        $itemsById = [];

        // First pass: create array indexed by ID
        foreach ($this->getItems() as $item) {
            $itemsById[$item->getId()] = $item;
            $item->setChildren([]);
        }

        // Second pass: build tree structure
        foreach ($itemsById as $item) {
            $currentParentId = $item->getParentId();

            if ($currentParentId === $parentId || ($parentId === null && $currentParentId === null)) {
                // Top level items
                $items[] = $item;
            } elseif (isset($itemsById[$currentParentId])) {
                // Add to parent's children
                $parent = $itemsById[$currentParentId];
                $children = $parent->getChildren();
                $children[] = $item;
                $parent->setChildren($children);
            }
        }

        return $items;
    }

    /**
     * Get items as hierarchical array
     *
     * @param int|null $parentId
     * @return array
     */
    public function toHierarchicalArray(?int $parentId = null): array
    {
        $tree = $this->toTree($parentId);
        return $this->itemsToArray($tree);
    }

    /**
     * Convert items to array recursively
     *
     * @param array $items
     * @return array
     */
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

    /**
     * Load items for specific menu and build tree
     *
     * @param int $menuId
     * @param int|null $parentId
     * @param bool $activeOnly
     * @return array
     */
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

    /**
     * Get root level items
     *
     * @return $this
     */
    public function addRootLevelFilter()
    {
        $this->addFieldToFilter('level', 0);
        return $this;
    }

    /**
     * Get items by path pattern
     *
     * @param int $itemId
     * @return $this
     */
    public function addDescendantsFilter(int $itemId)
    {
        $this->getSelect()
            ->where('path LIKE ?', '%/' . $itemId . '/%')
            ->orWhere('path LIKE ?', '%/' . $itemId);

        return $this;
    }

    /**
     * Get items at specific level under parent
     *
     * @param int $menuId
     * @param int $level
     * @param int|null $parentId
     * @return $this
     */
    public function loadByLevel(int $menuId, int $level, ?int $parentId = null)
    {
        $this->addMenuFilter($menuId);
        $this->addLevelFilter($level);

        if ($parentId !== null) {
            // Get parent path
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

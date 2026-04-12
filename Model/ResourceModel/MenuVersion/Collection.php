<?php
/**
 * Menu Version Collection
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel\MenuVersion;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\MegaMenu\Model\MenuVersion as MenuVersionModel;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'version_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_menu_version_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'menu_version_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(MenuVersionModel::class, MenuVersionResource::class);
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
     * Add version number filter
     *
     * @param int $versionNumber
     * @return $this
     */
    public function addVersionNumberFilter(int $versionNumber)
    {
        $this->addFieldToFilter('version_number', $versionNumber);
        return $this;
    }

    /**
     * Order by version number descending
     *
     * @return $this
     */
    public function orderByVersionDesc()
    {
        $this->setOrder('version_number', 'DESC');
        return $this;
    }

    /**
     * Order by created at descending
     *
     * @return $this
     */
    public function orderByCreatedAtDesc()
    {
        $this->setOrder('created_at', 'DESC');
        return $this;
    }
}

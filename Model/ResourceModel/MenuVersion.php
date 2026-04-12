<?php
/**
 * Menu Version Resource Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MenuVersion extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('panth_megamenu_menu_version', 'version_id');
    }

    /**
     * Get next version number for a menu
     *
     * @param int $menuId
     * @return int
     */
    public function getNextVersionNumber(int $menuId): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'MAX(version_number)')
            ->where('menu_id = ?', $menuId);

        $maxVersion = $connection->fetchOne($select);

        return $maxVersion ? (int)$maxVersion + 1 : 1;
    }

    /**
     * Get versions by menu ID
     *
     * @param int $menuId
     * @return array
     */
    public function getVersionsByMenuId(int $menuId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('menu_id = ?', $menuId)
            ->order('version_number DESC');

        return $connection->fetchAll($select);
    }
}

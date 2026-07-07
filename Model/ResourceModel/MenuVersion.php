<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MenuVersion extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('panth_megamenu_menu_version', 'version_id');
    }

    public function getNextVersionNumber(int $menuId): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), 'MAX(version_number)')
            ->where('menu_id = ?', $menuId);

        $maxVersion = $connection->fetchOne($select);

        return $maxVersion ? (int)$maxVersion + 1 : 1;
    }

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

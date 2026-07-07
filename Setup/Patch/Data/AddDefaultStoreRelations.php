<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddDefaultStoreRelations implements DataPatchInterface
{
    private $moduleDataSetup;

    private $logger;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $connection = $this->moduleDataSetup->getConnection();
            $menuTable = $this->moduleDataSetup->getTable('panth_megamenu_menu');
            $storeTable = $this->moduleDataSetup->getTable('panth_megamenu_store');

            $menus = $connection->fetchAll(
                $connection->select()->from($menuTable, ['menu_id'])
            );

            $fixedCount = 0;

            foreach ($menus as $menu) {
                $menuId = $menu['menu_id'];

                $relationCount = $connection->fetchOne(
                    $connection->select()
                        ->from($storeTable, ['COUNT(*)'])
                        ->where('menu_id = ?', $menuId)
                );

                if (!$relationCount) {
                    $connection->insert($storeTable, [
                        'menu_id' => $menuId,
                        'store_id' => 0
                    ]);
                    $fixedCount++;
                }
            }
        } catch (\Exception $e) {
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}

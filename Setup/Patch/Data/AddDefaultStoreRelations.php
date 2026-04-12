<?php
/**
 * Add default store relations to menus that don't have any
 * This fixes the issue where menus without store relations are not visible in admin
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class AddDefaultStoreRelations implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        try {
            $connection = $this->moduleDataSetup->getConnection();
            $menuTable = $this->moduleDataSetup->getTable('panth_megamenu_menu');
            $storeTable = $this->moduleDataSetup->getTable('panth_megamenu_store');

            // Get all menus
            $menus = $connection->fetchAll(
                $connection->select()->from($menuTable, ['menu_id'])
            );

            $fixedCount = 0;

            foreach ($menus as $menu) {
                $menuId = $menu['menu_id'];

                // Check if menu has store relations
                $relationCount = $connection->fetchOne(
                    $connection->select()
                        ->from($storeTable, ['COUNT(*)'])
                        ->where('menu_id = ?', $menuId)
                );

                // If no store relations exist, add default relation for all stores (store_id = 0)
                if (!$relationCount) {
                    $connection->insert($storeTable, [
                        'menu_id' => $menuId,
                        'store_id' => 0
                    ]);
                    $fixedCount++;
                }
            }

        } catch (\Exception $e) {
            // Silently handle errors
        }

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}

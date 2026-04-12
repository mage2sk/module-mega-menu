<?php
namespace Panth\MegaMenu\Block\Adminhtml\Menu;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;

class CustomIndex extends Template
{
    protected $menuCollectionFactory;
    protected $itemCollectionFactory;

    public function __construct(
        Context $context,
        CollectionFactory $menuCollectionFactory,
        ItemCollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        $this->menuCollectionFactory = $menuCollectionFactory;
        $this->itemCollectionFactory = $itemCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getMenusJson()
    {
        // Use direct table query to avoid store join issues
        $collection = $this->menuCollectionFactory->create();

        // Disable store table joining by setting the flag
        $collection->setFlag('store_table_joined', true);

        $collection->addFieldToSelect('*');
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);
        $collection->getSelect()->order('created_at DESC');

        $menus = [];
        foreach ($collection as $menu) {
            // Get item count from JSON column
            $itemCount = 0;
            $itemsJson = $menu->getItemsJson();
            if ($itemsJson) {
                $items = json_decode($itemsJson, true);
                $itemCount = is_array($items) ? count($items) : 0;
            } else {
                // Backward compatibility: check item table
                $itemCount = $this->itemCollectionFactory->create()
                    ->addFieldToFilter('menu_id', $menu->getMenuId())
                    ->getSize();
            }

            $menus[] = [
                'menu_id' => $menu->getMenuId(),
                'title' => $menu->getTitle(),
                'identifier' => $menu->getIdentifier(),
                'is_active' => (int)$menu->getIsActive(),
                'created_at' => $menu->getCreatedAt(),
                'item_count' => $itemCount
            ];
        }

        return json_encode($menus);
    }

    public function getNewUrl()
    {
        return $this->getUrl('panth_menu/menu/customedit');
    }

    public function getEditUrl()
    {
        return $this->getUrl('panth_menu/menu/customedit');
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('panth_menu/menu/delete');
    }

    public function getToggleUrl()
    {
        return $this->getUrl('panth_menu/menu/toggle');
    }

    public function getDuplicateUrl()
    {
        return $this->getUrl('panth_menu/menu/duplicate');
    }

    public function getExportUrl()
    {
        return $this->getUrl('panth_menu/menu/export');
    }

    public function getImportUrl()
    {
        return $this->getUrl('panth_menu/menu/import');
    }

    public function getFrontendUrl()
    {
        return $this->getUrl('');
    }

    public function getPreviewUrl()
    {
        return $this->getUrl('panth_menu/menu/preview');
    }
}

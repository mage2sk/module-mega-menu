<?php
namespace Panth\MegaMenu\Block\Adminhtml\Menu;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Item\CollectionFactory as ItemCollectionFactory;

class CustomEdit extends Template
{
    protected $menuFactory;
    protected $itemCollectionFactory;

    public function __construct(
        Context $context,
        MenuFactory $menuFactory,
        ItemCollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        $this->menuFactory = $menuFactory;
        $this->itemCollectionFactory = $itemCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getMenuId()
    {
        return $this->getRequest()->getParam('menu_id');
    }

    public function getMenuData()
    {
        $menuId = $this->getMenuId();
        if (!$menuId) {
            return json_encode([
                'menu_id' => null,
                'title' => '',
                'identifier' => '',
                'is_active' => 1,
                'css_class' => '',
                'sort_order' => 0,
                'description' => '',
                'custom_css' => ''
            ]);
        }

        $menu = $this->menuFactory->create()->load($menuId);
        if (!$menu->getId()) {
            return json_encode(null);
        }

        return json_encode([
            'menu_id' => $menu->getMenuId(),
            'title' => $menu->getTitle(),
            'identifier' => $menu->getIdentifier(),
            'is_active' => (int)$menu->getIsActive(),
            'css_class' => $menu->getCssClass(),
            'sort_order' => (int)$menu->getSortOrder(),
            'description' => $menu->getDescription(),
            'custom_css' => $menu->getCustomCss()
        ]);
    }

    public function getMenuItemsData()
    {
        $menuId = $this->getMenuId();
        if (!$menuId) {
            return json_encode([]);
        }

        $menu = $this->menuFactory->create()->load($menuId);
        if (!$menu->getId()) {
            return json_encode([]);
        }

        // Get items from JSON column
        $itemsJson = $menu->getItemsJson();

        // If JSON exists, return it; otherwise return empty array
        if ($itemsJson) {
            return $itemsJson;
        }

        // Backward compatibility: check if items exist in separate table
        $collection = $this->itemCollectionFactory->create();
        $collection->addFieldToFilter('menu_id', $menuId);
        $collection->setOrder('position', 'ASC');

        if ($collection->getSize() > 0) {
            $items = [];
            foreach ($collection as $item) {
                $items[] = [
                    'item_id' => $item->getItemId(),
                    'menu_id' => $item->getMenuId(),
                    'title' => $item->getTitle(),
                    'url' => $item->getUrl(),
                    'item_type' => $item->getItemType(),
                    'parent_id' => $item->getParentId(),
                    'position' => (int)$item->getPosition(),
                    'level' => (int)$item->getLevel(),
                    'is_active' => (int)$item->getIsActive(),
                    'show_on_frontend' => (int)($item->getShowOnFrontend() ?? 1),
                    'target' => $item->getTarget(),
                    'css_class' => $item->getCssClass(),
                    'icon' => $item->getIcon()
                ];
            }
            return json_encode($items);
        }

        return json_encode([]);
    }

    public function getSaveUrl()
    {
        return $this->getUrl('panth_menu/menu/customsave');
    }

    public function getListUrl()
    {
        return $this->getUrl('panth_menu/menu/index');
    }
}

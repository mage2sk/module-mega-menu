<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\DataObject;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;
use Panth\MegaMenu\Model\MenuFactory;

class Preview extends Template
{
    protected $jsonDecoder;

    private $previewMenuData = null;

    private $menuTree = null;

    private $currentMenu = null;

    private $viewModel;

    private $menuFactory;

    public function __construct(
        Context $context,
        DecoderInterface $jsonDecoder,
        MenuViewModel $viewModel,
        MenuFactory $menuFactory,
        array $data = []
    ) {
        $this->jsonDecoder = $jsonDecoder;
        $this->viewModel = $viewModel;
        $this->menuFactory = $menuFactory;
        parent::__construct($context, $data);
    }

    public function getMenuData(): array
    {
        if ($this->previewMenuData !== null) {
            return $this->previewMenuData;
        }

        $itemsJson = $this->getRequest()->getParam('items_json', '');

        if (empty($itemsJson)) {
            $itemsJson = (string) $this->getRequest()->getPostValue('items_json', '');
        }

        if (!empty($itemsJson)) {
            return $this->getMenuDataFromPost($itemsJson);
        }

        $menuId = $this->getRequest()->getParam('menu_id', '');
        if (!empty($menuId)) {
            return $this->getMenuDataFromDb($menuId);
        }

        return [];
    }

    private function getMenuDataFromPost(string $itemsJson): array
    {
        try {
            $items = $this->jsonDecoder->decode($itemsJson);

            $this->previewMenuData = [
                'menu_id' => 'preview',
                'identifier' => 'preview',
                'title' => 'Preview Menu',
                'is_active' => 1,
                'items' => $items,
                'css_class' => $this->getRequest()->getParam('css_class', ''),
                'custom_css' => $this->getRequest()->getParam('custom_css', ''),
                'container_bg_color' => $this->getRequest()->getParam('container_bg_color', ''),
                'container_padding' => $this->getRequest()->getParam('container_padding', ''),
                'container_margin' => $this->getRequest()->getParam('container_margin', ''),
                'item_gap' => $this->getRequest()->getParam('item_gap', ''),
                'container_max_width' => $this->getRequest()->getParam('container_max_width', ''),
                'container_border' => $this->getRequest()->getParam('container_border', ''),
                'container_border_radius' => $this->getRequest()->getParam('container_border_radius', ''),
                'container_box_shadow' => $this->getRequest()->getParam('container_box_shadow', ''),
                'menu_alignment' => $this->getRequest()->getParam('menu_alignment', ''),
            ];

            return $this->previewMenuData;
        } catch (\Exception $e) {
            $this->_logger->error('Preview Menu POST Error: ' . $e->getMessage());
            return [];
        }
    }

    private function getMenuDataFromDb(string $menuId): array
    {
        try {
            $menu = $this->menuFactory->create()->load($menuId);

            if (!$menu->getId()) {
                $this->_logger->error('Preview: Menu not found with ID ' . $menuId);
                return [];
            }

            $itemsJson = $menu->getItemsJson();
            $items = [];
            if ($itemsJson) {
                $items = $this->jsonDecoder->decode($itemsJson);
            }

            $this->previewMenuData = [
                'menu_id' => $menu->getMenuId(),
                'identifier' => $menu->getIdentifier(),
                'title' => $menu->getTitle(),
                'is_active' => (int) $menu->getIsActive(),
                'items' => is_array($items) ? $items : [],
                'css_class' => $menu->getCssClass() ?: '',
                'custom_css' => $menu->getCustomCss() ?: '',
                'container_bg_color' => $menu->getData('container_bg_color') ?: '',
                'container_padding' => $menu->getData('container_padding') ?: '',
                'container_margin' => $menu->getData('container_margin') ?: '',
                'item_gap' => $menu->getData('item_gap') ?: '',
                'container_max_width' => $menu->getData('container_max_width') ?: '',
                'container_border' => $menu->getData('container_border') ?: '',
                'container_border_radius' => $menu->getData('container_border_radius') ?: '',
                'container_box_shadow' => $menu->getData('container_box_shadow') ?: '',
                'menu_alignment' => $menu->getData('menu_alignment') ?: '',
            ];

            return $this->previewMenuData;
        } catch (\Exception $e) {
            $this->_logger->error('Preview Menu DB Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getCurrentMenuTree(): array
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        $menuData = $this->getMenuData();
        $items = $menuData['items'] ?? [];

        $this->_logger->info('Preview getCurrentMenuTree: menuData keys=' . implode(',', array_keys($menuData))
            . ', items count=' . count($items));

        $this->menuTree = $this->buildTree($items);

        $this->_logger->info('Preview getCurrentMenuTree: tree count=' . count($this->menuTree)
            . ', first root=' . ($this->menuTree[0]['title'] ?? 'EMPTY'));

        return $this->menuTree;
    }

    private function buildTree(array $items): array
    {
        $tree = [];
        $lookup = [];

        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $lookup[$itemId] = $item;
            $lookup[$itemId]['children'] = [];
        }

        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $parentId = $item['parent_id'] ?? 0;

            if ($parentId == 0 || $parentId === '0' || $parentId === '') {
            } else {
                if (isset($lookup[$parentId])) {
                    $lookup[$parentId]['children'][] = &$lookup[$itemId];
                }
            }
        }

        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $parentId = $item['parent_id'] ?? 0;

            if ($parentId == 0 || $parentId === '0' || $parentId === '') {
                $tree[] = &$lookup[$itemId];
            }
        }

        return $tree;
    }

    public function getCurrentMenu()
    {
        if ($this->currentMenu !== null) {
            return $this->currentMenu;
        }

        $menuData = $this->getMenuData();
        if (empty($menuData)) {
            return null;
        }

        $menuData['id'] = 'preview';

        $this->currentMenu = new DataObject($menuData);

        return $this->currentMenu;
    }

    public function getViewModel(): MenuViewModel
    {
        return $this->viewModel;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function getId(): string
    {
        return 'preview';
    }
}

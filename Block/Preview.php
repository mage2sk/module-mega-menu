<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\DataObject;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;
use Panth\MegaMenu\Model\MenuFactory;

/**
 * Preview Block - Renders menu using POST data or saved DB data (mimics Menu block interface)
 *
 * Supports two modes:
 * 1. POST data - original approach, receives unsaved menu data via form POST
 * 2. DB data - save-then-preview approach, loads saved menu by menu_id from request param
 */
class Preview extends Template
{
    /**
     * @var DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var array|null
     */
    private $previewMenuData = null;

    /**
     * @var array|null
     */
    private $menuTree = null;

    /**
     * @var DataObject|null
     */
    private $currentMenu = null;

    /**
     * @var MenuViewModel
     */
    private $viewModel;

    /**
     * @var MenuFactory
     */
    private $menuFactory;

    /**
     * @param Context $context
     * @param DecoderInterface $jsonDecoder
     * @param MenuViewModel $viewModel
     * @param MenuFactory $menuFactory
     * @param array $data
     */
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

    /**
     * Get preview menu data from POST or from DB (by menu_id)
     *
     * @return array
     */
    public function getMenuData(): array
    {
        if ($this->previewMenuData !== null) {
            return $this->previewMenuData;
        }

        // Check ALL possible sources for items_json
        $itemsJson = $this->getRequest()->getParam('items_json', '');

        // Also check POST body via Magento request
        if (empty($itemsJson)) {
            $itemsJson = (string) $this->getRequest()->getPostValue('items_json', '');
        }


        if (!empty($itemsJson)) {
            return $this->getMenuDataFromPost($itemsJson);
        }

        // Fallback: load from DB by menu_id
        $menuId = $this->getRequest()->getParam('menu_id', '');
        if (!empty($menuId)) {
            return $this->getMenuDataFromDb($menuId);
        }

        return [];
    }

    /**
     * Load menu data from POST parameters
     *
     * @param string $itemsJson
     * @return array
     */
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

    /**
     * Load menu data from the database by menu_id
     *
     * @param string $menuId
     * @return array
     */
    private function getMenuDataFromDb(string $menuId): array
    {
        try {
            $menu = $this->menuFactory->create()->load($menuId);

            if (!$menu->getId()) {
                $this->_logger->error('Preview: Menu not found with ID ' . $menuId);
                return [];
            }

            // Parse items from the saved JSON column
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

    /**
     * Get current menu tree (mimics Menu block method)
     *
     * @return array
     */
    public function getCurrentMenuTree(): array
    {
        if ($this->menuTree !== null) {
            return $this->menuTree;
        }

        $menuData = $this->getMenuData();
        $items = $menuData['items'] ?? [];

        $this->_logger->info('Preview getCurrentMenuTree: menuData keys=' . implode(',', array_keys($menuData))
            . ', items count=' . count($items));

        // Build tree structure from flat items array
        $this->menuTree = $this->buildTree($items);

        $this->_logger->info('Preview getCurrentMenuTree: tree count=' . count($this->menuTree)
            . ', first root=' . ($this->menuTree[0]['title'] ?? 'EMPTY'));

        return $this->menuTree;
    }

    /**
     * Build tree structure from flat items array
     *
     * @param array $items
     * @return array
     */
    private function buildTree(array $items): array
    {
        $tree = [];
        $lookup = [];

        // First pass: create lookup array
        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $lookup[$itemId] = $item;
            $lookup[$itemId]['children'] = [];
        }

        // Second pass: build relationships (add children to parents)
        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $parentId = $item['parent_id'] ?? 0;

            if ($parentId == 0 || $parentId === '0' || $parentId === '') {
                // Root item
            } else {
                // Child item - add to parent's children
                if (isset($lookup[$parentId])) {
                    $lookup[$parentId]['children'][] = &$lookup[$itemId];
                }
            }
        }

        // Third pass: collect root items (AFTER children are built)
        foreach ($items as $item) {
            $itemId = $item['item_id'] ?? '';
            $parentId = $item['parent_id'] ?? 0;

            if ($parentId == 0 || $parentId === '0' || $parentId === '') {
                // Add reference to lookup item which now has children
                $tree[] = &$lookup[$itemId];
            }
        }

        return $tree;
    }

    /**
     * Get current menu (mimics Menu block method)
     *
     * @return DataObject|null
     */
    public function getCurrentMenu()
    {
        if ($this->currentMenu !== null) {
            return $this->currentMenu;
        }

        $menuData = $this->getMenuData();
        if (empty($menuData)) {
            return null;
        }

        // Add ID method for the menu object
        $menuData['id'] = 'preview';

        // Create DataObject which auto-generates getters/setters for all keys
        $this->currentMenu = new DataObject($menuData);

        return $this->currentMenu;
    }

    /**
     * Get view model (mimics Menu block method)
     *
     * @return MenuViewModel
     */
    public function getViewModel(): MenuViewModel
    {
        return $this->viewModel;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get menu ID for preview (returns 'preview' for unique identification)
     *
     * @return string
     */
    public function getId(): string
    {
        return 'preview';
    }
}

<?php
/**
 * MenuItem Block
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;

class MenuItem extends Template
{
    /**
     * @var MenuHelper
     */
    protected $menuHelper;

    /**
     * @var MenuViewModel
     */
    protected $menuViewModel;

    /**
     * @var ItemInterface|array|null
     */
    protected $item;

    /**
     * @var int
     */
    protected $level = 0;

    /**
     * @var string
     */
    protected $_template = 'Panth_MegaMenu::menu-item.phtml';

    /**
     * @param Context $context
     * @param MenuHelper $menuHelper
     * @param MenuViewModel $menuViewModel
     * @param array $data
     */
    public function __construct(
        Context $context,
        MenuHelper $menuHelper,
        MenuViewModel $menuViewModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->menuHelper = $menuHelper;
        $this->menuViewModel = $menuViewModel;
    }

    /**
     * Set menu item
     *
     * @param ItemInterface $item
     * @return $this
     */
    public function setItem(ItemInterface $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * Get menu item
     *
     * @return ItemInterface|array|null
     */
    public function getItem()
    {
        if ($this->item === null && $this->hasData('item')) {
            $this->item = $this->getData('item');
        }

        return $this->item;
    }

    /**
     * Set item level
     *
     * @param int $level
     * @return $this
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Get item level
     *
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Get item URL
     *
     * @return string
     */
    public function getItemUrl(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '#';
        }

        return $this->menuViewModel->getItemUrl($item);
    }

    /**
     * Get item CSS classes
     *
     * @return string
     */
    public function getItemClasses(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        $classes = [
            'megamenu-item',
            'level-' . $this->level
        ];

        // Add item type class
        $itemType = is_array($item) ? ($item['item_type'] ?? null) : $item->getItemType();
        if ($itemType) {
            $classes[] = 'type-' . $itemType;
        }

        // Add has-children class
        if ($this->hasChildren()) {
            $classes[] = 'has-children';
        }

        // Add custom CSS class
        $cssClass = is_array($item) ? ($item['css_class'] ?? null) : $item->getCssClass();
        if ($cssClass) {
            $classes[] = $cssClass;
        }

        // Add active class
        if ($this->isActive()) {
            $classes[] = 'active';
        }

        return implode(' ', $classes);
    }

    /**
     * Get link attributes
     *
     * @return array
     */
    public function getLinkAttributes(): array
    {
        $item = $this->getItem();
        if (!$item) {
            return [];
        }

        $title = is_array($item) ? ($item['title'] ?? '') : $item->getTitle();

        $attributes = [
            'href' => $this->getItemUrl(),
            'title' => $this->escapeHtmlAttr($title),
            'class' => 'megamenu-link',
            'target' => $this->menuViewModel->getLinkTarget($item)
        ];

        $rel = $this->menuViewModel->getLinkRel($item);
        if ($rel) {
            $attributes['rel'] = $rel;
        }

        if ($this->hasChildren()) {
            $attributes['aria-haspopup'] = 'true';
            $attributes['aria-expanded'] = 'false';
        }

        return $attributes;
    }

    /**
     * Render link attributes
     *
     * @return string
     */
    public function renderLinkAttributes(): string
    {
        $attributes = $this->getLinkAttributes();
        $html = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $html[] = sprintf('%s="%s"', $name, $this->escapeHtmlAttr($value));
        }

        return implode(' ', $html);
    }

    /**
     * Get item title with icon
     *
     * @return string
     */
    public function getItemTitleHtml(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->getItemTitleWithIcon($item);
    }

    /**
     * Check if item has children
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->hasChildren($item);
    }

    /**
     * Get children items
     *
     * @return array
     */
    public function getChildren(): array
    {
        $item = $this->getItem();
        if (!$item) {
            return [];
        }

        // Handle array items
        if (is_array($item)) {
            return $item['children'] ?? [];
        }

        // Handle object items
        if (!$item->hasChildren()) {
            return [];
        }

        return $item->getChildren();
    }

    /**
     * Render children HTML
     *
     * @return string
     */
    public function renderChildren(): string
    {
        if (!$this->hasChildren()) {
            return '';
        }

        $children = $this->getChildren();
        $html = '<ul class="submenu level-' . ($this->level + 1) . '">';

        foreach ($children as $child) {
            $childBlock = $this->getLayout()->createBlock(self::class)
                ->setItem($child)
                ->setLevel($this->level + 1);

            $html .= $childBlock->toHtml();
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Check if item should show content
     *
     * @return bool
     */
    public function shouldShowContent(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->shouldShowContent($item);
    }

    /**
     * Get processed item content
     *
     * @return string
     */
    public function getItemContent(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->processItemContent($item);
    }

    /**
     * Get column width class
     *
     * @return string
     */
    public function getColumnWidthClass(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->getColumnWidthClass($item);
    }

    /**
     * Check if item is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->isActive($item);
    }

    /**
     * Get menu helper
     *
     * @return MenuHelper
     */
    public function getMenuHelper(): MenuHelper
    {
        return $this->menuHelper;
    }

    /**
     * Get menu view model
     *
     * @return MenuViewModel
     */
    public function getMenuViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    /**
     * Before rendering html process
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        // Ensure we have an item
        if (!$this->getItem()) {
            return parent::_beforeToHtml();
        }

        return parent::_beforeToHtml();
    }
}

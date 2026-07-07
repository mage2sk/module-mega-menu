<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\MegaMenu\Api\Data\ItemInterface;
use Panth\MegaMenu\Helper\Data as MenuHelper;
use Panth\MegaMenu\ViewModel\Menu as MenuViewModel;

class MenuItem extends Template
{
    protected $menuHelper;

    protected $menuViewModel;

    protected $item;

    protected $level = 0;

    protected $_template = 'Panth_MegaMenu::menu-item.phtml';

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

    public function setItem(ItemInterface $item)
    {
        $this->item = $item;
        return $this;
    }

    public function getItem()
    {
        if ($this->item === null && $this->hasData('item')) {
            $this->item = $this->getData('item');
        }

        return $this->item;
    }

    public function setLevel(int $level)
    {
        $this->level = $level;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getItemUrl(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '#';
        }

        return $this->menuViewModel->getItemUrl($item);
    }

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

        $itemType = is_array($item) ? ($item['item_type'] ?? null) : $item->getItemType();
        if ($itemType) {
            $classes[] = 'type-' . $itemType;
        }

        if ($this->hasChildren()) {
            $classes[] = 'has-children';
        }

        $cssClass = is_array($item) ? ($item['css_class'] ?? null) : $item->getCssClass();
        if ($cssClass) {
            $classes[] = $cssClass;
        }

        if ($this->isActive()) {
            $classes[] = 'active';
        }

        return implode(' ', $classes);
    }

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

    public function getItemTitleHtml(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->getItemTitleWithIcon($item);
    }

    public function hasChildren(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->hasChildren($item);
    }

    public function getChildren(): array
    {
        $item = $this->getItem();
        if (!$item) {
            return [];
        }

        if (is_array($item)) {
            return $item['children'] ?? [];
        }

        if (!$item->hasChildren()) {
            return [];
        }

        return $item->getChildren();
    }

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

    public function shouldShowContent(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->shouldShowContent($item);
    }

    public function getItemContent(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->processItemContent($item);
    }

    public function getColumnWidthClass(): string
    {
        $item = $this->getItem();
        if (!$item) {
            return '';
        }

        return $this->menuViewModel->getColumnWidthClass($item);
    }

    public function isActive(): bool
    {
        $item = $this->getItem();
        if (!$item) {
            return false;
        }

        return $this->menuViewModel->isActive($item);
    }

    public function getMenuHelper(): MenuHelper
    {
        return $this->menuHelper;
    }

    public function getMenuViewModel(): MenuViewModel
    {
        return $this->menuViewModel;
    }

    protected function _beforeToHtml()
    {
        if (!$this->getItem()) {
            return parent::_beforeToHtml();
        }

        return parent::_beforeToHtml();
    }
}

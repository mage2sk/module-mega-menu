<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\ItemInterface;

class Item extends AbstractModel implements ItemInterface, IdentityInterface
{
    const CACHE_TAG = 'panth_megamenu_item';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_megamenu_item';

    protected $children = [];

    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\Item::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getItemId(): ?int
    {
        return $this->getData(self::ITEM_ID) ? (int)$this->getData(self::ITEM_ID) : null;
    }

    public function setItemId(int $itemId): ItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    public function getMenuId(): ?int
    {
        return $this->getData(self::MENU_ID) ? (int)$this->getData(self::MENU_ID) : null;
    }

    public function setMenuId(int $menuId): ItemInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    public function getParentId(): ?int
    {
        $parentId = $this->getData(self::PARENT_ID);
        return $parentId !== null ? (int)$parentId : null;
    }

    public function setParentId(?int $parentId): ItemInterface
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    public function setTitle(string $title): ItemInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getItemType(): ?string
    {
        return $this->getData(self::ITEM_TYPE);
    }

    public function setItemType(string $itemType): ItemInterface
    {
        return $this->setData(self::ITEM_TYPE, $itemType);
    }

    public function getLinkType(): ?string
    {
        return $this->getData(self::LINK_TYPE);
    }

    public function setLinkType(?string $linkType): ItemInterface
    {
        return $this->setData(self::LINK_TYPE, $linkType);
    }

    public function getLinkValue(): ?string
    {
        return $this->getData(self::LINK_VALUE);
    }

    public function setLinkValue(?string $linkValue): ItemInterface
    {
        return $this->setData(self::LINK_VALUE, $linkValue);
    }

    public function getCssClass(): ?string
    {
        return $this->getData(self::CSS_CLASS);
    }

    public function setCssClass(?string $cssClass): ItemInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
    }

    public function getIconClass(): ?string
    {
        return $this->getData(self::ICON_CLASS);
    }

    public function setIconClass(?string $iconClass): ItemInterface
    {
        return $this->setData(self::ICON_CLASS, $iconClass);
    }

    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    public function setContent(?string $content): ItemInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    public function getColumns(): int
    {
        return (int)$this->getData(self::COLUMNS);
    }

    public function setColumns(int $columns): ItemInterface
    {
        return $this->setData(self::COLUMNS, $columns);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): ItemInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getOpenNewTab(): bool
    {
        return (bool)$this->getData(self::OPEN_NEW_TAB);
    }

    public function setOpenNewTab(bool $openNewTab): ItemInterface
    {
        return $this->setData(self::OPEN_NEW_TAB, $openNewTab);
    }

    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    public function setPosition(int $position): ItemInterface
    {
        return $this->setData(self::POSITION, $position);
    }

    public function getLevel(): int
    {
        return (int)$this->getData(self::LEVEL);
    }

    public function setLevel(int $level): ItemInterface
    {
        return $this->setData(self::LEVEL, $level);
    }

    public function getPath(): ?string
    {
        return $this->getData(self::PATH);
    }

    public function setPath(string $path): ItemInterface
    {
        return $this->setData(self::PATH, $path);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): ItemInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): ItemInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function setChildren(array $children): ItemInterface
    {
        $this->children = $children;
        return $this;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function getUrl(): ?string
    {
        return $this->getData(self::URL);
    }

    public function setUrl(?string $url): ItemInterface
    {
        return $this->setData(self::URL, $url);
    }

    public function getTarget(): ?string
    {
        return $this->getData(self::TARGET);
    }

    public function setTarget(?string $target): ItemInterface
    {
        return $this->setData(self::TARGET, $target);
    }

    public function getIconLibrary(): ?string
    {
        return $this->getData(self::ICON_LIBRARY);
    }

    public function setIconLibrary(?string $iconLibrary): ItemInterface
    {
        return $this->setData(self::ICON_LIBRARY, $iconLibrary);
    }

    public function getShowOnFrontend(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_FRONTEND);
    }

    public function setShowOnFrontend(bool $showOnFrontend): ItemInterface
    {
        return $this->setData(self::SHOW_ON_FRONTEND, $showOnFrontend);
    }

    public function getSubmenuColumns(): int
    {
        return (int)$this->getData(self::SUBMENU_COLUMNS);
    }

    public function setSubmenuColumns(int $submenuColumns): ItemInterface
    {
        return $this->setData(self::SUBMENU_COLUMNS, $submenuColumns);
    }

    public function getBackgroundColor(): ?string
    {
        return $this->getData(self::BACKGROUND_COLOR);
    }

    public function setBackgroundColor(?string $backgroundColor): ItemInterface
    {
        return $this->setData(self::BACKGROUND_COLOR, $backgroundColor);
    }

    public function getTextColor(): ?string
    {
        return $this->getData(self::TEXT_COLOR);
    }

    public function setTextColor(?string $textColor): ItemInterface
    {
        return $this->setData(self::TEXT_COLOR, $textColor);
    }

    public function getShowChildren(): bool
    {
        return (bool)$this->getData(self::SHOW_CHILDREN);
    }

    public function setShowChildren(bool $showChildren): ItemInterface
    {
        return $this->setData(self::SHOW_CHILDREN, $showChildren);
    }

    public function getHoverEffect(): ?string
    {
        return $this->getData(self::HOVER_EFFECT);
    }

    public function setHoverEffect(?string $hoverEffect): ItemInterface
    {
        return $this->setData(self::HOVER_EFFECT, $hoverEffect);
    }
}

<?php
/**
 * Menu Item Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\ItemInterface;

class Item extends AbstractModel implements ItemInterface, IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_megamenu_item';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_item';

    /**
     * @var array
     */
    protected $children = [];

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\Item::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getItemId(): ?int
    {
        return $this->getData(self::ITEM_ID) ? (int)$this->getData(self::ITEM_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setItemId(int $itemId): ItemInterface
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * @inheritDoc
     */
    public function getMenuId(): ?int
    {
        return $this->getData(self::MENU_ID) ? (int)$this->getData(self::MENU_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setMenuId(int $menuId): ItemInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    /**
     * @inheritDoc
     */
    public function getParentId(): ?int
    {
        $parentId = $this->getData(self::PARENT_ID);
        return $parentId !== null ? (int)$parentId : null;
    }

    /**
     * @inheritDoc
     */
    public function setParentId(?int $parentId): ItemInterface
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title): ItemInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritDoc
     */
    public function getItemType(): ?string
    {
        return $this->getData(self::ITEM_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setItemType(string $itemType): ItemInterface
    {
        return $this->setData(self::ITEM_TYPE, $itemType);
    }

    /**
     * @inheritDoc
     */
    public function getLinkType(): ?string
    {
        return $this->getData(self::LINK_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setLinkType(?string $linkType): ItemInterface
    {
        return $this->setData(self::LINK_TYPE, $linkType);
    }

    /**
     * @inheritDoc
     */
    public function getLinkValue(): ?string
    {
        return $this->getData(self::LINK_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setLinkValue(?string $linkValue): ItemInterface
    {
        return $this->setData(self::LINK_VALUE, $linkValue);
    }

    /**
     * @inheritDoc
     */
    public function getCssClass(): ?string
    {
        return $this->getData(self::CSS_CLASS);
    }

    /**
     * @inheritDoc
     */
    public function setCssClass(?string $cssClass): ItemInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
    }

    /**
     * @inheritDoc
     */
    public function getIconClass(): ?string
    {
        return $this->getData(self::ICON_CLASS);
    }

    /**
     * @inheritDoc
     */
    public function setIconClass(?string $iconClass): ItemInterface
    {
        return $this->setData(self::ICON_CLASS, $iconClass);
    }

    /**
     * @inheritDoc
     */
    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setContent(?string $content): ItemInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * @inheritDoc
     */
    public function getColumns(): int
    {
        return (int)$this->getData(self::COLUMNS);
    }

    /**
     * @inheritDoc
     */
    public function setColumns(int $columns): ItemInterface
    {
        return $this->setData(self::COLUMNS, $columns);
    }

    /**
     * @inheritDoc
     */
    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    /**
     * @inheritDoc
     */
    public function setIsActive(bool $isActive): ItemInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getOpenNewTab(): bool
    {
        return (bool)$this->getData(self::OPEN_NEW_TAB);
    }

    /**
     * @inheritDoc
     */
    public function setOpenNewTab(bool $openNewTab): ItemInterface
    {
        return $this->setData(self::OPEN_NEW_TAB, $openNewTab);
    }

    /**
     * @inheritDoc
     */
    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    /**
     * @inheritDoc
     */
    public function setPosition(int $position): ItemInterface
    {
        return $this->setData(self::POSITION, $position);
    }

    /**
     * @inheritDoc
     */
    public function getLevel(): int
    {
        return (int)$this->getData(self::LEVEL);
    }

    /**
     * @inheritDoc
     */
    public function setLevel(int $level): ItemInterface
    {
        return $this->setData(self::LEVEL, $level);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): ?string
    {
        return $this->getData(self::PATH);
    }

    /**
     * @inheritDoc
     */
    public function setPath(string $path): ItemInterface
    {
        return $this->setData(self::PATH, $path);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): ItemInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): ItemInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @inheritDoc
     */
    public function setChildren(array $children): ItemInterface
    {
        $this->children = $children;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(): ?string
    {
        return $this->getData(self::URL);
    }

    /**
     * @inheritDoc
     */
    public function setUrl(?string $url): ItemInterface
    {
        return $this->setData(self::URL, $url);
    }

    /**
     * @inheritDoc
     */
    public function getTarget(): ?string
    {
        return $this->getData(self::TARGET);
    }

    /**
     * @inheritDoc
     */
    public function setTarget(?string $target): ItemInterface
    {
        return $this->setData(self::TARGET, $target);
    }

    /**
     * @inheritDoc
     */
    public function getIconLibrary(): ?string
    {
        return $this->getData(self::ICON_LIBRARY);
    }

    /**
     * @inheritDoc
     */
    public function setIconLibrary(?string $iconLibrary): ItemInterface
    {
        return $this->setData(self::ICON_LIBRARY, $iconLibrary);
    }

    /**
     * @inheritDoc
     */
    public function getShowOnFrontend(): bool
    {
        return (bool)$this->getData(self::SHOW_ON_FRONTEND);
    }

    /**
     * @inheritDoc
     */
    public function setShowOnFrontend(bool $showOnFrontend): ItemInterface
    {
        return $this->setData(self::SHOW_ON_FRONTEND, $showOnFrontend);
    }

    /**
     * @inheritDoc
     */
    public function getSubmenuColumns(): int
    {
        return (int)$this->getData(self::SUBMENU_COLUMNS);
    }

    /**
     * @inheritDoc
     */
    public function setSubmenuColumns(int $submenuColumns): ItemInterface
    {
        return $this->setData(self::SUBMENU_COLUMNS, $submenuColumns);
    }

    /**
     * @inheritDoc
     */
    public function getBackgroundColor(): ?string
    {
        return $this->getData(self::BACKGROUND_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function setBackgroundColor(?string $backgroundColor): ItemInterface
    {
        return $this->setData(self::BACKGROUND_COLOR, $backgroundColor);
    }

    /**
     * @inheritDoc
     */
    public function getTextColor(): ?string
    {
        return $this->getData(self::TEXT_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function setTextColor(?string $textColor): ItemInterface
    {
        return $this->setData(self::TEXT_COLOR, $textColor);
    }

    /**
     * @inheritDoc
     */
    public function getShowChildren(): bool
    {
        return (bool)$this->getData(self::SHOW_CHILDREN);
    }

    /**
     * @inheritDoc
     */
    public function setShowChildren(bool $showChildren): ItemInterface
    {
        return $this->setData(self::SHOW_CHILDREN, $showChildren);
    }

    /**
     * @inheritDoc
     */
    public function getHoverEffect(): ?string
    {
        return $this->getData(self::HOVER_EFFECT);
    }

    /**
     * @inheritDoc
     */
    public function setHoverEffect(?string $hoverEffect): ItemInterface
    {
        return $this->setData(self::HOVER_EFFECT, $hoverEffect);
    }
}

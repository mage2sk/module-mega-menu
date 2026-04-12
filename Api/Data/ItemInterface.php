<?php
/**
 * Menu Item Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

interface ItemInterface
{
    const ITEM_ID = 'item_id';
    const MENU_ID = 'menu_id';
    const PARENT_ID = 'parent_id';
    const TITLE = 'title';
    const ITEM_TYPE = 'item_type';
    const LINK_TYPE = 'link_type';
    const LINK_VALUE = 'link_value';
    const CSS_CLASS = 'css_class';
    const ICON_CLASS = 'icon_class';
    const CONTENT = 'content';
    const COLUMNS = 'columns';
    const IS_ACTIVE = 'is_active';
    const OPEN_NEW_TAB = 'open_new_tab';
    const POSITION = 'position';
    const LEVEL = 'level';
    const PATH = 'path';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const URL = 'url';
    const TARGET = 'target';
    const ICON_LIBRARY = 'icon_library';
    const SHOW_ON_FRONTEND = 'show_on_frontend';
    const SUBMENU_COLUMNS = 'submenu_columns';
    const BACKGROUND_COLOR = 'background_color';
    const TEXT_COLOR = 'text_color';
    const SHOW_CHILDREN = 'show_children';
    const HOVER_EFFECT = 'hover_effect';

    // Item types
    const TYPE_CATEGORY = 'category';
    const TYPE_LINK = 'link';
    const TYPE_CONTENT = 'content';

    // Link types
    const LINK_CATEGORY = 'category';
    const LINK_CMS_PAGE = 'cms_page';
    const LINK_CUSTOM_URL = 'custom_url';

    /**
     * Get item ID
     *
     * @return int|null
     */
    public function getItemId(): ?int;

    /**
     * Set item ID
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self;

    /**
     * Get menu ID
     *
     * @return int|null
     */
    public function getMenuId(): ?int;

    /**
     * Set menu ID
     *
     * @param int $menuId
     * @return $this
     */
    public function setMenuId(int $menuId): self;

    /**
     * Get parent ID
     *
     * @return int|null
     */
    public function getParentId(): ?int;

    /**
     * Set parent ID
     *
     * @param int|null $parentId
     * @return $this
     */
    public function setParentId(?int $parentId): self;

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self;

    /**
     * Get item type
     *
     * @return string|null
     */
    public function getItemType(): ?string;

    /**
     * Set item type
     *
     * @param string $itemType
     * @return $this
     */
    public function setItemType(string $itemType): self;

    /**
     * Get link type
     *
     * @return string|null
     */
    public function getLinkType(): ?string;

    /**
     * Set link type
     *
     * @param string|null $linkType
     * @return $this
     */
    public function setLinkType(?string $linkType): self;

    /**
     * Get link value
     *
     * @return string|null
     */
    public function getLinkValue(): ?string;

    /**
     * Set link value
     *
     * @param string|null $linkValue
     * @return $this
     */
    public function setLinkValue(?string $linkValue): self;

    /**
     * Get CSS class
     *
     * @return string|null
     */
    public function getCssClass(): ?string;

    /**
     * Set CSS class
     *
     * @param string|null $cssClass
     * @return $this
     */
    public function setCssClass(?string $cssClass): self;

    /**
     * Get icon class
     *
     * @return string|null
     */
    public function getIconClass(): ?string;

    /**
     * Set icon class
     *
     * @param string|null $iconClass
     * @return $this
     */
    public function setIconClass(?string $iconClass): self;

    /**
     * Get content
     *
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * Set content
     *
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): self;

    /**
     * Get columns
     *
     * @return int
     */
    public function getColumns(): int;

    /**
     * Set columns
     *
     * @param int $columns
     * @return $this
     */
    public function setColumns(int $columns): self;

    /**
     * Get is active
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Set is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * Get open new tab
     *
     * @return bool
     */
    public function getOpenNewTab(): bool;

    /**
     * Set open new tab
     *
     * @param bool $openNewTab
     * @return $this
     */
    public function setOpenNewTab(bool $openNewTab): self;

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Set position
     *
     * @param int $position
     * @return $this
     */
    public function setPosition(int $position): self;

    /**
     * Get level
     *
     * @return int
     */
    public function getLevel(): int;

    /**
     * Set level
     *
     * @param int $level
     * @return $this
     */
    public function setLevel(int $level): self;

    /**
     * Get path
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Set path
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): self;

    /**
     * Get child items
     *
     * @return \Panth\MegaMenu\Api\Data\ItemInterface[]
     */
    public function getChildren(): array;

    /**
     * Set child items
     *
     * @param \Panth\MegaMenu\Api\Data\ItemInterface[] $children
     * @return $this
     */
    public function setChildren(array $children): self;

    /**
     * Check if item has children
     *
     * @return bool
     */
    public function hasChildren(): bool;

    /**
     * Get URL
     *
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * Set URL
     *
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): self;

    /**
     * Get target
     *
     * @return string|null
     */
    public function getTarget(): ?string;

    /**
     * Set target
     *
     * @param string|null $target
     * @return $this
     */
    public function setTarget(?string $target): self;

    /**
     * Get icon library
     *
     * @return string|null
     */
    public function getIconLibrary(): ?string;

    /**
     * Set icon library
     *
     * @param string|null $iconLibrary
     * @return $this
     */
    public function setIconLibrary(?string $iconLibrary): self;

    /**
     * Get show on frontend
     *
     * @return bool
     */
    public function getShowOnFrontend(): bool;

    /**
     * Set show on frontend
     *
     * @param bool $showOnFrontend
     * @return $this
     */
    public function setShowOnFrontend(bool $showOnFrontend): self;

    /**
     * Get submenu columns
     *
     * @return int
     */
    public function getSubmenuColumns(): int;

    /**
     * Set submenu columns
     *
     * @param int $submenuColumns
     * @return $this
     */
    public function setSubmenuColumns(int $submenuColumns): self;

    /**
     * Get background color
     *
     * @return string|null
     */
    public function getBackgroundColor(): ?string;

    /**
     * Set background color
     *
     * @param string|null $backgroundColor
     * @return $this
     */
    public function setBackgroundColor(?string $backgroundColor): self;

    /**
     * Get text color
     *
     * @return string|null
     */
    public function getTextColor(): ?string;

    /**
     * Set text color
     *
     * @param string|null $textColor
     * @return $this
     */
    public function setTextColor(?string $textColor): self;

    /**
     * Get show children
     *
     * @return bool
     */
    public function getShowChildren(): bool;

    /**
     * Set show children
     *
     * @param bool $showChildren
     * @return $this
     */
    public function setShowChildren(bool $showChildren): self;

    /**
     * Get hover effect
     *
     * @return string|null
     */
    public function getHoverEffect(): ?string;

    /**
     * Set hover effect
     *
     * @param string|null $hoverEffect
     * @return $this
     */
    public function setHoverEffect(?string $hoverEffect): self;
}

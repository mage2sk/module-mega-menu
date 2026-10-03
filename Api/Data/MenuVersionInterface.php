<?php
/**
 * Menu Version Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

interface MenuVersionInterface
{
    const VERSION_ID = 'version_id';
    const MENU_ID = 'menu_id';
    const VERSION_NUMBER = 'version_number';
    const TITLE = 'title';
    const IDENTIFIER = 'identifier';
    const ITEMS_JSON = 'items_json';
    const CSS_CLASS = 'css_class';
    const CUSTOM_CSS = 'custom_css';
    const CONTAINER_BG_COLOR = 'container_bg_color';
    const CONTAINER_PADDING = 'container_padding';
    const CONTAINER_MARGIN = 'container_margin';
    const CONTAINER_MAX_WIDTH = 'container_max_width';
    const CONTAINER_BORDER = 'container_border';
    const CONTAINER_BORDER_RADIUS = 'container_border_radius';
    const CONTAINER_BOX_SHADOW = 'container_box_shadow';
    const ITEM_GAP = 'item_gap';
    const IS_ACTIVE = 'is_active';
    const STORE_IDS = 'store_ids';
    const CREATED_AT = 'created_at';
    const CREATED_BY = 'created_by';
    const VERSION_COMMENT = 'version_comment';

    /**
     * Get version ID
     *
     * @return int|null
     */
    public function getVersionId(): ?int;

    /**
     * Set version ID
     *
     * @param int $versionId
     * @return $this
     */
    public function setVersionId(int $versionId): self;

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
     * Get version number
     *
     * @return int|null
     */
    public function getVersionNumber(): ?int;

    /**
     * Set version number
     *
     * @param int $versionNumber
     * @return $this
     */
    public function setVersionNumber(int $versionNumber): self;

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
     * Get identifier
     *
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * Set identifier
     *
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): self;

    /**
     * Get items JSON
     *
     * @return string|null
     */
    public function getItemsJson(): ?string;

    /**
     * Set items JSON
     *
     * @param string|null $itemsJson
     * @return $this
     */
    public function setItemsJson(?string $itemsJson): self;

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
     * Get custom CSS
     *
     * @return string|null
     */
    public function getCustomCss(): ?string;

    /**
     * Set custom CSS
     *
     * @param string|null $customCss
     * @return $this
     */
    public function setCustomCss(?string $customCss): self;

    /**
     * Get container background color
     *
     * @return string|null
     */
    public function getContainerBgColor(): ?string;

    /**
     * Set container background color
     *
     * @param string|null $containerBgColor
     * @return $this
     */
    public function setContainerBgColor(?string $containerBgColor): self;

    /**
     * Get container padding
     *
     * @return string|null
     */
    public function getContainerPadding(): ?string;

    /**
     * Set container padding
     *
     * @param string|null $containerPadding
     * @return $this
     */
    public function setContainerPadding(?string $containerPadding): self;

    /**
     * Get container margin
     *
     * @return string|null
     */
    public function getContainerMargin(): ?string;

    /**
     * Set container margin
     *
     * @param string|null $containerMargin
     * @return $this
     */
    public function setContainerMargin(?string $containerMargin): self;

    /**
     * Get container max width
     *
     * @return string|null
     */
    public function getContainerMaxWidth(): ?string;

    /**
     * Set container max width
     *
     * @param string|null $containerMaxWidth
     * @return $this
     */
    public function setContainerMaxWidth(?string $containerMaxWidth): self;

    /**
     * Get container border
     *
     * @return string|null
     */
    public function getContainerBorder(): ?string;

    /**
     * Set container border
     *
     * @param string|null $containerBorder
     * @return $this
     */
    public function setContainerBorder(?string $containerBorder): self;

    /**
     * Get container border radius
     *
     * @return string|null
     */
    public function getContainerBorderRadius(): ?string;

    /**
     * Set container border radius
     *
     * @param string|null $containerBorderRadius
     * @return $this
     */
    public function setContainerBorderRadius(?string $containerBorderRadius): self;

    /**
     * Get container box shadow
     *
     * @return string|null
     */
    public function getContainerBoxShadow(): ?string;

    /**
     * Set container box shadow
     *
     * @param string|null $containerBoxShadow
     * @return $this
     */
    public function setContainerBoxShadow(?string $containerBoxShadow): self;

    /**
     * Get item gap
     *
     * @return string|null
     */
    public function getItemGap(): ?string;

    /**
     * Set item gap
     *
     * @param string|null $itemGap
     * @return $this
     */
    public function setItemGap(?string $itemGap): self;

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
     * Get store IDs
     *
     * @return string|null
     */
    public function getStoreIds(): ?string;

    /**
     * Set store IDs
     *
     * @param string|null $storeIds
     * @return $this
     */
    public function setStoreIds(?string $storeIds): self;

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
     * Get created by
     *
     * @return string|null
     */
    public function getCreatedBy(): ?string;

    /**
     * Set created by
     *
     * @param string|null $createdBy
     * @return $this
     */
    public function setCreatedBy(?string $createdBy): self;

    /**
     * Get version comment
     *
     * @return string|null
     */
    public function getVersionComment(): ?string;

    /**
     * Set version comment
     *
     * @param string|null $versionComment
     * @return $this
     */
    public function setVersionComment(?string $versionComment): self;
}

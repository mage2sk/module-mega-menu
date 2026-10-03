<?php
/**
 * Menu Interface
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Api\Data;

interface MenuInterface
{
    const MENU_ID = 'menu_id';
    const IDENTIFIER = 'identifier';
    const TITLE = 'title';
    const IS_ACTIVE = 'is_active';
    const CSS_CLASS = 'css_class';
    const SORT_ORDER = 'sort_order';
    const DESCRIPTION = 'description';
    const STORE_IDS = 'store_ids';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const CUSTOM_CSS = 'custom_css';
    const MOBILE_LAYOUT = 'mobile_layout';
    const MENU_TYPE = 'menu_type';
    const ITEMS_JSON = 'items_json';

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
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): self;

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): self;

    /**
     * Get store IDs
     *
     * @return array
     */
    public function getStoreIds(): array;

    /**
     * Set store IDs
     *
     * @param array $storeIds
     * @return $this
     */
    public function setStoreIds(array $storeIds): self;

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
     * Get mobile layout
     *
     * @return string|null
     */
    public function getMobileLayout(): ?string;

    /**
     * Set mobile layout
     *
     * @param string|null $mobileLayout
     * @return $this
     */
    public function setMobileLayout(?string $mobileLayout): self;

    /**
     * Get menu type
     *
     * @return string|null
     */
    public function getMenuType(): ?string;

    /**
     * Set menu type
     *
     * @param string|null $menuType
     * @return $this
     */
    public function setMenuType(?string $menuType): self;

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
}

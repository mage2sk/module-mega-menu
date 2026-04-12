<?php
/**
 * Menu Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\MenuInterface;

class Menu extends AbstractModel implements MenuInterface, IdentityInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_megamenu_menu';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_menu';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\Menu::class);
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
    public function getMenuId(): ?int
    {
        return $this->getData(self::MENU_ID) ? (int)$this->getData(self::MENU_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setMenuId(int $menuId): MenuInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    /**
     * @inheritDoc
     */
    public function getIdentifier(): ?string
    {
        return $this->getData(self::IDENTIFIER);
    }

    /**
     * @inheritDoc
     */
    public function setIdentifier(string $identifier): MenuInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
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
    public function setTitle(string $title): MenuInterface
    {
        return $this->setData(self::TITLE, $title);
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
    public function setIsActive(bool $isActive): MenuInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
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
    public function setCssClass(?string $cssClass): MenuInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
    }

    /**
     * @inheritDoc
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    /**
     * @inheritDoc
     */
    public function setSortOrder(int $sortOrder): MenuInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritDoc
     */
    public function setDescription(?string $description): MenuInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritDoc
     */
    public function getStoreIds(): array
    {
        $storeIds = $this->getData(self::STORE_IDS);
        if (is_string($storeIds)) {
            return explode(',', $storeIds);
        }
        return is_array($storeIds) ? $storeIds : [];
    }

    /**
     * @inheritDoc
     */
    public function setStoreIds(array $storeIds): MenuInterface
    {
        return $this->setData(self::STORE_IDS, $storeIds);
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
    public function setCreatedAt(string $createdAt): MenuInterface
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
    public function setUpdatedAt(string $updatedAt): MenuInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getCustomCss(): ?string
    {
        return $this->getData(self::CUSTOM_CSS);
    }

    /**
     * @inheritDoc
     */
    public function setCustomCss(?string $customCss): MenuInterface
    {
        return $this->setData(self::CUSTOM_CSS, $customCss);
    }

    /**
     * @inheritDoc
     */
    public function getMobileLayout(): ?string
    {
        return $this->getData(self::MOBILE_LAYOUT);
    }

    /**
     * @inheritDoc
     */
    public function setMobileLayout(?string $mobileLayout): MenuInterface
    {
        return $this->setData(self::MOBILE_LAYOUT, $mobileLayout);
    }

    /**
     * @inheritDoc
     */
    public function getMenuType(): ?string
    {
        return $this->getData(self::MENU_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setMenuType(?string $menuType): MenuInterface
    {
        return $this->setData(self::MENU_TYPE, $menuType);
    }

    /**
     * @inheritDoc
     */
    public function getItemsJson(): ?string
    {
        return $this->getData(self::ITEMS_JSON);
    }

    /**
     * @inheritDoc
     */
    public function setItemsJson(?string $itemsJson): MenuInterface
    {
        return $this->setData(self::ITEMS_JSON, $itemsJson);
    }
}

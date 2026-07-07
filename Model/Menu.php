<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\MenuInterface;

class Menu extends AbstractModel implements MenuInterface, IdentityInterface
{
    const CACHE_TAG = 'panth_megamenu_menu';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_megamenu_menu';

    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\Menu::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getMenuId(): ?int
    {
        return $this->getData(self::MENU_ID) ? (int)$this->getData(self::MENU_ID) : null;
    }

    public function setMenuId(int $menuId): MenuInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    public function getIdentifier(): ?string
    {
        return $this->getData(self::IDENTIFIER);
    }

    public function setIdentifier(string $identifier): MenuInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    public function setTitle(string $title): MenuInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): MenuInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getCssClass(): ?string
    {
        return $this->getData(self::CSS_CLASS);
    }

    public function setCssClass(?string $cssClass): MenuInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
    }

    public function getSortOrder(): int
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder): MenuInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    public function setDescription(?string $description): MenuInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getStoreIds(): array
    {
        $storeIds = $this->getData(self::STORE_IDS);
        if (is_string($storeIds)) {
            return explode(',', $storeIds);
        }
        return is_array($storeIds) ? $storeIds : [];
    }

    public function setStoreIds(array $storeIds): MenuInterface
    {
        return $this->setData(self::STORE_IDS, $storeIds);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): MenuInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): MenuInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    public function getCustomCss(): ?string
    {
        return $this->getData(self::CUSTOM_CSS);
    }

    public function setCustomCss(?string $customCss): MenuInterface
    {
        return $this->setData(self::CUSTOM_CSS, $customCss);
    }

    public function getMobileLayout(): ?string
    {
        return $this->getData(self::MOBILE_LAYOUT);
    }

    public function setMobileLayout(?string $mobileLayout): MenuInterface
    {
        return $this->setData(self::MOBILE_LAYOUT, $mobileLayout);
    }

    public function getMenuType(): ?string
    {
        return $this->getData(self::MENU_TYPE);
    }

    public function setMenuType(?string $menuType): MenuInterface
    {
        return $this->setData(self::MENU_TYPE, $menuType);
    }

    public function getItemsJson(): ?string
    {
        return $this->getData(self::ITEMS_JSON);
    }

    public function setItemsJson(?string $itemsJson): MenuInterface
    {
        return $this->setData(self::ITEMS_JSON, $itemsJson);
    }
}

<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\MenuVersionInterface;

class MenuVersion extends AbstractModel implements MenuVersionInterface
{
    const CACHE_TAG = 'panth_megamenu_menu_version';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'panth_megamenu_menu_version';

    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\MenuVersion::class);
    }

    public function getVersionId(): ?int
    {
        return $this->getData(self::VERSION_ID) ? (int)$this->getData(self::VERSION_ID) : null;
    }

    public function setVersionId(int $versionId): MenuVersionInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
    }

    public function getMenuId(): ?int
    {
        return $this->getData(self::MENU_ID) ? (int)$this->getData(self::MENU_ID) : null;
    }

    public function setMenuId(int $menuId): MenuVersionInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    public function getVersionNumber(): ?int
    {
        return $this->getData(self::VERSION_NUMBER) ? (int)$this->getData(self::VERSION_NUMBER) : null;
    }

    public function setVersionNumber(int $versionNumber): MenuVersionInterface
    {
        return $this->setData(self::VERSION_NUMBER, $versionNumber);
    }

    public function getTitle(): ?string
    {
        return $this->getData(self::TITLE);
    }

    public function setTitle(string $title): MenuVersionInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getIdentifier(): ?string
    {
        return $this->getData(self::IDENTIFIER);
    }

    public function setIdentifier(string $identifier): MenuVersionInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
    }

    public function getItemsJson(): ?string
    {
        return $this->getData(self::ITEMS_JSON);
    }

    public function setItemsJson(?string $itemsJson): MenuVersionInterface
    {
        return $this->setData(self::ITEMS_JSON, $itemsJson);
    }

    public function getCssClass(): ?string
    {
        return $this->getData(self::CSS_CLASS);
    }

    public function setCssClass(?string $cssClass): MenuVersionInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
    }

    public function getCustomCss(): ?string
    {
        return $this->getData(self::CUSTOM_CSS);
    }

    public function setCustomCss(?string $customCss): MenuVersionInterface
    {
        return $this->setData(self::CUSTOM_CSS, $customCss);
    }

    public function getContainerBgColor(): ?string
    {
        return $this->getData(self::CONTAINER_BG_COLOR);
    }

    public function setContainerBgColor(?string $containerBgColor): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BG_COLOR, $containerBgColor);
    }

    public function getContainerPadding(): ?string
    {
        return $this->getData(self::CONTAINER_PADDING);
    }

    public function setContainerPadding(?string $containerPadding): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_PADDING, $containerPadding);
    }

    public function getContainerMargin(): ?string
    {
        return $this->getData(self::CONTAINER_MARGIN);
    }

    public function setContainerMargin(?string $containerMargin): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_MARGIN, $containerMargin);
    }

    public function getContainerMaxWidth(): ?string
    {
        return $this->getData(self::CONTAINER_MAX_WIDTH);
    }

    public function setContainerMaxWidth(?string $containerMaxWidth): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_MAX_WIDTH, $containerMaxWidth);
    }

    public function getContainerBorder(): ?string
    {
        return $this->getData(self::CONTAINER_BORDER);
    }

    public function setContainerBorder(?string $containerBorder): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BORDER, $containerBorder);
    }

    public function getContainerBorderRadius(): ?string
    {
        return $this->getData(self::CONTAINER_BORDER_RADIUS);
    }

    public function setContainerBorderRadius(?string $containerBorderRadius): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BORDER_RADIUS, $containerBorderRadius);
    }

    public function getContainerBoxShadow(): ?string
    {
        return $this->getData(self::CONTAINER_BOX_SHADOW);
    }

    public function setContainerBoxShadow(?string $containerBoxShadow): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BOX_SHADOW, $containerBoxShadow);
    }

    public function getItemGap(): ?string
    {
        return $this->getData(self::ITEM_GAP);
    }

    public function setItemGap(?string $itemGap): MenuVersionInterface
    {
        return $this->setData(self::ITEM_GAP, $itemGap);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive): MenuVersionInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getStoreIds(): ?string
    {
        return $this->getData(self::STORE_IDS);
    }

    public function setStoreIds(?string $storeIds): MenuVersionInterface
    {
        return $this->setData(self::STORE_IDS, $storeIds);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): MenuVersionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getCreatedBy(): ?string
    {
        return $this->getData(self::CREATED_BY);
    }

    public function setCreatedBy(?string $createdBy): MenuVersionInterface
    {
        return $this->setData(self::CREATED_BY, $createdBy);
    }

    public function getVersionComment(): ?string
    {
        return $this->getData(self::VERSION_COMMENT);
    }

    public function setVersionComment(?string $versionComment): MenuVersionInterface
    {
        return $this->setData(self::VERSION_COMMENT, $versionComment);
    }
}

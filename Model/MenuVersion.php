<?php
/**
 * Menu Version Model
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\MegaMenu\Api\Data\MenuVersionInterface;

class MenuVersion extends AbstractModel implements MenuVersionInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'panth_megamenu_menu_version';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'panth_megamenu_menu_version';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Panth\MegaMenu\Model\ResourceModel\MenuVersion::class);
    }

    /**
     * @inheritDoc
     */
    public function getVersionId(): ?int
    {
        return $this->getData(self::VERSION_ID) ? (int)$this->getData(self::VERSION_ID) : null;
    }

    /**
     * @inheritDoc
     */
    public function setVersionId(int $versionId): MenuVersionInterface
    {
        return $this->setData(self::VERSION_ID, $versionId);
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
    public function setMenuId(int $menuId): MenuVersionInterface
    {
        return $this->setData(self::MENU_ID, $menuId);
    }

    /**
     * @inheritDoc
     */
    public function getVersionNumber(): ?int
    {
        return $this->getData(self::VERSION_NUMBER) ? (int)$this->getData(self::VERSION_NUMBER) : null;
    }

    /**
     * @inheritDoc
     */
    public function setVersionNumber(int $versionNumber): MenuVersionInterface
    {
        return $this->setData(self::VERSION_NUMBER, $versionNumber);
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
    public function setTitle(string $title): MenuVersionInterface
    {
        return $this->setData(self::TITLE, $title);
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
    public function setIdentifier(string $identifier): MenuVersionInterface
    {
        return $this->setData(self::IDENTIFIER, $identifier);
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
    public function setItemsJson(?string $itemsJson): MenuVersionInterface
    {
        return $this->setData(self::ITEMS_JSON, $itemsJson);
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
    public function setCssClass(?string $cssClass): MenuVersionInterface
    {
        return $this->setData(self::CSS_CLASS, $cssClass);
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
    public function setCustomCss(?string $customCss): MenuVersionInterface
    {
        return $this->setData(self::CUSTOM_CSS, $customCss);
    }

    /**
     * @inheritDoc
     */
    public function getContainerBgColor(): ?string
    {
        return $this->getData(self::CONTAINER_BG_COLOR);
    }

    /**
     * @inheritDoc
     */
    public function setContainerBgColor(?string $containerBgColor): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BG_COLOR, $containerBgColor);
    }

    /**
     * @inheritDoc
     */
    public function getContainerPadding(): ?string
    {
        return $this->getData(self::CONTAINER_PADDING);
    }

    /**
     * @inheritDoc
     */
    public function setContainerPadding(?string $containerPadding): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_PADDING, $containerPadding);
    }

    /**
     * @inheritDoc
     */
    public function getContainerMargin(): ?string
    {
        return $this->getData(self::CONTAINER_MARGIN);
    }

    /**
     * @inheritDoc
     */
    public function setContainerMargin(?string $containerMargin): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_MARGIN, $containerMargin);
    }

    /**
     * @inheritDoc
     */
    public function getContainerMaxWidth(): ?string
    {
        return $this->getData(self::CONTAINER_MAX_WIDTH);
    }

    /**
     * @inheritDoc
     */
    public function setContainerMaxWidth(?string $containerMaxWidth): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_MAX_WIDTH, $containerMaxWidth);
    }

    /**
     * @inheritDoc
     */
    public function getContainerBorder(): ?string
    {
        return $this->getData(self::CONTAINER_BORDER);
    }

    /**
     * @inheritDoc
     */
    public function setContainerBorder(?string $containerBorder): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BORDER, $containerBorder);
    }

    /**
     * @inheritDoc
     */
    public function getContainerBorderRadius(): ?string
    {
        return $this->getData(self::CONTAINER_BORDER_RADIUS);
    }

    /**
     * @inheritDoc
     */
    public function setContainerBorderRadius(?string $containerBorderRadius): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BORDER_RADIUS, $containerBorderRadius);
    }

    /**
     * @inheritDoc
     */
    public function getContainerBoxShadow(): ?string
    {
        return $this->getData(self::CONTAINER_BOX_SHADOW);
    }

    /**
     * @inheritDoc
     */
    public function setContainerBoxShadow(?string $containerBoxShadow): MenuVersionInterface
    {
        return $this->setData(self::CONTAINER_BOX_SHADOW, $containerBoxShadow);
    }

    /**
     * @inheritDoc
     */
    public function getItemGap(): ?string
    {
        return $this->getData(self::ITEM_GAP);
    }

    /**
     * @inheritDoc
     */
    public function setItemGap(?string $itemGap): MenuVersionInterface
    {
        return $this->setData(self::ITEM_GAP, $itemGap);
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
    public function setIsActive(bool $isActive): MenuVersionInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritDoc
     */
    public function getStoreIds(): ?string
    {
        return $this->getData(self::STORE_IDS);
    }

    /**
     * @inheritDoc
     */
    public function setStoreIds(?string $storeIds): MenuVersionInterface
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
    public function setCreatedAt(string $createdAt): MenuVersionInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedBy(): ?string
    {
        return $this->getData(self::CREATED_BY);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedBy(?string $createdBy): MenuVersionInterface
    {
        return $this->setData(self::CREATED_BY, $createdBy);
    }

    /**
     * @inheritDoc
     */
    public function getVersionComment(): ?string
    {
        return $this->getData(self::VERSION_COMMENT);
    }

    /**
     * @inheritDoc
     */
    public function setVersionComment(?string $versionComment): MenuVersionInterface
    {
        return $this->setData(self::VERSION_COMMENT, $versionComment);
    }
}

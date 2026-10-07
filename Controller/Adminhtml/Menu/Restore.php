<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;

class Restore extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu_version_restore';

    protected $menuFactory;

    protected $menuVersionFactory;

    protected $menuResource;

    protected $menuVersionResource;

    protected $authSession;

    public function __construct(
        Context $context,
        MenuFactory $menuFactory,
        MenuVersionFactory $menuVersionFactory,
        MenuResource $menuResource,
        MenuVersionResource $menuVersionResource,
        Session $authSession
    ) {
        $this->menuFactory = $menuFactory;
        $this->menuVersionFactory = $menuVersionFactory;
        $this->menuResource = $menuResource;
        $this->menuVersionResource = $menuVersionResource;
        $this->authSession = $authSession;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $versionId = (int)$this->getRequest()->getParam('version_id');

        if (!$versionId) {
            $this->messageManager->addErrorMessage(__('Version ID is required.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $version = $this->menuVersionFactory->create();
            $this->menuVersionResource->load($version, $versionId);

            if (!$version->getId()) {
                throw new LocalizedException(__('Version not found.'));
            }

            $menuId = $version->getMenuId();

            $menu = $this->menuFactory->create();
            $this->menuResource->load($menu, $menuId);

            if (!$menu->getId()) {
                throw new LocalizedException(__('Menu no longer exists and cannot be restored.'));
            }

            $oldVersionNumber = $version->getVersionNumber();

            $menu->setTitle($version->getTitle());
            $menu->setIdentifier($version->getIdentifier());
            $menu->setItemsJson($version->getItemsJson());
            $menu->setCssClass($version->getCssClass());
            $menu->setCustomCss($version->getCustomCss());
            $menu->setData('container_bg_color', $version->getContainerBgColor());
            $menu->setData('container_padding', $version->getContainerPadding());
            $menu->setData('container_margin', $version->getContainerMargin());
            $menu->setData('item_gap', $version->getItemGap());
            $menu->setData('container_max_width', $version->getContainerMaxWidth());
            $menu->setData('container_border', $version->getContainerBorder());
            $menu->setData('container_border_radius', $version->getContainerBorderRadius());
            $menu->setData('container_box_shadow', $version->getContainerBoxShadow());
            $menu->setIsActive($version->getIsActive());

            if ($version->getStoreIds()) {
                $storeIds = explode(',', $version->getStoreIds());
                $menu->setStoreIds($storeIds);
            }

            $this->menuResource->save($menu);

            $newVersion = $this->menuVersionFactory->create();
            $nextVersionNumber = $this->menuVersionResource->getNextVersionNumber($menuId);

            $newVersion->setMenuId($menuId);
            $newVersion->setVersionNumber($nextVersionNumber);
            $newVersion->setTitle($menu->getTitle());
            $newVersion->setIdentifier($menu->getIdentifier());
            $newVersion->setItemsJson($menu->getItemsJson());
            $newVersion->setCssClass($menu->getCssClass());
            $newVersion->setCustomCss($menu->getCustomCss());
            $newVersion->setContainerBgColor($menu->getData('container_bg_color'));
            $newVersion->setContainerPadding($menu->getData('container_padding'));
            $newVersion->setContainerMargin($menu->getData('container_margin'));
            $newVersion->setItemGap($menu->getData('item_gap'));
            $newVersion->setContainerMaxWidth($menu->getData('container_max_width'));
            $newVersion->setContainerBorder($menu->getData('container_border'));
            $newVersion->setContainerBorderRadius($menu->getData('container_border_radius'));
            $newVersion->setContainerBoxShadow($menu->getData('container_box_shadow'));
            $newVersion->setIsActive($menu->getIsActive());

            if ($menu->getStoreIds()) {
                $storeIds = is_array($menu->getStoreIds()) ? $menu->getStoreIds() : [$menu->getStoreIds()];
                $newVersion->setStoreIds(implode(',', $storeIds));
            }

            $versionComment = sprintf('Restored from version #%d', $oldVersionNumber);
            $newVersion->setVersionComment($versionComment);

            $user = $this->authSession->getUser();
            if ($user) {
                $newVersion->setCreatedBy($user->getUserName());
            }

            $itemsJson = $menu->getItemsJson();
            $items = json_decode($itemsJson, true);
            $itemCount = is_array($items) ? count($items) : 0;
            $newVersion->setItemCount($itemCount);

            $this->menuVersionResource->save($newVersion);

            $this->messageManager->addSuccessMessage(
                __('Menu has been restored from version #%1. A new version #%2 has been created.',
                    $oldVersionNumber,
                    $nextVersionNumber
                )
            );

            return $resultRedirect->setPath('*/*/edit', ['menu_id' => $menuId]);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while restoring the menu from version.')
            );
        }

        return $resultRedirect->setPath('*/*/');
    }
}

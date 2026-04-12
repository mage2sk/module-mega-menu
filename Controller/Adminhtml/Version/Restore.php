<?php
/**
 * Restore Menu from Version Controller
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Version;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;
use Panth\MegaMenu\Api\MenuRepositoryInterface;

class Restore extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level for restore action
     */
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    /**
     * @var MenuFactory
     */
    protected $menuFactory;

    /**
     * @var MenuVersionFactory
     */
    protected $menuVersionFactory;

    /**
     * @var MenuResource
     */
    protected $menuResource;

    /**
     * @var MenuVersionResource
     */
    protected $menuVersionResource;

    /**
     * @var MenuRepositoryInterface
     */
    protected $menuRepository;

    /**
     * @param Context $context
     * @param MenuFactory $menuFactory
     * @param MenuVersionFactory $menuVersionFactory
     * @param MenuResource $menuResource
     * @param MenuVersionResource $menuVersionResource
     * @param MenuRepositoryInterface $menuRepository
     */
    public function __construct(
        Context $context,
        MenuFactory $menuFactory,
        MenuVersionFactory $menuVersionFactory,
        MenuResource $menuResource,
        MenuVersionResource $menuVersionResource,
        MenuRepositoryInterface $menuRepository
    ) {
        $this->menuFactory = $menuFactory;
        $this->menuVersionFactory = $menuVersionFactory;
        $this->menuResource = $menuResource;
        $this->menuVersionResource = $menuVersionResource;
        $this->menuRepository = $menuRepository;
        parent::__construct($context);
    }

    /**
     * Restore menu from version
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $versionId = (int)$this->getRequest()->getParam('version_id');

        if (!$versionId) {
            $this->messageManager->addErrorMessage(__('Version ID is required.'));
            return $resultRedirect->setPath('panth_menu/menu/');
        }

        try {
            // Load the version
            $version = $this->menuVersionFactory->create();
            $this->menuVersionResource->load($version, $versionId);

            if (!$version->getId()) {
                throw new LocalizedException(__('Version not found.'));
            }

            $menuId = $version->getMenuId();

            // Load the current menu
            $menu = $this->menuFactory->create();
            $this->menuResource->load($menu, $menuId);

            if (!$menu->getId()) {
                throw new LocalizedException(__('Menu no longer exists and cannot be restored.'));
            }

            // Store old version number for the message
            $oldVersionNumber = $version->getVersionNumber();

            // Restore all fields from version to current menu
            $menu->setTitle($version->getTitle());
            $menu->setIdentifier($version->getIdentifier());
            $menu->setItemsJson($version->getItemsJson());
            $menu->setCssClass($version->getCssClass());
            $menu->setCustomCss($version->getCustomCss());
            $menu->setData('container_bg_color', $version->getData('container_bg_color'));
            $menu->setData('container_padding', $version->getData('container_padding'));
            $menu->setData('container_margin', $version->getData('container_margin'));
            $menu->setData('item_gap', $version->getData('item_gap'));
            $menu->setData('container_max_width', $version->getData('container_max_width'));
            $menu->setData('container_border', $version->getData('container_border'));
            $menu->setData('container_border_radius', $version->getData('container_border_radius'));
            $menu->setData('container_box_shadow', $version->getData('container_box_shadow'));
            $menu->setData('menu_alignment', $version->getData('menu_alignment'));
            $menu->setIsActive($version->getIsActive());
            $menu->setSortOrder($version->getSortOrder());
            $menu->setDescription($version->getDescription());

            // Set version comment for the new version that will be created
            $menu->setData('version_comment', sprintf('Restored from version #%d', $oldVersionNumber));

            // Save through repository to trigger versioning
            $this->menuRepository->save($menu);

            $this->messageManager->addSuccessMessage(
                __('Menu has been restored from version #%1.', $oldVersionNumber)
            );

            return $resultRedirect->setPath('panth_menu/menu/edit', ['menu_id' => $menuId]);

        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('An error occurred while restoring the menu from version.')
            );
        }

        return $resultRedirect->setPath('panth_menu/menu/');
    }
}

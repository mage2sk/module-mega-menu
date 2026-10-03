<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Panth\MegaMenu\Model\MenuFactory;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Api\MenuRepositoryInterface;

class Save extends Action implements HttpPostActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    protected $dataPersistor;

    protected $menuFactory;

    protected $menuResource;

    protected $menuRepository;

    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        MenuFactory $menuFactory,
        MenuResource $menuResource,
        MenuRepositoryInterface $menuRepository
    ) {
        $this->dataPersistor = $dataPersistor;
        $this->menuFactory = $menuFactory;
        $this->menuResource = $menuResource;
        $this->menuRepository = $menuRepository;
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            $menuId = $this->getRequest()->getParam('menu_id');

            try {
                $menu = $this->menuFactory->create();

                if ($menuId) {
                    $this->menuResource->load($menu, $menuId);
                    if (!$menu->getId()) {
                        $this->messageManager->addErrorMessage(__('This menu no longer exists.'));
                        return $resultRedirect->setPath('*/*/');
                    }
                } else {
                    if (!empty($data['identifier'])) {
                        $existingMenu = $this->menuFactory->create();
                        $this->menuResource->load($existingMenu, $data['identifier'], 'identifier');
                        if ($existingMenu->getId()) {
                            throw new LocalizedException(
                                __('Menu identifier "%1" already exists. Please use a different identifier.', $data['identifier'])
                            );
                        }
                    }
                }

                $menu->setData([
                    'title' => $data['title'] ?? '',
                    'identifier' => $data['identifier'] ?? '',
                    'is_active' => $data['is_active'] ?? 0,
                    'css_class' => $data['css_class'] ?? '',
                    'sort_order' => $data['sort_order'] ?? 0,
                    'description' => $data['description'] ?? '',
                    'custom_css' => $data['custom_css'] ?? '',
                    'items_json' => $data['items_json'] ?? '[]',
                    'container_bg_color' => $data['container_bg_color'] ?? '',
                    'container_padding' => $data['container_padding'] ?? '',
                    'container_margin' => $data['container_margin'] ?? '',
                    'item_gap' => $data['item_gap'] ?? '',
                    'container_max_width' => $data['container_max_width'] ?? '',
                    'container_border' => $data['container_border'] ?? '',
                    'container_border_radius' => $data['container_border_radius'] ?? '',
                    'container_box_shadow' => $data['container_box_shadow'] ?? '',
                ]);

                if ($menuId) {
                    $menu->setId($menuId);
                }

                $this->menuRepository->save($menu);
                $savedMenuId = $menu->getId();

                $connection = $this->menuResource->getConnection();
                $storeTable = $this->menuResource->getTable('panth_megamenu_store');

                $connection->delete($storeTable, ['menu_id = ?' => $savedMenuId]);

                $connection->insert($storeTable, [
                    'menu_id' => $savedMenuId,
                    'store_id' => 0
                ]);

                $this->messageManager->addSuccessMessage(__('The menu has been saved.'));
                $this->dataPersistor->clear('panth_megamenu_menu');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['menu_id' => $savedMenuId]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while saving the menu.')
                );
            }

            $this->dataPersistor->set('panth_megamenu_menu', $data);
            return $resultRedirect->setPath('*/*/edit', ['menu_id' => $menuId]);
        }

        return $resultRedirect->setPath('*/*/');
    }
}

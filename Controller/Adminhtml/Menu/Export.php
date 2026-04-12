<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Panth\MegaMenu\Model\MenuFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

/**
 * Export Menu as JSON
 */
class Export extends Action implements CsrfAwareActionInterface
{
    protected $jsonFactory;
    protected $menuFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        MenuFactory $menuFactory
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->menuFactory = $menuFactory;
    }

    public function execute()
    {
        try {
            $menuId = $this->getRequest()->getParam('menu_id');
            $ajax = $this->getRequest()->getParam('ajax');

            if (!$menuId) {
                if ($ajax) {
                    $result = $this->jsonFactory->create();
                    return $result->setData([
                        'success' => false,
                        'message' => 'Menu ID is required'
                    ]);
                }
                $this->messageManager->addErrorMessage(__('Menu ID is required'));
                return $this->_redirect('*/*/index');
            }

            $menu = $this->menuFactory->create()->load($menuId);

            if (!$menu->getId()) {
                if ($ajax) {
                    $result = $this->jsonFactory->create();
                    return $result->setData([
                        'success' => false,
                        'message' => 'Menu not found'
                    ]);
                }
                $this->messageManager->addErrorMessage(__('Menu not found'));
                return $this->_redirect('*/*/index');
            }

            // Prepare export data
            $exportData = [
                'menu' => [
                    'title' => $menu->getTitle(),
                    'identifier' => $menu->getIdentifier(),
                    'menu_type' => $menu->getMenuType(),
                    'is_active' => $menu->getIsActive(),
                    'css_class' => $menu->getCssClass(),
                    'sort_order' => $menu->getSortOrder(),
                    'description' => $menu->getDescription(),
                    'custom_css' => $menu->getCustomCss(),
                    'mobile_layout' => $menu->getMobileLayout(),
                    'created_at' => $menu->getCreatedAt(),
                    'updated_at' => $menu->getUpdatedAt()
                ],
                'items' => json_decode($menu->getItemsJson() ?: '[]', true),
                'export_info' => [
                    'exported_at' => date('Y-m-d H:i:s'),
                    'exported_by' => 'Panth MegaMenu',
                    'version' => '1.0.0'
                ]
            ];

            $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
            $fileName = 'menu_' . $menu->getIdentifier() . '_' . date('Ymd_His') . '.json';

            // If AJAX request, return data for JavaScript to handle
            if ($ajax) {
                $result = $this->jsonFactory->create();
                $this->getResponse()->setHeader('Content-Type', 'application/json', true);
                return $result->setData([
                    'success' => true,
                    'data' => $exportData,
                    'message' => 'Menu exported successfully'
                ]);
            }

            // Otherwise, download the file
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-Type', 'application/json', true)
                ->setHeader('Content-Length', strlen($jsonContent), true)
                ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
                ->setHeader('Last-Modified', date('r'), true);

            $this->getResponse()->setBody($jsonContent);
            return $this->getResponse();

        } catch (\Exception $e) {
            if ($this->getRequest()->getParam('ajax')) {
                $result = $this->jsonFactory->create();
                return $result->setData([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ]);
            }
            $this->messageManager->addErrorMessage(__('Error: %1', $e->getMessage()));
            return $this->_redirect('*/*/index');
        }
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Panth_MegaMenu::menu');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}

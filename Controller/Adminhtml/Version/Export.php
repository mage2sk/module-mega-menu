<?php
namespace Panth\MegaMenu\Controller\Adminhtml\Version;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Panth\MegaMenu\Model\MenuVersionFactory;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as MenuVersionResource;

/**
 * Export Menu Version as JSON
 */
class Export extends Action implements CsrfAwareActionInterface
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    /**
     * @var MenuVersionFactory
     */
    protected $menuVersionFactory;

    /**
     * @var MenuVersionResource
     */
    protected $menuVersionResource;

    /**
     * @param Context $context
     * @param MenuVersionFactory $menuVersionFactory
     * @param MenuVersionResource $menuVersionResource
     */
    public function __construct(
        Context $context,
        MenuVersionFactory $menuVersionFactory,
        MenuVersionResource $menuVersionResource
    ) {
        parent::__construct($context);
        $this->menuVersionFactory = $menuVersionFactory;
        $this->menuVersionResource = $menuVersionResource;
    }

    /**
     * Export version as JSON
     */
    public function execute()
    {
        try {
            $versionId = (int) $this->getRequest()->getParam('version_id');

            if (!$versionId) {
                $this->messageManager->addErrorMessage(__('Version ID is required'));
                return $this->_redirect('*/*/index');
            }

            $version = $this->menuVersionFactory->create();
            $this->menuVersionResource->load($version, $versionId);

            if (!$version->getId()) {
                $this->messageManager->addErrorMessage(__('Version not found'));
                return $this->_redirect('*/*/index');
            }

            // Prepare export data
            $exportData = [
                'menu' => [
                    'menu_id' => $version->getMenuId(),
                    'title' => $version->getTitle(),
                    'identifier' => $version->getIdentifier(),
                    'is_active' => $version->getIsActive(),
                    'css_class' => $version->getCssClass(),
                    'sort_order' => $version->getSortOrder(),
                    'description' => $version->getDescription(),
                    'custom_css' => $version->getCustomCss(),
                    'container_bg_color' => $version->getData('container_bg_color'),
                    'container_padding' => $version->getData('container_padding'),
                    'container_margin' => $version->getData('container_margin'),
                    'item_gap' => $version->getData('item_gap'),
                    'container_max_width' => $version->getData('container_max_width'),
                    'container_border' => $version->getData('container_border'),
                    'container_border_radius' => $version->getData('container_border_radius'),
                    'container_box_shadow' => $version->getData('container_box_shadow'),
                    'menu_alignment' => $version->getData('menu_alignment'),
                ],
                'items' => json_decode($version->getItemsJson() ?: '[]', true),
                'version_info' => [
                    'version_number' => $version->getVersionNumber(),
                    'created_at' => $version->getCreatedAt(),
                    'created_by' => $version->getCreatedBy(),
                    'version_comment' => $version->getVersionComment(),
                    'exported_at' => date('Y-m-d H:i:s'),
                    'exported_by' => 'Panth MegaMenu'
                ]
            ];

            $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
            $fileName = 'menu_' . $version->getIdentifier() . '_version_' . $version->getVersionNumber() . '_' . date('Ymd_His') . '.json';

            // Download the file
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

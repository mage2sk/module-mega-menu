<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\PageCache\Model\Cache\Type as FpcType;

/**
 * Flush FPC cache via AJAX for preview
 */
class FlushCache extends Action
{
    public const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    private TypeListInterface $cacheTypeList;
    private JsonFactory $jsonFactory;

    public function __construct(
        Context $context,
        TypeListInterface $cacheTypeList,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->cacheTypeList = $cacheTypeList;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Flush page cache and block HTML cache (where menu renders)
            $this->cacheTypeList->cleanType('full_page');
            $this->cacheTypeList->cleanType('block_html');

            return $result->setData(['success' => true, 'message' => 'Cache flushed']);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

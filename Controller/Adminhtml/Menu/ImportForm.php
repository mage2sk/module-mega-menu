<?php
/**
 * MegaMenu Import Form Controller
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class ImportForm extends Action
{
    const ADMIN_RESOURCE = 'Panth_MegaMenu::menu';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Panth_MegaMenu::menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Import Menu'));

        return $resultPage;
    }
}

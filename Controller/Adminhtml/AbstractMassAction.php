<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Api\MenuRepositoryInterface;
use Panth\MegaMenu\Model\ResourceModel\Menu\CollectionFactory;

abstract class AbstractMassAction extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_MegaMenu::menu_save';

    protected Filter $filter;

    protected CollectionFactory $collectionFactory;

    protected MenuRepositoryInterface $menuRepository;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        MenuRepositoryInterface $menuRepository
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->menuRepository = $menuRepository;
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('*/*/');

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection->getItems() as $menu) {
                $this->processMenu($menu);
                $count++;
            }
            $this->messageManager->addSuccessMessage($this->getSuccessMessage($count));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect;
    }

    abstract protected function processMenu(MenuInterface $menu): void;

    abstract protected function getSuccessMessage(int $count): string;
}

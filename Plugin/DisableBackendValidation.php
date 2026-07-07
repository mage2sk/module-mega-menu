<?php
namespace Panth\MegaMenu\Plugin;

use Magento\Backend\App\Request\BackendValidator;
use Magento\Framework\App\RequestInterface;

class DisableBackendValidation
{
    public function aroundValidate(
        BackendValidator $subject,
        callable $proceed,
        RequestInterface $request,
        \Magento\Framework\App\ActionInterface $action
    ) {
        $actionName = get_class($action);

        $allowedActions = [
            'Panth\MegaMenu\Controller\Adminhtml\Menu\CustomSave\Interceptor',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\CustomSave',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\GetCategories\Interceptor',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\GetCategories',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\ImportCategories\Interceptor',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\ImportCategories',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\GetIcons\Interceptor',
            'Panth\MegaMenu\Controller\Adminhtml\Menu\GetIcons',
        ];

        if (in_array($actionName, $allowedActions)) {
            return true;
        }

        return $proceed($request, $action);
    }
}

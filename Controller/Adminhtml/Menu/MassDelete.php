<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Controller\Adminhtml\AbstractMassAction;

class MassDelete extends AbstractMassAction
{
    public const ADMIN_RESOURCE = 'Panth_MegaMenu::menu_delete';

    protected function processMenu(MenuInterface $menu): void
    {
        $this->menuRepository->delete($menu);
    }

    protected function getSuccessMessage(int $count): string
    {
        return (string) __('A total of %1 menu(s) have been deleted.', $count);
    }
}

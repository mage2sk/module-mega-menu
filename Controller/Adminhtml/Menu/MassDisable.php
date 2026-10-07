<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Controller\Adminhtml\AbstractMassAction;

class MassDisable extends AbstractMassAction
{
    protected function processMenu(MenuInterface $menu): void
    {
        if ((int) $menu->getIsActive() === 0) {
            return;
        }
        $menu->setIsActive(false);
        $this->menuRepository->save($menu);
    }

    protected function getSuccessMessage(int $count): string
    {
        return (string) __('A total of %1 menu(s) have been disabled.', $count);
    }
}

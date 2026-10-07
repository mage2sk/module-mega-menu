<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Controller\Adminhtml\Menu;

use Panth\MegaMenu\Api\Data\MenuInterface;
use Panth\MegaMenu\Controller\Adminhtml\AbstractMassAction;

class MassEnable extends AbstractMassAction
{
    protected function processMenu(MenuInterface $menu): void
    {
        if ((int) $menu->getIsActive() === 1) {
            return;
        }
        $menu->setIsActive(true);
        $this->menuRepository->save($menu);
    }

    protected function getSuccessMessage(int $count): string
    {
        return (string) __('A total of %1 menu(s) have been enabled.', $count);
    }
}

<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\Controller;

use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Registry;
use Panth\MegaMenu\Model\Menu;
use Panth\MegaMenu\Model\MenuVersion;
use Panth\MegaMenu\Model\ResourceModel\Menu as MenuResource;
use Panth\MegaMenu\Model\ResourceModel\MenuVersion as VersionResource;

trait MenuModelTrait
{
    private function newMenu(array $data = []): Menu
    {
        $resource = $this->createStub(MenuResource::class);
        $resource->method('getIdFieldName')->willReturn('menu_id');

        return new Menu($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    private function newVersion(array $data = []): MenuVersion
    {
        $resource = $this->createStub(VersionResource::class);
        $resource->method('getIdFieldName')->willReturn('version_id');

        return new MenuVersion($this->createStub(ModelContext::class), $this->createStub(Registry::class), $resource, null, $data);
    }

    private function selfLoadingMenu(array $rows, ?callable $onSave = null): Menu
    {
        $menu = $this->getMockBuilder(Menu::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load', 'save'])
            ->getMock();
        (new \ReflectionProperty($menu, '_idFieldName'))->setValue($menu, 'menu_id');
        $menu->method('load')->willReturnCallback(function ($value, $field = null) use ($menu, $rows) {
            foreach ($rows as $row) {
                if ((string) ($row[$field ?? 'menu_id'] ?? '') === (string) $value) {
                    $menu->setData($row);
                }
            }
            return $menu;
        });
        $menu->method('save')->willReturnCallback(function () use ($menu, $onSave) {
            if ($onSave !== null) {
                $onSave($menu);
            }
            return $menu;
        });

        return $menu;
    }

    private function loadingMenuResource(array $rows, string $class = MenuResource::class): object
    {
        $resource = $this->createStub($class);
        $resource->method('load')->willReturnCallback(function ($model, $value, $field = null) use ($rows, $resource) {
            foreach ($rows as $row) {
                $key = $field ?? array_key_first($row);
                if (isset($row[$key]) && (string) $row[$key] === (string) $value) {
                    $model->setData($row);
                    break;
                }
            }
            return $resource;
        });

        return $resource;
    }
}

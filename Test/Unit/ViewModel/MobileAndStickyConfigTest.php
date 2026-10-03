<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Test\Unit\ViewModel;

use Panth\MegaMenu\Helper\Data;
use Panth\MegaMenu\ViewModel\MobileConfig;
use Panth\MegaMenu\ViewModel\StickyConfig;
use PHPUnit\Framework\TestCase;

class MobileAndStickyConfigTest extends TestCase
{
    use ConfigHelperTrait;

    public function testMobileDefaults(): void
    {
        $vm = new MobileConfig($this->dataHelper());

        $this->assertFalse($vm->isEnabled());
        $this->assertSame('left', $vm->getPosition());
        $this->assertFalse($vm->isOverlayEnabled());
        $this->assertFalse($vm->isSwipeEnabled());
        $this->assertFalse($vm->isAccordionEnabled());
        $this->assertSame(300, $vm->getAnimationSpeed());
        $this->assertFalse($vm->showCategoryIcons());
    }

    public function testMobileConfigured(): void
    {
        $vm = new MobileConfig($this->dataHelper([
            Data::XML_PATH_MOBILE_ENABLED => 1,
            Data::XML_PATH_MOBILE_POSITION => 'right',
            Data::XML_PATH_MOBILE_OVERLAY => 1,
            Data::XML_PATH_MOBILE_SWIPE => 1,
            Data::XML_PATH_MOBILE_ACCORDION => 1,
            Data::XML_PATH_MOBILE_ANIMATION_SPEED => '150',
            Data::XML_PATH_MOBILE_SHOW_ICONS => 1,
        ]));

        $this->assertTrue($vm->isEnabled());
        $this->assertSame('right', $vm->getPosition());
        $this->assertTrue($vm->isOverlayEnabled());
        $this->assertTrue($vm->isSwipeEnabled());
        $this->assertTrue($vm->isAccordionEnabled());
        $this->assertSame(150, $vm->getAnimationSpeed());
        $this->assertTrue($vm->showCategoryIcons());
    }

    public function testStickyDefaultsAndConfigured(): void
    {
        $defaults = new StickyConfig($this->dataHelper());
        $this->assertFalse($defaults->isEnabled());
        $this->assertSame(100, $defaults->getOffset());
        $this->assertSame(300, $defaults->getAnimationSpeed());

        $vm = new StickyConfig($this->dataHelper([
            Data::XML_PATH_STICKY_MENU => 1,
            Data::XML_PATH_STICKY_OFFSET => '40',
            Data::XML_PATH_STICKY_HIDE_ON_SCROLL_DOWN => 1,
            Data::XML_PATH_STICKY_SHOW_ON_SCROLL_UP => 1,
            Data::XML_PATH_STICKY_COMPACT_MODE => 1,
            Data::XML_PATH_STICKY_ANIMATION_SPEED => '90',
            Data::XML_PATH_STICKY_SHOW_SHADOW => 1,
        ]));

        $this->assertTrue($vm->isEnabled());
        $this->assertSame(40, $vm->getOffset());
        $this->assertTrue($vm->hideOnScrollDown());
        $this->assertTrue($vm->showOnScrollUp());
        $this->assertTrue($vm->isCompactMode());
        $this->assertSame(90, $vm->getAnimationSpeed());
        $this->assertTrue($vm->showShadow());
    }
}

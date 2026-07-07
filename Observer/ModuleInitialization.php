<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Panth\MegaMenu\Model\InitFlag;
use Psr\Log\LoggerInterface;

class ModuleInitialization implements ObserverInterface
{
    private $initFlag;

    private $logger;

    public function __construct(
        InitFlag $initFlag,
        LoggerInterface $logger
    ) {
        $this->initFlag = $initFlag;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        if ($this->initFlag->isInitialized()) {
            return;
        }

        try {
            $this->initFlag->markAsInitialized();
        } catch (\Exception $e) {
        }
    }
}

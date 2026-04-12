<?php
/**
 * Module Initialization Observer
 *
 * Runs ONE TIME when module is first configured
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Panth\MegaMenu\Model\InitFlag;
use Psr\Log\LoggerInterface;

class ModuleInitialization implements ObserverInterface
{
    /**
     * @var InitFlag
     */
    private $initFlag;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param InitFlag $initFlag
     * @param LoggerInterface $logger
     */
    public function __construct(
        InitFlag $initFlag,
        LoggerInterface $logger
    ) {
        $this->initFlag = $initFlag;
        $this->logger = $logger;
    }

    /**
     * Execute one-time initialization
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        // Check if already initialized
        if ($this->initFlag->isInitialized()) {
            return;
        }

        try {
            // Perform one-time initialization tasks
            // Mark as initialized
            $this->initFlag->markAsInitialized();
        } catch (\Exception $e) {
            // Silently handle errors
        }
    }
}

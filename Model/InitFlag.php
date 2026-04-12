<?php
/**
 * One-Time Initialization Flag Model
 *
 * Tracks whether module initialization has been run
 *
 * @category  Panth
 * @package   Panth_MegaMenu
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

class InitFlag
{
    const XML_PATH_INIT_FLAG = 'panth_megamenu/system/initialized';
    const XML_PATH_INIT_DATE = 'panth_megamenu/system/init_date';

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     */
    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        DateTime $dateTime
    ) {
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->dateTime = $dateTime;
    }

    /**
     * Check if module has been initialized
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::XML_PATH_INIT_FLAG,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Mark module as initialized
     *
     * @return void
     */
    public function markAsInitialized(): void
    {
        $this->configWriter->save(
            self::XML_PATH_INIT_FLAG,
            '1',
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        $this->configWriter->save(
            self::XML_PATH_INIT_DATE,
            $this->dateTime->gmtDate(),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }

    /**
     * Get initialization date
     *
     * @return string|null
     */
    public function getInitDate(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_INIT_DATE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT
        );
    }

    /**
     * Reset initialization flag (for testing)
     *
     * @return void
     */
    public function reset(): void
    {
        $this->configWriter->delete(
            self::XML_PATH_INIT_FLAG,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );

        $this->configWriter->delete(
            self::XML_PATH_INIT_DATE,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
    }
}

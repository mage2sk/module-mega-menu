<?php
namespace Panth\MegaMenu\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Flag\FlagResource;
use Magento\Framework\FlagFactory;

class Webhook extends AbstractHelper
{
    const WEBHOOK_FLAG_PREFIX = 'panth_megamenu_webhook_';
    const WEBHOOK_INTERVAL = 604800;

    protected $curl;

    protected $flagFactory;

    protected $flagResource;

    protected $logger;

    protected $productMetadata;

    public function __construct(
        Context $context,
        Curl $curl,
        FlagFactory $flagFactory,
        FlagResource $flagResource,
        ProductMetadataInterface $productMetadata
    ) {
        $this->curl = $curl;
        $this->flagFactory = $flagFactory;
        $this->flagResource = $flagResource;
        $this->logger = $context->getLogger();
        $this->productMetadata = $productMetadata;
        parent::__construct($context);
    }

    public function sendConfigChangeNotification(array $data): bool
    {
        $eventKey = $data['module'] . '_' . $data['action'];

        if (!$this->shouldSendNotification($eventKey)) {
            return false;
        }

        try {
            $notificationData = array_merge($data, [
                'timestamp' => date('c'),
                'domain' => $this->_getRequest()->getServer('HTTP_HOST'),
                'magento_version' => $this->getMagentoVersion(),
                'module_version' => '1.0.0'
            ]);

            $webhookUrl = $this->scopeConfig->getValue('panth_megamenu/advanced/webhook_url');

            if ($webhookUrl) {
                $this->curl->setHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Panth-MegaMenu/1.0'
                ]);
                $this->curl->post($webhookUrl, json_encode($notificationData));

                $response = $this->curl->getBody();
                $status = $this->curl->getStatus();

                if ($status >= 200 && $status < 300) {
                    $this->markNotificationSent($eventKey);
                    return true;
                }
            }
        } catch (\Exception $e) {
        }

        return false;
    }

    protected function shouldSendNotification(string $eventKey): bool
    {
        $flagCode = self::WEBHOOK_FLAG_PREFIX . $eventKey;
        $flag = $this->flagFactory->create(['data' => ['flag_code' => $flagCode]]);
        $this->flagResource->load($flag, $flagCode, 'flag_code');

        $lastSent = $flag->getLastUpdate();
        if (!$lastSent) {
            return true;
        }

        $lastSentTimestamp = strtotime($lastSent);
        $now = time();

        return ($now - $lastSentTimestamp) >= self::WEBHOOK_INTERVAL;
    }

    protected function markNotificationSent(string $eventKey)
    {
        $flagCode = self::WEBHOOK_FLAG_PREFIX . $eventKey;
        $flag = $this->flagFactory->create(['data' => ['flag_code' => $flagCode]]);
        $this->flagResource->load($flag, $flagCode, 'flag_code');

        $flag->setFlagCode($flagCode);
        $flag->setFlagData(1);
        $flag->setLastUpdate(date('Y-m-d H:i:s'));

        $this->flagResource->save($flag);
    }

    protected function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }
}

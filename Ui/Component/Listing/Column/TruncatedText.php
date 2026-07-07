<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TruncatedText extends Column
{
    const MAX_LENGTH = 100;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $fullText = $item[$fieldName];
                    $truncated = $this->truncateText($fullText);

                    $item[$fieldName . '_full'] = $fullText;

                    if (strlen($fullText) > self::MAX_LENGTH) {
                        $item[$fieldName] = sprintf(
                            '<span title="%s">%s...</span>',
                            htmlspecialchars($fullText),
                            htmlspecialchars($truncated)
                        );
                    } else {
                        $item[$fieldName] = htmlspecialchars($fullText);
                    }
                }
            }
        }

        return $dataSource;
    }

    protected function truncateText(string $text): string
    {
        if (strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        $truncated = substr($text, 0, self::MAX_LENGTH);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated;
    }
}

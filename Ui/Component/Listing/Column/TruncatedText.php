<?php
/**
 * Truncated Text Column Component
 * Displays truncated text with tooltip showing full content
 */
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class TruncatedText extends Column
{
    /**
     * Maximum length for truncated text
     */
    const MAX_LENGTH = 100;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$fieldName])) {
                    $fullText = $item[$fieldName];
                    $truncated = $this->truncateText($fullText);

                    // Store full text for tooltip
                    $item[$fieldName . '_full'] = $fullText;

                    // Store truncated text with HTML for display
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

    /**
     * Truncate text to maximum length
     *
     * @param string $text
     * @return string
     */
    protected function truncateText(string $text): string
    {
        if (strlen($text) <= self::MAX_LENGTH) {
            return $text;
        }

        // Truncate at word boundary
        $truncated = substr($text, 0, self::MAX_LENGTH);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated;
    }
}

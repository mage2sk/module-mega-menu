<?php
declare(strict_types=1);

namespace Panth\MegaMenu\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class VersionItemCount extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $count = 0;
            $json = $item['items_json'] ?? '';
            if (is_string($json) && $json !== '') {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $count = count(array_filter($decoded, 'is_array'));
                }
            }
            $item[$name] = $count;
        }
        unset($item);
        return $dataSource;
    }
}

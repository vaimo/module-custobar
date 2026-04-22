<?php

namespace Custobar\CustoConnector\Model\MappedDataBuilder\DataExtender\CatalogProduct;

use Custobar\CustoConnector\Model\MappedDataBuilder\DataExtenderInterface;
use Magento\Catalog\Model\Product;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

class AddInventoryData implements DataExtenderInterface
{
    /**
     * @inheritDoc
     */
    public function execute($entity)
    {
        /** @var Product $entity */

        $stockData = [];
        /** @var SourceItemInterface[] $sourceItems */
        $sourceItems = $entity->getExportSourceItems() ?? [];
        foreach ($sourceItems as $sourceItem) {
            $stockData[] = [
                'shop_id' => $sourceItem->getSourceCode(),
                'quantity' => $sourceItem->getQuantity(),
            ];
        }

        $entity->setData('custobar_stock', $stockData);

        return $entity;
    }
}

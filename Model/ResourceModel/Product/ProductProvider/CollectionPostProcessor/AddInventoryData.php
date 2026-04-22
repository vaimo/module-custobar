<?php

namespace Custobar\CustoConnector\Model\ResourceModel\Product\ProductProvider\CollectionPostProcessor;

use Custobar\CustoConnector\Model\ResourceModel\Product\ProductProvider\CollectionProcessorInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalog\Model\GetStockIdForByStoreId;

class AddInventoryData implements CollectionProcessorInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var GetStockIdForByStoreId
     */
    private $stockIdProvider;

    /**
     * @param CollectionFactory $collectionFactory
     * @param GetStockIdForByStoreId $stockIdProvider
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        GetStockIdForByStoreId $stockIdProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->stockIdProvider = $stockIdProvider;
    }

    /**
     * @inheritDoc
     */
    public function execute($collection)
    {
        $sourceItems = $this->getSourceItemsByProductCollection($collection);
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            $product = $collection->getItemByColumnValue(ProductInterface::SKU, $sku);
            if (!$product) {
                continue;
            }

            $qty = $sourceItem->getQuantity();
            if ($sourceItem->getStatus() === SourceItemInterface::STATUS_OUT_OF_STOCK) {
                $qty = 0;
            }

            $sourceItem->setQuantity($qty);

            $productSourceItems = $product->getExportSourceItems() ?? [];
            $productSourceItems[$sourceItem->getSourceCode()] = $sourceItem;
            $product->setExportSourceItems($productSourceItems);
        }

        return $collection;
    }

    /**
     * Based on given collection, retrieves only the relevant source item data for the products
     *
     * @param Collection $collection
     *
     * @return SourceItemInterface[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSourceItemsByProductCollection($collection)
    {
        $skus = $collection->getColumnValues(ProductInterface::SKU);
        $storeId = (int) $collection->getStoreId();
        $stockId = $this->stockIdProvider->execute($storeId);

        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(SourceItemInterface::SKU, ['in' => $skus]);
        $collection->getSelect()
            ->joinInner(
                ['issl' => 'inventory_source_stock_link'],
                'issl.source_code = main_table.source_code and issl.stock_id = ' . $stockId,
                []
            )
            ->joinInner(
                ['is' => 'inventory_source'],
                'issl.source_code = is.source_code and is.enabled = 1',
                []
            );

        return $collection->getItems();
    }
}

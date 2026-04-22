<?php

namespace Custobar\CustoConnector\Model\ResourceModel\Product\ProductProvider\CollectionPostProcessor;

use Custobar\CustoConnector\Model\ResourceModel\Product\ProductProvider\CollectionProcessorInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
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
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @param CollectionFactory $collectionFactory
     * @param GetStockIdForByStoreId $stockIdProvider
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        GetStockIdForByStoreId $stockIdProvider,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->stockIdProvider = $stockIdProvider;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * @inheritDoc
     */
    public function execute($collection)
    {
        $skus = $collection->getColumnValues(ProductInterface::SKU);
        $storeId = (int) $collection->getStoreId();
        $sourceCodes = $this->getSourceCodesByStoreId($storeId);

        /** @var SourceItemInterface[] $sourceItems */
        $sourceItems = $this->collectionFactory->create()
            ->addFieldToFilter(SourceItemInterface::SKU, ['in' => $skus])
            ->addFieldToFilter(SourceItemInterface::SOURCE_CODE, ['in' => $sourceCodes]);
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
     * Resolves array of source code strings by store - stock links
     *
     * @param int $storeId
     *
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getSourceCodesByStoreId(int $storeId)
    {
        $stockId = $this->stockIdProvider->execute($storeId);

        $searchCriteria = $this->criteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->create();
        $sourceLinks = $this->getStockSourceLinks->execute($searchCriteria);

        return \array_map(function ($sourceLink) {
            return (string) $sourceLink->getSourceCode();
        }, $sourceLinks->getItems());
    }
}

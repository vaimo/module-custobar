<?php

namespace Custobar\CustoConnector\Model;

use Custobar\CustoConnector\Api\EntityTypeResolverInterface;
use Custobar\CustoConnector\Api\MappedDataBuilderInterface;
use Custobar\CustoConnector\Model\MappedDataBuilder\DataExtenderProviderInterface;
use Custobar\CustoConnector\Api\MappingDataProviderInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject\Mapper;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NotFoundException;

class MappedDataBuilder implements MappedDataBuilderInterface
{
    /**
     * @var EntityTypeResolverInterface
     */
    private $identiferResolver;

    /**
     * @var MappingDataProviderInterface
     */
    private $mappingDataProvider;

    /**
     * @var DataObjectFactory
     */
    private $dataFactory;

    /**
     * @var DataExtenderProviderInterface
     */
    private $extenderProvider;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @param EntityTypeResolverInterface $identiferResolver
     * @param MappingDataProviderInterface $mappingDataProvider
     * @param DataObjectFactory $dataFactory
     * @param DataExtenderProviderInterface $extenderProvider
     * @param Mapper $mapper
     */
    public function __construct(
        EntityTypeResolverInterface $identiferResolver,
        MappingDataProviderInterface $mappingDataProvider,
        DataObjectFactory $dataFactory,
        DataExtenderProviderInterface $extenderProvider,
        Mapper $mapper
    ) {
        $this->identiferResolver = $identiferResolver;
        $this->mappingDataProvider = $mappingDataProvider;
        $this->dataFactory = $dataFactory;
        $this->extenderProvider = $extenderProvider;
        $this->mapper = $mapper;
    }

    /**
     * @inheritDoc
     */
    public function buildMappedData($entity, ?int $storeId = null)
    {
        $entityType = $this->identiferResolver->resolveEntityType($entity);
        if ($entityType == Product::ENTITY) {
            $storeId = $storeId === null ? $entity->getStoreId() : $storeId;
            $entity->setStoreId($storeId);
        }
        $mappingData = $this->mappingDataProvider->getMappingDataByEntityType($entityType, $storeId);
        if (!$mappingData) {
            throw new NotFoundException(__('No mapping data available for \'%1\'', $entityType));
        }
        $entity = $this->extendEntityData($entity, $entityType);

        $mappedData = $this->dataFactory->create();
        $this->mapper->accumulateByMap(
            [$entity, 'getDataUsingMethod'],
            $mappedData,
            $mappingData->getFieldMap()
        );

        return $mappedData;
    }

    /**
     * Modify entity data before applying mapping
     *
     * @param mixed $entity
     * @param string $entityType
     *
     * @return mixed
     * @throws \Magento\Framework\Validation\ValidationException
     */
    private function extendEntityData($entity, string $entityType)
    {
        // Not every entity necessarily needs the extenders so catch the exception of no extender available

        try {
            $dataExtender = $this->extenderProvider->getDataExtender($entityType);

            return $dataExtender->execute($entity);
        } catch (NotFoundException $e) {
            return $entity;
        }
    }
}

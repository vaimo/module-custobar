<?php

namespace Custobar\CustoConnector\Model;

use Custobar\CustoConnector\Api\EntityTypeResolverInterface;
use Custobar\CustoConnector\Api\LoggerInterface;
use Custobar\CustoConnector\Api\MappingDataProviderInterface;
use Custobar\CustoConnector\Model\MappingDataProvider\DataExtenderInterface;
use Custobar\CustoConnector\Api\Data\MappingDataInterface;
use Magento\Framework\Validation\ValidationException;

class MappingDataProvider implements MappingDataProviderInterface
{
    /**
     * @var DataExtenderInterface
     */
    private $dataExtender;

    /**
     * @var EntityTypeResolverInterface
     */
    private $typeResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MappingDataInterface[]
     */
    private $mappingDataModels;

    /**
     * @var MappingDataInterface[][]
     */
    private $cachedData = [];

    /**
     * @param DataExtenderInterface $dataExtender
     * @param EntityTypeResolverInterface $typeResolver
     * @param LoggerInterface $logger
     * @param MappingDataInterface[] $mappingDataModels
     */
    public function __construct(
        DataExtenderInterface $dataExtender,
        EntityTypeResolverInterface $typeResolver,
        LoggerInterface $logger,
        array $mappingDataModels = []
    ) {
        $this->dataExtender = $dataExtender;
        $this->typeResolver = $typeResolver;
        $this->logger = $logger;
        $this->mappingDataModels = $mappingDataModels;
    }

    /**
     * @inheritDoc
     */
    public function getAllMappingData(?int $storeId = null)
    {
        if (!isset($this->cachedData[$storeId])) {
            $mappingModels = $this->resolveValidModels();
            foreach ($mappingModels as $index => $mappingModel) {
                $mappingModel = $this->dataExtender->extendData($mappingModel, $storeId);
                $mappingModels[$index] = $mappingModel;
            }

            if (!$mappingModels) {
                $this->logger->debug('No mapping data models set');
            }

            $this->cachedData[$storeId] = $mappingModels;
        }

        return $this->cachedData[$storeId];
    }

    /**
     * @inheritDoc
     */
    public function getMappingDataByEntityType(string $entityType, ?int $storeId = null)
    {
        $models = $this->getAllMappingData($storeId);

        return $models[$entityType] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getMappingDataByTargetField(string $targetField, ?int $storeId = null)
    {
        $models = $this->getAllMappingData($storeId);
        foreach ($models as $model) {
            if ($model->getTargetField() == $targetField) {
                return $model;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function getMappingDataByObject($entity, ?int $storeId = null)
    {
        $entityType = $this->typeResolver->resolveEntityType($entity);

        return $this->getMappingDataByEntityType($entityType, $storeId);
    }

    /**
     * Collect all valid mapping data models
     *
     * @return MappingDataInterface[]
     * @throws ValidationException
     */
    private function resolveValidModels()
    {
        $models = [];
        foreach ($this->mappingDataModels as $index => $mappingData) {
            if ($mappingData === null) {
                continue;
            }
            if (!($mappingData instanceof MappingDataInterface)) {
                throw new ValidationException(__('Mapping data model \'%1\' is not valid', $index));
            }

            $models[$index] = clone $mappingData;
        }

        return $models;
    }
}

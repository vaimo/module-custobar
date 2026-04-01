<?php

namespace Custobar\CustoConnector\Api;

interface MappingDataProviderInterface
{
    /**
     * Returns all mapping data as array keyed by entity types
     *
     * @param int|null $storeId
     *
     * @return \Custobar\CustoConnector\Api\Data\MappingDataInterface[]
     */
    public function getAllMappingData(?int $storeId = null);

    /**
     * Returns entity specific mapping data by type
     *
     * @param string $entityType
     * @param int|null $storeId
     *
     * @return \Custobar\CustoConnector\Api\Data\MappingDataInterface
     */
    public function getMappingDataByEntityType(string $entityType, ?int $storeId = null);

    /**
     * Returns entity specific mapping data by target field
     *
     * @param string $targetField
     * @param int|null $storeId
     *
     * @return \Custobar\CustoConnector\Api\Data\MappingDataInterface
     */
    public function getMappingDataByTargetField(string $targetField, ?int $storeId = null);

    /**
     * Returns entity specific mapping data by entity object
     *
     * @param mixed $entity
     * @param int|null $storeId
     *
     * @return \Custobar\CustoConnector\Api\Data\MappingDataInterface
     */
    public function getMappingDataByObject($entity, ?int $storeId = null);
}

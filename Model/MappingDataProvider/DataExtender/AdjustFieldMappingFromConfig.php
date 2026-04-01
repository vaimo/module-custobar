<?php

namespace Custobar\CustoConnector\Model\MappingDataProvider\DataExtender;

use Custobar\CustoConnector\Model\MappingDataProvider\DataExtenderInterface;
use Custobar\CustoConnector\Api\Data\MappingDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class AdjustFieldMappingFromConfig implements DataExtenderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function extendData(MappingDataInterface $mappingData, ?int $storeId = null)
    {
        $configPath = $mappingData->getFieldMapConfig();
        if (!$configPath) {
            return $mappingData;
        }

        $fieldMapping = $mappingData->getFieldMap() ?? [];
        $configData = (string)$this->scopeConfig->getValue($configPath);
        if ($storeId) {
            $configData = (string)$this->scopeConfig->getValue(
                $configPath,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }
        if ($configData) {
            $configData = \str_replace('<br>', "\n", $configData);
            $splitDataItems = \explode("\n", $configData);
            foreach ($splitDataItems as $splitDataItem) {
                $rowData = \explode(":", $splitDataItem);
                $key = \trim($rowData[0]);
                $value = \trim($rowData[1]);

                $fieldMapping[$key] = $value;
            }
        }

        $mappingData->setFieldMap($fieldMapping);

        return $mappingData;
    }
}

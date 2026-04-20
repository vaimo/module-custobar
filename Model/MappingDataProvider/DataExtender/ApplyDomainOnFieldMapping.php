<?php

namespace Custobar\CustoConnector\Model\MappingDataProvider\DataExtender;

use Custobar\CustoConnector\Api\Data\MappingDataInterface;
use Custobar\CustoConnector\Model\Config;
use Custobar\CustoConnector\Model\MappingDataProvider\DataExtenderInterface;

class ApplyDomainOnFieldMapping implements DataExtenderInterface
{
    public const DEFAULT_NEEDLE = '*domain*';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $needle;

    /**
     * @param Config $config
     * @param string $needle
     */
    public function __construct(
        Config $config,
        string $needle = self::DEFAULT_NEEDLE
    ) {
        $this->config = $config;
        $this->needle = $needle;
    }

    /**
     * @inheritDoc
     */
    public function extendData(MappingDataInterface $mappingData, ?int $storeId = null)
    {
        $domain = $this->config->getApiPrefix();
        $fieldMapping = $mappingData->getFieldMap() ?? [];
        foreach ($fieldMapping as $key => $value) {
            $value = \str_replace($this->needle, $domain, $value);
            $fieldMapping[$key] = $value;
        }

        $mappingData->setFieldMap($fieldMapping);

        return $mappingData;
    }
}

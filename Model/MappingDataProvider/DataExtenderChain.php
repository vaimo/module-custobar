<?php

namespace Custobar\CustoConnector\Model\MappingDataProvider;

use Custobar\CustoConnector\Api\Data\MappingDataInterface;
use Magento\Framework\Validation\ValidationException;

class DataExtenderChain implements DataExtenderInterface
{
    /**
     * @var DataExtenderInterface[]
     */
    private $dataExtenders;

    /**
     * @param DataExtenderInterface[] $dataExtenders
     */
    public function __construct(
        array $dataExtenders = []
    ) {
        $this->dataExtenders = $dataExtenders;
    }

    /**
     * @inheritDoc
     */
    public function extendData(MappingDataInterface $mappingData, ?int $storeId = null)
    {
        foreach ($this->dataExtenders as $name => $dataExtender) {
            if ($dataExtender === null) {
                continue;
            }
            if (!($dataExtender instanceof DataExtenderInterface)) {
                throw new ValidationException(__('Mapping data extender \'%1\' is not valid', $name));
            }

            $mappingData = $dataExtender->extendData($mappingData, $storeId);
        }

        return $mappingData;
    }
}

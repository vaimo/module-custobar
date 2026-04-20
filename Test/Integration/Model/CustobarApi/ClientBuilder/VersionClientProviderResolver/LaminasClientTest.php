<?php

namespace Custobar\CustoConnector\Test\Integration\Model\CustobarApi\ClientBuilder\VersionClientProviderResolver;

use Custobar\CustoConnector\Model\CustobarApi\ClientBuilder\VersionClientProviderResolver\LaminasClient;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class LaminasClientTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var LaminasClient
     */
    private $clientResolver;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->clientResolver = $this->objectManager->create(LaminasClient::class);
    }

    /**
     * @dataProvider doesVersionApplyDataProvider
     * @magentoAppIsolation enabled
     */
    public function testDoesVersionApply(string $version, bool $expectedResult)
    {
        $result = $this->clientResolver->doesVersionApply($version);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return mixed[]
     */
    public static function doesVersionApplyDataProvider()
    {
        return [
            'Should return false on 2.4.4' => [
                '2.4.4',
                false,
            ],
            'Should return false on 2.4.5' => [
                '2.4.5',
                false,
            ],
            'Should return false on 2.4.5-p1' => [
                '2.4.5-p1',
                false,
            ],
            'Should return true on 2.4.6' => [
                '2.4.6',
                true,
            ],
            'Should return true on 2.4.6-p1' => [
                '2.4.6-p1',
                true,
            ],
            'Should return true on 2.4.7' => [
                '2.4.7',
                true,
            ],
            'Should return true on 2.5.0' => [
                '2.5.0',
                true,
            ],
        ];
    }
}

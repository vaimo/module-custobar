<?php

namespace Custobar\CustoConnector\Test\Integration\Model\CustobarApi\ClientBuilder\VersionClientProviderResolver;

use Custobar\CustoConnector\Model\CustobarApi\ClientBuilder\VersionClientProviderResolver\ZendClient;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ZendClientTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ZendClient
     */
    private $clientResolver;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->clientResolver = $this->objectManager->create(ZendClient::class);
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
            'Should return true on 2.4.4' => [
                '2.4.4',
                true,
            ],
            'Should return true on 2.4.5' => [
                '2.4.5',
                true,
            ],
            'Should return true on 2.4.5-p1' => [
                '2.4.5-p1',
                true,
            ],
            'Should return false on 2.4.6' => [
                '2.4.6',
                false,
            ],
            'Should return false on 2.4.6-p1' => [
                '2.4.6-p1',
                false,
            ],
            'Should return false on 2.4.7' => [
                '2.4.7',
                false,
            ],
            'Should return false on 2.5.0' => [
                '2.5.0',
                false,
            ],
        ];
    }
}

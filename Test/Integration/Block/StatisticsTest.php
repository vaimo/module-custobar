<?php

namespace Custobar\CustoConnector\Test\Integration\Block;

use Custobar\CustoConnector\Block\Statistics;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StatisticsTest extends AbstractController
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Statistics
     */
    private $statistics;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SessionFactory
     */
    private $custSessionFactory;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->statistics = $this->objectManager->get(Statistics::class);
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->custSessionFactory = $this->objectManager->get(SessionFactory::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script
     */
    public function testCustomScriptNoTrackingCode()
    {
        $this->assertEmpty($this->statistics->getTrackingScript());

        $this->dispatch('/');
        $html = $this->getResponse()->getBody();

        // Tracking script is intentionally shortened here since the long config line won't pass code quality checks
        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that code is not present as its set to empty'
        );
        $this->assertFalse(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is not added instead'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script v1/custobar.js
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script_v2 <script async src>
     */
    public function testCustomScriptNotLoggedIn()
    {
        $this->dispatch('/');
        $html = $this->getResponse()->getBody();

        $this->assertTrue(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that we get the code when its set'
        );
        $this->assertFalse(
            \str_contains($html, 'cstbrConfig.customerId ='),
            'Assert that no customer details is present'
        );
        $this->assertFalse(
            \str_contains($html, 'cstbrConfig.productId ='),
            'Assert that product line is not output'
        );
        $this->assertFalse(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is not added instead'
        );
        $this->assertFalse(
            \str_contains($html, '<script async src>'),
            'Assert that V2 script is not added'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 2
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script
     */
    public function testGtmNotLoggedIn()
    {
        $this->dispatch('/');
        $html = $this->getResponse()->getBody();

        $this->assertTrue(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is added'
        );
        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that custom script is not added instead'
        );
        $this->assertFalse(
            \str_contains($html, 'cstbrConfig.customerId ='),
            'Assert that no customer details is present'
        );
        $this->assertFalse(
            \str_contains($html, 'cstbrConfig.productId ='),
            'Assert that product line is not output'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1,0
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script v1/custobar.js
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script_v2 <script async src>
     */
    public function testCustomScriptLoggedIn()
    {
        $this->custSessionFactory->create()->loginById(1);
        $product = $this->productRepository->get('simple_product_1');

        $this->dispatch(\sprintf(
            'catalog/product/view/id/%s',
            $product->getEntityId()
        ));

        $html = $this->getResponse()->getBody();

        $this->assertTrue(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that custom script is added'
        );
        $this->assertFalse(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is not added instead'
        );
        $this->assertTrue(
            \str_contains($html, 'cstbrConfig.customerId = "1";'),
            'Is customer id present'
        );
        $this->assertTrue(
            \str_contains($html, 'cstbrConfig.productId = "simple_product_1";'),
            'Is cb.track_browse_product present'
        );
        $this->assertFalse(
            \str_contains($html, '<script async src>'),
            'Assert that V2 script is not added'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 2
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script
     */
    public function testGtmLoggedIn()
    {
        $this->custSessionFactory->create()->loginById(1);
        $product = $this->productRepository->get('simple_product_1');

        $this->dispatch(\sprintf(
            'catalog/product/view/id/%s',
            $product->getEntityId()
        ));

        $html = $this->getResponse()->getBody();

        $this->assertTrue(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is added'
        );
        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that custom script is not added instead'
        );
        $this->assertTrue(
            \str_contains($html, 'cstbrConfig.customerId = "1";'),
            'Is customer id present'
        );
        $this->assertTrue(
            \str_contains($html, 'cstbrConfig.productId = "simple_product_1";'),
            'Is cb.track_browse_product present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 100
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script v1/custobar.js
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script_v2 <script async src>
     */
    public function testCustomScriptWebsiteNotAllowed()
    {
        $this->custSessionFactory->create()->loginById(1);
        $product = $this->productRepository->get('simple_product_1');

        $this->dispatch(\sprintf(
            'catalog/product/view/id/%s',
            $product->getEntityId()
        ));

        $html = $this->getResponse()->getBody();

        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that custobar code is not present'
        );
        $this->assertFalse(
            \str_contains($html, '<script async src>'),
            'Assert that V2 script is not added'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture   Magento/Customer/_files/customer.php
     * @magentoDataFixture   Magento/Catalog/controllers/_files/products.php
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 100
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 2
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script
     */
    public function testGtmWebsiteNotAllowed()
    {
        $this->custSessionFactory->create()->loginById(1);
        $product = $this->productRepository->get('simple_product_1');

        $this->dispatch(\sprintf(
            'catalog/product/view/id/%s',
            $product->getEntityId()
        ));

        $html = $this->getResponse()->getBody();

        $this->assertFalse(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is not added'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 3
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script_v2
     */
    public function testCustomScriptV2NoTrackingScript()
    {
        $this->assertEmpty($this->statistics->getTrackingScript());

        $this->dispatch('/');
        $html = $this->getResponse()->getBody();

        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that code is not present as its set to empty'
        );
        $this->assertFalse(
            \str_contains($html, '<script async src>'),
            'Assert that script is not present as its set to empty'
        );
        $this->assertFalse(
            \str_contains($html, 'window.dataLayer.push(gtmData)'),
            'Assert that GTM script is not added instead'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/allowed_websites 1
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/prefix prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/apikey prefixthatdoesntexists
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_mode 3
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script v1/custobar.js
     * @magentoConfigFixture default_store custobar/custobar_custoconnector/tracking_script_v2 <script async src>
     */
    public function testCustomScriptV2TrackingScript()
    {
        $this->dispatch('/');
        $html = $this->getResponse()->getBody();

        $this->assertFalse(
            \str_contains($html, 'v1/custobar.js'),
            'Assert that V1 script is not added'
        );
        $this->assertTrue(
            \str_contains($html, '<script async src>'),
            'Assert that V2 script is present'
        );
        $this->assertFalse(
            \str_contains($html, '<script><script async src>'),
            'Assert that script is not wrapped in another script tag'
        );
    }
}

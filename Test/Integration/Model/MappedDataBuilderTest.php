<?php

namespace Custobar\CustoConnector\Test\Integration\Model;

use Custobar\CustoConnector\Model\EntityDataResolver;
use Custobar\CustoConnector\Model\MappedDataBuilder;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MappedDataBuilderTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    /**
     * @var EntityDataResolver
     */
    private $entityDataResolver;

    /**
     * @var MappedDataBuilder
     */
    private $mappedDataBuilder;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepository::class);
        $this->orderFactory = $this->objectManager->get(OrderFactory::class);
        $this->customerFactory = $this->objectManager->get(CustomerFactory::class);
        $this->storeManager = $this->objectManager->get(StoreManager::class);
        $this->subscriberFactory = $this->objectManager->get(SubscriberFactory::class);
        $this->entityDataResolver = $this->objectManager->get(EntityDataResolver::class);
        $this->mappedDataBuilder = $this->objectManager->get(MappedDataBuilder::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_with_all_fields.php
     */
    public function testBuildMappedDataProduct()
    {
        $product = $this->productRepository->get('simple');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertEquals('simple', $mappedData->getData('external_id'));
        $this->assertEquals('Simple Product', $mappedData->getData('title'));
        $this->assertEquals(1000, $mappedData->getData('price'));
        $this->assertEquals(382, $mappedData->getData('sale_price'));
        $this->assertEquals(3412, $mappedData->getData('minimal_price'));
        $this->assertEquals('simple', $mappedData->getData('mage_type'));
        $this->assertEquals('Default', $mappedData->getData('type'));
        $this->assertEquals('en', $mappedData->getData('language'));
        $this->assertEquals(
            'Description with <b>html tag</b>',
            $mappedData->getData('description')
        );
        $this->assertStringContainsString('simple-product', $mappedData->getData('url'));
        $this->assertEquals(
            'Default Category,Movable Position 2,Filter category',
            $mappedData->getData('category')
        );
    }

    /**
     * @dataProvider buildMappedDataProductWithStocksDataProvider
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testBuildMappedDataProductWithStocks(string $storeCode, array $expectedStocks)
    {
        $skus = \array_keys($expectedStocks);
        $productIds = \array_map(function ($sku) {
            return $this->productRepository->get($sku)->getId();
        }, $skus);

        $mappedData = [];
        $productDataItems = $this->entityDataResolver->resolveEntities(
            Product::class,
            $productIds,
            $this->storeManager->getStore($storeCode)->getId()
        );
        foreach ($productDataItems as $productDataItem) {
            $mappedData[$productDataItem->getSku()] = $this->mappedDataBuilder->buildMappedData($productDataItem);
        }

        $mappedStockData = \array_map(function ($mappedData) {
            return $mappedData->getData('stock');
        }, $mappedData);

        $this->assertEquals($expectedStocks, $mappedStockData);
    }

    /**
     * @return mixed[]
     */
    public static function buildMappedDataProductWithStocksDataProvider()
    {
        return [
            'should set only stocks on products based on \'store_for_eu_website\'' => [
                'store_for_eu_website',
                [
                    'SKU-1' => [
                        ['shop_id' => 'eu-1', 'quantity' => 5.5],
                        ['shop_id' => 'eu-2', 'quantity' => 3],
                        ['shop_id' => 'eu-3', 'quantity' => 0],
                    ],
                    'SKU-2' => [],
                    'SKU-3' => [
                        ['shop_id' => 'eu-2', 'quantity' => 0],
                    ],
                ],
            ],
            'should set only stocks on products based on \'store_for_us_website\'' => [
                'store_for_us_website',
                [
                    'SKU-1' => [],
                    'SKU-2' => [
                        ['shop_id' => 'us-1', 'quantity' => 5.0],
                    ],
                    'SKU-3' => [],
                ],
            ],
            'should set only stocks on products based on \'store_for_global_website\'' => [
                'store_for_global_website',
                [
                    'SKU-1' => [
                        ['shop_id' => 'eu-1', 'quantity' => 5.5],
                        ['shop_id' => 'eu-2', 'quantity' => 3],
                        ['shop_id' => 'eu-3', 'quantity' => 0],
                    ],
                    'SKU-2' => [
                        ['shop_id' => 'us-1', 'quantity' => 5.0],
                    ],
                    'SKU-3' => [
                        ['shop_id' => 'eu-2', 'quantity' => 0],
                    ],
                ],
            ],
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Catalog/_files/product_with_image.php
     */
    public function testBuildMappedDataProductImage()
    {
        $product = $this->productRepository->get('simple');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertEquals('simple', $mappedData->getData('external_id'));
        $this->assertStringContainsString('/m/a/magento_image.jpg', $mappedData->getData('image'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple_multistore.php
     */
    public function testBuildMappedDataProductStores()
    {
        $product = $this->productRepository->get('simple');
        $defaultStoreId = $this->storeManager->getStore('default')->getId();
        $testStoreId = $this->storeManager->getStore('fixturestore')->getId();

        $defaultStoreData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $defaultStoreId
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($defaultStoreData);

        $this->assertEquals(
            'Simple Product One',
            $mappedData->getData('title'),
            "Assert that store name is present"
        );
        $this->assertEquals(
            $defaultStoreId,
            $mappedData->getData('store_id')
        );

        $testStoreData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $testStoreId
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($testStoreData);

        $this->assertEquals(
            'StoreTitle',
            $mappedData->getData('title'),
            "Assert that store name is present"
        );
        $this->assertEquals(
            $testStoreId,
            $mappedData->getData('store_id')
        );

        // TODO: Could also cover language, url, category and description differences
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testBuildMappedDataProductVisibilitySwitch()
    {
        $product = $this->productRepository->get('simple');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertStringContainsString(
            'simple-product',
            $mappedData->getData('url'),
            'Assert that product url present'
        );

        $product->setStoreId(0);
        $product->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE);
        $product = $this->productRepository->save($product);
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );

        $this->assertEquals(Visibility::VISIBILITY_NOT_VISIBLE, $product->getVisibility());

        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertEquals(
            'simple',
            $mappedData->getData('external_id'),
            'Assert that sku matches external_id'
        );
        $this->assertArrayNotHasKey(
            'url',
            $mappedData->getData(),
            'Assert that product url not present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/Catalog/_files/enable_price_index_schedule.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     */
    public function testBuildMappedDataProductNotInStock()
    {
        $product = $this->productRepository->get('simple');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertEquals(
            'Simple Product',
            $mappedData->getData('title'),
            'Assert that product is present'
        );

        $this->assertEquals(
            0,
            $mappedData->getData('price'),
            'Assert that product price is not on mapped data'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Catalog/_files/products_with_multiselect_attribute.php
     * @magentoConfigFixture current_store custobar/custoconnector_field_mapping/product name:scope_title<br>sku:external_id<br>custobar_minimal_price:minimal_price<br>custobar_price:price<br>type_id:mage_type<br>configurable_min_price:my_configurable_min_price<br>custobar_attribute_set_name:type<br>custobar_category:category<br>custobar_category_id:category_id<br>custobar_image:image<br>custobar_product_url:url<br>custobar_special_price:sale_price<br>description:description<br>custobar_language:language<br>custobar_store_id:store_id<br>custobar_child_ids:mage_child_ids<br>custobar_parent_ids:mage_parent_ids<br>multiselect_attribute:brand
     */
    public function testBuildMappedDataProductMultiselectAttribute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->productRepository->get('simple_ms_2');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $storeId
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData, $storeId);

        $this->assertEquals(
            'simple_ms_2',
            $mappedData->getData('external_id'),
            'Assert if product external_id present'
        );

        $this->assertEquals(
            "Option 2, Option 3, Option 4 \"!@#$%^&*",
            $mappedData->getData('brand'),
            'Assert if product attribute present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Catalog/_files/products_with_dropdown_attribute.php
     * @magentoConfigFixture current_store custobar/custoconnector_field_mapping/product name:scope_title<br>sku:external_id<br>custobar_minimal_price:minimal_price<br>custobar_price:price<br>type_id:mage_type<br>configurable_min_price:my_configurable_min_price<br>custobar_attribute_set_name:type<br>custobar_category:category<br>custobar_category_id:category_id<br>custobar_image:image<br>custobar_product_url:url<br>custobar_special_price:sale_price<br>description:description<br>custobar_language:language<br>custobar_store_id:store_id<br>custobar_child_ids:mage_child_ids<br>custobar_parent_ids:mage_parent_ids<br>dropdown_attribute:brand
     */
    public function testBuildMappedDataDropdownAttribute()
    {
        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->productRepository->get('simple_op_2');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $storeId
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData, $storeId);

        $this->assertEquals(
            'simple_op_2',
            $mappedData->getData('external_id'),
            'Assert if product external_id present'
        );

        $this->assertEquals(
            'Option 2',
            $mappedData->getData('brand'),
            'Assert if product attribute present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     * @magentoDataFixture Magento/Catalog/_files/enable_price_index_schedule.php
     */
    public function testBuildMappedDataConfigurableProduct()
    {
        $parentProduct = $this->productRepository->get('configurable');
        $parentProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $parentProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $parentMappedData = $this->mappedDataBuilder->buildMappedData($parentProductData);

        $this->assertEquals(
            'configurable',
            $parentMappedData->getData('external_id'),
            'Assert that product external_id present'
        );

        $this->assertEquals(
            'configurable',
            $parentMappedData->getData('mage_type'),
            'Assert that product mage_type present'
        );

        $this->assertEquals(
            1000,
            $parentMappedData->getData('minimal_price'),
            'Assert that product minimal price present'
        );

        $this->assertEquals(
            'Default',
            $parentMappedData->getData('type'),
            'Assert that product type present'
        );

        $this->assertEquals(
            'simple_10,simple_20',
            $parentMappedData->getData('mage_child_ids'),
            'Assert that product child ids present'
        );

        $childProduct = $this->productRepository->get('simple_10');
        $childProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $childProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $childMappedData = $this->mappedDataBuilder->buildMappedData($childProductData);

        $this->assertEquals(
            'configurable',
            $childMappedData->getData('mage_parent_ids'),
            'Assert that product parent ids present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/ConfigurableProduct/_files/product_configurable.php
     */
    public function testCanGetSku2ExternalIdOnConfigurableProduct()
    {
        $product = $this->productRepository->get('configurable');
        $productData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $product->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($productData);

        $this->assertEquals(
            'configurable',
            $mappedData->getData('external_id'),
            'Product external_id mapped on configurable product'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Bundle/_files/product.php
     */
    public function testBuildMappedDataBundleProduct()
    {
        $parentProduct = $this->productRepository->get('bundle-product');
        $parentProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $parentProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $parentMappedData = $this->mappedDataBuilder->buildMappedData($parentProductData);

        $this->assertEquals(
            'simple',
            $parentMappedData->getData('mage_child_ids'),
            'Assert that bundle has child ids set'
        );
        $this->assertArrayNotHasKey(
            'mage_parent_ids',
            $parentMappedData->getData(),
            'Assert that bundle has no parent ids set'
        );

        $childProduct = $this->productRepository->get('simple');
        $childProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $childProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $childMappedData = $this->mappedDataBuilder->buildMappedData($childProductData);

        $this->assertEquals(
            'bundle-product',
            $childMappedData->getData('mage_parent_ids'),
            'Assert that product parent ids present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     *
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped.php
     */
    public function testBuildMappedDataGroupedProduct()
    {
        $parentProduct = $this->productRepository->get('grouped-product');
        $parentProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $parentProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $parentMappedData = $this->mappedDataBuilder->buildMappedData($parentProductData);

        $this->assertEquals(
            'simple,virtual-product',
            $parentMappedData->getData('mage_child_ids'),
            'Assert that child ids present'
        );

        $childProduct = $this->productRepository->get('simple');
        $childProductData = $this->entityDataResolver->resolveEntity(
            Product::class,
            $childProduct->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedChildProduct = $this->mappedDataBuilder->buildMappedData($childProductData);

        $this->assertEquals(
            'grouped-product',
            $mappedChildProduct->getData('mage_parent_ids'),
            'Assert that parent ids present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     */
    public function testBuildMappedDataOrder()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $orderData = $this->entityDataResolver->resolveEntity(
            Order::class,
            $order->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($orderData);

        $this->assertEquals('PROCESSING', $mappedData->getData('sale_state'));
        $this->assertEquals('100000001', $mappedData->getData('sale_external_id'));
        $this->assertEquals('1', $mappedData->getData('sale_customer_id'));
        $this->assertStringContainsString('customer@', $mappedData->getData('sale_email'));
        $this->assertEquals('1', $mappedData->getData('sale_shop_id'));
        $this->assertEquals(0, $mappedData->getData('sale_discount'));
        $this->assertEquals(10000, $mappedData->getData('sale_total'));
        $this->assertEquals('checkmo', $mappedData->getData('sale_payment_method'));

        $items = $mappedData->getData('magento__items');
        $this->assertNotEmpty($items);

        $firstItem = \reset($items);

        $this->assertEquals('100000001', $firstItem['sale_external_id']);
        $this->assertEquals('2.0000', $firstItem['quantity']);
        $this->assertEquals('simple', $firstItem['product_id']);
        $this->assertEquals(0, $firstItem['unit_price']);
        $this->assertEquals(0, $firstItem['discount']);
        $this->assertEquals(0, $firstItem['total']);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Customer/_files/customer_one_address.php
     */
    public function testBuildMappedDataCustomer()
    {
        $customer = $this->customerFactory->create()
            ->setWebsiteId(1)
            ->loadByEmail('customer_one_address@test.com');
        $customerData = $this->entityDataResolver->resolveEntity(
            Customer::class,
            $customer->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($customerData);

        $this->assertEquals($customer->getId(), $mappedData->getData('external_id'));
        $this->assertEquals('John', $mappedData->getData('first_name'));
        $this->assertEquals('Smith', $mappedData->getData('last_name'));
        $this->assertEquals(
            'CustomerAddress1',
            $mappedData->getData('street_address'),
            'Assert if address is present'
        );

        $this->assertEquals(
            'US',
            $mappedData->getData('country'),
            'Assert if the country id is present'
        );

        $this->assertEquals(
            1,
            $mappedData->getData('store_id'),
            'Assert if the store id is present'
        );

        $this->assertEquals(
            75477,
            $mappedData->getData('zip_code'),
            'Assert if the zip code is present'
        );

        $this->assertEquals(
            'CityM',
            $mappedData->getData('city'),
            'Assert if the city is present'
        );

        $this->assertEquals(
            '3468676',
            $mappedData->getData('phone_number'),
            'Assert if the phone is present'
        );

        $this->assertNotNull(
            $mappedData->getData('date_joined'),
            'Assert if the date joined is present'
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Newsletter/_files/subscribers.php
     */
    public function testBuildMappedDataNewsletter()
    {
        $subscriber = $this->subscriberFactory->create()
            ->loadByEmail('customer@example.com');
        $subscriberData = $this->entityDataResolver->resolveEntity(
            Subscriber::class,
            $subscriber->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($subscriberData);

        $currentStore = $this->storeManager->getStore()->getId();

        $this->assertEquals('customer@example.com', $mappedData->getData('email'));
        $this->assertEquals(1, $mappedData->getData('customer_id'));
        $this->assertEquals('MAIL_SUBSCRIBE', $mappedData->getData('type'));
        $this->assertEquals($currentStore, $mappedData->getData('store_id'));

        $subscriber->setStatus(Subscriber::STATUS_UNSUBSCRIBED);
        $subscriber->save();
        $subscriberData = $this->entityDataResolver->resolveEntity(
            Subscriber::class,
            $subscriber->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($subscriberData);

        $this->assertEquals('MAIL_UNSUBSCRIBE', $mappedData->getData('type'));

        $subscriber = $this->subscriberFactory->create()
            ->loadByEmail('customer_confirm@example.com');
        $subscriberData = $this->entityDataResolver->resolveEntity(
            Subscriber::class,
            $subscriber->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($subscriberData);

        $this->assertEquals('MAIL_UNSUBSCRIBE', $mappedData->getData('type'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_two_stores.php
     */
    public function testBuildMappedDataStore()
    {
        $store = $this->storeManager->getStore();
        $storeData = $this->entityDataResolver->resolveEntity(
            Store::class,
            $store->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($storeData);

        $this->assertEquals($store->getId(), $mappedData->getData('external_id'));
        $this->assertEquals(
            'Main Website, Main Website Store, Default Store View',
            $mappedData->getData('name')
        );

        $store = $this->storeManager->getStore('fixture_second_store');
        $storeData = $this->entityDataResolver->resolveEntity(
            Store::class,
            $store->getId(),
            $this->storeManager->getStore()->getId()
        );
        $mappedData = $this->mappedDataBuilder->buildMappedData($storeData);

        $this->assertEquals($store->getId(), $mappedData->getData('external_id'));
        $this->assertEquals(
            'Test Website, Main Website Store, Fixture Second Store',
            $mappedData->getData('name')
        );
    }
}

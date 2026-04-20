<?php

namespace Custobar\CustoConnector\Test\Integration\Controller\Adminhtml\Status;

use Custobar\CustoConnector\Api\Data\InitialInterface;
use Custobar\CustoConnector\Model\Initial\Config\Source\Status;
use Custobar\CustoConnector\Model\InitialRepository;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $resource = 'Custobar_CustoConnector::status';

    /**
     * @var string
     */
    protected $uri = 'backend/custobar/status/cancel';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var InitialRepository
     */
    private $initialRepository;

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->initialRepository = $this->objectManager->get(InitialRepository::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Custobar_CustoConnector::Test/Integration/_files/initials_for_all.php
     */
    public function testCancel()
    {
        $this->assertInitials([
            \Magento\Catalog\Model\Product::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Catalog\Model\Product::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
        ]);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['identifier' => 'products']);
        $this->dispatch($this->uri);

        $this->assertSessionMessages(
            $this->equalTo(['Successfully canceled all running exports']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('/backend/custobar/status/index'));

        $this->assertInitials([
            \Magento\Catalog\Model\Product::class => null,
        ]);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCancelNoInitials()
    {
        $this->assertInitials([
            \Magento\Catalog\Model\Product::class => null,
        ]);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['identifier' => 'products']);
        $this->dispatch($this->uri);

        $this->assertSessionMessages(
            $this->equalTo(['No exports to cancel']),
            MessageInterface::TYPE_WARNING
        );
        $this->assertRedirect($this->stringContains('/backend/custobar/status/index'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCancelUnmappedType()
    {
        $this->assertInitials([
            'unknown_type' => null,
        ]);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['identifier' => 'unknown_type']);
        $this->dispatch($this->uri);

        $this->assertSessionMessages(
            $this->equalTo(['Cannot start export for unconfigured type &#039;unknown_type&#039;']),
            MessageInterface::TYPE_ERROR
        );
        $this->assertRedirect($this->stringContains('/backend/custobar/status/index'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     *
     * @magentoDataFixture Custobar_CustoConnector::Test/Integration/_files/initials_for_all.php
     */
    public function testCancelAll()
    {
        $this->assertInitials([
            \Magento\Catalog\Model\Product::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Catalog\Model\Product::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
            \Magento\Customer\Model\Customer::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Customer\Model\Customer::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
            \Magento\Sales\Model\Order::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Sales\Model\Order::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
            \Magento\Newsletter\Model\Subscriber::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Newsletter\Model\Subscriber::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
            \Magento\Store\Model\Store::class => [
                InitialInterface::ENTITY_TYPE => \Magento\Store\Model\Store::class,
                InitialInterface::STATUS => Status::STATUS_RUNNING,
            ],
        ]);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->setPostValue(['identifier' => 'all']);
        $this->dispatch($this->uri);

        $this->assertSessionMessages(
            $this->equalTo(['Successfully canceled all running exports']),
            MessageInterface::TYPE_SUCCESS
        );
        $this->assertRedirect($this->stringContains('/backend/custobar/status/index'));

        $this->assertInitials([
            \Magento\Catalog\Model\Product::class => null,
            \Magento\Customer\Model\Customer::class => null,
            \Magento\Sales\Model\Order::class => null,
            \Magento\Newsletter\Model\Subscriber::class => null,
            \Magento\Store\Model\Store::class => null,
        ]);
    }

    /**
     * @param mixed[] $allExpectedData
     * @throws NoSuchEntityException
     */
    private function assertInitials(array $allExpectedData)
    {
        // Recreated due to class scope caching
        $this->initialRepository = $this->objectManager->create(InitialRepository::class);

        foreach ($allExpectedData as $entityType => $expectedData) {
            if (!\is_array($expectedData)) {
                try {
                    $this->initialRepository->getByEntityType($entityType);
                    $this->assertTrue(false, 'Should not find initial of type \'' . $entityType . '\'');
                } catch (NoSuchEntityException $exception) {
                    $this->assertTrue(true, 'Should not find initial of type \'' . $entityType . '\'');
                }

                continue;
            }

            $initial = $this->initialRepository->getByEntityType($entityType);
            foreach ($expectedData as $field => $expectedValue) {
                $realValue = $initial->getData($field);
                $this->assertEquals($expectedValue, $realValue, \sprintf(
                    'Assert that %s initial\'s field %s matches expected value',
                    $entityType,
                    $field
                ));
            }
        }
    }
}

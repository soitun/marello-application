<?php

namespace Marello\Bundle\ReturnBundle\Tests\Functional\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Marello\Bundle\ReturnBundle\Entity\ReturnEntity;
use Marello\Bundle\ReturnBundle\Entity\ReturnItem;
use Marello\Bundle\SalesBundle\Tests\Functional\DataFixtures\LoadSalesData;
use Marello\Bundle\ReturnBundle\Tests\Functional\DataFixtures\LoadReturnData;

/**
 * @dbIsolation
 */
class ReturnControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateWsseAuthHeader()
        );

        $this->loadFixtures([
            LoadReturnData::class
        ]);
    }


    public function testCreate()
    {
        /** @var Order $returnedOrder */
        $returnedOrder = $this->getReference('marello_order_unreturned');

        $data = [
            'order'       => $returnedOrder->getOrderNumber(),
            'salesChannel' => $returnedOrder->getSalesChannel()->getCode(),
            'returnReference' => uniqid() . 'TEST',
            'returnItems' => $returnedOrder->getItems()->map(function (OrderItem $item) {
                return [
                    'orderItem' => $item->getId(),
                    'quantity'  => 1,
                    'reason'    => 'damaged',
                ];
            })->toArray(),
        ];

        $this->client->request(
            'POST',
            $this->getUrl('marello_return_api_post_return'),
            $data
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_CREATED);

        $this->assertArrayHasKey('id', $response);

        /** @var ReturnEntity $return */
        $return = $this->client->getContainer()
            ->get('doctrine')
            ->getRepository('MarelloReturnBundle:ReturnEntity')
            ->findOneBy($response);

        $this->assertEquals(
            $returnedOrder->getId(),
            $return->getOrder()->getId(),
            'Created return should have correct order assigned.'
        );

        $orderedItemIds = $returnedOrder->getItems()->map(function (OrderItem $orderItem) {
            return $orderItem->getId();
        });

        $returnedItemIds = $return->getReturnItems()->map(function (ReturnItem $returnItem) {
            return $returnItem->getOrderItem()->getId();
        });

        $this->assertEquals(count($orderedItemIds), count($returnedItemIds));
        $this->assertEquals($orderedItemIds->toArray(), $returnedItemIds->toArray());

        $return->getReturnItems()->map(function (ReturnItem $returnItem) {
            $this->assertEquals(
                $returnItem->getOrderItem()->getQuantity(),
                $returnItem->getQuantity()
            );
        });
    }
}

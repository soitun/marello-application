<?php

namespace MarelloEnterprise\Bundle\ReplenishmentBundle\Workflow;

use Marello\Bundle\InventoryBundle\Entity\Warehouse;
use Marello\Bundle\InventoryBundle\Event\InventoryUpdateEvent;
use Marello\Bundle\InventoryBundle\Model\InventoryUpdateContextFactory;
use MarelloEnterprise\Bundle\ReplenishmentBundle\Entity\ReplenishmentOrder;
use MarelloEnterprise\Bundle\ReplenishmentBundle\Entity\ReplenishmentOrderItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class ReplenishmentOrderShipAction extends ReplenishmentOrderTransitionAction
{
    /**
     * @param WorkflowItem|mixed $context
     */
    protected function executeAction($context)
    {
        /** @var ReplenishmentOrder $order */
        $order = $context->getEntity();
        $warehouse = $order->getOrigin();
        $items = $order->getReplOrderItems();
        $items->map(function (ReplenishmentOrderItem $item) use ($order, $warehouse) {
            $this->handleInventoryUpdate(
                $item,
                -$item->getInventoryQty(),
                -$item->getInventoryQty(),
                $warehouse,
                $order
            );
        });
    }

    /**
     * handle the inventory update for items which have been shipped
     * @param ReplenishmentOrderItem $item
     * @param $inventoryUpdateQty
     * @param $allocatedInventoryQty
     * @param Warehouse $warehouse
     * @param ReplenishmentOrder $order
     */
    protected function handleInventoryUpdate($item, $inventoryUpdateQty, $allocatedInventoryQty, $warehouse, $order)
    {
        $context = InventoryUpdateContextFactory::createInventoryUpdateContext(
            $item,
            null,
            $inventoryUpdateQty,
            $allocatedInventoryQty,
            'marelloenterprise.replenishment.replenishmentorder.workflow.shipped',
            $order
        );

        $context->setValue('warehouse', $warehouse);

        $this->eventDispatcher->dispatch(
            InventoryUpdateEvent::NAME,
            new InventoryUpdateEvent($context)
        );
    }
}
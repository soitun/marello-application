<?php

namespace Marello\Bundle\PurchaseOrderBundle\Cron;

use Doctrine\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;

use Marello\Bundle\CustomerBundle\Entity\Customer;
use Marello\Bundle\PurchaseOrderBundle\Model\PurchaseOrder;
use Marello\Bundle\PurchaseOrderBundle\Entity\PurchaseOrderItem;
use Marello\Bundle\NotificationBundle\Provider\EmailSendProcessor;
use Marello\Bundle\NotificationMessageBundle\Model\NotificationMessageContext;
use Marello\Bundle\PurchaseOrderBundle\Provider\PurchaseOrderCandidatesProvider;
use Marello\Bundle\NotificationMessageBundle\Event\CreateNotificationMessageEvent;
use Marello\Bundle\NotificationMessageBundle\Event\ResolveNotificationMessageEvent;
use Marello\Bundle\NotificationMessageBundle\Provider\NotificationMessageTypeInterface;
use Marello\Bundle\NotificationMessageBundle\Factory\NotificationMessageContextFactory;
use Marello\Bundle\NotificationMessageBundle\Provider\NotificationMessageSourceInterface;
use Marello\Bundle\NotificationMessageBundle\Provider\NotificationMessageResolvedInterface;

class PurchaseOrderAdviceCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    const COMMAND_NAME = 'oro:cron:marello:po-advice';
    const EXIT_CODE = 0;

    public function __construct(
        protected ContainerInterface $container
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    public function getDefaultDefinition()
    {
        return '0 13 * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Sending Purchase Orders advice notification');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $featureChecker = $this->container->get('oro_featuretoggle.checker.feature_checker');
        $configManager = $this->container->get('oro_config.manager');

        $isActive = $featureChecker->isResourceEnabled(self::COMMAND_NAME, 'cron_jobs') &&
            $configManager->get('marello_purchaseorder.purchaseorder_notification') === true;
        if (!$isActive) {
            $output->writeln('This cron command is not active.');
            return self::EXIT_CODE;
        }

        $configManager = $this->container->get('oro_config.manager');
        if ($configManager->get('marello_purchaseorder.purchaseorder_notification') !== true) {
            $output->writeln('The PO notification feature is disabled. The command will not run.');
            return self::EXIT_CODE;
        }

        $context = $this->createNotificationContext();
        /** @var PurchaseOrderCandidatesProvider $provider */
        $provider = $this
            ->container
            ->get('Marello\Bundle\PurchaseOrderBundle\Provider\PurchaseOrderCandidatesProvider');
        $advisedItems = $provider->getPurchaseOrderCandidates();
        if (empty($advisedItems)) {
            $output->writeln('There are no advised items for PO notification. The command will not run.');
            $this->container
                ->get('event_dispatcher')
                ->dispatch(
                    new ResolveNotificationMessageEvent($context),
                    ResolveNotificationMessageEvent::NAME
                );
            return self::EXIT_CODE;
        }

        $entity = new PurchaseOrder();
        $entity->setOrganization($this->getOrganization());

        foreach ($advisedItems as $advisedItem) {
            $poItem = new PurchaseOrderItem();
            $poItem
                ->setSupplier($advisedItem['supplier'])
                ->setProductSku($advisedItem['sku'])
                ->setOrderedAmount((double)$advisedItem['orderAmount']);
            $entity->addItem($poItem);
        }

        if ($entity->getItems()->count() > 0) {
            $this->container
                ->get('event_dispatcher')
                ->dispatch(
                    new CreateNotificationMessageEvent($context),
                    CreateNotificationMessageEvent::NAME
                );
        }

        $recipient = new Customer();
        $recipient->setEmail($configManager->get('marello_purchaseorder.purchaseorder_notification_address'));
        $recipient->setOrganization($this->getOrganization());
        /** @var EmailSendProcessor $sendProcessor */
        $sendProcessor = $this->container->get('marello_notification.email.send_processor');
        $sendProcessor->sendNotification(
            'marello_purchase_order_model_advise',
            [$recipient],
            $entity
        );

        return self::EXIT_CODE;
    }

    /**
     * @return OrganizationInterface
     */
    protected function getOrganization()
    {
        return $this->container->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getFirst();
    }

    /**
     * @return NotificationMessageContext
     */
    protected function createNotificationContext()
    {
        $url = $this
            ->container
            ->get('router')
            ->generate('marello_purchase_order_widget_purchase_order_candidates_grid', [], 302);
        $translation = $this
            ->container
            ->get('translator')
            ->trans(
                'marello.notificationmessage.purchaseorder.candidates.solution',
                ['%url%' => $url],
                'notificationMessage'
            );

        return NotificationMessageContextFactory::create(
            NotificationMessageTypeInterface::NOTIFICATION_MESSAGE_TYPE_INFO,
            NotificationMessageResolvedInterface::NOTIFICATION_MESSAGE_RESOLVED_NO,
            NotificationMessageSourceInterface::NOTIFICATION_MESSAGE_SOURCE_SYSTEM,
            'marello.notificationmessage.purchaseorder.candidates.title',
            'marello.notificationmessage.purchaseorder.candidates.message',
            $translation,
            null,
            null,
            null,
            null,
            null,
            $this->getOrganization()
        );
    }
}

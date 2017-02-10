<?php

namespace Marello\Bundle\ReturnBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Marello\Bundle\ReturnBundle\Form\DataTransformer\OrderToOrderNumberTransformer;

class ReturnApiType extends AbstractType
{
    const NAME = 'marello_return_api';

    /** @var OrderToOrderNumberTransformer */
    protected $orderToOrderNumberTransformer;

    protected $salesChannelTransformer;

    /**
     * ReturnApiType constructor.
     *
     * @param OrderToOrderNumberTransformer $orderToOrderNumberTransformer
     * @param EntityToIdTransformer         $salesChannelTransformer
     */
    public function __construct(
        OrderToOrderNumberTransformer $orderToOrderNumberTransformer,
        EntityToIdTransformer $salesChannelTransformer
    ) {
        $this->orderToOrderNumberTransformer    = $orderToOrderNumberTransformer;
        $this->salesChannelTransformer          = $salesChannelTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('order', 'text', [
                'required'    => true,
                'constraints' => new NotNull(),
            ])
            ->add('returnNumber', 'text', [
                'required' => false,
            ])
            ->add('returnReference', 'text', [
                'required' => false,
            ])
            ->add('salesChannel', 'marello_sales_channel_select_api', [
                'required'    => true,
                'constraints' => new NotNull(),
            ])
            ->add('returnItems', 'collection', [
                'type'         => ReturnItemApiType::NAME,
                'allow_add'    => true,
                'by_reference' => false,
            ]);

        $builder->get('order')->addModelTransformer($this->orderToOrderNumberTransformer);
//        $builder->get('salesChannel')->addModelTransformer($this->salesChannelTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'      => 'Marello\Bundle\ReturnBundle\Entity\ReturnEntity',
            'csrf_protection' => false,
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return self::NAME;
    }
}

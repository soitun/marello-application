<?php

namespace MarelloEnterprise\Bundle\InventoryBundle\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Marello\Bundle\InventoryBundle\Entity\WarehouseGroup;
use Symfony\Component\Validator\Constraints\NotBlank;

class WarehouseGroupType extends AbstractType
{
    const BLOCK_PREFIX = 'marello_warehouse_group';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'required' => true
                ]
            )
            ->add(
                'description',
                TextareaType::class,
                [
                    'required' => false
                ]
            )
            ->add(
                'warehouses',
                WarehouseGridType::class,
                [
                    'required' => true,
                    'constraints' => new NotBlank()
                ]
            )
            ->add(
                'isConsolidationWarehouse',
                HiddenType::class,
                [
                    'mapped' => false
                ]
            )
            ->add(
                'isOrderOnDemandLocation',
                HiddenType::class,
                [
                    'mapped' => false
                ]
            )
            ->add(
                'sortOrder',
                HiddenType::class,
                [
                    'mapped' => false
                ]
            )->addEventListener(
                FormEvents::POST_SUBMIT,
                [$this, 'postSubmitDataListener']
            );
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmitDataListener(FormEvent $event)
    {
        $this->assignDataToWarehouses($event, 'isConsolidationWarehouse', 'setIsConsolidationWarehouse', false);
        $this->assignDataToWarehouses($event, 'isOrderOnDemandLocation', 'setOrderOnDemandLocation', false);
        $this->assignDataToWarehouses($event, 'sortOrder', 'setSortOrder', 0);
    }

    private function assignDataToWarehouses(FormEvent $event, string $fieldName, string $setter, $defaultValue): void
    {
        /** @var WarehouseGroup $warehouseGroup */
        $warehouseGroup = $event->getData();
        $form = $event->getForm();

        if ($form->has($fieldName)) {
            $data = $form->get($fieldName)->getData();
            $consolidationData = json_decode($data, true);
            foreach ($warehouseGroup->getWarehouses() as $warehouse) {
                if (array_key_exists($warehouse->getCode(), $consolidationData)) {
                    $warehouse->$setter($consolidationData[$warehouse->getCode()]);
                } else {
                    $warehouse->$setter($defaultValue);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WarehouseGroup::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}

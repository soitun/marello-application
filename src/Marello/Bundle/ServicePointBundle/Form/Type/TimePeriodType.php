<?php

namespace Marello\Bundle\ServicePointBundle\Form\Type;

use Marello\Bundle\ServicePointBundle\Entity\TimePeriod;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimePeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dayOfWeek', DayOfWeekType::class, [
                'label' => 'marello.servicepoint.timeperiod.day_of_week.label',
                'required' => true,
            ])
            ->add('openTime', TimeType::class, [
                'label' => 'marello.servicepoint.timeperiod.open_time.label',
                'required' => true,
            ])
            ->add('closeTime', TimeType::class, [
                'label' => 'marello.servicepoint.timeperiod.close_time.label',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TimePeriod::class,
        ]);
    }
}

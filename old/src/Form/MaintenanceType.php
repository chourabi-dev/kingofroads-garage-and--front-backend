<?php

namespace App\Form;

use App\Entity\Maintenance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', TextareaType::class, [
                'required' => false,
                'label' => 'Note',
                'attr' => ['rows' => 5, 'class' => 'form-control', 'placeholder' => 'Describe the work done, observations, recommendations...'],
            ])

             ->add('odometer', NumberType::class, [
                'required' => false,
                'label' => 'Odometer (km)',
                'attr' => [ 'class' => 'form-control'],
            ])
            ->add('lines', CollectionType::class, [
                'entry_type' => MaintenanceLineType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Maintenance::class]);
    }
}

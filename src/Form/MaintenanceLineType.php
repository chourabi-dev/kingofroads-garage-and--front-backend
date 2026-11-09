<?php

namespace App\Form;

use App\Entity\MaintenanceAction;
use App\Entity\MaintenanceLine;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MaintenanceLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', EntityType::class, [
                'class' => MaintenanceAction::class,
                'choice_label' => 'label',
                'placeholder' => '— Select an action —',
                'label' => 'Action',
                'query_builder' => fn ($repo) => $repo->createQueryBuilder('a')->orderBy('a.label', 'ASC'),
                // used by the JS to pre-fill the price when an action is chosen
                'choice_attr' => fn (?MaintenanceAction $a) => $a ? ['data-price' => $a->getDefaultPrice()] : [],
                'attr' => ['class' => 'form-select line-action select2'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['min' => 1, 'class' => 'form-control line-qty'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Unit price (TND)',
                'input' => 'string',
                'scale' => 3,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.001', 'class' => 'form-control line-price'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MaintenanceLine::class]);
    }
}

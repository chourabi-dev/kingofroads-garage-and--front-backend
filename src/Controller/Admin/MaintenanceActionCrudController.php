<?php

namespace App\Controller\Admin;

use App\Entity\MaintenanceAction;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class MaintenanceActionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MaintenanceAction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Maintenance action')
            ->setEntityLabelInPlural('Maintenance actions');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('label'),
            MoneyField::new('defaultPrice', 'Default price')
                ->setCurrency('TND')
                ->setNumDecimals(3)
                ->setStoredAsCents(false),
        ];
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Bike;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BikeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Bike::class;
    } 
    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('info')->setTemplatePath("admin/bike/bike-info-column.html.twig")->hideOnForm(),
            AssociationField::new('client')->setTemplatePath("admin/bike/client-column.html.twig")->hideOnForm(),

             
        ];
    }
    
}

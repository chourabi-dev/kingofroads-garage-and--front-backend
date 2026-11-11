<?php

namespace App\Controller\Admin;

use App\Entity\BikeBrand;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BikeBrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BikeBrand::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('label')->setColumns(6)->setLabel("Label"),
            ImageField::new('photo')
            ->setColumns(6)
            ->setBasePath('/uploads/brands') 
            ->setUploadDir('public/uploads/brands') 
            ->setRequired(false) 
            ->setUploadedFileNamePattern('[randomhash].[extension]') 
            ->setLabel('Image primaire'),

        ];
    }
    
}

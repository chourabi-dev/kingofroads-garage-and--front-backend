<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('label')->setColumns(12),
            ImageField::new('photo') 
            ->setColumns(12)
            ->setBasePath('/uploads/companies_brands') 
            ->setUploadDir('public/uploads/companies_brands') 
            ->setRequired(false) 
            ->setUploadedFileNamePattern('[randomhash].[extension]') 
            ->setLabel('Image'),
        ];
    }
    



}

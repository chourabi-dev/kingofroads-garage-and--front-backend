<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductSize;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->setLabel('N°'),
            
            TextField::new('label')->setColumns(6)->setLabel("Label"),
            AssociationField::new('category')->setColumns(6)->setLabel("Catégory")->setFormTypeOption('choice_label', 'label'), 
              
            NumberField::new('stock')->setColumns(12)->setLabel("Stock"),
            

            AssociationField::new('brand')->setColumns(12)->setLabel("Brand")->setFormTypeOption('choice_label', 'label'), 
             
              

            ImageField::new('image')
            ->setColumns(6)
            ->setBasePath('/uploads/gallery') 
            ->setUploadDir('public/uploads/gallery') 
            ->setRequired(false) 
            ->setUploadedFileNamePattern('[randomhash].[extension]') 
            ->setLabel('Image primaire'),

            ImageField::new('secondaryImage')
            ->onlyOnForms()
            ->setColumns(6)
            ->setBasePath('/uploads/gallery') 
            ->setUploadDir('public/uploads/gallery') 
            ->setRequired(false) 
            ->setUploadedFileNamePattern('[randomhash].[extension]') 
            ->setLabel('Image secondaire'),
            


            NumberField::new('price')->setColumns(12)->setLabel("Prix"),
            BooleanField::new('available')->setColumns(12)->setLabel("Affiché"),
            BooleanField::new('featured')->setColumns(12)->setLabel("En premier"),


            AssociationField::new('sizes')  
            ->onlyOnForms()
            ->setColumns(12)
            ->setLabel("Tailles"),
 
    
            TextEditorField::new('content')->setColumns(12)->setLabel("Contenue")->hideOnIndex(),
            
            DateTimeField::new('createdAt')->setLabel("product.createdAt")->hideOnForm()
        ];
    }
    


    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
{
    if ($entityInstance instanceof Product) {
        if ($entityInstance->getCreatedAt() === null) {
            $entityInstance->setCreatedAt(new \DateTime());
        }
    }

    parent::persistEntity($entityManager, $entityInstance);
}
}

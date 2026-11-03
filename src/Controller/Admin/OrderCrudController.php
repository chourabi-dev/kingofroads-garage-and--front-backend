<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Repository\OrderStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class OrderCrudController extends AbstractCrudController
{
    private OrderStatusRepository $statusRepository;

public function __construct(OrderStatusRepository $statusRepository)
{
    $this->statusRepository = $statusRepository;
}

    public function createIndexQueryBuilder(
    $searchDto,
    $entityDto,
    $fields,
    $filters
): QueryBuilder {
    $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

    return $qb
        ->andWhere('entity.archived IS NULL OR entity.archived = false');
}


    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        $status = $this->statusRepository->findAll();

        return [
            IdField::new('id')->setLabel('N°'),
            TextField::new('Contact')->onlyOnIndex()->setTemplatePath("admin/comps/contact.html.twig"),
            TextField::new('Prix Total')->onlyOnIndex()->setTemplatePath("admin/comps/price.html.twig"),
            TextField::new('Produits')->onlyOnIndex()->setTemplatePath("admin/comps/products.html.twig"),
            AssociationField::new('status')->setLabel("État de la commande")->setTemplatePath("admin/comps/status.html.twig")->setCustomOption("status",$status)
             
        ];
    }


public function configureActions(Actions $actions): Actions
{
    $archive = Action::new('archive', 'Archiver')
        ->linkToCrudAction('archiveOrder')
        ->displayIf(fn ($entity) => $entity && !$entity->isArchived());

    return $actions
        ->add(Crud::PAGE_INDEX, $archive);
}

    public function archiveOrder( Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $id = $request->query->get('entityId');

    if (!$id) {
        throw new \RuntimeException('Missing order ID');
    }

    $order = $em->getRepository(Order::class)->find($id);

    if (!$order) {
        throw new \RuntimeException('Order not found');
    }

    $order->setArchived(true);
    $em->flush();

    $this->addFlash('success', 'Commande archivée avec succès');

    return $this->redirect($request->headers->get('referer'));
    }
    
}

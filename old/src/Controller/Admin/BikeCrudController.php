<?php

namespace App\Controller\Admin;

use App\Entity\Bike;
use App\Entity\Maintenance;
use App\Form\MaintenanceType; 
use App\Repository\BikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BikeCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly BikeRepository $bikeReportRepository
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Bike::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $history = Action::new('history', 'Bike history', 'fa fa-clock-rotate-left')
            ->linkToCrudAction('history');

        $addMaintenance = Action::new('addMaintenance', 'New maintenance', 'fa fa-plus')
            ->linkToCrudAction('addMaintenance')
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $history)
            ->add(Crud::PAGE_DETAIL, $history->setCssClass('btn btn-secondary'))
            ->add(Crud::PAGE_DETAIL, $addMaintenance);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('info')->setTemplatePath("admin/bike/bike-info-column.html.twig")->hideOnForm(),
            AssociationField::new('client')->setTemplatePath("admin/bike/client-column.html.twig")->hideOnForm(),
            TextField::new('maintenanceHistory', 'Maintenance history')
                ->setVirtual(true)
                ->setTemplatePath('admin/bike/maintenance-summary.html.twig')
                ->onlyOnDetail(),


            AssociationField::new('client')->setLabel("client")->setColumns(12)->hideOnIndex(),
            AssociationField::new('make')->setLabel("Brand")->setColumns(6)->hideOnIndex(),
            TextField::new('model')->setColumns(6)->hideOnIndex(),
            NumberField::new('mileage')->setColumns(12)->hideOnIndex(),


            TextField::new('cc')->setColumns(12)->hideOnIndex(), 
            NumberField::new('year')->setColumns(6)->hideOnIndex(),

            NumberField::new('plate')->setColumns(6)->hideOnIndex(), 
            TextField::new('vin')->setColumns(12)->hideOnIndex(), 
            
            
        ];
    }



    

    /**
     * Bike history: reports sent by the client + maintenance history (invoices).
     */
    public function history(AdminContext $context): Response
    {
        $bike = $this->getBikeFromContext($context);

        $reports = $bike->getBikeReports()->toArray();
        usort($reports, fn ($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $this->render('admin/bike/history.html.twig', [
            'bike' => $bike,
            'reports' => $reports,
            'maintenances' => $bike->getMaintenances(),
            'add_url' => $this->adminUrlGenerator
                ->unsetAll()
                ->setController(self::class)
                ->setAction('addMaintenance')
                ->setEntityId($bike->getId())
                ->generateUrl(),
            'detail_url' => $this->detailUrl($bike),
        ]);
    }

    /**
     * New maintenance page. After saving we go back to the bike details page.
     */
    public function addMaintenance(AdminContext $context, Request $request): Response
    {
        $bike = $this->getBikeFromContext($context);

        $maintenance = new Maintenance();
        $maintenance->setBike($bike);

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $bike->addMaintenance($maintenance);

            // UPDATE BIKE ODO
            $bike->setMileage($maintenance->getOdometer());


            $this->em->persist($maintenance);
            $this->em->persist($bike);
            $this->em->flush();
            
            $this->addFlash('success', 'Maintenance saved.');

            return new RedirectResponse($this->detailUrl($bike));
        }

        return $this->render('admin/bike/maintenance-new.html.twig', [
            'bike' => $bike,
            'form' => $form,
            'cancel_url' => $this->detailUrl($bike),
        ]);
    }

    private function detailUrl(Bike $bike): string
    {
        return $this->adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($bike->getId())
            ->generateUrl();
    }

    private function getBikeFromContext(AdminContext $context): Bike
    { 
        $bikeID = $context->getRequest()->query->get("entityId");
 
        $bike = $this->bikeReportRepository->findOneBy(['id'=>$bikeID]);
 

        if (!$bike instanceof Bike) {
            throw new NotFoundHttpException('Bike not found.');
        }

        return $bike;
    }
}

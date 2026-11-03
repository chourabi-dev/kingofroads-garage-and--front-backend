<?php

namespace App\Controller\Admin;

use App\Entity\BikeBrand;
use App\Entity\Category;
use App\Entity\Client;
use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\Product;
use App\Entity\ProductSize;
use App\Repository\OrderRepository;
use App\Repository\OrderStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private OrderRepository $orderRepository;
    private OrderStatusRepository $orderStatusRepository;

    public function __construct(OrderRepository $orderRepository, OrderStatusRepository $orderStatusRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        
    }


    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            // the name visible to end users
            ->setTitle('King of roads store') 
            ->setFaviconPath('favicon.svg')  
            // set this option if you prefer the page content to span the entire
            // browser width, instead of the default design which sets a max width
            ->renderContentMaximized() 
            ->generateRelativeUrls()

             
        ;
    }


 



    public function index(): Response
    { 
        return $this->render('admin/index.html.twig');
    }

 

    public function configureMenuItems(): iterable
    {
       $orders = $this->orderRepository->createQueryBuilder('o')
        ->where('o.status = :status')
        ->andWhere('o.archived IS NULL OR o.archived = :archived')
        ->setParameter('status', 1)
        ->setParameter('archived', false)
        ->getQuery()
        ->getResult();

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');


        yield MenuItem::section('Ventes');

        if (sizeof($orders) != 0) {
            yield MenuItem::linkToCrud('Commandes', 'fa fa-cart-plus', Order::class)->setBadge(sizeof($orders),"danger");
        
        } else {
            yield MenuItem::linkToCrud('Commandes', 'fa fa-cart-plus', Order::class);
        
        }
        
        
        yield MenuItem::section('Produits');
        yield MenuItem::linkToCrud('Categories', 'fas fa-list', Category::class);
        yield MenuItem::linkToCrud('Tailles', 'fas fa-list', ProductSize::class);
        yield MenuItem::linkToCrud('Stock', 'fas fa-list', Product::class);
        yield MenuItem::linkToCrud('Status', 'fas fa-list', OrderStatus::class);
        
        

        yield MenuItem::section('Garage');
        yield MenuItem::linkToCrud('Marque', 'fas fa-list', BikeBrand::class); 
        yield MenuItem::linkToCrud('Clients', 'fas fa-list', Client::class); 
        
        
        
        
    }


    #[Route( path: '/admin/order/update-status', name: 'admin_update_order_status', methods:['GET','POST'])]
    function updateOrder( Request $request,EntityManagerInterface $em ) : JsonResponse {
         $data = json_decode($request->getContent(), true);



        $order = $this->orderRepository->find($data['orderId']);
        $status = $this->orderStatusRepository->find($data['statusId']); 
        $order->setStatus($status);

        $em->flush();
        
        return $this->json(['success'=>true]);
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Bike;
use App\Entity\BikeBrand;
use App\Entity\BikeReport;
use App\Entity\Brand;
use App\Entity\Category;
use App\Entity\Client;
use App\Entity\Maintenance;
use App\Entity\MaintenanceAction;
use App\Entity\Order;
use App\Entity\OrderItem;
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
    /** Status given to every new order (see ApiController: $orderStatusRepository->find(1)). */
    private const NEW_ORDER_STATUS_ID = 1;

    /** A product is "low on stock" when its stock is at or below this value. */
    private const LOW_STOCK_THRESHOLD = 5;

    private OrderRepository $orderRepository;
    private OrderStatusRepository $orderStatusRepository;
    private EntityManagerInterface $em;

    public function __construct(
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $em
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->em = $em;
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
        $now = new \DateTimeImmutable();
        $monthStart = $now->modify('first day of this month')->setTime(0, 0);
        $prevMonthStart = $monthStart->modify('-1 month');
        $chartStart = $monthStart->modify('-5 months');          // 6 months window, current month included
        $last30Days = $now->modify('-30 days');

        $cancelledIds = $this->cancelledStatusIds();

        // ---- 6-month buckets (revenue + counts), aggregated in PHP to stay database agnostic ----
        $buckets = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $chartStart->modify("+$i months");
            $buckets[$month->format('Y-m')] = [
                'label' => $month->format('M'),
                'store' => 0.0,
                'garage' => 0.0,
                'orders' => 0,
                'maintenances' => 0,
            ];
        }

        // Store: orders (cancelled ones are ignored)
        $orderRows = $this->em->createQueryBuilder()
            ->select('o.createdAt AS createdAt', 'o.total AS total', 'IDENTITY(o.status) AS statusId')
            ->from(Order::class, 'o')
            ->where('o.createdAt >= :from')
            ->setParameter('from', \DateTime::createFromImmutable($chartStart))
            ->getQuery()
            ->getArrayResult();

        foreach ($orderRows as $row) {
            if ($row['statusId'] !== null && \in_array((int) $row['statusId'], $cancelledIds, true)) {
                continue;
            }
            $key = $row['createdAt']->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['store'] += (float) $row['total'];
                ++$buckets[$key]['orders'];
            }
        }

        // Garage: maintenance invoices (total HT = actions + products)
        $maintenances = $this->em->createQueryBuilder()
            ->select('m', 'l')
            ->from(Maintenance::class, 'm')
            ->leftJoin('m.lines', 'l')
            ->where('m.createdAt >= :from')
            ->setParameter('from', \DateTime::createFromImmutable($chartStart))
            ->getQuery()
            ->getResult();

        foreach ($maintenances as $maintenance) {
            $key = $maintenance->getCreatedAt()->format('Y-m');
            if (isset($buckets[$key])) {
                $buckets[$key]['garage'] += $maintenance->getTotal();
                ++$buckets[$key]['maintenances'];
            }
        }

        $current = $buckets[$monthStart->format('Y-m')];
        $previous = $buckets[$prevMonthStart->format('Y-m')];

        // ---- counters ----
        $pendingOrders = (int) $this->em->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.archived IS NULL OR o.archived = :archived')
            ->setParameter('status', self::NEW_ORDER_STATUS_ID)
            ->setParameter('archived', false)
            ->getQuery()
            ->getSingleScalarResult();

        $lowStockCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Product::class, 'p')
            ->where('p.stock <= :threshold')
            ->setParameter('threshold', self::LOW_STOCK_THRESHOLD)
            ->getQuery()
            ->getSingleScalarResult();

        $newBikes = (int) $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(Bike::class, 'b')
            ->where('b.createdAt >= :from')
            ->setParameter('from', \DateTime::createFromImmutable($monthStart))
            ->getQuery()
            ->getSingleScalarResult();

        $reports30d = (int) $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(BikeReport::class, 'r')
            ->where('r.createdAt >= :from')
            ->setParameter('from', \DateTime::createFromImmutable($last30Days))
            ->getQuery()
            ->getSingleScalarResult();

        // ---- lists ----
        $lowStockProducts = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('p.stock <= :threshold')
            ->setParameter('threshold', self::LOW_STOCK_THRESHOLD)
            ->orderBy('p.stock', 'ASC')
            ->addOrderBy('p.label', 'ASC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();

        $topProductsQb = $this->em->createQueryBuilder()
            ->select('p.id AS id', 'p.label AS label', 'SUM(i.quantity) AS qty')
            ->from(OrderItem::class, 'i')
            ->join('i.product', 'p')
            ->join('i.request', 'o')
            ->where('o.createdAt >= :from')
            ->setParameter('from', \DateTime::createFromImmutable($last30Days))
            ->groupBy('p.id')
            ->addGroupBy('p.label')
            ->orderBy('qty', 'DESC')
            ->setMaxResults(5);
        if ($cancelledIds) {
            $topProductsQb
                ->andWhere('o.status IS NULL OR o.status NOT IN (:cancelled)')
                ->setParameter('cancelled', $cancelledIds);
        }
        $topProducts = $topProductsQb->getQuery()->getArrayResult();

        $statusBreakdown = $this->em->createQueryBuilder()
            ->select('s.label AS label', 'COUNT(o.id) AS total')
            ->from(Order::class, 'o')
            ->leftJoin('o.status', 's')
            ->where('o.archived IS NULL OR o.archived = :archived')
            ->setParameter('archived', false)
            ->groupBy('s.id')
            ->addGroupBy('s.label')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $storeTotal = round(array_sum(array_column($buckets, 'store')), 3);
        $garageTotal = round(array_sum(array_column($buckets, 'garage')), 3);

        return $this->render('admin/index.html.twig', [
            'kpi' => [
                'store_month' => round($current['store'], 3),
                'store_delta' => $this->delta($current['store'], $previous['store']),
                'orders_month' => $current['orders'],
                'orders_delta' => $this->delta($current['orders'], $previous['orders']),
                'garage_month' => round($current['garage'], 3),
                'garage_delta' => $this->delta($current['garage'], $previous['garage']),
                'maintenances_month' => $current['maintenances'],
                'maintenances_delta' => $this->delta($current['maintenances'], $previous['maintenances']),
                'pending_orders' => $pendingOrders,
                'low_stock' => $lowStockCount,
                'low_stock_threshold' => self::LOW_STOCK_THRESHOLD,
                'clients' => $this->em->getRepository(Client::class)->count([]),
                'bikes' => $this->em->getRepository(Bike::class)->count([]),
                'bikes_new' => $newBikes,
                'reports_30d' => $reports30d,
            ],
            'chart' => [
                'labels' => array_column($buckets, 'label'),
                'store' => array_map(fn (float $v) => round($v, 3), array_column($buckets, 'store')),
                'garage' => array_map(fn (float $v) => round($v, 3), array_column($buckets, 'garage')),
                'store_total' => $storeTotal,
                'garage_total' => $garageTotal,
            ],
            'status_breakdown' => $statusBreakdown,
            'status_total' => array_sum(array_column($statusBreakdown, 'total')),
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
            'recent_orders' => $this->orderRepository->findBy([], ['createdAt' => 'DESC'], 6),
            'recent_maintenances' => $this->em->getRepository(Maintenance::class)->findBy([], ['createdAt' => 'DESC'], 6),
            'recent_reports' => $this->em->getRepository(BikeReport::class)->findBy([], ['createdAt' => 'DESC'], 5),
        ]);
    }

    /** Percentage change between two periods, or null when there is nothing to compare with. */
    private function delta(float|int $current, float|int $previous): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * Ids of the order statuses that mean "cancelled" (matched on the label), so cancelled
     * orders do not count as revenue.
     *
     * @return int[]
     */
    private function cancelledStatusIds(): array
    {
        $ids = [];
        foreach ($this->orderStatusRepository->findAll() as $status) {
            if (preg_match('/annul|cancel|refus|reject/iu', (string) $status->getLabel())) {
                $ids[] = $status->getId();
            }
        }

        return $ids;
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
        yield MenuItem::linkToCrud('Brands', 'fas fa-list', Brand::class);
        yield MenuItem::linkToCrud('Clients', 'fas fa-users', Client::class); 
        yield MenuItem::linkToCrud('Bikes', 'fa fa-motorcycle', Bike::class); 
        yield MenuItem::linkToCrud('Maintenance actions', 'fas fa-wrench', MaintenanceAction::class);
        
        
        
        
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

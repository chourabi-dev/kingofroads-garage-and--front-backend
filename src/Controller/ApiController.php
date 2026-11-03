<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\OrderStatusRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductSizeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1' )]
final class ApiController extends AbstractController
{
    
      



    #[Route('/categories/list', name: 'api_categories_list')]
    public function categoriesList( CategoryRepository $categoryRepository): Response
    {
        $data = [];

        foreach ($categoryRepository->findAll() as $key => $c) {
            
            $data[] = ['id'=>$c->getId(), 'label'=>$c->getLabel()];

        }

        return $this->json($data);
    }

    #[Route('/sizes/list', name: 'api_sizes_list')]
    public function sizesList( ProductSizeRepository $repo): Response
    {
        $data = [];

        foreach ($repo->findAll() as $key => $c) {
            
            $data[] = ['id'=>$c->getId(), 'label'=>$c->getLabel()];

        }

        return $this->json($data);
    }


    
    #[Route('/products/list', name: 'api_products_list')]
    public function productsList( ProductSizeRepository $repo): Response
    {
        $data = []; 
        
        foreach ($repo->findBy(['available'=>true]) as $key => $c) { 
            $data[] = ['id'=>$c->getId(), 'label'=>$c->getLabel()]; 
        }
        
        return $this->json($data);
    }



    #[Route('/products/featured', name: 'api_products_featured_list')]
    public function productsFeatured( ProductRepository $repo): Response
    {
        $data = []; 
        
        foreach ($repo->findBy(['available'=>true,'featured'=>true]) as $key => $p) { 
            $sizes = [];

            foreach ($p->getSizes() as $key => $s) {
                $sizes[] = $s->getLabel();
            }

            $data[] = [

                'id'=>$p->getId(), 
                'name'=>$p->getLabel(),
                'price'=>$p->getPrice(),
                'stock'=>$p->getStock(),
                'description'=>$p->getContent(), 
                'category'=> $p->getCategory()->getLabel(),
                'sizes'=>$sizes,
                'images'=>[
                    $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getImage(),
                    $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getSecondaryImage()
                ]
            ]; 
        }
        
        return $this->json($data);
    }



    #[Route('/shop/data', name: 'api_shop_data')]
    public function shopContent( ProductRepository $repo, CategoryRepository $crepo): Response
    {
        $data = []; 
        
        foreach ($repo->findBy(['available'=>true]) as $key => $p) { 
            $sizes = [];

            foreach ($p->getSizes() as $key => $s) {
                $sizes[] = $s->getLabel();
            }
 
            $data[] = [
                'id'=>$p->getId(), 
                'name'=>$p->getLabel(),
                'price'=>$p->getPrice(),
                'stock'=>$p->getStock(),
                'description'=>$p->getContent(), 
                'category'=> $p->getCategory()->getLabel(),
                'sizes'=>$sizes,
                'images'=>[
                    $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getImage(),
                    $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getSecondaryImage()
                ]
                
                
            ]; 
        }


        $categories = ['all']; 
        foreach ($crepo->findAll() as $key => $c) { 
            $categories[] = $c->getLabel(); 
        } 
        return $this->json([
            'categories'=>$categories,
            'products'=>$data
            
        ]);
    }

    





    #[Route('/shop/data/product/details/{id}', name: 'api_shop_product_details_route')]
    public function productDetails( Product $p): Response
    { 
        foreach ($p->getSizes() as $key => $s) {
            $sizes[] = $s->getLabel();
        }

        $res = [
            'id'=>$p->getId(), 
            'name'=>$p->getLabel(),
            'price'=>$p->getPrice(),
            'stock'=>$p->getStock(),
            'description'=>$p->getContent(), 
            'category'=> $p->getCategory()->getLabel(),
            'sizes'=>$sizes,
            'images'=>[
                $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getImage(),
                $_ENV['HOST_URL'].'/uploads/gallery/'.$p->getSecondaryImage()
            ]
            
            
        ]; 

        return $this->json($res);
        
    }





    #[Route('/checkout', name: 'api_checkout', methods: ['POST'])]
    public function checkout(
        Request $request, 
        EntityManagerInterface $em, 
        ProductRepository $productRepository,
        ProductSizeRepository $productSizeRepository,
        OrderStatusRepository $orderStatusRepository
        
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json([
                    'message' => 'Invalid JSON'
                ], 400);
            }

            // ---------------- CUSTOMER ----------------
            $customer = $data['customer'] ?? null;

            if (
                !$customer ||
                empty($customer['name']) ||
                empty($customer['phone']) ||
                empty($customer['address'])
            ) {
                return $this->json([
                    'message' => 'Missing customer information'
                ], 422);
            }

            // ---------------- ITEMS ----------------
            $items = $data['items'] ?? [];

            if (count($items) === 0) {
                return $this->json([
                    'message' => 'Cart is empty'
                ], 422);
            }

            // ---------------- TOTAL ----------------
            $total = (float) ($data['total'] ?? 0);

            $initStatus = $orderStatusRepository->find(1);

            
             $order = new Order();
             $order->setName($customer['name']);
             $order->setPhone($customer['phone']);
             $order->setAddress($customer['address']);
             $order->setMessage($customer['message'] ?? null);
             $order->setTotal($total);
             $order->setCreatedAt(new \DateTime());
             $order->setStatus($initStatus);
            
             $em->persist($order);
            
             foreach ($items as $item) {
                $p = $productRepository->find($item['productId']);
                $size = $productSizeRepository->findOneBy(['label'=>$item['size']]);

                 $orderItem = new OrderItem();
                 $orderItem->setRequest($order);
                 $orderItem->setProduct($p); 
                 $orderItem->setQuantity($item['quantity']); 
                 
                 $orderItem->setSize($size);


                 $em->persist($orderItem);
             }
            
             $em->flush();

            return $this->json([
                'message' => 'Order created successfully',
                'data' => [
                    'customer' => $customer,
                    'itemsCount' => count($items),
                    'total' => $total
                ]
            ], 201);

        } catch (\Throwable $e) {
            return $this->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}

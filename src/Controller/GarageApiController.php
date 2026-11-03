<?php

namespace App\Controller;

use App\Entity\Bike;
use App\Entity\BikeReport;
use App\Entity\Client;
use App\Repository\BikeBrandRepository;
use App\Repository\BikeRepository;
use App\Repository\ClientRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GarageApiController extends AbstractController
{


    #[Route('/api/v1/auth/register', name: 'app_garage_api_create_client_account', methods: ['POST'])]
    public function createClientAccount(
        Request $request,
        ClientRepository $clientRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): Response {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';
        $phone = trim($data['phone'] ?? '');

        if (!$email || !$name || !$password || !$phone) {
            return $this->json([
                'message' => 'All fields are required.',
                'required' => ['email', 'name', 'password', 'phone'],
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'message' => 'Invalid email address.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return $this->json([
                'message' => 'Password must contain at least 8 characters.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($clientRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'message' => 'An account with this email already exists.',
            ], Response::HTTP_CONFLICT);
        }

        $client = new Client();

        $client->setEmail($email);
        $client->setFullname($name);
        $client->setPhone($phone);
        $client->setRoles(['ROLE_CLIENT']);

        $client->setUniqueID( uniqid() );

        $client->setPassword(
            $passwordHasher->hashPassword($client, $password)
        );

        $entityManager->persist($client);
        $entityManager->flush();

        return $this->json([
            'message' => 'Account created successfully.',
            'client' => [
                'id' => $client->getId(),
                'email' => $client->getEmail(),
                'name' => $client->getFullname(),
                'phone' => $client->getPhone(),
            ],
        ], Response::HTTP_CREATED);
    }



    #[Route('/api/v1/auth/login', name: 'app_garage_api_login', methods: ['POST'])]
    public function authClient() {
         
    }



    #[Route('/api/v1/auth/me', name: 'app_garage_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?Client $client): Response
    {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        } 

        return $this->json([
            'id' => $client->getUniqueID(),
            'email' => $client->getEmail(),
            'name' => $client->getFullname(),
            'phone' => $client->getPhone(),
         ]);
    }



    #[Route('/api/v1/auth/me', name: 'app_garage_auth_update_me', methods: ['PUT'])]
    public function updateMe(
        Request $request,
        #[CurrentUser] ?Client $client,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $fullname = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if ($fullname === '' || $phone === '') {
            return $this->json([
                'success' => false,
                'message' => 'Fullname and phone are required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($fullname) < 3) {
            return $this->json([
                'success' => false,
                'message' => 'Fullname must contain at least 3 characters.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!preg_match('/^\+?[0-9]{8,15}$/', $phone)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid phone number.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $client->setFullname($fullname);
        $client->setPhone($phone);

        $entityManager->flush();

        return $this->json([
            'id' => $client->getUniqueID(),
            'email' => $client->getEmail(),
            'name' => $client->getFullname(),
            'phone' => $client->getPhone(),
          ]);
    }


    #[Route('/api/v1/auth/password', name: 'app_garage_auth_update_password', methods: ['PUT'])]
    public function updatePassword(
        Request $request,
        #[CurrentUser] ?Client $client,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (!$currentPassword || !$newPassword) {
            return $this->json([
                'success' => false,
                'message' => 'Current password and new password are required.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($client, $currentPassword)) {
            return $this->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($newPassword) < 8) {
            return $this->json([
                'success' => false,
                'message' => 'New password must contain at least 8 characters.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($currentPassword === $newPassword) {
            return $this->json([
                'success' => false,
                'message' => 'New password must be different from current password.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $client->setPassword(
            $passwordHasher->hashPassword($client, $newPassword)
        );

        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Password updated successfully.',
        ]);
    }





    #[Route('/api/v1/garage/bikes', name: 'app_garage_my_bikes', methods: ['GET'])]
    public function bikes(#[CurrentUser] ?Client $client, BikeRepository $bikeRepository): Response
    {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        } 

        $bikesData = $bikeRepository->findBy(['client'=>$client]);

        $bikes = [];

        foreach ($bikesData as $key => $b) {
            $bikes[] = [
                "id" => $b->getUniqueID(),
                "make" => $b->getMake()->getLabel(),
                "model" => $b->getModel(),
                "year" => $b->getYear(),
                "plate" => $b->getPlate(),
                "mileage" => (int) $b->getMileage(), 
                "vin" => $b->getVin(), 
                "maintenanceCount" => 0,
                "lastServiceDate" => "2026-10-25"
            ];

        }

        return $this->json( $bikes, 201);
    }




    #[Route('/api/v1/garage/bikes/{uniqueID}', name: 'app_garage_my_bike_details_route', methods: ['GET'])]
    public function bikeDetails(#[CurrentUser] ?Client $client, BikeRepository $bikeRepository, $uniqueID): Response
    {
        $b = $bikeRepository->findOneBy(['uniqueID'=>$uniqueID]);

        if (!$b) {
            return $this->json([
                'success' => false,
                'message' => 'not found.',
            ], Response::HTTP_NOT_FOUND);
        } 
        


        return $this->json( [
                "id" => $b->getUniqueID(),
                "make" => $b->getMake()->getLabel(),
                "model" => $b->getModel(),
                "year" => $b->getYear(),
                "plate" => $b->getPlate(),
                "mileage" => (int) $b->getMileage(), 
                "vin" => $b->getVin(), 
                "maintenanceCount" => 0,
                "lastServiceDate" => "2026-10-25"
            ]
            , 201);
    }
    

    

    




    #[Route('/api/v1/garage/bikes', name: 'app_garage_create_new_bike', methods: ['POST'])]
    public function createBike(
        Request $request,
        #[CurrentUser] ?Client $client,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        BikeBrandRepository $bikeBrandRepository
    ): Response {
        if (!$client) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $data = $request->toArray();
        } catch (\JsonException) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }

        $required = ['make', 'model', 'year', 'cc', 'plate', 'mileage'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->json([
                    'message' => "Missing required field: $field"
                ], 400);
            }
        }

        if (
            !is_string($data['make']) ||
            !is_string($data['model']) ||
            !is_string($data['plate']) ||
            !is_int($data['year']) ||
            !is_int($data['cc']) ||
            !is_int($data['mileage']) ||
            $data['year'] < 1900 ||
            $data['year'] > (int) date('Y') + 1 ||
            $data['cc'] <= 0 ||
            $data['mileage'] < 0
        ) {
            return $this->json([
                'message' => 'Invalid bike data'
            ], 422);
        }

        $brand = $bikeBrandRepository->findOneBy(['label'=>$data['make']]);

        $bike = new Bike();
        $bike->setCreatedAt(new DateTime());
        $bike->setUniqueID( uniqid() );
        $bike->setClient($client);
        $bike->setMake($brand);
        $bike->setModel($data['model']);
        $bike->setYear($data['year']);
        $bike->setCc($data['cc']);
        $bike->setPlate($data['plate']);
        $bike->setMileage($data['mileage']);
        $bike->setVin($data['vin'] ?? null);

        $errors = $validator->validate($bike);

        if (count($errors) > 0) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => (string) $errors
            ], 422);
        }

        $em->persist($bike);
        $em->flush();

        return $this->json([
            'id' => $bike->getId(),
            'make' => $bike->getMake()->getLabel(),
            'model' => $bike->getModel(),
            'year' => $bike->getYear(),
            'cc' => $bike->getCc(),
            'plate' => $bike->getPlate(),
            'mileage' => $bike->getMileage(),
            'vin' => $bike->getVin(),
        ], 201);
    }


        
 
    
    #[Route('/api/v1/garage/brands', name: 'app_garage_garage_brands', methods: ['GET'])]
    public function brands(#[CurrentUser] ?Client $client, BikeBrandRepository $bikeBrandRepository): Response
    {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        } 

        $res = [];

        foreach ($bikeBrandRepository->findAll() as $key => $b) {
            $res[]=[
                "id"=>$b->getId(),
                "name"=>$b->getLabel(),
                "photo"=>$_ENV['HOST_URL'].'/uploads/brands/'.$b->getPhoto()
            ];

        }
        return $this->json($res, 201);
    }






 
        
    #[Route('/api/v1/garage/bikes/{uniqueID}/maintenance', name: 'app_garage_bike_maintenance_route', methods: ['GET'])]
    public function maintenance(#[CurrentUser] ?Client $client, BikeRepository $bikeRepository, $uniqueID ): Response
    {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        } 

        $b = $bikeRepository->findOneBy(['uniqueID'=>$uniqueID]);

        if (!$b) {
            return $this->json([
                'success' => false,
                'message' => 'not found.',
            ], Response::HTTP_NOT_FOUND);
        } 
         


        return $this->json([], 201);
    }



    


            
    #[Route(
        '/api/v1/garage/bikes/{uniqueID}/reports',
        name: 'app_garage_bike_report_prob_route',
        methods: ['POST']
    )]
    public function reportProb(
        #[CurrentUser] ?Client $client,
        BikeRepository $bikeRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        string $uniqueID
    ): Response {
        if (!$client) {
            return $this->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $bike = $bikeRepository->findOneBy([
            'uniqueID' => $uniqueID,
            'client' => $client,
        ]);

        if (!$bike) {
            return $this->json([
                'success' => false,
                'message' => 'Bike not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid JSON payload.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $description = trim($payload['description'] ?? '');

        if (!is_string($description) || $description === '') {
            return $this->json([
                'success' => false,
                'message' => 'Description is required.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $report = new BikeReport();
        $report->setCreatedAt(new DateTime());
        $report->setBike($bike);
        $report->setDescription($description);

        $entityManager->persist($report);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Problem reported successfully.',
            'report' => [
                'id' => $report->getId(),
                'description' => $report->getDescription(),
                'createdAt' => $report->getCreatedAt()->format(
                    \DateTimeInterface::ATOM
                ),
            ],
        ], Response::HTTP_CREATED);
    }



}

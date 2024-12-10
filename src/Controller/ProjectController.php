<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Project;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api', name: 'api_')]
class ProjectController extends AbstractFOSRestController
{
    #[Rest\Get("/projects")]
    public function index(ManagerRegistry $doctrine, SerializerInterface $serializer): JsonResponse
    {
        $products = $doctrine
            ->getRepository(Project::class)
            ->findAll();

        // ******************************************
        // If we don't use the Serializer, we have to manually loop the $products
        // and insert each element in the $data array bellow
        // $data = [];

        // foreach ($products as $product) {
        //    $data[] = [
        //        'id' => $product->getId(),
        //        'name' => $product->getName(),
        //        'description' => $product->getDescription(),
        //    ];
        // }
        // return $this->json($data);
        // ******************************************

        // Return the serialized JSON response
        return new JsonResponse($serializer->serialize($products, 'json'), 200, [], true);
    }

    // #[Route('/projects', name: 'project_create', methods: ['post'])]
    #[Rest\Post('/projects')]
    public function create(ManagerRegistry $doctrine, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        // *************************************
        // Code without serialize
        // $entityManager = $doctrine->getManager();

        // $project = new Project();
        // $project->setName($request->request->get('name'));
        // $project->setDescription($request->request->get('description'));

        // $entityManager->persist($project);
        // $entityManager->flush();

        // $data =  [
        //     'id' => $project->getId(),
        //     'name' => $project->getName(),
        //     'description' => $project->getDescription(),
        // ];
        // return $this->json($data);
        // *************************************

        // Deserialize the request content (assuming JSON input)
        $data = $serializer->deserialize($request->getContent(), Project::class, 'json');

        // Validate the incoming data
        $errors = $validator->validate($data);

        // Check if there are validation errors
        if (count($errors) > 0) {
            // Return validation errors
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(
                ['errors' => $errorMessages],
                400
            );
        }

        // Persist the project to the database
        $entityManager = $doctrine->getManager();
        $entityManager->persist($data);
        $entityManager->flush();

        // Serialize the project object to JSON before sending it in the response
        $jsonContent = $serializer->serialize($data, 'json');
        // Return the created project as a JSON response
        return new JsonResponse(
            [
                'message' => 'Project created successfully',
                'project' => json_decode($jsonContent),  // Include the project data under the 'project' key
            ],
            201  // HTTP status code for created resource (201)
        );
    }

    // #[Route('/projects/{id}', name: 'project_show', methods: ['get'])]
    #[Rest\Get("/projects/{id}")]
    public function show(ManagerRegistry $doctrine, int $id, SerializerInterface $serializer): JsonResponse
    {
        $project = $doctrine->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Project not found'], 404);
        }

        // Return the serialized project as a JSON response
        return new JsonResponse(
            $serializer->serialize($project, 'json'),
            200, // HTTP status 200 OK
            [],
            true // Indicates the content is already serialized in JSON
        );
    }

    // #[Route('/projects/{id}', name: 'project_update', methods: ['put', 'patch'])]
    #[Rest\Put("/projects/{id}")]
    #[Rest\Patch("/projects/{id}")]
    #[IsGranted('ROLE_USER')]
    public function update(ManagerRegistry $doctrine, Request $request, int $id, SerializerInterface $serializer): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = $entityManager->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(['error' => 'Project not found'], 404);
        }

        // Get the JSON data from the request body
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON data'], 400);
        }

        // Check if the name or description is provided in the request
        if (isset($data['name'])) {
            $project->setName($data['name']);
        }

        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }

        // Persist the changes and save them to the database
        $entityManager->flush();

        // Serialize the project object to JSON
        $jsonContent = $serializer->serialize($project, 'json');

        // Return the updated project as a JSON response
        return new JsonResponse(
            [
                'message' => 'Project updated successfully',
                'project' => json_decode($jsonContent),  // Include the project data under the 'project' key

            ],
            200  // HTTP status code for created resource (201)
        );
        // return new JsonResponse(
        //     $serializer->serialize($project, 'json'),
        //     200,
        //     [],
        //     true // Indicates the content is already serialized in JSON
        // );
    }

    // #[Route('/projects/{id}', name: 'project_delete', methods: ['delete'])]
    #[Rest\Delete("/projects/{id}")]
    public function delete(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $entityManager = $doctrine->getManager();
        $project = $entityManager->getRepository(Project::class)->find($id);

        if (!$project) {
            return new JsonResponse(
                ['error' => 'No project found for id ' . $id],
                404
            );
        }

        $entityManager->remove($project);
        $entityManager->flush();

        return new JsonResponse(
            ['message' => 'Deleted a project successfully with id ' . $id],
            200
        );
    }
}

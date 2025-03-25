<?php
// src/Controller/AdminController.php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use ReflectionClass;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{


    #[Route('/', name: 'dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        //on récupères les noms des entitées
        $entityClasses = [];
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metaData as $meta) {
            $entityClasses[] = $meta->getName();
        }
        $entities = array_map(function ($className) {
            return (new ReflectionClass($className))->getShortName();
        }, $entityClasses);
        return $this->render(
            'admin/dashboard.html.twig',
            ['entities' => $entities]
        );
    }

    #[Route('/update', name: 'update_field', methods: ['POST'])]
    public function updateField(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entity'], $data['field'], $data['value'], $data['id'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $entityClass = 'App\\Entity\\' . ucfirst($data['entity']);
        $entity = $em->getRepository($entityClass)->find($data['id']);

        if (!$entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        $setter = 'set' . ucfirst($data['field']);
        if (!method_exists($entity, $setter)) {
            return new JsonResponse(['error' => 'Invalid field'], 400);
        }

        $entity->$setter($data['value']);
        $em->persist($entity);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/creer', name: 'create_Entitie', methods: ['POST'])]
    public function createEntitie(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entity'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $entityClass = 'App\\Entity\\' . ucfirst($data['entity']);
        try {
            $entity = new $entityClass();
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Entity class not found or not instantiable'], 400);
        }

        $em->persist($entity);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/entities/{entity}', name: 'list_entities', methods: ['GET'])]
    public function listEntities(string $entity, EntityManagerInterface $em): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);

        if (!class_exists($entityClass)) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        $repository = $em->getRepository($entityClass);
        $objects = $repository->findAll();
        return $this->render('admin/entity_list.html.twig', [
            'objects' => $objects,
            'entity' => $entity
        ]);
    }
    #[Route('/delete', name: 'delete_entity', methods: ['POST'])]
    public function deleteEntity(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entity'], $data['id'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $entityClass = 'App\\Entity\\' . ucfirst($data['entity']);
        $entity = $em->getRepository($entityClass)->find($data['id']);

        if (!$entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        $em->remove($entity);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}

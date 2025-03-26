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
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render(
            'admin/dashboard.html.twig',
            ['entities' => $this->getEntitiesName()]
        );
    }

    #[Route('/update', name: 'update_field', methods: ['POST'])]
    public function updateField(Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entity'], $data['field'], $data['value'], $data['id'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $entityClass = 'App\\Entity\\' . ucfirst($data['entity']);
        $entity = $em->getRepository($entityClass)->find($data['id']);

        if (!$entity) {
            $this->addFlash('error', "L'entité n'existe pas.");
        }

        $setter = 'set' . ucfirst($data['field']);
        if (!method_exists($entity, $setter)) {
            $this->addFlash('error', "Le champ n'existe pas.");
        }

        $entity->$setter($data['value']);
        $em->persist($entity);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/entities/{entity}', name: 'list_entities', methods: ['GET'])]
    public function listEntities(string $entity, EntityManagerInterface $em): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);

        if (!class_exists($entityClass)) {
            $this->addFlash('error', "L'entité n'existe pas.");
        }

        $repository = $em->getRepository($entityClass);
        $objects = $repository->findAll();
        return $this->render('admin/dashboard.html.twig', [
            'objects' => $objects,
            'entity' => $entity,
            'entities' => $this->getEntitiesName()
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
    private function getEntitiesName()
    {
        //on récupères les noms des entitées
        $entityClasses = [];
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($metaData as $meta) {
            $entityClasses[] = $meta->getName();
        }
        return array_map(function ($className) {
            return (new ReflectionClass($className))->getShortName();
        }, $entityClasses);
    }
}

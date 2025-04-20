<?php

namespace App\Controller;

use App\Service\GetRenderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ReflectionClass;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/dashboard', name: 'dashboard_')]
class DashboardController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'index')]
    public function index(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        return $this->render(
            'dashboard/index.html.twig',
            ['entities' => $this->getEntitiesName()]
        );
    }

    #[Route('/update/{entity}/{field}/{id}', name: 'update_field', methods: ['POST'])]
    public function updateField(string $entity, string $field, int $id, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['value'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entity = $em->getRepository($entityClass)->find($id);
        $setter = 'set' . ucfirst($field);
        $metadata = $em->getClassMetadata($entityClass);
        $fieldMapping = $metadata->getFieldMapping($field);
        $value = $data['value'];
        if (isset($fieldMapping['enumType'])) {
            $enumClass = $metadata->getFieldMapping($field)['enumType'];
            $value = constant($enumClass . '::' . $data['value']);
        }
        $entity->$setter($value);
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
        //on récupère le type des attributs
        $objectsType = [];
        $objectsValues = [];
        $objetsAttributs = [];
        $metadata = $em->getClassMetadata($entityClass);
        foreach ($metadata->getFieldNames() as $field) {
            $fieldMapping = $metadata->getFieldMapping($field);
            $reflectionProperty = new \ReflectionProperty($entityClass, $field);
            foreach ($reflectionProperty->getAttributes() as $attribute) {
                $objetsAttributs[$field][explode('\\', $attribute->getName())[count(explode('\\', $attribute->getName())) - 1]] = [
                    'arguments' => $attribute->getArguments(),
                ];
            }
            if (isset($fieldMapping['enumType'])) {
                $enumClass = $fieldMapping['enumType'];

                // Vérifie si la classe existe et est une enum
                if (enum_exists($enumClass)) {
                    // On récupère les valeurs possibles (cas et valeurs)
                    $values = [];

                    foreach ($enumClass::cases() as $case) {
                        $values[$case->name] = $case->value;
                    }

                    $objectsType[$field] = 'enum';
                    $objectsValues[$field] = $values;
                }
            } else {
                $objectsType[$field] = $metadata->getTypeOfField($field);
            }
        }
        //on regarde si on a des relations
        $metadata = $em->getClassMetadata($entityClass);
        $associations = $metadata->getAssociationMappings();
        $parent = null;
        foreach ($associations as $parentNom => $association) {
            if (in_array(\get_class($association), ['Doctrine\ORM\Mapping\ManyToOneAssociationMapping'])) {
                $parent = $parentNom;
            }
        }
        return $this->render('dashboard/index.html.twig', [
            'objects' => $objects,
            'objectsType' => $objectsType,
            'objectsValues' => $objectsValues,
            'objetsAttributs' => $objetsAttributs,
            'entity' => $entity,
            'entities' => $this->getEntitiesName(),
            'associations' => $associations,
            'parent' => $parent
        ]);
    }
    #[Route('/delete/{entity}/{id}', name: 'delete_entity', methods: ['DELETE'])]
    public function deleteEntity(string $entity, string $id,  EntityManagerInterface $em): JsonResponse
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entity = $em->getRepository($entityClass)->find($id);

        if (!$entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        $em->remove($entity);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    //dashboard_create_entity
    #[Route('/create/{entity}', name: 'create_entity', methods: ['POST'])]
    #[Route('/create/{entity}/{entityParentId}', name: 'create_child_entity', methods: ['POST'])]
    public function createEntity(string $entity, string $entityParentId = null, EntityManagerInterface $em, SerializerInterface $serializer): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        if (!class_exists($entityClass)) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }
        $entityN = new $entityClass();
        //on liste les champs qui n'ont pas de valeur par defaut et on les initialise
        $reflection = new ReflectionClass($entityN);
        $a_eviter = ['Doctrine\ORM\Mapping\GeneratedValue'];
        foreach ($reflection->getProperties() as $property) {

            //on modifie pas les champs qui ont GeneratedValue
            foreach ($property->getAttributes() as $attribute) {
                if ($attribute->getName() === 'Doctrine\ORM\Mapping\GeneratedValue') {
                    continue 2;
                }
                //pour le cas des relations
                if ($entityParentId && ($attribute->getName() === 'Doctrine\ORM\Mapping\ManyToMany' || $attribute->getName() === 'Doctrine\ORM\Mapping\ManyToOne' || $attribute->getName() === 'Doctrine\ORM\Mapping\OneToOne')) {
                    //on récupère l'entité parent par son id
                    $entityParent = $em->getRepository($property->getType()->getName())->find($entityParentId);
                    if ($attribute->getName() === 'Doctrine\ORM\Mapping\ManyToMany') {
                        $addMethod = 'add' . ucfirst($property->getName());
                    } else {
                        $addMethod = 'set' . ucfirst($property->getName());
                    }
                    $entityN->$addMethod($entityParent);
                    continue 2;
                }
            }

            if ($property->getDefaultValue() === null) {
                $setter = 'set' . ucfirst($property->getName());
                switch ($property->getType()->getName()) {
                    case 'int':
                        $entityN->$setter(0);
                        break;
                    case 'string':
                        $entityN->$setter('');
                        break;
                    case 'float':
                        $entityN->$setter(0.0);
                        break;
                    case 'bool':
                        $entityN->$setter(false);
                        break;
                    case 'DateTime':
                        $entityN->$setter(new \DateTime());
                        break;
                    case 'array':
                        $entityN->$setter([]);
                        break;
                    default:
                        foreach ($property->getAttributes() as $attribute) {
                            if ($attribute->getName() === 'Doctrine\ORM\Mapping\ManyToMany' || $attribute->getName() === 'Doctrine\ORM\Mapping\OneToMany' || $attribute->getName() === 'Doctrine\ORM\Mapping\OneToOne') {
                                break 2;
                            }
                        }
                        $entityN->$setter(null);
                        break;
                }
            }
        }
        $em->persist($entityN);
        $em->flush();
        return $this->redirectToRoute('dashboard_list_entities', ['entity' => $entity]);
    }
    #[Route('/get/{entity}/{id}/{field}', name: 'get_entity', methods: ['GET'])]
    public function getDatasOfObjet(string $entity, string $id, string $field, GetRenderService $getRender): Response
    {
        return $getRender->render($entity, $id, $field);
    }
    #[Route('/getEnvEditorjs', name: 'get_env', methods: ['GET'])]
    public function getEnvEditorjs(): JsonResponse
    {
        return new JsonResponse(\explode(',', $_ENV['EDITORJS_PLUGINS_INTERDITS'] ?? ''));
    }
    #[Route('/getEnvMode', name: 'get_env_mode', methods: ['GET'])]
    public function getEnvMode(): JsonResponse
    {
        return new JsonResponse(\explode(',', $_ENV['APP_ENV']));
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

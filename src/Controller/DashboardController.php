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
    #[Route('/clone/{entity}/{id}', name: 'clone_entity')]
    public function cloner(string $entity, int $id, EntityManagerInterface $em, Request $request)
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $originalEntity = $em->getRepository($entityClass)->find($id);

        $clonedEntity = clone $originalEntity;
        $reflection = new ReflectionClass($clonedEntity);
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if ($attribute->getName() === 'Doctrine\ORM\Mapping\OneToMany') {
                    $getter = 'get' . ucfirst($property->getName());
                    $setter = 'add' . ucfirst(substr($property->getName(), 0, -1));
                    foreach ($clonedEntity->$getter() as $item) {
                        $clonedEntity->$setter(clone $item);
                    }
                }
            }
        }

        $em->persist($clonedEntity);
        $em->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/update/{entity}/{field}/{id}', name: 'update_field', methods: ['POST', 'GET'])]
    #[Route('/update/{entity}/{field}/{id}/{associationType}', name: 'update_field_association', methods: ['POST', 'GET'])]
    public function updateField(string $entity, string $field, int $id, string $associationType = null, Request $request, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['value'])) {
            return new JsonResponse(['error' => 'Invalid data:' . $data], 400);
        }

        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entityR = $em->getRepository($entityClass)->find($id);
        $setter = 'set' . ucfirst($field);
        $metadata = $em->getClassMetadata($entityClass);


        //on vérifie si on est dans une association avec ce field
        if ($metadata->hasAssociation($field)) {
            if ($associationType == 'ManyToOneAssociationMapping') {
                $entityClass = 'App\\Entity\\' . ucfirst($entity);
                $entityR = $em->getRepository($entityClass)->find($id);
                $classEnfant = $metadata->getAssociationMapping($field)->targetEntity;
                $setMethod = 'set' . ucfirst($field);
                $entityR->$setMethod($data['value'] ? $em->getRepository($classEnfant)->find($data['value']) : null);
            }
        } else {
            $fieldMapping = $metadata->getFieldMapping($field);
            $value = $data['value'];
            if (isset($fieldMapping['enumType'])) {
                $enumClass = $metadata->getFieldMapping($field)['enumType'];
                //is $data['value'] est un tableau
                if (is_array($data['value'])) {
                    $value = array_map(function ($value) use ($enumClass) {
                        return constant($enumClass . '::' . $value);
                    }, $data['value']);
                } else
                    $value = constant($enumClass . '::' . $data['value']);
            }
            //si on demande un datetime on convertis le string en datetime
            if (($metadata->getTypeOfField($field) == 'datetime' || $metadata->getTypeOfField($field) == 'date')) {
                $value == '' ? $value = null : $value = new \DateTime($value);
            }

            $entityR->$setter($value);
        }
        $em->persist($entityR);

        $em->flush();

        return new JsonResponse(['success' => true]);
    }


    #[Route('/entities/{entity}/{parent}/{parentid}', name: 'list_entities_parent', methods: ['GET', 'POST'])]
    #[Route('/entities/{entity}', name: 'list_entities')]
    public function listEntity(string $entity, EntityManagerInterface $em, string $parent = null, string $parentid = null): Response
    {
        $class = 'App\\Entity\\' . ucfirst($entity);
        if (!class_exists($class)) {
            throw $this->createNotFoundException("Entité introuvable");
        }
        $repo = $em->getRepository($class);
        $objects['repo'] = $repo->findAll();

        // On suppose au moins 1 objet pour récupérer la structure
        $sample = $objects['repo'][0] ?? new $class();
        // Récupération des attributs via Doctrine
        $metadata = $em->getClassMetadata($class);
        $fields = $metadata->getFieldNames();
        $assocs = $metadata->getAssociationNames();
        // Préparer la liste des objets pour Twig (par attribut)
        foreach ($fields as $field) {
            $objects['fields'][$field] = [
                'type' => $metadata->getTypeOfField($field),
                'crud' => method_exists($sample, 'cruds') &&  isset($sample->cruds()[$field]) ? $sample->cruds()[$field] : null,
            ];
        }

        // Préparer la liste des objets pour Twig (par association)
        $assocObjets = [];

        foreach ($assocs as $assoc) {
            $metas = $metadata->getAssociationMapping($assoc);
            $typeAssociation = substr(get_class($metas), strlen('Doctrine\ORM\Mapping\\'));
            $assocObjets[$assoc] = [
                'type' => $typeAssociation,
                'metas' => $metas,
                'values' => $em->getRepository($metas->targetEntity)->findAll(),
                'crud' => method_exists($sample, 'cruds') &&  isset($sample->cruds()[$assoc]) ? $sample->cruds()[$assoc] : null,
                'source' => substr($metas->sourceEntity, strlen('App\\Entity\\')),
                'target' => substr($metas->targetEntity, strlen('App\\Entity\\'))
            ];
        }
        //on ajoute les attributs
        foreach ($metadata->getFieldNames() as $field) {
            $fieldMapping = $metadata->getFieldMapping($field);
            $reflectionProperty = new \ReflectionProperty($class, $field);
            $objetsAttributs = [];

            foreach ($reflectionProperty->getAttributes() as $attribute) {
                $attributeName = explode('\\', $attribute->getName())[count(explode('\\', $attribute->getName())) - 1];
                $arguments = $attribute->getArguments();

                // Traitement spécial pour les attributs Regex
                if ($attributeName === 'Regex' && isset($arguments['pattern'])) {
                    $pattern = $arguments['pattern'];

                    // Nettoyer la regex : supprimer les délimiteurs PHP
                    if (preg_match('/^\/(.*)\/[gimuy]*$/i', $pattern, $matches)) {
                        $arguments['pattern'] = $matches[1];
                    }
                }

                $objetsAttributs[$attributeName] = $arguments;
            }

            // Ajouter les attributs
            if (isset($objects['fields'][$field]))
                $objects['fields'][$field]['attributs'] = $objetsAttributs;
            if (isset($assocObjets[$field]))
                $assocObjets[$field]['attributs'] = $objetsAttributs;

            // Pour les attributs enums...
            if (isset($fieldMapping['enumType'])) {
                $enumClass = $fieldMapping['enumType'];
                if (enum_exists($enumClass)) {
                    $values = [];
                    foreach ($enumClass::cases() as $case) {
                        $values[$case->name] = $case->value;
                    }
                    $objects['fields'][$field]['enumValues'] = $values;
                    $objects['fields'][$field]['type'] = 'enum';
                }
            } else {
                $objects['fields'][$field]['typeMapping'] = $metadata->getTypeOfField($field);
            }
        }
        // Récupération de la config CRUD et ajout de certaine propriétés
        $cruds = method_exists($sample, 'cruds') ? $sample->cruds() : [];
        $objects['InfoIdCrud'] = isset($cruds['id']) &&  isset($cruds['id']['InfoIdCrud']) ? $cruds['id']['InfoIdCrud'] : null;
        $objects['ActionsTableauEntite'] = isset($cruds['ActionsTableauEntite']) ? $cruds['ActionsTableauEntite'] : null;
        $objects['Ordre'] = isset($cruds['Ordre']) ? $cruds['Ordre'] : null;
        $objects['Actions'] = isset($cruds['id']) &&  isset($cruds['id']['Actions']) ? $cruds['id']['Actions'] : null;

        return $this->render('/dashboard/index.html.twig', [
            'objets' => $objects,
            'assocs' => $assocObjets,
            'cruds' => $cruds,
            'entity' => $entity,
            'entities' => $this->getEntitiesName(),
            'parentsOfEntity' => null, // donné par request ou par assocs
        ]);
    }





    #[Route('/delete/{entity}/{id}', name: 'delete_entity', methods: ['DELETE'])]
    public function deleteEntity(string $entity, string $id,  EntityManagerInterface $em, Request $request): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entity = $em->getRepository($entityClass)->find($id);

        if (!$entity) {
            $this->addFlash('error', "L'entité $entity n'a pas pu étre trouvée.");
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('dashboard'), 303);
    }
    //dashboard_create_entity
    #[Route('/create/{entity}', name: 'create_entity')]
    #[Route('/create/{entity}/{entityParent}/{entityParentId}', name: 'create_child_entity')]
    public function createEntity(string $entity, string $entityParentId = null, string $entityParent = null, EntityManagerInterface $em): Response
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
                if (($entityParentId && ($attribute->getName() === 'Doctrine\ORM\Mapping\ManyToOne' || $attribute->getName() === 'Doctrine\ORM\Mapping\OneToOne'))) {
                    //on récupère l'entité parent par son id
                    $entityParentR = $em->getRepository($property->getType()->getName())->find($entityParentId);
                    $setMethod = 'set' . ucfirst($property->getName());
                    $entityN->$setMethod($entityParentR);
                    //au cas ou pas de persist cascade mis
                    $inversedBy = lcfirst($attribute->getArguments()['inversedBy']);
                    $addMethod = 'add' . substr($inversedBy, 0, -1);
                    $entityParentR->$addMethod($entityN);
                    $this->em->persist($entityParentR);
                    $this->em->flush();
                    $entityParent = $property->getName();
                    continue 2;
                }
                if ($entityParentId && ($attribute->getName() === 'Doctrine\ORM\Mapping\ManyToMany')) {
                    //on récupère l'entité parent par son id
                    $entityParentEntity = $em->getRepository('App\Entity\\' . $entityParent)->find($entityParentId);
                    $addMethod = 'add' . $entityParent;
                    $entityN->$addMethod($entityParentEntity);
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
        return $this->redirectToRoute('dashboard_list_entities_parent', ['entity' => $entity, 'parentid' => $entityParentId, 'parent' => $entityParent]);
    }
    #[Route('/get/{entity}/{id}/{field}', name: 'get_entity', methods: ['GET'])]
    public function getDatasOfObjet(string $entity, string $id, string $field, GetRenderService $getRender): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entity = $this->em->getRepository($entityClass)->find($id);
        $getter = 'get' . ucfirst($field);
        $json = $entity->$getter();
        return  new Response($getRender->render($json));
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
    //upload file par le controller stimulus uploadFile
    #[Route('/uploadFile', name: 'upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): JsonResponse
    {
        $data = json_decode($request->request->get('data'), true);
        $entityName = $data['entity'];
        $field = $data['field'];
        $id = $data['id'];
        $entityClass = 'App\\Entity\\' . ucfirst($entityName);
        $entity = $this->em->getRepository($entityClass)->find($id);
        if (!$entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }
        $setter = 'set' . ucfirst($field);
        $file = $request->files->get('file');
        $directory = 'uploads' . '/' . $entityName;
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        $filename = $file->getClientOriginalName();
        //on nettoie le nom avec des caractères autorisés et on ajoute la date
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)))
            . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $file->guessExtension();
        $file->move($directory, $filename);
        //on efface si on a un ancien fichier
        $getter = 'get' . ucfirst($field);
        $oldFilename = $entity->$getter();
        if ($oldFilename && file_exists($directory . '/' . $oldFilename)) {
            unlink($directory . '/' . $oldFilename);
        }
        $entity->$setter($filename);
        $this->em->flush();
        return new JsonResponse(['success' => true]);
    }


    #[Route('/reorder', name: '_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['entity'], $data['field'], $data['id'], $data['newOrder'])) {
                return new JsonResponse(['success' => false, 'message' => 'Données manquantes'], 400);
            }

            $entityName = $data['entity'];
            $field = $data['field'];
            $id = (int) $data['id'];
            $newOrder = (int) $data['newOrder'];

            $entityClass = 'App\\Entity\\' . $entityName;
            if (!class_exists($entityClass)) {
                return new JsonResponse(['success' => false, 'message' => 'Entité introuvable'], 404);
            }

            $repository = $this->em->getRepository($entityClass);
            $getter = 'get' . ucfirst($field);
            $setter = 'set' . ucfirst($field);

            $entities = $repository->findBy([], [$field => 'ASC']);
            $targetEntity = $repository->find($id);

            if (!$targetEntity || !method_exists($targetEntity, $getter) || !method_exists($targetEntity, $setter)) {
                return new JsonResponse(['success' => false, 'message' => 'Entité invalide'], 400);
            }

            // Retire l'entité ciblée de la liste
            $entities = array_filter($entities, fn($e) => $e->getId() !== $id);

            // Insère l'entité à sa nouvelle position dans la liste
            array_splice($entities, $newOrder - 1, 0, [$targetEntity]);

            // Réindexe proprement toutes les entités
            foreach (array_values($entities) as $index => $entity) {
                $entity->$setter($index + 1);
            }

            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Ordre mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}

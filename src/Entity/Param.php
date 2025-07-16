<?php

namespace App\Entity;

use App\Repository\ParamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParamRepository::class)]
class Param
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = str_replace(["\n", "\r"], '', strip_tags($nom));

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function cruds()
    {
        return [
        //'Ordre' => ['propriete' => 'ordre'],
        // 'ActionsTableauEntite' => [
             //   'slug' => [
             //       'url' => 'http://google.com', //'http://google.com/{{entity}}/{{ligne.id}}' possible
             //       'target' => '_blank',
             //       'icon' => 'bi bi-globe2',
             //       'texte' => 'Voir le site',
             //       'turbo' => false
             //   ]
            //],
        'id' => [
            //'InfoIdCrud' => [
            //'devis' => ->getLieu(),
            //],
            'Actions' => [] // comme ActionTableauEntite
            ] ,
        'nom' => ['Edition' => true, 'tooltip' => null, 'label' => null],
        'value' => ['Edition' => true, 'tooltip' => null, 'label' => null]
    ];
    }}

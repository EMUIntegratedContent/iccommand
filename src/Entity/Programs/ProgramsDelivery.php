<?php

namespace App\Entity\Programs;

use App\Repository\Programs\ProgramsDeliveryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramsDeliveryRepository::class)]
#[ORM\Table(name: 'programs_delivery', schema: 'programs')]
class ProgramsDelivery
{
    #[ORM\Column]
    private ?int $program_id = null;

    #[ORM\Column]
    private ?int $delivery_id = null;

    //set up joinned relations in Programs entity
    [ORM\ManyToOne(targetEntity: Programs::class)]
    [ORM\JoinColumn(name: 'program_id', referencedColumnName: 'id')]
    private ?Programs $program = null;

    [ORM\ManyToOne(targetEntity: DeliveryMethods::class)]
    [ORM\JoinColumn(name: 'delivery_id', referencedColumnName: 'delivery_id')]
    private ?DeliveryMethods $deliveryMethod = null;

    public function getProgramId(): ?int
    {
        return $this->program_id;
    }

    public function setProgramId(int $program_id): static
    {
        $this->program_id = $program_id;

        return $this;
    }

    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }
}
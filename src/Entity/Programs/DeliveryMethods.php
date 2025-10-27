<?php

namespace App\Entity\Programs;
use App\Repository\Programs\DeliveryMethodsRepository;
use Doctrine\ORM\Mapping as ORM;
// #[ORM\Entity(repositoryClass: DeliveryMethodsRepository::class)]
#[ORM\Table(name: 'delivery_methods', schema: 'programs')]
class DeliveryMethods
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $delivery_id = null;
    #[ORM\Column(length: 100)]
    private ?string $delivery_method = null;
    public function getDeliveryId(): ?int
    {
        return $this->delivery_id;
    }
    public function getDeliveryMethod(): ?string
    {
        return $this->delivery_method;
    }
    public function setDeliveryMethod(string $delivery_method): static
    {
        $this->delivery_method = $delivery_method;
        return $this;
    }
}
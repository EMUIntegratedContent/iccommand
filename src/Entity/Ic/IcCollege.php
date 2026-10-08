<?php
namespace App\Entity\Ic;

use App\Repository\Ic\IcCollegeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * A college, shared across IC Command apps (Programs, Scholarships). Formerly the
 * Programs-only program_colleges table; ids were preserved by the rename.
 */
#[ORM\Entity(repositoryClass: IcCollegeRepository::class)]
#[ORM\Table(name: 'ic_colleges')]
class IcCollege
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(options: ['unsigned' => true])]
    #[Groups("ic")]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups("ic")]
    private ?string $college = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups("ic")]
    private ?string $url = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCollege(): ?string
    {
        return $this->college;
    }

    public function setCollege(string $college): self
    {
        $this->college = $college;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }
}

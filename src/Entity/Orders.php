<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
class Orders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $valid = null;

    #[ORM\Column]
    private ?\DateTime $dateHour = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?CustomerAddress $customerAddress = null;

    /**
     * @var Collection<int, CommandLine>
     */
    #[ORM\OneToMany(targetEntity: CommandLine::class, mappedBy: 'orders', cascade: ['persist', 'remove'])]
    private Collection $commandLines;

    public function __construct()
    {
        $this->commandLines = new ArrayCollection();
        $this->dateHour = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): static
    {
        $this->valid = $valid;

        return $this;
    }

    public function getDateHour(): ?\DateTime
    {
        return $this->dateHour;
    }

    public function setDateHour(\DateTime $dateHour): static
    {
        $this->dateHour = $dateHour;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCustomerAddress(): ?CustomerAddress
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?CustomerAddress $customerAddress): static
    {
        $this->customerAddress = $customerAddress;

        return $this;
    }

    /**
     * @return Collection<int, CommandLine>
     */
    public function getCommandLines(): Collection
    {
        return $this->commandLines;
    }

    public function addCommandLine(CommandLine $commandLine): static
    {
        if (!$this->commandLines->contains($commandLine)) {
            $this->commandLines->add($commandLine);
            $commandLine->setOrders($this);
        }

        return $this;
    }

    public function removeCommandLine(CommandLine $commandLine): static
    {
        if ($this->commandLines->removeElement($commandLine)) {
            // set the owning side to null (unless already changed)
            if ($commandLine->getOrders() === $this) {
                $commandLine->setOrders(null);
            }
        }

        return $this;
    }

    public function __toString() {
        return $this->name;
    }
}

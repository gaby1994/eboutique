<?php

namespace App\Entity;

use App\Repository\UserCartRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: UserCartRepository::class)]
class UserCart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:"integer")]
    private $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable:false)]
    private $user;

    #[ORM\Column(type:"json")]
    private $items = []; // [productId => quantity]

    public function getId(): ?int { 
        return $this->id;     
    }

    public function getUser(): User { 
        return $this->user; 
    }

    public function setUser(User $user): self { 
        $this->user = $user; return $this; 
    }

    public function getItems(): array { 
        return $this->items; 
    }

    public function setItems(array $items): self { 
        $this->items = $items; return $this; 
    }
}

<?php

namespace App\Entity;

use App\Repository\OnlineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OnlineRepository::class)]
class Online
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?int $crc32server = null;

    #[ORM\Column(type: Types::BIGINT)]
    private ?int $time = null;

    #[ORM\Column]
    private ?int $total = null;

    #[ORM\Column]
    private ?int $players = null;

    #[ORM\Column]
    private ?int $bots = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCrc32server(): ?int
    {
        return $this->crc32server;
    }

    public function setCrc32server(int $crc32server): static
    {
        $this->crc32server = $crc32server;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getPlayers(): ?int
    {
        return $this->players;
    }

    public function setPlayers(int $players): static
    {
        $this->players = $players;

        return $this;
    }

    public function getBots(): ?int
    {
        return $this->bots;
    }

    public function setBots(int $bots): static
    {
        $this->bots = $bots;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Clicars\Models;

use Clicars\Interfaces\IMember;

class Member implements IMember
{
    private int $id;
    private int $age;
    private ?IMember $boss;
    private array $subordinates;

    public function __construct(int $id, int $age)
    {
        $this->id = $id;
        $this->age = $age;
        $this->boss = null;
        $this->subordinates = [];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function addSubordinate(IMember $subordinate): IMember
    {
        if (in_array($subordinate, $this->subordinates, true)) {
            return $this;
        }

        $this->subordinates[] = $subordinate;
        $subordinate->setBoss($this);

        return $this;
    }

    public function removeSubordinate(IMember $subordinate): ?IMember
    {
        $subordinatePosition = array_search($subordinate, $this->subordinates, true);

        if ($subordinatePosition === false) {
            return $this;
        }

        unset($this->subordinates[$subordinatePosition]);

        return $this;
    }

    public function getSubordinates(): array
    {
        return $this->subordinates;
    }

    public function getBoss(): ?IMember
    {
        return $this->boss;
    }

    public function setBoss(?IMember $boss): IMember
    {
        $this->boss = $boss;

        if (!is_null($boss)) {
            $boss->addSubordinate($this);
        }

        return $this;
    }
}
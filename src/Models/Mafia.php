<?php

declare(strict_types=1);

namespace Clicars\Models;

use Clicars\Exception\MafiaMemberNotFound;
use Clicars\Interfaces\IMafia;
use Clicars\Interfaces\IMember;

class Mafia implements IMafia
{
    private IMember $godfather;
    private array $members;

    public function __construct(IMember $godfather)
    {
        $this->setGodFather($godfather);
        $this->members = [];
        $this->addMember($godfather);
    }

    public function getGodfather(): IMember
    {
        return $this->godfather;
    }

    public function setGodFather(IMember $godfather): IMember
    {
        $this->godfather = $godfather;

        return $this->godfather;
    }

    public function addMember(IMember $member): ?IMember
    {
        $this->members[$member->getId()] = $member;

        return $member;
    }

    public function getMember(int $id): ?IMember
    {
        return $this->members[$id] ?? null;

    }

    public function sendToPrison(IMember $member): bool
    {
        try {
            $this->removeMember($member);

            $member->getBoss()?->removeSubordinate($member);

            if (empty($member->getSubordinates())) {
                return true;
            }

            $this->relocateSubordinates($member);

            return true;
        } catch (MafiaMemberNotFound) {
            return false;
        }
    }

    public function releaseFromPrison(IMember $member): bool
    {
        $this->addMember($member);

        $member->getBoss()?->addSubordinate($member);

        if (empty($member->getSubordinates())) {
            return true;
        }

        $this->restoreSubordinates($member);

        return true;
    }

    public function findBigBosses(int $minimumSubordinates): array
    {
        return array_filter($this->members, static function ($member) use ($minimumSubordinates) {
            /** @var IMember $member */
            $amountOfMemberSubordinates = count($member->getSubordinates());
            return $amountOfMemberSubordinates >= $minimumSubordinates;
        });
    }

    public function compareMembers(IMember $memberA, IMember $memberB): ?IMember
    {
        $memberARank = $this->getMemberRank($memberA);
        $memberBRank = $this->getMemberRank($memberB);

        if ($memberARank !== $memberBRank) {
            return $memberARank < $memberBRank ? $memberA : $memberB;
        }

        return $this->getOldestMember([$memberA, $memberB]);
    }

    /**
     * @throws MafiaMemberNotFound If member to remove is not found throughout the members
     */
    private function removeMember(IMember $member): void
    {
        if (!$this->getMember($member->getId())) {
            throw MafiaMemberNotFound::create($member);
        }

        unset($this->members[$member->getId()]);
    }

    private function getMemberRank(IMember $member): int
    {
        return count($this->findMemberBosses($member));
    }

    private function findMemberBosses(IMember $member): array
    {
        $bosses = [];
        $boss = $member->getBoss();

        if (!$boss) {
            return $bosses;
        }

        $bosses[] = $boss;

        if ($boss->getBoss()) {
            $bosses = array_merge($bosses, $this->findMemberBosses($boss));
        }

        return $bosses;
    }

    private function relocateSubordinates(IMember $member): void
    {
        $newBoss = $this->getMemberSuccessor($member);

        if ($member->getBoss()) {
            $newBoss->setBoss($member->getBoss());
        } else {
            $this->setGodFather($newBoss);
        }

        foreach ($member->getSubordinates() as $memberSubordinate) {
            if ($this->isPromotedSubordinate($newBoss, $memberSubordinate)) {
                continue;
            }

            $memberSubordinate->setBoss($newBoss);
        }
    }

    private function getMemberSuccessor(IMember $member): IMember
    {
        $sameRankMembers = $member->getBoss()?->getSubordinates() ?: [];

        if (empty($sameRankMembers)) {
            return $this->getOldestMember($member->getSubordinates());
        }

        return $this->getOldestMember($sameRankMembers);
    }

    private function getOldestMember(array $members): IMember
    {
        $firstOldestMember = array_pop($members);

        return array_reduce($members, static function ($oldest, $member) {
            if ($member->getAge() > $oldest->getAge()) {
                $oldest = $member;
            }

            return $oldest;
        }, $firstOldestMember);
    }

    private function isPromotedSubordinate(IMember $promotedSubordinate, IMember $subordinate): bool
    {
        return $promotedSubordinate === $subordinate;
    }

    private function restoreSubordinates(IMember $member): void
    {
        if (!$member->getBoss()) {
            $this->setGodFather($member);
        }

        foreach ($member->getSubordinates() as $memberSubordinate) {
            $memberSubordinate->getBoss()?->removeSubordinate($memberSubordinate);
            $memberSubordinate->setBoss($member);
        }
    }
}
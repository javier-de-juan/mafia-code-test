<?php

declare(strict_types=1);

namespace Clicars\Exception;

use Clicars\Interfaces\IMember;
use Exception;

class MafiaMemberNotFound extends Exception
{
    private const MESSAGE_TEMPLATE = "Member with ID %s was not found in the organization.";

    public static function create(IMember $member): self
    {
        $message = sprintf(self::MESSAGE_TEMPLATE, $member->getId());

        return new self($message);
    }
}
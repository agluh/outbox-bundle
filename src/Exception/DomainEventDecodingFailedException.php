<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Exception;

use InvalidArgumentException;

class DomainEventDecodingFailedException extends InvalidArgumentException
{
}

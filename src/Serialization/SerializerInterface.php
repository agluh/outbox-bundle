<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Serialization;

use AGluh\Bundle\OutboxBundle\Exception\DomainEventDecodingFailedException;

interface SerializerInterface
{
    public function encode(object $domainEvent): string;

    /**
     * @return mixed
     *
     * @throws DomainEventDecodingFailedException
     */
    public function decode(string $data);
}

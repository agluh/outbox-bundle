<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Serialization;

use AGluh\Bundle\OutboxBundle\Exception\DomainEventDecodingFailedException;

class PhpSerializer implements SerializerInterface
{
    /**
     * @param mixed $domainEvent
     */
    public function encode($domainEvent): string
    {
        return addslashes(serialize($domainEvent));
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decode(string $data)
    {
        $signalingException = new DomainEventDecodingFailedException(sprintf('Could not decode message using PHP serialization: %s.', $data));
        $prevUnserializeHandler = ini_set('unserialize_callback_func', self::class.'::handleUnserializeCallback');
        $prevErrorHandler = set_error_handler(static function ($type, $msg, $file, $line, $context = []) use (&$prevErrorHandler, $signalingException) {
            if (__FILE__ === $file) {
                throw $signalingException;
            }

            return $prevErrorHandler ? $prevErrorHandler($type, $msg, $file, $line, $context) : false;
        });

        try {
            $object = unserialize(stripslashes($data));
        } finally {
            restore_error_handler();

            if (false !== $prevUnserializeHandler) {
                ini_set('unserialize_callback_func', $prevUnserializeHandler);
            }
        }

        return $object;
    }

    public static function handleUnserializeCallback(string $class): void
    {
        throw new DomainEventDecodingFailedException(sprintf('Domain event class "%s" not found during decoding.', $class));
    }
}

<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Tests\Unit\Serialization;

use AGluh\Bundle\OutboxBundle\Exception\DomainEventDecodingFailedException;
use AGluh\Bundle\OutboxBundle\Serialization\PhpSerializer;
use PHPUnit\Framework\TestCase;

class PhpSerializerTest extends TestCase
{
    public function test_encoded_is_decodable(): void
    {
        $serializer = new PhpSerializer();

        $event = new \stdClass();

        $encoded = $serializer->encode($event);
        self::assertStringNotContainsString("\0", $encoded, 'Does not contain the binary characters');
        self::assertEquals($event, $serializer->decode($encoded));
    }

    public function test_decoding_fails_with_bad_format(): void
    {
        $this->expectException(DomainEventDecodingFailedException::class);
        $this->expectExceptionMessageMatches('/Could not decode/');

        $serializer = new PhpSerializer();

        $serializer->decode('{"message": "bar"}');
    }

    public function test_decoding_fails_with_bad_class(): void
    {
        $this->expectException(DomainEventDecodingFailedException::class);
        $this->expectExceptionMessageMatches('/class "D0mainEvent" not found/');

        $serializer = new PhpSerializer();

        $serializer->decode('O:11:"D0mainEvent":0:{}');
    }
}

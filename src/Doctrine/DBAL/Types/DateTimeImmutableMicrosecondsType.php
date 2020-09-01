<?php

declare(strict_types=1);

namespace AGluh\Bundle\OutboxBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\VarDateTimeImmutableType;

class DateTimeImmutableMicrosecondsType extends VarDateTimeImmutableType
{
    public const NAME = 'datetime_immutable_microseconds';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param array<mixed> $fieldDeclaration
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        if (isset($fieldDeclaration['version']) && true === $fieldDeclaration['version']) {
            return 'TIMESTAMP';
        }

        return 'DATETIME(6)';
    }

    /**
     * @throws ConversionException
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (\is_object($value) && $value instanceof \DateTimeImmutable &&
            ($platform instanceof PostgreSqlPlatform || $platform instanceof MySQL57Platform)
        ) {
            $dateTimeFormat = $platform->getDateTimeFormatString();

            return $value->format("{$dateTimeFormat}.u");
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

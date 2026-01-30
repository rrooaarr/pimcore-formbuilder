<?php

declare(strict_types=1);

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace FormBuilderBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

/**
 * Custom Doctrine type to replace the removed 'object' type in DBAL 3.x.
 *
 * This type serializes PHP arrays/objects for storage in the database,
 * maintaining backwards compatibility with existing serialized data.
 */
class FormBuilderObjectType extends Type
{
    public const FORM_BUILDER_OBJECT = 'form_builder_object';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return serialize($value);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        set_error_handler(function (int $code, string $message): bool {
            throw ConversionException::conversionFailedUnserialization($this->getName(), $message);
        });

        try {
            $result = unserialize($value, ['allowed_classes' => true]);
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    public function getName(): string
    {
        return self::FORM_BUILDER_OBJECT;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}

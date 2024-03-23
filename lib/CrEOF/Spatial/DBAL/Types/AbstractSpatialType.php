<?php
/**
 * Copyright (C) 2015 Derek J. Lambert
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace CrEOF\Spatial\DBAL\Types;

use CrEOF\Spatial\DBAL\Platform\MySql;
use CrEOF\Spatial\DBAL\Platform\PostgreSql;
use CrEOF\Spatial\Exception\InvalidValueException;
use CrEOF\Spatial\Exception\UnsupportedPlatformException;
use CrEOF\Spatial\DBAL\Platform\PlatformInterface;
use CrEOF\Spatial\PHP\Types\Geography\GeographyInterface;
use CrEOF\Spatial\PHP\Types\Geometry\GeometryInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Abstract Doctrine GEOMETRY type
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
abstract class AbstractSpatialType extends Type
{
    public function getTypeFamily(): string
    {
        return $this instanceof GeographyType ? GeographyInterface::GEOGRAPHY : GeometryInterface::GEOMETRY;
    }

    public function getSQLType(): string
    {
        $class = get_class($this);
        $start = strrpos($class, '\\') + 1;
        $len   = strlen($class) - $start - 4;

        return substr($class, strrpos($class, '\\') + 1, $len);
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }

    /**
     * @throws InvalidValueException
     * @throws UnsupportedPlatformException
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! ($value instanceof GeometryInterface)) {
            throw new InvalidValueException('Geometry column values must implement GeometryInterface');
        }

        return $this->getSpatialPlatform($platform)->convertToDatabaseValue($this, $value);
    }

    public function convertToPHPValueSQL(string $sqlExpr, AbstractPlatform $platform): string
    {
        return $this->getSpatialPlatform($platform)->convertToPHPValueSQL($this, $sqlExpr);
    }

    public function convertToDatabaseValueSQL(string $sqlExpr, AbstractPlatform $platform): string
    {
        return $this->getSpatialPlatform($platform)->convertToDatabaseValueSQL($this, $sqlExpr);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?GeometryInterface
    {
        if (null === $value) {
            return null;
        }

        if (ctype_alpha($value[0])) {
            return $this->getSpatialPlatform($platform)->convertStringToPHPValue($this, $value);
        }

        return $this->getSpatialPlatform($platform)->convertBinaryToPHPValue($this, $value);
    }

    public function getName(): string
    {
        return array_search(get_class($this), self::getTypesMap(), true);
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->getSpatialPlatform($platform)->getSQLDeclaration($column);
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return $this->getSpatialPlatform($platform)->getMappedDatabaseTypes($this);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        // TODO onSchemaColumnDefinition event listener?
        return true;
    }

    private function getSpatialPlatform(AbstractPlatform $platform): PlatformInterface
    {
        return new (match($platform::class) {
            MySQL80Platform::class,
            MySQLPlatform::class => new MySql(),
            PostgreSQLPlatform::class => new PostgreSql(),
            default => throw new UnsupportedPlatformException(sprintf(
                'DBAL platform "%s" is not currently supported.',
                $platform::class,
            )),
        });
    }
}

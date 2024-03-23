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

namespace CrEOF\Spatial\DBAL\Platform;

use CrEOF\Geo\WKT\Parser as StringParser;
use CrEOF\Geo\WKB\Parser as BinaryParser;
use CrEOF\Spatial\DBAL\Types\AbstractSpatialType;
use CrEOF\Spatial\DBAL\Types\GeographyType;
use CrEOF\Spatial\Exception\InvalidValueException;
use CrEOF\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * Abstract spatial platform
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
abstract class AbstractPlatform implements PlatformInterface
{
    public function convertStringToPHPValue(AbstractSpatialType $type, string $sqlExpr): GeometryInterface
    {
        $parser = new StringParser($sqlExpr);

        return $this->newObjectFromValue($type, $parser->parse());
    }

    public function convertBinaryToPHPValue(AbstractSpatialType $type, string $sqlExpr): GeometryInterface
    {
        $parser = new BinaryParser($sqlExpr);

        return $this->newObjectFromValue($type, $parser->parse());
    }

    public function convertToDatabaseValue(AbstractSpatialType $type, GeometryInterface $value): string
    {
        return sprintf('%s(%s)', strtoupper($value->getType()), $value);
    }

    public function getMappedDatabaseTypes(AbstractSpatialType $type): array
    {
        $sqlType = strtolower($type->getSQLType());

        if ($type instanceof GeographyType && $sqlType !== 'geography') {
            $sqlType = sprintf('geography(%s)', $sqlType);
        }

        return array($sqlType);
    }

    /**
     * @throws \CrEOF\Spatial\Exception\InvalidValueException
     */
    private function newObjectFromValue(AbstractSpatialType $type, array $value): GeometryInterface
    {
        $typeFamily = $type->getTypeFamily();
        $typeName   = strtoupper($value['type']);

        $constName = sprintf('CrEOF\Spatial\PHP\Types\Geometry\GeometryInterface::%s', $typeName);

        if (! defined($constName)) {
            // @codeCoverageIgnoreStart
            throw new InvalidValueException(sprintf('Unsupported %s type "%s".', $typeFamily, $typeName));
            // @codeCoverageIgnoreEnd
        }

        $class = sprintf('CrEOF\Spatial\PHP\Types\%s\%s', $typeFamily, constant($constName));

        return new $class($value['value'], $value['srid']);
    }
}

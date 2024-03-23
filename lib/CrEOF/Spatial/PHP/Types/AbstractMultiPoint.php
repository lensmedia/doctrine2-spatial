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

namespace CrEOF\Spatial\PHP\Types;

use CrEOF\Spatial\Exception\InvalidValueException;

/**
 * Abstract MultiPoint object for MULTIPOINT spatial types
 *
 * @author  Derek J. Lambert <dlambert@dereklambert.com>
 * @license http://dlambert.mit-license.org MIT
 */
abstract class AbstractMultiPoint extends AbstractGeometry
{
    /**
     * @var array[] $points
     */
    protected array $points = array();

    /**
     * @param AbstractPoint[]|array[] $points
     * @param int|null $srid
     */
    public function __construct(array $points, ?int $srid = null)
    {
        $this->setPoints($points)
            ->setSrid($srid);
    }

    /**
     * @param array|AbstractPoint $point
     *
     * @return self
     * @throws InvalidValueException
     */
    public function addPoint(AbstractPoint|array $point): static
    {
        $this->points[] = $this->validatePointValue($point);

        return $this;
    }

    /**
     * @return AbstractPoint[]
     */
    public function getPoints(): array
    {
        $points = array();

        for ($i = 0, $iMax = count($this->points); $i < $iMax; $i++) {
            $points[] = $this->getPoint($i);
        }

        return $points;
    }

    /**
     * @param AbstractPoint[]|array[] $points
     *
     * @return self
     */
    public function setPoints(array $points): static
    {
        $this->points = $this->validateMultiPointValue($points);

        return $this;
    }

    /**
     * @param int $index
     *
     * @return AbstractPoint
     */
    public function getPoint(int $index): AbstractPoint
    {
        $point = match ($index) {
            -1 => $this->points[count($this->points) - 1],
            default => $this->points[$index],
        };

        $pointClass = $this->getNamespace() . '\Point';

        return new $pointClass($point[0], $point[1], $this->srid);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return self::MULTIPOINT;
    }

    /**
     * @return array[]
     */
    public function toArray(): array
    {
        return $this->points;
    }
}

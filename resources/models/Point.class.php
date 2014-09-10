<?php

/**
 * @class Point
 * ============
 * 
 * Provides a class for a 2-dimensional point in the Cartesian coordinate system.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Point {
    /**
     * The x-coordinate.
     * 
     * @var double
     */
    public $x;
    
    /**
     * The y-coordinate
     * 
     * @var double
     */
    public $y;
    
    /**
     * Constructs a Point instance with the given x- and y-coordinates.
     * 
     * @param double $x [optional] Defaults to 0
     * @param double $y [optional] Defaults to 0
     */
    function __construct($x = 0, $y = 0) {
        $this->x = (double)$x;
        $this->y = (double)$y;
    }
}
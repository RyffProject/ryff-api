<?php

class Point {
    public $x;
    public $y;
    
    function __construct($x = 0, $y = 0) {
        $this->x = (double)$x;
        $this->y = (double)$y;
    }
}
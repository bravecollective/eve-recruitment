<?php

namespace App\Models;

/**
 * Class that represents a response from /universe/names
 *
 * Class ESINameResponse
 * @package App\Models
 */
class ESINameResponse
{
    public $category;
    public $id;
    public $name;

    public function __construct($category, $id, $name)
    {
        $this->category = $category;
        $this->id = $id;
        $this->name = $name;
    }
}
<?php

namespace App\Service\Generator;

class CodeGenerator
{

    public function generate(): string
    {
        return sprintf("%04d\n", rand(0, 9999));
    }
}
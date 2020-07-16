<?php

declare(strict_types=1);

namespace jkorn\practice\misc;


interface ISaved
{

    public function export(): array;
}
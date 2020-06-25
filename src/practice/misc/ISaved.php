<?php

declare(strict_types=1);

namespace practice\misc;


interface ISaved
{

    public function export(): array;
}
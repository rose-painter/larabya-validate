<?php

declare(strict_types=1);
/*
 * This file is part of the duomai.
 *
 * (c) duomai.com
 *
 * This source file is subject to the MIT license that is bundled.
 */
namespace larabya\validate\Utils;

interface Jsonable
{
    public function toJson(): string;
}

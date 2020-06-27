<?php

namespace Aniart\Main\Cron\Lib\Interfaces;

interface TaskInterface
{
    public function run();

    public function getName(): string;

    /**
     * @return string[]
     */
    public function getArguments(): array;
}
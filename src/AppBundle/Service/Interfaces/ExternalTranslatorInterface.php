<?php

namespace AppBundle\Service\Interfaces;

use Symfony\Component\Console\Logger\ConsoleLogger;

interface ExternalTranslatorInterface
{
    public function translate(string $entity, string $fromLangCode, string $toLangCode, ConsoleLogger $logger);

    public function checkLimits(string $string, string $langCode, ConsoleLogger $logger);
}

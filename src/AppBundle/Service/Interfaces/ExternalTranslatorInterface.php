<?php

namespace AppBundle\Service\Interfaces;

use Symfony\Component\Console\Output\OutputInterface;

interface ExternalTranslatorInterface
{
    public function translate(string $entity, string $fromLangCode, string $toLangCode, OutputInterface $output);

    public function checkLimits(string $string, string $langCode, OutputInterface $output);
}

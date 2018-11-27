<?php

namespace AppBundle\Service;

use AppBundle\Service\Interfaces\ExternalTranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NinjaTranslator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ExternalTranslatorInterface
     */
    private $translator;

    public function __construct(EntityManagerInterface $em, ExternalTranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function translate(string $fromLangCode, string $toLangCode, OutputInterface $output)
    {
        $entities = $this->findAllEntitiesToTranslate();

        foreach ($entities as $entity) {
            $this->translator->translate($entity, $fromLangCode, $toLangCode, $output);
        }
    }

    private function findAllEntitiesToTranslate(): array
    {
        $translationEntities = [];
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();

        foreach ($metadata as $meta) {
            if (false === strpos($meta->getName(), 'Translation')) {
                $translationEntities[] = $meta->getName();
            }
        }

        return $translationEntities;
    }
}

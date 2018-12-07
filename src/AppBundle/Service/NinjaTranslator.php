<?php

namespace AppBundle\Service;

use AppBundle\Service\Interfaces\ExternalTranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;

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

    public function translate(array $entities, string $fromLangCode, string $toLangCode, ConsoleLogger $logger): void
    {
        $logger->info('A ninja is translating your entities...');
        $entities = $this->setEntitiesToTranslate($entities);

        foreach ($entities as $entity) {
            $this->translator->translate($entity, $fromLangCode, $toLangCode, $logger);
        }
    }

    private function setEntitiesToTranslate(array $entities): array
    {
        if (\in_array('all', $entities, true)) {
            $entities = $this->findAllEntitiesToTranslate();
        } else {
            foreach ($entities as $key => $entity) {
                $entity = 'AppBundle\\Entity\\'.$entity;
                $entities[$key] = $entity;
            }
        }

        return $entities;
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

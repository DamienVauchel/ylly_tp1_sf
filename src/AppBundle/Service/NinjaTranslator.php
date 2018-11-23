<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;

class NinjaTranslator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function translate(string $fromLangCode, string $toLangCode)
    {
        $entities = $this->findAllEntitiesToTranslate();

        foreach ($entities as $entity) {
            $this->translateEntity($entity, $fromLangCode, $toLangCode);
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

    private function translateEntity(string $entity, string $fromLangCode, string $toLangCode)
    {
        $reflection = new ReflectionClass($entity.'Translation');
        $objects = $this->em->getRepository($entity)->findAll();

        foreach ($objects as $object) {
            $getters = [];

            foreach ($reflection->getMethods() as $method) {
                $methodName = $method->name;

                if ('get' === substr($methodName, 0, 3) && \is_string($object->translate($fromLangCode)->{$methodName}()) && 'getLocale' !== $methodName && 'getTranslatableEntityClass' !== $methodName) {
                    $getters[$method->name] = $object->translate($fromLangCode)->{$methodName}();
                }
            }

            foreach ($getters as $getter) {
                $this->translateString($getter);
            }
        }
    }

    private function translateString(string $string)
    {
    }
}

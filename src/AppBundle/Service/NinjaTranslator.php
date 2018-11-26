<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Translate\TranslateClient;
use ReflectionClass;

class NinjaTranslator
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslateClient
     */
    private $translator;

    public function __construct(EntityManagerInterface $em, string $googleTranslateKey)
    {
        $this->em = $em;
        $this->translator = new TranslateClient(['key' => $googleTranslateKey]);
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
            if (true === $object->translate($fromLangCode)->getUpdated()) {
                $getters = [];

                foreach ($reflection->getMethods() as $method) {
                    $methodName = $method->name;

                    if ('get' === substr($methodName, 0, 3) && \is_string($object->translate($fromLangCode)->{$methodName}()) && 'getLocale' !== $methodName && 'getTranslatableEntityClass' !== $methodName) {
                        $getters[$method->name] = $object->translate($fromLangCode)->{$methodName}();
                    }
                }

                $setters = [];

                foreach ($getters as $key => $getter) {
                    $key = substr_replace($key, 'set', 0, 3);

                    // Nb charac getter < 2000 par call API
                    // 1 000 000 charac toutes les 100 secondes

                    $setters[$key] = $this->translator->translate($getter, ['target' => $toLangCode]);
                }

                foreach ($setters as $key => $setter) {
                    $object->translate($toLangCode)->{$key}($setter);
                }

                $this->em->persist($object);
                $object->mergeNewTranslations();
                $this->em->flush();
            }
        }
    }
}

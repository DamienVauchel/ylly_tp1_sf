<?php

namespace AppBundle\Service\Bridges;

use AppBundle\Service\Interfaces\ExternalTranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Google\Cloud\Translate\TranslateClient;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Output\OutputInterface;

class GoogleTranslatorBridge implements ExternalTranslatorInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var TranslateClient
     */
    private $translator;

    /**
     * @var int
     */
    private $lengthByCallLimit;

    /**
     * @var int
     */
    private $lengthFor100SecondsLimit;

    /**
     * @var int
     */
    private $characsCounter;

    public function __construct(
        EntityManagerInterface $em,
        string $googleTranslateKey,
        int $googleTranslateLengthByCallLimit,
        int $googleTranslateLengthFor100SecondsLimit
    ) {
        $this->em = $em;
        $this->translator = new TranslateClient(['key' => $googleTranslateKey]);
        $this->lengthByCallLimit = $googleTranslateLengthByCallLimit;
        $this->lengthFor100SecondsLimit = $googleTranslateLengthFor100SecondsLimit;
    }

    /**
     * @throws ReflectionException
     */
    public function translate(string $entity, string $fromLangCode, string $toLangCode, OutputInterface $output): void
    {
        $reflection = new ReflectionClass($entity.'Translation');
        $objects = $this->em->getRepository($entity)->findAll();

        foreach ($objects as $object) {
            if (true === $object->translate($fromLangCode)->getUpdated()) {
                $getters = $this->findAllGetters($reflection, $object, $fromLangCode);
                $setters = $this->setAllSetters($getters, $toLangCode, $output);

                foreach ($setters as $key => $setter) {
                    $object->translate($toLangCode)->{$key}($setter);
                }

                $this->save($object);
            }
        }
    }

    public function checkLimits(string $string, string $langCode, OutputInterface $output): string
    {
        $translatedString = '';

        if (\strlen($string) >= $this->lengthByCallLimit) {
            $strings = explode('.', $string);

            foreach ($strings as $str) {
                if (\strlen($str) >= $this->lengthByCallLimit) {
                    $strings = explode(',', $str);

                    foreach ($strings as $subString) {
                        if (\strlen($subString) > $this->lengthByCallLimit) {
                            $output->writeln('Sorry, your sentence is way too long, Please make it smaller than 2000 characters or add punctuation');
                        } else {
                            $this->checkForCharacsNumberLimit($subString);
                            $translatedString .= $this->translator->translate($subString, ['target' => $langCode]).'.';
                        }
                    }
                } else {
                    $this->checkForCharacsNumberLimit($str);
                    $translatedString .= $this->translator->translate($str, ['target' => $langCode]).'.';
                }
            }
        } else {
            $this->checkForCharacsNumberLimit($string);
            $translatedString = $this->translator->translate($string, ['target' => $langCode]).'.';
        }

        return $translatedString;
    }

    private function checkForCharacsNumberLimit(string $string)
    {
        $this->characsCounter += \strlen($string);

        if ($this->characsCounter >= $this->lengthFor100SecondsLimit) {
            sleep(102);
            $this->characsCounter = \strlen($string);
        }
    }

    private function findAllGetters(ReflectionClass $reflection, $object, string $fromLangCode)
    {
        $getters = [];

        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->name;

            if ('get' === substr($methodName, 0, 3)
                && \is_string($object->translate($fromLangCode)->{$methodName}())
                && 'getLocale' !== $methodName && 'getTranslatableEntityClass' !== $methodName) {
                $getters[$method->name] = $object->translate($fromLangCode)->{$methodName}();
            }
        }

        return $getters;
    }

    private function setAllSetters(array $getters, string $toLangCode, OutputInterface $output)
    {
        $setters = [];

        foreach ($getters as $key => $getter) {
            $key = substr_replace($key, 'set', 0, 3);
            $setters[$key] = $this->checkLimits($getter, $toLangCode, $output);
        }

        return $setters;
    }

    private function save($object)
    {
        $this->em->persist($object);
        $object->mergeNewTranslations();
        $this->em->flush();
    }
}

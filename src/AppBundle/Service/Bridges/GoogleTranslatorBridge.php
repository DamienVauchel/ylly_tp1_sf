<?php

namespace AppBundle\Service\Bridges;

use AppBundle\Service\Interfaces\ExternalTranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Google\Cloud\Translate\TranslateClient;
use ReflectionClass;
use Symfony\Component\Console\Logger\ConsoleLogger;

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
    private $characsNbByCallLimit;

    public function __construct(EntityManagerInterface $em, array $googleTranslate)
    {
        $this->em = $em;
        $this->translator = new TranslateClient(['key' => $googleTranslate['key']]);
        $this->characsNbByCallLimit = $googleTranslate['characs_nb_by_call_limit'];
    }

    public function translate(string $entity, string $fromLangCode, string $toLangCode, ConsoleLogger $logger): void
    {
        $objects = $this->em->getRepository($entity)->findAll();

        foreach ($objects as $object) {
            if (true === $object->translate($fromLangCode)->getUpdated()) {
                $getters = $this->findAllStringGetters($entity, $object, $fromLangCode);
                $setters = $this->setAllSetters($getters, $toLangCode, $logger);

                foreach ($setters as $key => $setter) {
                    $object->translate($toLangCode, false)->{$key}($setter);
                }

                $object->translate($fromLangCode)->setUpdated(false);
                $object->translate($toLangCode, false)->setUpdated(true);
                $object->mergeNewTranslations();
            }
        }

        $this->em->flush();
    }

    public function checkLimits(string $string, string $langCode, ConsoleLogger $logger): string
    {
        $strings = $this->cutString($string, $logger);
        $translatedString = '';

        try {
            foreach ($strings as $string) {
                $translatedString .= $this->htmlDecode($this->translator->translate($string, ['target' => $langCode])['text']);
            }
        } catch (Exception $e) {
            if (400 === $e->getCode()) {
                $logger->info('Sorry, your sentence is way too long, the ninja can\t cut it by himself so please make it smaller than 2000 characters or add "." or "," punctuation');
            }

            if (403 === $e->getCode() && strpos($e->getMessage(), 'User Rate Limit Exceeded')) {
                $logger->info('The ninja had to stop because of the 100 seconds limit! He is camping 100 seconds...');
                sleep(102);
            }

            if (403 === $e->getCode() && strpos($e->getMessage(), 'Daily Limit Exceeded')) {
                $logger->info('The ninja had to stop because of the day limit! He is camping until tomorrow...');
                time_sleep_until(strtotime('tomorrow 00:10'));
            }
        }

        return $translatedString;
    }

    private function cutString(string $string, ConsoleLogger $logger): array
    {
        $result = [];

        if (\strlen($string) >= $this->characsNbByCallLimit) {
            $strings = explode('.', $string);

            foreach ($strings as $str) {
                if (\strlen($str) >= $this->characsNbByCallLimit) {
                    $strings = explode(',', $str);

                    foreach ($strings as $subString) {
                        if (\strlen($subString) > $this->characsNbByCallLimit) {
                            $logger->info('Sorry, your sentence is way too long, the ninja can\t cut it by himself so please make it smaller than 2000 characters or add "." or "," punctuation');
                        } else {
                            $result[] = $subString.',';
                        }
                    }
                } else {
                    $result[] = $str.'.';
                }
            }
        } else {
            $result[] = $string;
        }

        return $result;
    }

    private function findAllStringGetters(string $entity, $object, string $fromLangCode): array
    {
        $reflection = new ReflectionClass($entity.'Translation');
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

    private function setAllSetters(array $getters, string $toLangCode, ConsoleLogger $logger): array
    {
        $setters = [];

        foreach ($getters as $key => $getter) {
            $key = substr_replace($key, 'set', 0, 3);
            $setters[$key] = $this->checkLimits($getter, $toLangCode, $logger);
        }

        return $setters;
    }

    private function htmlDecode(string $string): string
    {
        return htmlspecialchars_decode(html_entity_decode($string, ENT_QUOTES));
    }
}

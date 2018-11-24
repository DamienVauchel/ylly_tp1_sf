<?php

namespace AppBundle\Command;

use AppBundle\Service\NinjaTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NinjaTranslatorCommand extends Command
{
    /**
     * @var NinjaTranslator
     */
    private $ninjaTranslator;

    public function __construct(NinjaTranslator $ninjaTranslator)
    {
        $this->ninjaTranslator = $ninjaTranslator;

        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output
            ->writeln([
                'Welcome to the Ylly\'s NINJA TRANSLATOR',
                '=====================================================',
                'This command can translate your throat and cut your string (or the opposite)',
                '-----------------------------------------------------',
            ]);

        $fromLangCode = $input->getOption('from');
        $toLangCodes = $input->getArgument('to');

        foreach ($toLangCodes as $toLangCode) {
            if ($fromLangCode === $toLangCode) {
                $output
                    ->writeln([
                        'Nothing to translate, language from and language to are the same ('.$fromLangCode.')',
                        '=======================================================',
                        'Bye... NINJA!',
                    ]);

                if (1 === \count($toLangCodes)) {
                    return;
                }
            }

            $this->ninjaTranslator->translate($fromLangCode, $toLangCode);
        }
        $output
            ->writeln([
                'Your strings have been translated by a ninja',
                '=======================================================',
                'Bye... NINJA!',
            ]);
    }

    protected function configure()
    {
        $this
            ->setName('app:ninja-translator')
            ->setDescription('Translate all the website string data to the language in command argument')
            ->setHelp('This command translate your throat and cut your string (or the opposite)')
            ->addArgument('to', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'The language langCode to translate to')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, 'The language langCode to be translated from', 'en')
        ;
    }
}

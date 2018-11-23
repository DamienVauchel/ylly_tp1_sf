<?php

namespace AppBundle\Command;

use AppBundle\Service\NinjaTranslator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
                "Welcome to the Ylly's NINJA TRANSLATOR",
                '=====================================================',
                'This command can translate your throat and cut your string (or the opposite)',
            ]);

        $input->getArgument('fromLangCode') ? $fromLangCode = $input->getArgument('fromLangCode') : $fromLangCode = 'en';
        $toLangCode = $input->getArgument('toLangCode');

        $this->ninjaTranslator->translate($fromLangCode, $toLangCode);
    }

    protected function configure()
    {
        $this
            ->setName('app:ninja-translator')
            ->setDescription('Translate all the website string data to the language in command argument')
            ->setHelp('This command translate your throat and cut your string (or the opposite)')
            ->addArgument('toLangCode', InputArgument::REQUIRED, 'The language langCode to translate to')
            ->addArgument('fromLangCode', InputArgument::OPTIONAL, 'The language langCode to be translated from')
        ;
    }
}

<?php

namespace app\command;

use plugin\admin\app\services\LangServices;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// 脚本翻译
//php webman lang
#[AsCommand('lang', 'lang')]
class Lang extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
//        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("开始翻译\n");
        LangServices::translateAll();
        return self::SUCCESS;
    }

}

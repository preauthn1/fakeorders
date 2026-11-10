<?php

namespace app\command;

use app\common\finance\HSI;
use app\common\payment\WalletPayment;
use app\common\payment\YpUpay;
use app\common\upay\Transactions;
use plugin\admin\app\model\PaymentPlatform;
use plugin\admin\app\services\AdminServices;
use plugin\admin\app\services\LangServices;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('test', 'test')]
class Test extends Command
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
        $output->writeln('Hello test');
        $platform = PaymentPlatform::where('id', 1)->find();
        $WalletPayment = new YpUpay($platform, '');
        $temp = $WalletPayment->get_withdrawal_wallet();
        print_r($temp);
        return self::SUCCESS;
    }

}

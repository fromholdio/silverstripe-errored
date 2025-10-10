<?php

namespace Fromholdio\Errored\Tasks;

use Fromholdio\Errored\Errored;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class ErroredManager extends BuildTask
{
    private static $segment = 'errored-manager';

    protected string $title = 'Errored Manager';

    protected static string $description = 'Manage static error documents';

    private static bool $is_enabled = true;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $doForceWrite = (int) $input->getOption('force') === 1;
        $errored = Injector::inst()->get(Errored::class);
        $errored::writeAllStaticErrors($doForceWrite);
        return Command::SUCCESS;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('force', null, InputOption::VALUE_NONE, 'Force write of all static errors'),
        ];
    }
}

<?php

namespace Fromholdio\Errored\Tasks;

use Fromholdio\Errored\Extensions\Errored;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;

class ErroredManager extends BuildTask
{
    private static $segment = 'errored-manager';

    protected $title = 'Errored Manager';

    protected $description = '';

    protected $enabled = true;

    public function run($request)
    {
        $doForceWrite = (int) $request->getVar('force') === 1;
        $errored = Injector::inst()->get(Errored::class);
        $errored::writeAllStaticErrors($doForceWrite);
    }
}

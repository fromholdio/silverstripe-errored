<?php

namespace Fromholdio\Errored\Extensions;

use Fromholdio\Errored\Errored;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;

class DbBuildExtension extends Extension
{
    public function onAfterBuild(PolyOutput $output, bool $populate = true, bool $testMode = false)
    {
        $errored = Injector::inst()->create(Errored::class);
        $errored::writeAllStaticErrors(true, $output->isQuiet());
    }
}

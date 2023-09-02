<?php

namespace Fromholdio\Errored\Extensions;

use Fromholdio\Errored\Errored;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

class DatabaseAdminExtension extends Extension
{
    public function onAfterBuild($quiet, $populate, $testMode)
    {
        $errored = Injector::inst()->create(Errored::class);
        $errored::writeAllStaticErrors(true, $quiet);
    }
}

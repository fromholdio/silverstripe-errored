<?php

namespace Fromholdio\Errored\Extensions;

use Fromholdio\Errored\Errored;
use SilverStripe\Core\Extension;

class DatabaseAdminExtension extends Extension
{
    public function onAfterBuild($quiet, $populate, $testMode)
    {
        Errored::writeAllStaticErrors(true, $quiet);
    }
}

<?php

namespace Fromholdio\Errored\View;

use Fromholdio\Errored\Errored;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Logging\DebugViewFriendlyErrorFormatter;

class DebugViewFriendlyErroredFormatter extends DebugViewFriendlyErrorFormatter
{
    public function output($statusCode)
    {
        if (Director::is_ajax()) {
            return parent::output($statusCode);
        }
        try {
            $errored = Injector::inst()->create(Errored::class);
            return $errored::getResponseFor($statusCode)->getBody();
        }
        catch (\Exception $e) {
            return parent::output($statusCode);
        }
    }

}

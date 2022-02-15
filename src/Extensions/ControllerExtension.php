<?php

namespace Fromholdio\Errored\Extensions;

use Fromholdio\Errored\Errored;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

class ControllerExtension extends Extension
{
    public function onBeforeHTTPError($statusCode, $request, $errorMessage = null)
    {
        if (Director::is_ajax()) {
            return;
        }
        $errored = Injector::inst()->get(Errored::class);
        $response = $errored::response_for($statusCode, $request, $errorMessage);
        if ($response) {
            throw new HTTPResponse_Exception($response, $statusCode);
        }
    }
}

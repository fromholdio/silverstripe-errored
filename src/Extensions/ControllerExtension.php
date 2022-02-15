<?php

namespace Fromholdio\Errored\Extensions;

use Fromholdio\Errored\Errored;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;

class ControllerExtension extends Extension
{
    public function onBeforeHTTPError($statusCode, $request, $errorMessage = null, $userMessage = null)
    {
        if (Director::is_ajax()) {
            return;
        }
        $errored = Injector::inst()->create(Errored::class);
        $response = $errored::getResponseFor($statusCode, $request, $errorMessage);
        if ($response) {
            throw new HTTPResponse_Exception($response, $statusCode);
        }
    }

    public function friendlyHTTPError($errorCode, $errorMessage = null, $userMessage = null)
    {
        $request = $this->getRequest();

        // Call a handler method such as onBeforeHTTPError404
        $this->getOwner()->extend("onBeforeHTTPError{$errorCode}", $request, $errorMessage, $userMessage);

        // Call a handler method such as onBeforeHTTPError, passing 404 as the first arg
        $this->getOwner()->extend('onBeforeHTTPError', $errorCode, $request, $errorMessage, $userMessage);

        if (empty($userMessage)) {
            $body = $errorMessage;
        }
        elseif (!empty($errorMessage)) {
            $body = $userMessage . ' (' . $errorMessage . ')';
        }
        else {
            $body = $userMessage;
        }

        // Throw a new exception
        throw new HTTPResponse_Exception($body, $errorCode);
    }
}

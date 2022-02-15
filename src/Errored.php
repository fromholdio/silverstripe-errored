<?php

namespace Fromholdio\Errored\Extensions;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Storage\GeneratedAssetHandler;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\NullHTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\i18n;
use SilverStripe\ORM\DB;
use SilverStripe\Security\InheritedPermissions;
use SilverStripe\Security\Member;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

class Errored extends Controller
{
    protected int $statusCode;
    protected ?string $errorMessage;
    protected ?string $userMessage;

    protected GeneratedAssetHandler $assetHandler;

    private static $is_static_file_enabled = true;

    private static $dev_append_error_message = false;

    private static $page_class = \Page::class;
    private static $themes;

    public static function getResponseFor(
        $statusCode,
        $errorMessage = null,
        ?HTTPRequest $request = null
    ): ?HTTPResponse
    {
        $controller = static::create($statusCode, $errorMessage);
        try {
            $body = $controller->getResponseBody();
        }
        catch (\Throwable $throwable) {
            $body = $controller->getStaticResponseBody();
        }
        if (!empty($body)) {
            /** @var HTTPResponse $response */
            $response = Injector::inst()->create(HTTPResponse::class);
            $response->setStatusCode($statusCode);
            $response->setBody($body);
            return $response;
        }
        return null;
    }

    public static function getCodes()
    {
        return [
            400 => _t(self::class . '.CODE_400', '400 - Bad Request'),
            401 => _t(self::class . '.CODE_401', '401 - Unauthorized'),
            402 => _t(self::class . '.CODE_402', '402 - Payment Required'),
            403 => _t(self::class . '.CODE_403', '403 - Forbidden'),
            404 => _t(self::class . '.CODE_404', '404 - Not Found'),
            405 => _t(self::class . '.CODE_405', '405 - Method Not Allowed'),
            406 => _t(self::class . '.CODE_406', '406 - Not Acceptable'),
            407 => _t(self::class . '.CODE_407', '407 - Proxy Authentication Required'),
            408 => _t(self::class . '.CODE_408', '408 - Request Timeout'),
            409 => _t(self::class . '.CODE_409', '409 - Conflict'),
            410 => _t(self::class . '.CODE_410', '410 - Gone'),
            411 => _t(self::class . '.CODE_411', '411 - Length Required'),
            412 => _t(self::class . '.CODE_412', '412 - Precondition Failed'),
            413 => _t(self::class . '.CODE_413', '413 - Request Entity Too Large'),
            414 => _t(self::class . '.CODE_414', '414 - Request-URI Too Long'),
            415 => _t(self::class . '.CODE_415', '415 - Unsupported Media Type'),
            416 => _t(self::class . '.CODE_416', '416 - Request Range Not Satisfiable'),
            417 => _t(self::class . '.CODE_417', '417 - Expectation Failed'),
            422 => _t(self::class . '.CODE_422', '422 - Unprocessable Entity'),
            429 => _t(self::class . '.CODE_429', '429 - Too Many Requests'),
            451 => _t(self::class . '.CODE_451', '451 - Unavailable For Legal Reasons'),
            500 => _t(self::class . '.CODE_500', '500 - Internal Server Error'),
            501 => _t(self::class . '.CODE_501', '501 - Not Implemented'),
            502 => _t(self::class . '.CODE_502', '502 - Bad Gateway'),
            503 => _t(self::class . '.CODE_503', '503 - Service Unavailable'),
            504 => _t(self::class . '.CODE_504', '504 - Gateway Timeout'),
            505 => _t(self::class . '.CODE_505', '505 - HTTP Version Not Supported'),
        ];
    }


    public function __construct(
        int $statusCode,
        ?HTTPRequest $request = null,
        ?string $errorMessage = null,
        ?string $userMessage = null
    ){
        $this->statusCode = $statusCode;
        $this->errorMessage = $errorMessage;
        $this->userMessage = $userMessage;
        parent::__construct();
    }


    /**
     * Instance values
     * ----------------------------------------------------
     */

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getXStatusCode(): string
    {
        $code = (string) $this->getStatusCode();
        $codeGroup = $code[0] ?? '0';
        return $codeGroup . 'xx';
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }


    /**
     * Static file generation
     * ----------------------------------------------------
     */

    public static function writeAllStaticErrors(bool $forceWrite = false): void
    {
        $codes = static::getCodes();
        foreach ($codes as $code => $title)
        {
            $errored = static::create($code);
            $staticExists = $errored->hasStaticFile();
            if ($forceWrite || !$staticExists)
            {
                $action = $staticExists ? 'refreshed' : 'created';
                $write = $errored->writeStaticFile();
                if ($write) {
                    DB::alteration_message(
                        sprintf('%s error document %s', $code, $action),
                        'created'
                    );
                }
                else {
                    DB::alteration_message(
                        sprintf(
                            '%s error document could not be %s. Please check permissions',
                            $code, $action
                        ),
                        'error'
                    );
                }
            }
        }
    }

    public function writeStaticFile(): bool
    {
        $ogThemes = SSViewer::get_themes();
        Requirements::clear();
        Requirements::clear_combined_files();
        try {
            $themes = $this->getThemes() ?? $ogThemes;
            SSViewer::set_themes($themes);
            $response = Member::actAs(null, function () {
                return Director::mockRequest(
                    function (HTTPRequest $request) {
                        $request->setScheme('https');
                        return Director::singleton()->handleRequest($request);
                    },
                    '/'
                );
            });
            $content = $response->getBody();
        }
        finally {
            SSViewer::set_themes($ogThemes);
            Requirements::clear();
            Requirements::clear_combined_files();
        }

        if (empty($content)) {
            return false;
        }

        $storeFileName = $this->getStoreStaticFileName();
        $this->getAssetHandler()->setContent($storeFileName, $content);
        return true;
    }

    public function hasStaticFile(): bool
    {
        return !is_null($this->getStaticResponseBody());
    }

    protected function getStaticFileName(): string
    {
        $name = 'error-' . $this->getStatusCode() . '.html';
        $this->extend('updateStaticFileName', $name);
        return $name;
    }

    protected function getStaticFilePath(): string
    {
        $path = static::config()->get('static_file_path');
        $this->extend('updateStaticFilePath', $path);
        return $path;
    }

    protected function getStoreStaticFileName(): string
    {
        return File::join_paths(
            $this->getStaticFilePath(),
            $this->getStaticFileName()
        );
    }


    /**
     * Response
     * ----------------------------------------------------
     */

    public function getResponseBody(?HTTPRequest $request = null): string
    {
        $controller = $this->getResponsePageController($request);
        $templates = $this->getTemplates();
        $themes = $this->getThemes();

        if (!empty($themes)) {
            SSViewer::set_themes($themes);
        }

        $body = is_null($controller)
            ? $this->renderWith($templates)
            : $controller->renderWith($templates);

        return $body->forTemplate();
    }

    public function getStaticResponseBody(): ?string
    {
        $storeFileName = $this->getStoreStaticFileName();
        $body = $this->getAssetHandler()->getContent($storeFileName);
        return empty($body) ? null : $body;
    }


    /**
     * Response content
     * ----------------------------------------------------
     */

    protected function getTitles()
    {
        return static::getCodes();
    }

    public function getTitle(): ?string
    {
        $title = $this->getTitles()[$this->getStatusCode()] ?? null;
        $this->extend('updateTitle', $title);
        return $title;
    }

    public function getContent(): ?string
    {
        $templates = $this->getTemplates('_Content');
        $content = $this->renderWith($templates)->forTemplate();
        $showDevMessage = (bool) static::config()->get('dev_append_error_message');
        $message = $this->getErrorMessage();
        if (!empty($message) && Director::isDev() && $showDevMessage === true) {
            $content .= "\n<p><b>Error detail: "
                . Convert::raw2xml($message) ."</b></p>";
        }
        return $content;
    }

    public function ContentLocale(): string
    {
        $locale = i18n::get_locale();
        return i18n::convert_rfc1766($locale);
    }


    /**
     * Response page
     * ----------------------------------------------------
     */

    public function getResponsePageController(?HTTPRequest $request = null): ?ContentController
    {
        $controller = null;
        $page = $this->getResponsePage();
        if (!is_null($page))
        {
            $controller = ModelAsController::controller_for($page);
            if (is_null($request)) {
                $request = new NullHTTPRequest();
                $request->setSession(new Session([]));
            }
            $controller->setRequest($request);
            $controller->doInit();
        }
        $this->extend('updateResponsePageController', $controller, $request);
        return $controller;
    }

    public function getResponsePage(): ?SiteTree
    {
        $class = $this->getResponsePageClass();
        if (empty($class)) {
            return null;
        }
        $page = Injector::inst()->create($class);
        $page->URLSegment = 'error';
        $page->ID = '-' . $this->getStatusCode();
        $page->Title = $this->getTitle();
        $page->Content = $this->getContent();
        $page->CanViewType = InheritedPermissions::ANYONE;
        $this->extend('updateResponsePage', $page);
        return $page;
    }

    public function getResponsePageClass(): ?string
    {
        $class = static::config()->get('page_class');
        if (is_array($class))
        {
            $classes = $class;
            $class = false;
            $code = $this->getStatusCode();
            $xCode = $this->getXStatusCode();
            if (isset($classes[$code])) {
                $class = $classes['e'.$code];
            }
            elseif (isset($classes[$xCode])) {
                $class = $classes['e'.$xCode];
            }
            if ($class === false || $class === 'default') {
                $class = $classes['default'] ?? null;
            }
        }
        $class = !empty($class) && is_a($class, SiteTree::class, true)
            ? $class
            : null;
        $this->extend('updateResponsePageClass', $class);
        return $class;
    }


    /**
     * Response templates/themes
     * ----------------------------------------------------
     */

    protected function getTemplates(string $suffix = ''): array
    {
        $statusCode = $this->getStatusCode();
        $statusCodeSuffix = '_' . $statusCode . $suffix;
        $templates[] = SSViewer::get_templates_by_class(
            static::class, $statusCodeSuffix, __CLASS__
        );

        $xStatusCode = $this->getXStatusCode();
        $xStatusCodeSuffix = '_' . $xStatusCode . $suffix;
        $templates[] = SSViewer::get_templates_by_class(
            static::class, $xStatusCodeSuffix, __CLASS__
        );

        $templates[] = SSViewer::get_templates_by_class(
            static::class, $suffix, __CLASS__
        );

        $templates[] = [
            'Error' . $statusCodeSuffix,
            'Error' . $xStatusCodeSuffix,
            'Error'
        ];

        $page = $this->getResponsePage();
        if (!is_null($page)) {
            $templates[] = SSViewer::get_templates_by_class(
                $page::class, '', SiteTree::class
            );
        }

        $templates[] = ['BlankPage'];
        return array_merge(...$templates);
    }

    protected function getThemes(): ?array
    {
        $themes = static::config()->get('themes');
        $this->extend('updateThemes', $themes);
        if (empty($themes)) {
            $themes = SSViewer::config()->get('themes');
        }
        return $themes;
    }



    /**
     * Asset handler
     * ----------------------------------------------------
     */

    public function getAssetHandler(): GeneratedAssetHandler
    {
        return $this->assetHandler;
    }

    public function setAssetHandler(GeneratedAssetHandler $handler)
    {
        $this->assetHandler = $handler;
    }



    /**
     * Minimal implementation:
     *  > ErrorObject created on-fly, statusCode+errMessage set
     *  > ErrorObject->getTitle() + ErrorObject->getContent()
     *  > ErrorController::page_class = Page
     *  - Renders with Title, Content using Page/PageController and active Theme
     *
     * Use ErrorObjects to allow CMS-overridden content
     *
     * Extend content needs to include image, for example
     *
     * Use ErrorController as response controller
     *
     * Use ErrorController + custom theme stack
     */
}

# silverstripe-errored

Flexible error page generation for Silverstripe 6.x

(For Silverstripe 4 & 5+ see 1.x.)

- Set specific theme stack for error pages, for example use the silverstripe/login-forms security theme for error pages rather than the site global theme
- Create templates for content of error pages, on a per error code basis, or per 4xx/5xx/etc basis
- Writes all pages as static files to public webroot during dev/build
- Displays actual error message from code thrown when environment is in dev mode

## Configured multisites

This module does not require `fromholdio/silverstripe-configured-multisites`, but
projects using that module can subclass `Errored` to write static error pages per
site.

See `docs/examples/ConfiguredMultisitesErrored.php.example` for a starting point.
Copy it into your project and configure the injector:

```yml
SilverStripe\Core\Injector\Injector:
  Fromholdio\Errored\Errored:
    class: Fromholdio\Errored\ConfiguredMultisitesErrored

Fromholdio\Errored\ConfiguredMultisitesErrored:
  site_themes:
    default:
      - '$public'
      - '$default'
```

TODO:
* cms editable content per error code
* full docs

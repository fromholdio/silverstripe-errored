# silverstripe-errored

Flexible error page generation for Silverstripe 4 & 5+.

- Set specific theme stack for error pages, for example use the silverstripe/login-forms security theme for error pages rather than the site global theme
- Create templates for content of error pages, on a per error code basis, or per 4xx/5xx/etc basis
- Writes all pages as static files to public webroot during dev/build
- Displays actual error message from code thrown when environment is in dev mode

TODO:
* cms editable content per error code
* multisites
* full docs

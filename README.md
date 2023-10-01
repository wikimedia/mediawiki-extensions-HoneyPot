# HoneyPot

The HoneyPot extension adds a basic ("honey pot")[https://en.wikipedia.org/wiki/Honeypot_(computing)]
to the account creation interface. It inserts a text field that is hidden from
users via CSS but not of the `hidden` input type, and prevents account creation
if a value is provided to that field. This is intended to help prevent spambots
or other automated creation attempts.

## Installation

To install the HoneyPot extension, download its code from the source repository
and add it to the `extensions/` folder of your Mediawiki installation. To
enable the extension, add the following code to your 
[LocalSettings.php](https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:LocalSettings.php)
file.

```php
wfLoadExtension( 'HoneyPot' );
```

## Configuration

The HoneyPot extension adds a configuration option, `$wgHoneyPotMisleadingError`,
that controls the error shown on `Special:CreateAccount` when the honey pot is
triggered.

If set to `true`, a misleading error is shown, specifically the same error that
is shown when password and password confirmation fields don't match, i.e. the
`badretype` system message.

If set to `false`, an explanation is shown of what actually happened, indicating
that the field should not have been filled in, with the `honeypot-triggered-error`
system message. This message can be customized on-wiki by editing the page 
`MediaWiki:Honeypot-triggered-error`.

By default `$wgHoneyPotMisleadingError` is set to `false`.

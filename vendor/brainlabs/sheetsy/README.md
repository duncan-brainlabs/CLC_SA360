# Sheetsy - a wrapper for Google Sheets API

[![Build Status](https://jenkins.brainlabsdigital.com/buildStatus/icon?job=Brainlabs-Digital/sheetsy/master)](https://jenkins.brainlabsdigital.com/job/Brainlabs-Digital/job/sheetsy/job/master/)

## Installation

Install composer. You can find instructions in <https://getcomposer.org/>

In your project, add the following to your composer.json

```json
{

  "minimum-stability": "dev",
	"prefer-stable": true,

  "repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/Brainlabs-Digital/sheetsy"
		}
	],

}
```

Finally, run `composer require brainlabs/sheetsy`.

If you get a 404 error during installation, you probably need to get an access
token from github and tell composer to use it. Log in to github.com and go to
github.com/settings/tokens to generate an access token. Run

```
composer config -g github-oauth.github.com <oauthtoken>
```

## Credentials

See the instructions in https://github.com/Brainlabs-Digital/credulous for how
to set up your Google API credentials.

Information about scopes can be found here
https://developers.google.com/sheets/guides/authorizing


## Examples
Have a look in `tests`.

## Testing

Unit tests that don't depend on the service can be run with
```
make test
```

To do a smoke test with code that depends on the service, first create a
spreadsheet on Google Sheets. Set your environment variable
`SPREADSHEET_ID` to the spreadsheet like this

```bash
export SPREADSHEET_ID=1Hq_EYu0745OGsoQ2lNw-bilEWGe1qsYSITc7AkPAPCw
```

Now run
```
vendor/bin/phpunit
```

## Creating Spreadsheets

Sheetsy cannot create new spreadsheets, but [Driven->makeSpreadsheet](https://github.com/Brainlabs-Digital/driven) can.

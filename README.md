# KnownPasswords

KnownPasswords for Laravel 5

Requires:

- libsodium-php
- a knownpasswords.org registration

## Installation

Add `bitbeans/knownpasswords` to `composer.json`.
```
"bitbeans/knownpasswords": "dev-master"
```

Run `composer update` to pull down the latest version of KnownPasswords.

Now open up `PROJECTFOLDER/config/app.php` and add the service provider to your `providers` array.
```php
'providers' => array(
	'Bitbeans\KnownPasswords\KnownPasswordsServiceProvider',
)
```

And also the alias.
```php
'aliases' => array(
	'KnownPasswords' => 'Bitbeans\KnownPasswords\KnownPasswordsFacade',
)
```


## Configuration

Run `php artisan vendor:publish` and modify the config file (PROJECTFOLDER/config/knownpasswords.php) with your own information.


## Example

```php

	<?php namespace App\Services\Validation;

	use KnownPasswords;

	class MyValidation {

		public function validateKnownPassword($attribute, $value, $parameters) {
			try {
				return KnownPasswords::checkPassword($value);
			}
			catch (\Exception $e)
			{
				return false;
			}
			return false;
		}
	}
```

## Note
KnownPasswords can validate the following password formats:

- Blake2b (64 byte hash)
- Sha512 (64 byte hash)
- Cleartext password

Never store passwords in these formats, always us a KDF (key derivation function)!
<?php
 /*
 * This file is part of the Laravel 5 KnownPassword package.
 *
 * (c) 2015 Christian Hermann
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *
 * @author      Christian Hermann
 * @package     KnownPasswords
 * @copyright   (c) 2015 Chistian Hermann <c.hermann@bitbeans.de>
 * @link        https://github.com/bitbeans/KnownPasswords
 * @link        https://knownpasswords.org
 */

namespace Bitbeans\KnownPasswords;

use Illuminate\Support\ServiceProvider;

class KnownPasswordsServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/knownpasswords.php' => config_path('knownpasswords.php'),

		]);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		 $this->app['knownpasswords'] = $this->app->share(function($app)
		{
			return new KnownPasswords;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('knownpasswords');
	}
}

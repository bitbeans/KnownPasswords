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
 * @author    	Christian Hermann
 * @package     KnownPasswords
 * @copyright   (c) 2015 Chistian Hermann <c.hermann@bitbeans.de>
 * @link        https://github.com/bitbeans/KnownPasswords
 * @link        https://knownpasswords.org
 */

namespace Bitbeans\KnownPasswords;

use Illuminate\Support\Facades\Facade;

class KnownPasswordsFacade extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'knownpasswords'; }
}
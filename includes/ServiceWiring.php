<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

// PHPUnit doesn't understand code coverage for code outside of classes/functions,
// like service wiring files. This *is* tested though, see
// tests/phpunit/integration/ServiceWiringTest.php
// @codeCoverageIgnoreStart

/*
 * CheckUser provides a service for this, but
 * we define our own nullable here to make CheckUser a soft dependency
 */
return [
	'NukeIPLookup' => static function (
		MediaWikiServices $services
	) {
		// Allow IP lookups if temp user is known and CheckUser is present
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'CheckUser' ) ) {
			return null;
		}
		$tempUserIsKnown = $services->getTempUserConfig()->isKnown();
		if ( !$tempUserIsKnown ) {
			return null;
		}
		return $services->get( 'CheckUserTemporaryAccountsByIPLookup' );
	}
];

// @codeCoverageIgnoreEnd

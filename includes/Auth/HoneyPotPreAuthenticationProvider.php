<?php

namespace MediaWiki\Extension\HoneyPot\Auth;

use Config;
use ConfigException;
use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use StatusValue;

/**
 * @license GPL-2.0-or-later
 */
class HoneyPotPreAuthenticationProvider extends AbstractPreAuthenticationProvider {
	private bool $misleadingErrors;

	public function __construct( Config $config ) {
		if ( !$config->has( 'HoneyPotMisleadingError' ) ) {
			throw new ConfigException(
				'Missing value for $wgHoneyPotMisleadingError'
			);
		}
		$misleadingErrors = $config->get( 'HoneyPotMisleadingError' );
		// Use a specific error message instead of breaking on the typehint
		if ( !is_bool( $misleadingErrors ) ) {
			$misleadingErrorsType = (
				is_object( $misleadingErrors )
					? get_class( $misleadingErrors )
					: gettype( $misleadingErrors )
			);
			throw new ConfigException(
				'$wgHoneyPotMisleadingError should be a boolean, got ' .
					$misleadingErrorsType
			);
		}
		$this->misleadingErrors = $misleadingErrors;
	}

	/**
	 * @inheritDoc
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		$req = new HoneyPotAuthenticationRequest();
		return [ $req ];
	}

	/**
	 * @inheritDoc
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		$req = AuthenticationRequest::getRequestByClass(
			$reqs,
			HoneyPotAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return StatusValue::newGood();
		}
		if ( !$req->wasTriggered() ) {
			return StatusValue::newGood();
		}
		if ( $this->misleadingErrors ) {
			return StatusValue::newFatal( 'badretype' );
		}
		return StatusValue::newFatal( 'honeypot-triggered-error' );
	}

}

<?php

namespace MediaWiki\Extension\HoneyPot\Auth;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;

/**
 * Honey pot authentication request that adds a field that should be left blank.
 */
class HoneyPotAuthenticationRequest extends AuthenticationRequest {
	/** @var string Value of the honeypot field */
	public $trigger = '';

	/**
	 * @inheritDoc
	 */
	public function loadFromSubmission( array $data ) {
		parent::loadFromSubmission( $data );
		// Always return true to simplify debugging, the check for this is
		// very quick
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getFieldInfo() {
		// Only applicable to account creation
		if ( $this->action !== AuthManager::ACTION_CREATE ) {
			return [];
		}
		return [
			'trigger' => [
				// NOT using `hidden` type because we want to make bots think
				// that the field is shown, the output gets overridden with a
				// UndisplayedTextField in the hooks
				'type' => 'string',
				'value' => $this->trigger,
				// Only shown if the UndisplayedTextField handling doesn't work
				'label' => wfMessage( 'honeypot-field-label' ),
				// Not required (duh)
				'optional' => true,
			],
		];
	}

	public function wasTriggered(): bool {
		return $this->trigger !== '';
	}

}

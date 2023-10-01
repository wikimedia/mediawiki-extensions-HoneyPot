<?php

namespace MediaWiki\Extension\HoneyPot;

use HTMLTextField;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;
use Psr\Log\LoggerInterface;

class Hooks implements AuthChangeFormFieldsHook {

	private LoggerInterface $logger;

	public function __construct() {
		$this->logger = LoggerFactory::getInstance( 'HoneyPot' );
	}

	/**
	 * @param AuthenticationRequest[] $requests
	 * @param array $fieldInfo
	 * @param array &$formDescriptor
	 * @param string $action
	 */
	public function onAuthChangeFormFields(
		$requests,
		$fieldInfo,
		&$formDescriptor,
		$action
	) {
		$req = AuthenticationRequest::getRequestByClass(
			$requests,
			HoneyPotAuthenticationRequest::class,
			true
		);
		if ( !$req ) {
			return;
		}
		if ( isset( $formDescriptor['trigger']['class'] ) &&
			$formDescriptor['trigger']['class'] !== HTMLTextField::class
		) {
			$this->logger->debug(
				'Not overridding trigger class, already set to {class}',
				[
					'class' => $formDescriptor['trigger']['class']
				]
			);
			return;
		}
		$formDescriptor['trigger']['class'] = UndisplayedTextField::class;
	}

}

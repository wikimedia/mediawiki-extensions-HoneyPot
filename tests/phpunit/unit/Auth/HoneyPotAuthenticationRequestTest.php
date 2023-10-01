<?php

namespace MediaWiki\Extension\HoneyPot\Test\Unit\Auth;

use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest
 * @group extension-HoneyPot
 */
class HoneyPotAuthenticationRequestTest extends MediaWikiUnitTestCase {

	public static function provideLoadFromSubmission() {
		yield 'field set' => [ true, 'field value' ];
		yield 'field not set' => [ false, '' ];
	}

	/** @dataProvider provideLoadFromSubmission */
	public function testLoadFromSubmission( bool $fieldSet, string $value ) {
		$req = new HoneyPotAuthenticationRequest();
		$data = [];
		if ( $fieldSet ) {
			$data['trigger'] = $value;
		}
		// Action needs to be set correctly for the field to actually be loaded
		$req->action = AuthManager::ACTION_CREATE;
		$this->assertTrue(
			$req->loadFromSubmission( $data ),
			'Always returns true'
		);
		$this->assertSame(
			$value,
			$req->trigger,
			'Trigger field is set based on data'
		);
	}

	public function testFieldInfo() {
		$req = new HoneyPotAuthenticationRequest();
		$this->assertSame(
			[],
			$req->getFieldInfo(),
			'By default has not fields'
		);
		$req->action = AuthManager::ACTION_CREATE;
		$fields = $req->getFieldInfo();
		$this->assertArrayHasKey( 'trigger', $fields, 'Field added' );
	}

	public function testWasTriggered() {
		$req = new HoneyPotAuthenticationRequest();
		$this->assertFalse(
			$req->wasTriggered(),
			'Starts not triggered'
		);
		// Action needs to be set correctly for the field to actually be loaded
		$req->action = AuthManager::ACTION_CREATE;
		$req->loadFromSubmission( [ 'trigger' => 'foo' ] );
		$this->assertTrue(
			$req->wasTriggered(),
			'Trigged based on submission value'
		);
	}

}

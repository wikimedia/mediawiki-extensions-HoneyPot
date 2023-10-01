<?php

namespace MediaWiki\Extension\HoneyPot\Test\Unit\Auth;

use ConfigException;
use HashConfig;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotPreAuthenticationProvider;
use MediaWikiUnitTestCase;
use User;

/**
 * @covers \MediaWiki\Extension\HoneyPot\Auth\HoneyPotPreAuthenticationProvider
 * @group extension-HoneyPot
 */
class HoneyPotPreAuthenticationProviderTest extends MediaWikiUnitTestCase {

	public static function provideInvalidConfig() {
		yield 'missing' => [ [], 'Missing value for $wgHoneyPotMisleadingError' ];
		yield 'non-bool non-object' => [
			[ 'HoneyPotMisleadingError' => 1 ],
			'$wgHoneyPotMisleadingError should be a boolean, got integer'
		];
		yield 'non-bool object' => [
			[ 'HoneyPotMisleadingError' => (object)[ 'k' => true ] ],
			'$wgHoneyPotMisleadingError should be a boolean, got stdClass'
		];
	}

	/** @dataProvider provideInvalidConfig */
	public function testInvalidConfig( array $cfg, string $errorMsg ) {
		$this->expectException( ConfigException::class );
		$this->expectExceptionMessage( $errorMsg );
		$provider = new HoneyPotPreAuthenticationProvider(
			new HashConfig( $cfg )
		);
	}

	public static function provideValidConfig() {
		yield 'not misleading' => [ [ 'HoneyPotMisleadingError' => false ] ];
		yield 'misleading' => [ [ 'HoneyPotMisleadingError' => true ] ];
	}

	/** @dataProvider provideValidConfig */
	public function testGetAuthenticationRequests( array $cfg ) {
		$provider = new HoneyPotPreAuthenticationProvider(
			new HashConfig( $cfg )
		);
		$requests = $provider->getAuthenticationRequests(
			AuthManager::ACTION_LOGIN, // gets ignored
			[]
		);
		$this->assertIsArray( $requests );
		$this->assertCount( 1, $requests );
		$this->assertInstanceOf(
			HoneyPotAuthenticationRequest::class,
			$requests[0]
		);
	}

	/** @dataProvider provideValidConfig */
	public function testAccountCreationMissingRequest( array $cfg ) {
		$provider = new HoneyPotPreAuthenticationProvider(
			new HashConfig( $cfg )
		);
		$status = $provider->testForAccountCreation(
			$this->createNoOpMock( User::class ),
			$this->createNoOpMock( User::class ),
			[]
		);
		$this->assertStatusGood( $status );
	}

	public static function provideAccountCreationTest() {
		// misleading configuration, value of trigger field, expected error
		// or true for passing
		yield 'misleading, not triggered' => [ true, '', true ];
		yield 'not misleading, not triggered' => [ false, '', true ];

		yield 'misleading, triggered' => [ true, 'foo', 'badretype' ];
		yield 'not misleading, triggered' => [
			false,
			'foo',
			'honeypot-triggered-error'
		];
	}

	/** @dataProvider provideAccountCreationTest */
	public function testAccountCreationTest(
		bool $misleading,
		string $triggerVal,
		$expected
	) {
		$provider = new HoneyPotPreAuthenticationProvider(
			new HashConfig( [ 'HoneyPotMisleadingError' => $misleading ] )
		);
		$req = new HoneyPotAuthenticationRequest();
		// Action needs to be set correctly for the field to actually be loaded
		$req->action = AuthManager::ACTION_CREATE;
		$req->loadFromSubmission( [ 'trigger' => $triggerVal ] );

		$status = $provider->testForAccountCreation(
			$this->createNoOpMock( User::class ),
			$this->createNoOpMock( User::class ),
			[ $req ]
		);
		if ( $expected === true ) {
			$this->assertStatusGood( $status );
		} else {
			$this->assertStatusError( $expected, $status );
		}
	}

}

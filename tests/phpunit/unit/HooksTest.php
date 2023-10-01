<?php

namespace MediaWiki\Extension\HoneyPot\Test\Unit;

use HTMLTextField;
use MediaWiki\Auth\AuthManager;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest;
use MediaWiki\Extension\HoneyPot\Hooks;
use MediaWiki\Extension\HoneyPot\UndisplayedTextField;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TestLogger;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \MediaWiki\Extension\HoneyPot\Hooks
 * @group extension-HoneyPot
 */
class HooksTest extends MediaWikiUnitTestCase {

	private function newWithLogger( LoggerInterface $logger ): Hooks {
		$hooks = new Hooks();
		TestingAccessWrapper::newFromObject( $hooks )->logger = $logger;
		return $hooks;
	}

	public function testNoRequest() {
		// false -> should never be triggered
		$logger = new TestLogger( false );
		$hooks = $this->newWithLogger( $logger );
		$formDescription = [];
		$hooks->onAuthChangeFormFields(
			[],
			[],
			$formDescription,
			AuthManager::ACTION_LOGIN
		);
		// Logger verifies its not used
		$this->addToAssertionCount( 1 );
	}

	public static function provideIsOverridden() {
		// minimal specification of the $formDescriptor['trigger'] field
		yield 'no class set' => [ [] ];
		yield 'HTMLTextField' => [ [ 'class' => HTMLTextField::class ] ];
	}

	/** @dataProvider provideIsOverridden */
	public function testGetsOverridden( array $fieldDescription ) {
		// false -> should never be triggered
		$logger = new TestLogger( false );
		$hooks = $this->newWithLogger( $logger );
		$formDescription = [ 'trigger' => $fieldDescription ];
		$hooks->onAuthChangeFormFields(
			[ new HoneyPotAuthenticationRequest() ],
			[],
			$formDescription,
			AuthManager::ACTION_LOGIN
		);
		$this->assertArrayHasKey( 'trigger', $formDescription );
		$this->assertIsArray( $formDescription['trigger'] );
		$this->assertArrayHasKey( 'class', $formDescription['trigger'] );
		$this->assertSame(
			UndisplayedTextField::class,
			$formDescription['trigger']['class']
		);
	}

	public function testNoOverride() {
		$logger = new TestLogger( true );
		$hooks = $this->newWithLogger( $logger );
		$formDescription = [ 'trigger' => [ 'class' => 'foo' ] ];
		$hooks->onAuthChangeFormFields(
			[ new HoneyPotAuthenticationRequest() ],
			[],
			$formDescription,
			AuthManager::ACTION_LOGIN
		);
		// Should not get overridden
		$this->assertArrayHasKey( 'trigger', $formDescription );
		$this->assertIsArray( $formDescription['trigger'] );
		$this->assertArrayHasKey( 'class', $formDescription['trigger'] );
		$this->assertSame( 'foo', $formDescription['trigger']['class'] );
		// Should be logged
		$this->assertSame( [
			[
				LogLevel::DEBUG,
				'Not overridding trigger class, already set to {class}'
			],
		], $logger->getBuffer() );
		$logger->clearBuffer();
	}
}

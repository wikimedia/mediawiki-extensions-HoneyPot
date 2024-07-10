<?php

namespace MediaWiki\Extension\HoneyPot\Test\Integration;

use FauxRequest;
use MediaWiki\Extension\HoneyPot\Auth\HoneyPotPreAuthenticationProvider;
use MediaWiki\MainConfigNames;
use MediaWikiIntegrationTestCase;
use SpecialCreateAccount;
use Title;

/**
 * Integration test that the `trigger` field actually gets added to
 * Special:CreateAccount and when triggered will prevent account creation with
 * an error message (misleading or not), but if not triggered the creation will
 * succeed.
 *
 * The testFieldExists() method also ensures that the hook is used and that
 * UndisplayedTextField correctly removes the label.
 *
 * @covers \MediaWiki\Extension\HoneyPot\Auth\HoneyPotPreAuthenticationProvider
 * @covers \MediaWiki\Extension\HoneyPot\Auth\HoneyPotAuthenticationRequest
 * @covers \MediaWiki\Extension\HoneyPot\Hooks
 * @covers \MediaWiki\Extension\HoneyPot\UndisplayedTextField
 * @group extension-HoneyPot
 * @group Database
 * @license GPL-2.0-or-later
 */
class AccountCreationTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Override TestSetup::applyInitialConfig()
		$authConfig = $this->getConfVar( MainConfigNames::AuthManagerConfig );
		$authConfig['preauth'][] = [
			'class' => HoneyPotPreAuthenticationProvider::class,
			'services' => [ 'MainConfig' ],
			'sort' => 10,
		];
		$this->overrideConfigValue(
			MainConfigNames::AuthManagerConfig,
			$authConfig
		);
		// Use qqx to simplify testing messages
		$this->setContentLang( 'qqx' );
		$this->setUserLang( 'qqx' );
	}

	private function getCreateAccount(): SpecialCreateAccount {
		$page = $this->getServiceContainer()->getSpecialPageFactory()
						->getPage( 'CreateAccount' );
		$title = Title::makeTitle( NS_SPECIAL, 'CreateAccount' );
		$page->getContext()->setTitle( $title );
		return $page;
	}

	/**
	 * Get a user_id or false for a given username and caller
	 * @param string $username
	 * @param string $caller
	 * @return mixed
	 */
	private function selectUserId( string $username, string $caller ) {
		return $this->getDb()->newSelectQueryBuilder()
					->select( 'user_id' )
					->from( 'user' )
					->where( [ 'user_name' => $username ] )
					->caller( $caller )
					->fetchField();
	}

	public function testFieldExists() {
		$req = new FauxRequest(
			[], // data
			false, // posted
			null, // session
			'https' // protocol
		);
		$sp = $this->getCreateAccount();
		$sp->getContext()->setRequest( $req );
		$sp->run( null );
		$outputHTML = $sp->getOutput()->getHTML();
		$this->assertStringContainsString(
			'mw-htmlform-field-UndisplayedTextField',
			$outputHTML,
			'UndisplayedTextField used'
		);
		$this->assertStringContainsString(
			'input id="mw-input-trigger"',
			$outputHTML,
			'Trigger input added'
		);
		$this->assertStringNotContainsString(
			'honeypot-field-label',
			$outputHTML,
			'UndisplayedTextField removes the label'
		);
	}

	public static function provideConfig() {
		yield 'not misleading' => [ false, 'honeypot-triggered-error' ];
		yield 'misleading' => [ true, 'badretype' ];
	}

	/** @dataProvider provideConfig */
	public function testTriggered( bool $misleading, string $error ) {
		$username = 'HoneyPotTriggered' . wfTimestampNow();
		$userId = $this->selectUserId(
			$username,
			__METHOD__ . ' before submission'
		);
		$this->assertFalse( $userId, 'User does not exist before submission' );

		$this->overrideConfigValue( 'HoneyPotMisleadingError', $misleading );
		$req = new FauxRequest(
			[
				'wpName' => $username,
				'wpPassword' => 'foobarbaz',
				'retype' => 'foobarbaz',
				'authAction' => 'create',
				'trigger' => 'foo',
			], // data
			true, // posted
			null, // session
			'https' // protocol
		);
		// Set up token, see SpecialCreateAccount::getToken()
		$token = $req->getSession()->getToken( '', 'createaccount' );
		$req->setVal( 'wpCreateaccountToken', $token->toString() );
		$sp = $this->getCreateAccount();
		$sp->getContext()->setRequest( $req );
		$sp->run( null );
		$outputHTML = $sp->getOutput()->getHTML();
		$this->assertStringContainsString(
			"($error)",
			$outputHTML,
			'Error message is shown'
		);

		$userId = $this->selectUserId(
			$username,
			__METHOD__ . ' after submission'
		);
		$this->assertFalse( $userId, 'User not created after submission' );
	}

	/** @dataProvider provideConfig */
	public function testNotTriggered( bool $misleading, string $error ) {
		// $error is unused here
		$username = 'HoneyPotNotTriggered' . wfTimestampNow();
		// Make the names distinct for $misleading so that if they run too
		// close together nothing breaks
		$username .= 'v' . (string)(int)$misleading;
		$userId = $this->selectUserId(
			$username,
			__METHOD__ . ' before submission'
		);
		$this->assertFalse( $userId, 'User does not exist before submission' );

		$this->overrideConfigValue( 'HoneyPotMisleadingError', $misleading );
		$req = new FauxRequest(
			[
				'wpName' => $username,
				'wpPassword' => 'foobarbaz',
				'retype' => 'foobarbaz',
				'authAction' => 'create',
			], // data
			true, // posted
			null, // session
			'https' // protocol
		);
		// Set up token, see SpecialCreateAccount::getToken()
		$token = $req->getSession()->getToken( '', 'createaccount' );
		$req->setVal( 'wpCreateaccountToken', $token->toString() );
		$sp = $this->getCreateAccount();
		$sp->getContext()->setRequest( $req );
		$sp->run( null );
		$outputHTML = $sp->getOutput()->getHTML();
		$this->assertStringNotContainsString(
			"($error)",
			$outputHTML,
			'No error message is shown'
		);

		$userId = $this->selectUserId(
			$username,
			__METHOD__ . ' after submission'
		);
		$this->assertNotFalse( $userId, 'User created after submission' );
	}

}

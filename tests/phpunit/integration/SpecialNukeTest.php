<?php

namespace MediaWiki\Extension\Nuke\Test\Integration;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Nuke\Form\SpecialNukeHTMLFormUIRenderer;
use MediaWiki\Extension\Nuke\SpecialNuke;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWikiIntegrationTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 *
 * @covers \MediaWiki\Extension\Nuke\SpecialNuke
 * @covers \MediaWiki\Extension\Nuke\NukeContext
 */
class SpecialNukeTest extends MediaWikiIntegrationTestCase {

	use TempUserTestTrait;

	protected function newSpecialPage( bool $withIPLookup = true ): SpecialNuke {
		$services = $this->getServiceContainer();

		return new SpecialNuke(
			$services->getJobQueueGroup(),
			$services->getDBLoadBalancerFactory(),
			$services->getPermissionManager(),
			$services->getRepoGroup(),
			$services->getUserFactory(),
			$services->getUserOptionsLookup(),
			$services->getUserNamePrefixSearch(),
			$services->getUserNameUtils(),
			$services->getNamespaceInfo(),
			$services->getContentLanguage(),
			$withIPLookup ? $services->getService( 'NukeIPLookup' ) : null
		);
	}

	public function testGetTempAccounts() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$specialPage = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );
		$context = $specialPage->getNukeContextFromRequest(
			new FauxRequest( [], true )
		);
		$adminUser = $this->getTestSysop();
		$permissionManager = $this->getServiceContainer()->getPermissionManager();
		$oldPermissions = $permissionManager->getUserPermissions( $adminUser->getUser() );
		$permissionManager->overrideUserRightsForTesting( $adminUser->getUser(),
			array_merge(
				$oldPermissions,
				[ 'checkuser-temporary-account-no-preference' ]
			) );

		$this->assertCount( 0, $specialPage->getTempAccounts( $context ) );

		$this->enableAutoCreateTempUser();

		$this->assertCount( 0, $specialPage->getTempAccounts( $context ) );

		$ip = '1.2.3.4';
		RequestContext::getMain()->getRequest()->setIP( $ip );
		RequestContext::getMain()->setUser( $adminUser->getUser() );
		RequestContext::getMain()->setAuthority( $adminUser->getAuthority() );
		$context = $specialPage->getNukeContextFromRequest(
			new FauxRequest( [
				'target' => $ip
			], true )
		);
		$testTempUser = $this->getServiceContainer()->getTempUserCreator()
			->create( null, new FauxRequest() )->getUser();
		$this->editPage( 'Target1', 'test', "", NS_MAIN, $testTempUser );

		$this->assertCount( 1, $specialPage->getTempAccounts( $context ) );

		// Without the service, the list should be completely empty.
		$specialPage = TestingAccessWrapper::newFromObject( $this->newSpecialPage( false ) );
		$this->assertCount( 0, $specialPage->getTempAccounts( $context ) );

		// Without permissions, the list should be completely empty.
		$permissionManager->overrideUserRightsForTesting( $adminUser->getUser(), $oldPermissions );
		$this->assertCount( 0, $specialPage->getTempAccounts( $context ) );
	}

	public function testUIRenderer() {
		$specialPage = TestingAccessWrapper::newFromObject( $this->newSpecialPage() );

		$uiTypes = [
			'htmlform' => SpecialNukeHTMLFormUIRenderer::class
		];

		foreach ( $uiTypes as $type => $class ) {
			// Check if changing the global variable works
			$this->overrideConfigValue( 'NukeUIType', $type );
			$context = $specialPage->getNukeContextFromRequest(
				new FauxRequest( [], true )
			);
			$this->assertInstanceOf( $class, $specialPage->getUIRenderer( $context ) );

			// Check if changing the request variable works
			$this->overrideConfigValue( 'NukeUIType', null );
			$context = $specialPage->getNukeContextFromRequest(
				new FauxRequest( [
					'nukeUI' => $type
				], true )
			);
			$this->assertInstanceOf( $class, $specialPage->getUIRenderer( $context ) );
		}
	}

}

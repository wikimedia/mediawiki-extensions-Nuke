<?php

namespace MediaWiki\Extension\Nuke\Tests;

use MediaWiki\Extension\Nuke\SpecialNuke;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Request\FauxRequest;
use SpecialPageTestBase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Nuke\SpecialNuke
 */
class SpecialNukeTest extends SpecialPageTestBase {

	protected function newSpecialPage(): SpecialNuke {
		$services = $this->getServiceContainer();

		return new SpecialNuke(
			$services->getJobQueueGroup(),
			$services->getDBLoadBalancerFactory(),
			$services->getPermissionManager(),
			$services->getRepoGroup(),
			$services->getUserFactory(),
			$services->getUserNamePrefixSearch(),
			$services->getUserNameUtils(),
			$services->getNamespaceInfo(),
			$services->getContentLanguage()
		);
	}

	public function testExecutePattern() {
		// Test that matching wildcards works, and that escaping wildcards works as documented
		// at https://www.mediawiki.org/wiki/Help:Extension:Nuke
		$this->editPage( '%PositiveNukeTest123', 'test' );
		$this->editPage( 'NegativeNukeTest123', 'test' );

		$user = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => 'submit',
			'pattern' => '\\%PositiveNukeTest%',
			'wpFormIdentifier' => 'massdelete',
			'wpEditToken' => $user->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $user );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'PositiveNukeTest123', $html );
		$this->assertStringNotContainsString( 'NegativeNukeTest123', $html );
	}

	public function testUserPages() {
		$user = $this->getTestUser()->getUser();
		$this->insertPage( 'Page123', 'Test', NS_MAIN, $user );
		$this->insertPage( 'Paging456', 'Test', NS_MAIN, $user );
		$this->insertPage( 'Should not show', 'No show' );

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => 'submit',
			'target' => $user->getName(),
			'wpFormIdentifier' => 'massdelete',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'Page123', $html );
		$this->assertStringContainsString( 'Paging456', $html );
		$this->assertStringNotContainsString( 'Should not show', $html );
	}

	public function testNamespaces() {
		$this->insertPage( 'Page123', 'Test', NS_MAIN );
		$this->insertPage( 'Paging456', 'Test', NS_MAIN );
		$this->insertPage( 'Should not show', 'No show', NS_TALK );

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => 'submit',
			'namespace' => NS_MAIN,
			'wpFormIdentifier' => 'massdelete',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'Page123', $html );
		$this->assertStringContainsString( 'Paging456', $html );
		$this->assertStringNotContainsString( 'Should not show', $html );
	}

	public function testDelete() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Reason',
			'wpReason' => 'Reason',
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( '(nuke-deletion-queued: Page123)', $html );
		$this->assertStringContainsString( '(nuke-deletion-queued: Paging456)', $html );
	}

}

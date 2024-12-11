<?php

namespace MediaWiki\Extension\Nuke\Test\Integration;

use ErrorPageError;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Nuke\SpecialNuke;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PermissionsError;
use SpecialPageTestBase;
use UploadFromFile;
use UserBlockedError;

/**
 * @group Database
 * @covers \MediaWiki\Extension\Nuke\SpecialNuke
 */
class SpecialNukeTest extends SpecialPageTestBase {

	use TempUserTestTrait;

	protected function newSpecialPage(): SpecialNuke {
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
			$services->getService( 'NukeIPLookup' )
		);
	}

	/**
	 * Ensure that the prompt appears as expected.
	 *
	 * @return void
	 */
	public function testPrompt() {
		$admin = $this->getTestSysop()->getUser();
		$this->disableAutoCreateTempUser();
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', null, 'qqx', $performer );

		$this->assertStringContainsString( '(nuke-summary)', $html );
		$this->assertStringContainsString( '(nuke-tools)', $html );
		$this->assertStringContainsString( '(nuke-tools-prompt)', $html );
	}

	/**
	 * Ensure that the prompt displays the correct messages when
	 * temp accounts and CheckUser are enabled
	 *
	 * @return void
	 */
	public function testPromptWithCheckUser() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$admin = $this->getTestSysop()->getUser();
		$this->enableAutoCreateTempUser();
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', null, 'qqx', $performer );

		$this->assertStringContainsString( '(nuke-summary)', $html );
		$this->assertStringContainsString( '(nuke-tools-tempaccount)', $html );
	}

	/**
	 * Ensure that the prompt prevents a user blocked from deleting pages from accessing the form
	 *
	 * @return void
	 */
	public function testPromptBlocked() {
		$user = $this->getTestSysop()->getUser();
		$performer = new UltimateAuthority( $user );

		// Self-blocks should still prevent the form from being shown
		$this->getServiceContainer()
			->getBlockUserFactory()
			->newBlockUser( $user, $performer, 'infinity', 'SpecialNukeTest::testBlocked' )
			->placeBlockUnsafe();

		$this->expectException( UserBlockedError::class );
		$this->executeSpecialPage( '', null, 'qqx', $performer );

		$this->getServiceContainer()
			->getUnblockUserFactory()
			->newUnblockUser( $user, $performer, 'SpecialNukeTest::testBlocked' )
			->unblockUnsafe();
	}

	/**
	 * Ensure that the prompt prevents a nuke user without the checkuser-temporary-account permission
	 * from performing CheckUser IP lookups
	 *
	 * @return void
	 */
	public function testListCheckUserNoPermission() {
		$this->expectException( PermissionsError::class );

		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->enableAutoCreateTempUser();
		$ip = '1.2.3.4';

		$adminUser = $this->getTestSysop()->getUser();
		$permissionManager = $this->getServiceContainer()->getPermissionManager();
		$permissions = $permissionManager->getUserPermissions( $adminUser );
		$permissions = array_diff( $permissions, [ 'checkuser-temporary-account' ] );
		$permissionManager->overrideUserRightsForTesting( $adminUser,
			$permissions
		);
		$performer = new UltimateAuthority( $adminUser );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $ip,
		], true );
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
	}

	/**
	 * Ensure that the prompt prevents a nuke user who hasn't accepted the agreement
	 * from performing CheckUser IP lookups
	 *
	 * @return void
	 */
	public function testListCheckUserNoPreference() {
		$this->expectException( ErrorPageError::class );
		$this->expectExceptionMessage(
			'To view temporary account contributions for an IP, please accept' .
			' the agreement in [[Special:Preferences|your preferences]].'
		);
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->enableAutoCreateTempUser();
		$ip = '1.2.3.4';
		$this->overrideConfigValues( [
			'GroupPermissions' => [
				'testgroup' => [
					'nuke' => true,
					'checkuser-temporary-account' => true
				]
			]
		] );

		$adminUser = $this->getTestUser( [ 'testgroup' ] )->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $ip,
		], true );
		$adminPerformer = new UltimateAuthority( $adminUser );
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );
	}

	/**
	 * Ensure that pages created by a specific user are shown accordingly
	 *
	 * @return void
	 */
	public function testListTarget() {
		$user = $this->getTestUser()->getUser();

		$this->insertPage( 'Target1', 'test', NS_MAIN, $user );
		$this->insertPage( 'Target2', 'test', NS_MAIN, $user );
		$this->insertPage( 'Should not show', 'No show' );

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );
		$request1 = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $user->getName()
		] );

		[ $html1 ] = $this->executeSpecialPage( '', $request1, 'qqx', $adminPerformer );

		$this->assertStringContainsString( 'Target1', $html1 );
		$this->assertStringContainsString( 'Target2', $html1 );
		$this->assertStringNotContainsString( 'Should not show', $html1 );

		// Not supplying an action should imply 'list'.
		$request2 = new FauxRequest( [
			'target' => $user->getName()
		] );

		[ $html2 ] = $this->executeSpecialPage( '', $request2, 'qqx', $adminPerformer );

		$this->assertStringContainsString( 'Target1', $html2 );
		$this->assertStringContainsString( 'Target2', $html2 );
		$this->assertStringNotContainsString( 'Should not show', $html2 );
	}

	/**
	 * Ensure that the prompt works with anon IP searches when temp accounts are disabled
	 *
	 * @return void
	 */
	public function testListTargetAnonUser() {
		$this->disableAutoCreateTempUser( [ 'known' => false ] );
		$ip = '127.0.0.1';
		$testUser = $this->getServiceContainer()->getUserFactory()->newAnonymous( $ip );
		$performer = new UltimateAuthority( $testUser );

		$this->editPage( 'Target1', 'test', "", NS_MAIN, $performer );
		$this->editPage( 'Target2', 'test', "", NS_MAIN, $performer );

		$adminUser = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $testUser->getUser()->getName()
		] );
		$adminPerformer = new UltimateAuthority( $adminUser );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertStringContainsString( 'Target1', $html );
		$this->assertStringContainsString( 'Target2', $html );

		$this->assertEquals( 2, substr_count( $html, '(nuke-editby: 127.0.0.1)' ) );
	}

	/**
	 * Ensure that the prompt returns temp accounts from IP lookups when temp accounts and
	 * CheckUser are enabled
	 *
	 * @return void
	 */
	public function testListTargetCheckUser() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->enableAutoCreateTempUser();
		$ip = '1.2.3.4';
		RequestContext::getMain()->getRequest()->setIP( $ip );
		$testUser = $this->getServiceContainer()->getTempUserCreator()
			->create( null, new FauxRequest() )->getUser();
		$this->editPage( 'Target1', 'test', "", NS_MAIN, $testUser );
		$this->editPage( 'Target2', 'test', "", NS_MAIN, $testUser );

		$adminUser = $this->getTestSysop()->getUser();
		$permissionManager = $this->getServiceContainer()->getPermissionManager();
		$permissionManager->overrideUserRightsForTesting( $adminUser,
			array_merge(
				$permissionManager->getUserPermissions( $adminUser ),
				[ 'checkuser-temporary-account-no-preference' ]
			) );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $ip,
		], true );
		$adminPerformer = new UltimateAuthority( $adminUser );
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertEquals( 2, substr_count( $html, '(nuke-editby: ~2024-1)' ) );

		$this->assertStringContainsString( 'Target1', $html );
		$this->assertStringContainsString( 'Target2', $html );
	}

	/**
	 * Ensure that the prompt returns temp accounts and IP accounts from IP lookups when
	 * temp accounts and CheckUser are enabled and Anonymous IP accounts exist
	 *
	 * @return void
	 */
	public function testListTargetCheckUserMixed() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->disableAutoCreateTempUser( [ 'known' => false ] );
		$ip = '1.2.3.4';
		$testUser = $this->getServiceContainer()->getUserFactory()->newAnonymous( $ip );
		$performer = new UltimateAuthority( $testUser );

		// create a page as an anonymous IP user
		$this->editPage( 'Target1', 'test', "", NS_MAIN, $performer );

		$this->enableAutoCreateTempUser();
		RequestContext::getMain()->getRequest()->setIP( $ip );
		$testUser = $this->getServiceContainer()->getTempUserCreator()
										  ->create( null, new FauxRequest() )->getUser();
		$performer = new UltimateAuthority( $testUser );

		// create a page as a temp user
		$this->editPage( 'Target2', 'test', "", NS_MAIN, $performer );

		$adminUser = $this->getTestSysop()->getUser();
		$permissionManager = $this->getServiceContainer()->getPermissionManager();
		$permissionManager->overrideUserRightsForTesting( $adminUser,
			array_merge(
				$permissionManager->getUserPermissions( $adminUser ),
				[ 'checkuser-temporary-account-no-preference' ]
			) );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => $ip,
		], true );
		$adminPerformer = new UltimateAuthority( $adminUser );
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertSame( 1, substr_count( $html, ' (nuke-editby: ~2024-1)' ) );
		$this->assertSame( 1, substr_count( $html, ' (nuke-editby: 1.2.3.4)' ) );

		// They should all show up together
		$this->assertStringContainsString( 'Target1', $html );
		$this->assertStringContainsString( 'Target2', $html );
	}

	/**
	 * Ensure that matching wildcards works, and that escaping wildcards works as documented at
	 * https://www.mediawiki.org/wiki/Help:Extension:Nuke
	 *
	 * @return void
	 */
	public function testListPattern() {
		$this->editPage( '%PositiveNukeTest123', 'test' );
		$this->editPage( 'NegativeNukeTest123', 'test' );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => '\\%PositiveNukeTest%'
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'PositiveNukeTest123', $html );
		$this->assertStringNotContainsString( 'NegativeNukeTest123', $html );
	}

	public function testListNamespaces() {
		$this->insertPage( 'Page123', 'Test', NS_MAIN );
		$this->insertPage( 'Paging456', 'Test', NS_MAIN );
		$this->insertPage( 'Should not show', 'No show', NS_TALK );

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'namespace' => NS_MAIN,
			'wpFormIdentifier' => 'massdelete'
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( 'Page123', $html );
		$this->assertStringContainsString( 'Paging456', $html );
		$this->assertStringNotContainsString( 'Should not show', $html );
	}

	public function testListNoPagesGlobal() {
		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'ThisPageShouldNotExist-' . rand()
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( '(nuke-nopages-global)', $html );
	}

	public function testListNoPagesUser() {
		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'target' => 'ThisPageShouldNotExist-' . rand()
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( '(nuke-nopages-global)', $html );
	}

	public function testListNamespace() {
		$this->editPage( 'NukeUserPageTarget', 'test', '', NS_USER );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'NukeUserPageTarget',
			'namespace' => NS_USER
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$expectedTitle = Title::makeTitle( NS_USER, 'NukeUserPageTarget' )
			->getPrefixedText();
		$this->assertStringContainsString( $expectedTitle, $html );
	}

	public function testListTalk() {
		$this->editPage( 'NukeTalkPageTarget', 'test', '', NS_TALK );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'NukeTalkPageTarget'
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$expectedTitle = Title::makeTitle( NS_TALK, 'NukeTalkPageTarget' )
			->getPrefixedText();
		$this->assertStringContainsString( $expectedTitle, $html );
	}

	public function testListNamespaceMultiple() {
		$this->editPage( 'NukeMainPageTarget', 'test' );
		$this->editPage( 'NukeProjectPageTarget', 'test', '', NS_PROJECT );
		$this->editPage( 'NukeUserPageTarget', 'test', '', NS_USER );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Nuke%PageTarget',
			'namespace' => implode( "\n", [ NS_MAIN, NS_USER ] )
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString(
			Title::makeTitle( NS_MAIN, 'NukeMainPageTarget' )->getPrefixedText(),
			$html
		);
		$this->assertStringContainsString(
			Title::makeTitle( NS_USER, 'NukeUserPageTarget' )->getPrefixedText(),
			$html
		);
		$this->assertStringNotContainsString(
			Title::makeTitle( NS_PROJECT, 'NukNukeProjectPageTarget' )->getPrefixedText(),
			$html
		);
	}

	public function testListNamespaceMultipleEdge() {
		$page1 = $this->insertPage( 'NukeMainPageTarget', 'test' )['title'];
		$page2 = $this->insertPage( 'NukeProjectPageTarget', 'test', NS_PROJECT )['title'];
		$page3 = $this->insertPage( 'NukeUserPageTarget', 'test', NS_USER )['title'];

		$admin = $this->getTestSysop()->getUser();
		$performer = new UltimateAuthority( $admin );

		// Input includes empty line
		$request1 = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Nuke%PageTarget',
			'namespace' => NS_PROJECT . "\n"
		], true );
		[ $html1 ] = $this->executeSpecialPage( '', $request1, 'qqx', $performer );
		$this->assertStringNotContainsString( $page1, $html1 );
		$this->assertStringContainsString( $page2, $html1 );
		$this->assertStringNotContainsString( $page3, $html1 );

		// Input includes invalid namespace ID
		$request1 = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Nuke%PageTarget',
			'namespace' => NS_PROJECT . "\n99999999"
		], true );
		[ $html1 ] = $this->executeSpecialPage( '', $request1, 'qqx', $performer );
		$this->assertStringNotContainsString( $page1, $html1 );
		$this->assertStringContainsString( $page2, $html1 );
		$this->assertStringNotContainsString( $page3, $html1 );

		// Input includes a string
		$request1 = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Nuke%PageTarget',
			'namespace' => NS_PROJECT . "\nUser"
		], true );
		[ $html1 ] = $this->executeSpecialPage( '', $request1, 'qqx', $performer );
		$this->assertStringNotContainsString( $page1, $html1 );
		$this->assertStringContainsString( $page2, $html1 );
		$this->assertStringNotContainsString( $page3, $html1 );

		// Input is entirely invalid
		$request1 = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Nuke%PageTarget',
			'namespace' => "Project\nUser"
		], true );
		[ $html1 ] = $this->executeSpecialPage( '', $request1, 'qqx', $performer );
		// Pages from all namespaces will be returned
		$this->assertStringContainsString( $page1, $html1 );
		$this->assertStringContainsString( $page2, $html1 );
		$this->assertStringContainsString( $page3, $html1 );
	}

	public function testListCapitalizedNamespace() {
		$this->overrideConfigValues( [
			'CapitalLinks' => false,
			'CapitalLinkOverrides' => []
		] );
		$this->editPage( 'uncapsTarget', 'test' );
		$this->editPage( 'UncapsTarget', 'test' );

		$admin = $this->getTestSysop()->getUser();

		$expectedTitle = Title::makeTitle( NS_MAIN, 'uncapsTarget' )
			->getPrefixedText();

		$shouldMatch = [
			"%ncapsTarget",
			"u%",
			"uncapsTarget"
		];
		$shouldNotMatch = [
			"UncapsTarget"
		];

		foreach ( $shouldMatch as $match ) {
			$request = new FauxRequest( [
				'action' => SpecialNuke::ACTION_LIST,
				'pattern' => 'uncapsTarget'
			], true );
			$performer = new UltimateAuthority( $admin );
			[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

			$this->assertStringContainsString( $expectedTitle, $html, "match: $match" );
			foreach ( $shouldNotMatch as $noMatch ) {
				$this->assertStringNotContainsString( $noMatch, $html );
			}
		}
	}

	public function testListCapitalizedNamespaceOverrides() {
		$overriddenNamespaces = [
			NS_PROJECT => true,
			NS_MEDIAWIKI => true,
			NS_USER => true,
		];
		$this->overrideConfigValues( [
			'CapitalLinks' => false,
			'CapitalLinkOverrides' => $overriddenNamespaces
		] );
		$overriddenNamespaces[ NS_MAIN ] = false;
		foreach ( $overriddenNamespaces as $ns => $override ) {
			$this->editPage( "UncapsTarget" . $ns, 'test', '', $ns );
			// If capital links for this ns is `true`, then the existing page should be edited.
			$this->editPage( "uncapsTarget" . $ns, 'test2', '', $ns );
		}

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'u%'
		], true );
		$performer = new UltimateAuthority( $admin );
		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString( "uncapsTarget" . NS_MAIN, $html );
		// Check that the overridden namespaces are included in the search
		$this->assertStringContainsString( "UncapsTarget" . NS_PROJECT, $html );
		$this->assertStringContainsString( "UncapsTarget" . NS_MEDIAWIKI, $html );
		$this->assertStringContainsString( "UncapsTarget" . NS_USER, $html );

		$this->assertStringNotContainsString( "UncapsTarget" . NS_MAIN, $html );
		// Check that the non-overridden namespaces are not included in the search
		$this->assertStringNotContainsString( "uncapsTarget" . NS_PROJECT, $html );
		$this->assertStringNotContainsString( "uncapsTarget" . NS_MEDIAWIKI, $html );
		$this->assertStringNotContainsString( "uncapsTarget" . NS_USER, $html );
	}

	public function testListCapitalizedNamespaceMultiple() {
		// The goal of this test is to:
		// - Ensure that for case-sensitive namespaces, the search remains case-sensitive.
		// - Ensure that for non-case-sensitive namespaces, the search capitalizes the condition accordingly.
		// Here, `NS_MAIN` remains case-insensitive, but other namespaces will be case-sensitive.
		$this->overrideConfigValues( [
			'CapitalLinks' => false,
			'CapitalLinkOverrides' => [
				NS_MAIN => true
			]
		] );

		$this->editPage( 'UncapsTargetMain', 'test' );
		$this->editPage( 'UncapsTarget', 'test', '', NS_PROJECT );
		$this->editPage( 'uncapsTarget', 'test', '', NS_PROJECT );
		$this->editPage( 'UncapsTarget', 'test', '', NS_HELP );
		$this->editPage( 'uncapsTarget', 'test', '', NS_HELP );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'uncapsTarget%',
			'namespace' => implode( "\n", [ NS_MAIN, NS_HELP ] )
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertStringContainsString(
			Title::makeTitle( NS_MAIN, 'UncapsTargetMain' )->getPrefixedText(),
			$html
		);
		$this->assertStringContainsString(
			Title::makeTitle( NS_HELP, 'uncapsTarget' )->getPrefixedText(),
			$html
		);

		$this->assertStringNotContainsString(
			Title::makeTitle( NS_PROJECT, 'UncapsTarget' )->getPrefixedText(),
			$html
		);

		$this->assertStringNotContainsString(
			Title::makeTitle( NS_PROJECT, 'uncapsTarget' )->getPrefixedText(),
			$html
		);

		$this->assertStringNotContainsString(
			Title::makeTitle( NS_HELP, 'UncapsTarget' )->getPrefixedText(),
			$html
		);
	}

	/**
	 * Check if Nuke still works with languages with complicated capitalization
	 * or no capitalization.
	 *
	 * Since the first letter of each title should be capitalized, searching for the
	 * lowercase version should still yield the uppercase version, or the same character
	 * if casing does not exist for the language.
	 *
	 * @return void
	 */
	public function testListCapitalized() {
		/** @noinspection SpellCheckingInspection */
		$testedLanguages = [
			"ee" => [
				"Ülemiste",
				"ülemiste"
			],
			"zh" => "你好",
			"ja" => "にほんご",
		];

		foreach ( $testedLanguages as $lang => $testData ) {
			[ $created, $wanted ] = is_array( $testData ) ?
				$testData : [ $testData, $testData ];

			$this->overrideConfigValue( 'LanguageCode', $lang );
			$this->editPage( $created, 'test' );

			$admin = $this->getTestSysop()->getUser();
			$request = new FauxRequest( [
				'action' => SpecialNuke::ACTION_LIST,
				'pattern' => $wanted
			], true );
			$performer = new UltimateAuthority( $admin );

			[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

			$this->assertStringContainsString( $created, $html );
		}
	}

	public function testListLimit() {
		$this->editPage( 'Page1', 'test' );
		$this->editPage( 'Page2', 'test' );
		$this->editPage( 'Page3', 'test' );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Page%',
			'limit' => 2
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertEquals( 2, substr_count( $html, '<li>' ) );
	}

	public function testListLimitWithHooks() {
		$this->editPage( 'Page1', 'test' );
		$this->editPage( 'Page2', 'test' );
		$this->editPage( 'Page3', 'test' );
		$this->setTemporaryHook( "NukeGetNewPages", static function ( $_1, $_2, $_3, $_4, &$pages ) {
			$pages[] = "Page3";

			return true;
		} );

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Page%',
			'limit' => 2
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertEquals( 2, substr_count( $html, '<li>' ) );
	}

	public function testListMaxAge() {
		// 1 day
		$maxAge = 86400;
		$this->overrideConfigValues( [
			'NukeMaxAge' => $maxAge,
			'RCMaxAge' => $maxAge
		] );

		// Will never show up. If it does, the max age isn't being applied at all.
		$page1Status = $this->editPage( 'Page1', 'test' );
		// Will show up conditionally (see below).
		$page2Status = $this->editPage( 'Page2', 'test' );
		// Will always show up.
		$this->editPage( 'Page3', 'test' );

		// Prepare database connection and update query builder
		$dbw = $this->getServiceContainer()->getConnectionProvider()->getPrimaryDatabase();
		$uqb = $dbw->newUpdateQueryBuilder()
			->update( 'revision' )
			->caller( __METHOD__ );

		// Manually change the rev_timestamp of Page1's creation in the database.
		( clone $uqb )
			->set( [ 'rev_timestamp' => $dbw->timestamp( time() - ( $maxAge * 3 ) ) ] )
			->where( [
				'rev_id' => $page1Status->getNewRevision()->getId()
			] )->execute();
		// Manually change the rev_timestamp of Page2's creation in the database.
		( clone $uqb )
			->set( [ 'rev_timestamp' => $dbw->timestamp( time() - $maxAge - 60 ) ] )
			->where( [
				'rev_id' => $page2Status->getNewRevision()->getId()
			] )->execute();

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'pattern' => 'Page%',
			'limit' => 2
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
		$this->assertStringNotContainsString( 'Page1', $html );
		$this->assertStringNotContainsString( 'Page2', $html );
		$this->assertStringContainsString( 'Page3', $html );

		// Now check if resetting $wgNukeMaxAge will show Page2.
		// This should follow $wgRCMaxAge, which will include the page.
		$this->overrideConfigValues( [
			'RCMaxAge' => $maxAge * 2,
			'NukeMaxAge' => 0
		] );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
		$this->assertStringNotContainsString( 'Page1', $html );
		$this->assertStringContainsString( 'Page2', $html );
		$this->assertStringContainsString( 'Page3', $html );

		// Now check if expanding just $wgNukeMaxAge will show Page2.
		$this->overrideConfigValues( [
			'RCMaxAge' => $maxAge,
			'NukeMaxAge' => $maxAge * 2
		] );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );
		$this->assertStringNotContainsString( 'Page1', $html );
		$this->assertStringContainsString( 'Page2', $html );
		$this->assertStringContainsString( 'Page3', $html );
	}

	public function testListFiles() {
		$testFileName = $this->uploadTestFile()['title']->getPrefixedText();

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_LIST,
			'namespace' => NS_FILE
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		// The title should be in the list
		$this->assertStringContainsString( $testFileName, $html );
		// There should also be an image preview
		$this->assertStringContainsString( "<img src", $html );
	}

	public function testConfirm() {
		$user = $this->getTestUser()->getUser();

		// Set content language to English; needed to get the right "defaultreason".
		$this->overrideConfigValue( 'wgLanguageCode', 'en' );
		$defaultReason = RequestContext::getMain()
			->msg( 'nuke-defaultreason', $user->getName() )
			->inContentLanguage()
			->text();

		/** @var Title $page1 */
		$page1 = $this->insertPage( 'Target1', 'test', NS_MAIN, $user )['title'];
		/** @var Title $page2 */
		$page2 = $this->insertPage( 'Target2', 'test' )['title'];

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_CONFIRM,
			'target' => $user->getName(),
			'pages' => [
				$page1->getPrefixedDBkey()
			],
			'originalPageList' => implode(
				SpecialNuke::PAGE_LIST_SEPARATOR,
				[ $page1->getPrefixedDBkey(), $page2->getPrefixedDBkey() ]
			),
			// 'confirm' action requires an edit token
			'wpEditToken' => $user->getEditToken()
		], true );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		// Default reason should pre-fill the reason field
		$this->assertStringContainsString( $defaultReason, $html );

		// Selected pages will have talk and history links
		$this->assertStringContainsString( 'Target1', $html );
		$this->assertStringContainsString( 'Talk:Target1', $html );
		$this->assertStringContainsString( 'Target1&amp;action=history', $html );

		// Skipped pages will not show up at all, but will be found in the hidden
		// originalPageList field.
		$this->assertStringContainsString( 'Target2', $html );
		$this->assertStringNotContainsString( 'Talk:Target2', $html );
		$this->assertStringNotContainsString( 'Target2&amp;action=history', $html );

		// Ensure the originalPageList field was included
		$this->assertStringContainsString( '"originalPageList"', $html );
		$this->assertStringContainsString( '"Target1|Target2"', $html );
	}

	/**
	 * Tests a malformed confirm request: a request that either
	 *  - was not posted
	 *  - does not have an edit token
	 *
	 * @return void
	 */
	public function testConfirmMalformed() {
		$user = $this->getTestUser()->getUser();

		/** @var Title $page1 */
		$page1 = $this->insertPage( 'Target1', 'test', NS_MAIN, $user )['title'];
		/** @var Title $page2 */
		$page2 = $this->insertPage( 'Target2', 'test' )['title'];

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_CONFIRM,
			'target' => $user->getName(),
			'pages' => [
				$page1->getPrefixedDBkey()
			],
			'originalPageList' => implode(
				SpecialNuke::PAGE_LIST_SEPARATOR,
				[ $page1->getPrefixedDBkey(), $page2->getPrefixedDBkey() ]
			),
		], false );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		// We should be re-prompting the user, since no edit token was provided.
		$this->assertStringContainsString( "(nuke-tools-prompt)", $html );
		// Correct target should still be on the prompt box
		$this->assertStringContainsString( $user->getName(), $html );
	}

	/**
	 * Ensure that the default deletion reason used correctly changes based on whether or not
	 * temporary accounts are being used or not.
	 *
	 * @return void
	 */
	public function testConfirmAnonUser() {
		$this->markTestSkippedIfExtensionNotLoaded( 'CheckUser' );
		$this->enableAutoCreateTempUser();
		$ip = '1.2.3.4';
		RequestContext::getMain()->getRequest()->setIP( $ip );
		$testUser = $this->getServiceContainer()->getTempUserCreator()
			->create( null, new FauxRequest() )->getUser();

		$user = $this->getTestUser()->getUser();

		// Set content language to English; needed to get the right "defaultreason".
		$this->overrideConfigValue( 'wgLanguageCode', 'en' );
		$defaultReason = RequestContext::getMain()
			->msg( 'nuke-defaultreason-tempaccount', $user->getName() )
			->inContentLanguage()
			->text();

		/** @var Title $page1 */
		$page1 = $this->insertPage( 'Target1', 'test', NS_MAIN, $testUser )['title'];

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );

		$permissionManager = $this->getServiceContainer()->getPermissionManager();
		$permissionManager->overrideUserRightsForTesting( $adminUser,
			array_merge(
				$permissionManager->getUserPermissions( $adminUser ),
				[ 'checkuser-temporary-account-no-preference' ]
			) );

		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_CONFIRM,
			'target' => $ip,
			'pages' => [
				$page1->getPrefixedDBkey()
			],
			'originalPageList' => $page1->getPrefixedDBkey(),
			// 'confirm' action requires an edit token
			'wpEditToken' => $user->getEditToken()
		], true );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertStringContainsString( $defaultReason, $html );
		$this->assertStringContainsString( 'Target1', $html );
		$this->assertStringContainsString( 'Talk:Target1', $html );
		$this->assertStringContainsString( 'Target1&amp;action=history', $html );
	}

	/**
	 * Tests requests which have pages but no original page list. This is similar to a click on
	 * the "Continue" button without having first clicked on the "List pages" button.
	 *
	 * @return void
	 */
	public function testConfirmWithoutList() {
		$user = $this->getTestUser()->getUser();

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_CONFIRM,
			// 'confirm' action requires an edit token
			'wpEditToken' => $user->getEditToken()
		], true );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertStringContainsString( '(nuke-nolist)', $html );
	}

	public function testConfirmWithoutSelection() {
		$user = $this->getTestUser()->getUser();

		/** @var Title $page1 */
		$page1 = $this->insertPage( 'Target1', 'test', NS_MAIN, $user )['title'];
		/** @var Title $page2 */
		$page2 = $this->insertPage( 'Target2', 'test' )['title'];

		$adminUser = $this->getTestSysop()->getUser();
		$adminPerformer = new UltimateAuthority( $adminUser );
		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_CONFIRM,
			'target' => $user->getName(),
			'originalPageList' => implode( SpecialNuke::PAGE_LIST_SEPARATOR, [
				$page1->getPrefixedDBkey(),
				$page2->getPrefixedDBkey()
			] ),
			// 'confirm' action requires an edit token
			'wpEditToken' => $user->getEditToken()
		], true );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $adminPerformer );

		$this->assertStringContainsString( '(nuke-noselected)', $html );
	}

	public function testDelete() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$fauxReason = "Reason " . wfRandomString();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Vandalism',
			'wpReason' => $fauxReason,
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$deleteCount = count( $pages );
		$this->assertStringContainsString( "(nuke-delete-summary: $deleteCount)", $html );
		$this->assertStringContainsString( '(nuke-deletion-queued: Page123)', $html );
		$this->assertStringContainsString( '(nuke-deletion-queued: Paging456)', $html );

		// Pre-check to confirm that the page hasn't been deleted yet
		// "mw-tag-marker-nuke" is the CSS class for the "Nuke" tag's <span> on Special:Log.
		$this->assertStringNotContainsString( 'mw-tag-marker-nuke', $this->getDeleteLogHtml() );

		// Ensure all delete jobs are run
		$this->runJobs();

		$deleteLogHtml = $this->getDeleteLogHtml();
		$this->assertStringContainsString( 'Vandalism', $deleteLogHtml );
		$this->assertStringContainsString( 'mw-tag-marker-nuke', $deleteLogHtml );
		$this->assertStringContainsString( $fauxReason, $deleteLogHtml );
	}

	public function testDeleteTarget() {
		$pages = [];

		$testUser = $this->getTestUser( "user" )->getUser();
		$testUserName = $testUser->getName();

		$pages[] = $this->uploadTestFile( $testUser )[ 'title' ];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN, $testUser )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN, $testUser )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$fauxReason = "Reason " . wfRandomString();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Vandalism',
			'wpReason' => $fauxReason,
			'target' => $testUserName,
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$deleteCount = count( $pages );
		$this->assertStringContainsString(
			"(nuke-delete-summary-user: $deleteCount, $testUserName)",
			$html
		);
	}

	public function testDeleteTargetAnon() {
		$pages = [];

		$testUser = $this->getTestUser( "user" )->getUser();
		$testUserName = $testUser->getName();

		$pages[] = $this->uploadTestFile( $testUser )[ 'title' ];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN, $testUser )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN, $testUser )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$fauxReason = "Reason " . wfRandomString();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Vandalism',
			'wpReason' => $fauxReason,
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$deleteCount = count( $pages );
		$this->assertStringContainsString(
			"(nuke-delete-summary: $deleteCount)",
			$html
		);
	}

	public function testDeleteSkipped() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging 456', 'Test', NS_MAIN )[ 'title' ];
		$skippedPage = $this->insertPage( 'Page 789', 'Test', NS_MAIN )['title'];

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Vandalism',
			'originalPageList' => implode( '|', $pages ),
			'pages' => [ $skippedPage->getPrefixedDBkey() ],
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$skippedCount = count( $pages );
		$this->assertStringContainsString( '(nuke-delete-summary: 1)', $html );
		$this->assertStringContainsString( "(nuke-skipped-summary: $skippedCount)", $html );

		// Ensure all delete jobs are run
		$this->runJobs();

		// Make sure that those pages are not in the deletion log.
		$deleteLogHtml = $this->getDeleteLogHtml();
		foreach ( $pages as $title ) {
			$this->assertStringNotContainsString( $title->getPrefixedText(), $deleteLogHtml );
		}
		$this->assertStringContainsString( $skippedPage->getPrefixedText(), $deleteLogHtml );
	}

	public function testDeleteDropdownReason() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Vandalism',
			'wpReason' => "",
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		$this->executeSpecialPage( '', $request, 'qqx', $performer );

		// Ensure all jobs are run
		$this->runJobs();

		// Check logs
		$this->assertStringContainsString( "Vandalism", $this->getDeleteLogHtml() );
	}

	public function testDeleteCustomReason() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Paging456', 'Test', NS_MAIN )[ 'title' ];

		$admin = $this->getTestSysop()->getUser();

		$fauxReason = "Reason " . wfRandomString();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'other',
			'wpReason' => $fauxReason,
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		$this->executeSpecialPage( '', $request, 'qqx', $performer );

		// Ensure all jobs are run
		$this->runJobs();

		// Check logs
		$this->assertStringContainsString( $fauxReason, $this->getDeleteLogHtml() );
	}

	public function testDeleteFiles() {
		$testFileName = $this->uploadTestFile()['title']->getPrefixedText();

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Reason',
			'wpReason' => 'Reason',
			'pages' => [ $testFileName ],
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getEditToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		// Files are deleted instantly
		$this->assertStringContainsString( "nuke-deleted", $html );
		$this->assertStringContainsString( $testFileName, $html );
	}

	public function testDeleteHook() {
		$wasCalled = false;
		$pages = [];
		$pages[] = $this->insertPage( 'Bad article', 'test', NS_MAIN )['title'];
		$pages[] = $this->insertPage( 'DO NOT DELETE', 'test', NS_MAIN )['title'];
		$pages[] = $this->insertPage( 'Bad article 2', 'test', NS_MAIN )['title'];
		$this->setTemporaryHook(
			"NukeDeletePage",
			static function ( Title $title, $reason, &$deletionResult ) use ( &$wasCalled ) {
				$wasCalled = true;
				$deletionResult = $title->getPrefixedText() !== "DO NOT DELETE";
				return $title->getPrefixedText() === "Bad article 2";
			}
		);

		$admin = $this->getTestSysop()->getUser();
		$request = new FauxRequest( [
			'action' => 'delete',
			'wpDeleteReasonList' => 'Reason',
			'wpReason' => 'Reason',
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $admin->getRequest()->getSession()->getToken(),
		], true );
		$performer = new UltimateAuthority( $admin );

		[ $html ] = $this->executeSpecialPage( '', $request, 'qqx', $performer );

		$this->assertTrue( $wasCalled );
		$this->assertStringContainsString( '(nuke-deleted: Bad article)', $html );
		$this->assertStringContainsString( '(nuke-not-deleted: DO NOT DELETE)', $html );
		$this->assertStringContainsString( '(nuke-deletion-queued: Bad article 2)', $html );
	}

	public function testOldDeleteProtectedPage() {
		$pages = [];
		$pages[] = $this->insertPage( 'Page123', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Page456', 'Test', NS_MAIN )[ 'title' ];
		$pages[] = $this->insertPage( 'Page789', 'Test', NS_MAIN )[ 'title' ];

		$this->overrideConfigValues( [
			"GroupPermissions" => [
				"testgroup" => [
					"nuke" => true,
					"delete" => true
				]
			]
		] );

		$services = $this->getServiceContainer();
		$page = $services->getWikiPageFactory()
			->newFromTitle( $pages[2] );
		$restrictions = [];
		foreach ( $services->getRestrictionStore()->listApplicableRestrictionTypes( $page ) as $type ) {
			$restrictions[$type] = "sysop";
		}
		$cascade = false;
		$page->doUpdateRestrictions(
			$restrictions,
			[],
			$cascade,
			"test",
			$this->getTestSysop()->getUser()
		);

		$testUser = $this->getTestUser( [ "testgroup" ] );

		$request = new FauxRequest( [
			'action' => SpecialNuke::ACTION_DELETE,
			'wpDeleteReasonList' => 'other',
			'wpReason' => 'Reason',
			'pages' => $pages,
			'wpFormIdentifier' => 'nukelist',
			'wpEditToken' => $testUser->getUser()->getEditToken(),
		], true );

		$this->expectException( PermissionsError::class );
		$this->executeSpecialPage( '', $request, 'qqx', $testUser->getAuthority() );

		foreach ( $pages as $checkedPage ) {
			$this->assertTrue( $checkedPage->exists() );
		}
	}

	public function testSearchSubpages() {
		$user = $this->getTestUser()->getUser();
		$username = $user->getName();
		$truncatedUsername = substr( $username, 0, 2 );

		$searchResults1 = $this->newSpecialPage()
			->prefixSearchSubpages( $truncatedUsername, 10, 0 );

		$this->assertArrayContains( [ $username ], $searchResults1 );

		$searchResults2 = $this->newSpecialPage()
			->prefixSearchSubpages( "", 10, 0 );

		$this->assertCount( 0, $searchResults2 );
	}

	/**
	 * Upload a test file.
	 *
	 * @param ?User|null $user
	 * @return array Title object and page id
	 */
	private function uploadTestFile( ?User $user = null ): array {
		$exampleFilePath = realpath( __DIR__ . "/../../assets/Example.png" );
		$tempFilePath = $this->getNewTempFile();
		copy( $exampleFilePath, $tempFilePath );

		$title = Title::makeTitle( NS_FILE, "Example " . rand() . ".png" );
		$request = new FauxRequest( [], true );
		$request->setUpload( 'wpUploadFile', [
			'name' => $title->getText(),
			'type' => 'image/png',
			'tmp_name' => $tempFilePath,
			'size' => filesize( $tempFilePath ),
			'error' => UPLOAD_ERR_OK
		] );
		$upload = UploadFromFile::createFromRequest( $request );
		$uploadStatus = $upload->performUpload(
			"test",
			false,
			false,
			$user ?? $this->getTestUser( "user" )->getUser()
		);
		$this->assertTrue( $uploadStatus->isOK() );
		$this->getServiceContainer()->getJobRunner()->run( [] );

		return [
			'title' => $title,
			'id' => $title->getId()
		];
	}

	private function getDeleteLogHtml(): string {
		$services = $this->getServiceContainer();
		// TODO: Make this use qqx so tests can be checked against system message keys.
		$specialLog = $services->getSpecialPageFactory()->getPage( 'Log' );
		$specialLog->execute( "delete" );
		return $specialLog->getOutput()->getHTML();
	}

}

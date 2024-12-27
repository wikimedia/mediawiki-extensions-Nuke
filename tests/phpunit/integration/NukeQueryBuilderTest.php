<?php

namespace MediaWiki\Extension\Nuke\Test\Unit;

use LogicException;
use MediaWiki\Extension\Nuke\NukeQueryBuilder;
use MediaWiki\Extension\Nuke\Test\NukeIntegrationTest;
use MediaWikiIntegrationTestCase;

/**
 * Tests for the {@link NukeQueryBuilder} class.
 *
 * @group Database
 * @covers \MediaWiki\Extension\Nuke\NukeQueryBuilder
 */
class NukeQueryBuilderTest extends MediaWikiIntegrationTestCase {

	use NukeIntegrationTest;

	private function newQueryBuilder( string $table ) {
		$serviceContainer = $this->getServiceContainer();
		$dbr = $serviceContainer->getConnectionProvider()->getReplicaDatabase();
		$config = $serviceContainer->getMainConfig();
		$namespaceInfo = $serviceContainer->getNamespaceInfo();
		$contentLanguage = $serviceContainer->getContentLanguage();

		return new NukeQueryBuilder( $dbr, $config, $namespaceInfo, $contentLanguage, $table );
	}

	public function testConstructRevision() {
		$this->expectNotToPerformAssertions();
		$this->newQueryBuilder( NukeQueryBuilder::TABLE_REVISION );
	}

	public function testConstructRecentchanges() {
		$this->expectNotToPerformAssertions();
		$this->newQueryBuilder( NukeQueryBuilder::TABLE_RECENTCHANGES );
	}

	public function testConstructUnknownTable() {
		$this->expectException( LogicException::class );
		$this->newQueryBuilder( 'archive' );
	}

	public function testFilterActor() {
		$user1 = $this->getMutableTestUser();
		$user2 = $this->getMutableTestUser();

		$this->editPage(
			'Page1',
			'',
			'',
			NS_MAIN,
			$user1->getAuthority()
		);
		$this->editPage(
			'Page2',
			'',
			'',
			NS_MAIN,
			$user2->getAuthority()
		);

		$rows = $this->newQueryBuilder( NukeQueryBuilder::TABLE_REVISION )
			->filterActor( $user1->getAuthority() )
			->build()
			->caller( __METHOD__ )
			->fetchResultSet();

		$this->assertCount( 1, $rows );
		$row = $rows->fetchObject();
		$this->assertSame( 'Page1', $row->page_title );

		// Calling this with an actor string array should return the same result
		$rows = $this->newQueryBuilder( NukeQueryBuilder::TABLE_REVISION )
			->filterActor( [ $user1->getAuthority() ] )
			->build()
			->caller( __METHOD__ )
			->fetchResultSet();

		$this->assertCount( 1, $rows );
		$row = $rows->fetchObject();
		$this->assertSame( 'Page1', $row->page_title );
	}

	public function testFilterToTimestamp() {
		$time = time();
		$this->editPageAtTime( 'Page1', '', '', $time - 86400 * 2 );
		$this->editPage( 'Page2', '' );
		$rows = $this->newQueryBuilder( NukeQueryBuilder::TABLE_REVISION )
			->filterToTimestamp( time() - 86400 )
			->build()
			->caller( __METHOD__ )
			->fetchResultSet();

		$this->assertCount( 1, $rows );
		$row = $rows->fetchObject();
		$this->assertSame( 'Page1', $row->page_title );

		// Rebuild recent changes before doing recentchanges table queries
		$this->rebuildRecentChanges();

		$rows = $this->newQueryBuilder( NukeQueryBuilder::TABLE_RECENTCHANGES )
			->filterToTimestamp( time() - 86400 )
			->build()
			->caller( __METHOD__ )
			->fetchResultSet();

		$this->assertCount( 1, $rows );
		$row = $rows->fetchObject();
		$this->assertSame( 'Page1', $row->page_title );
	}

}

<?php

namespace MediaWiki\Extension\Nuke\Test;

use MediaWiki\Permissions\Authority;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PurgeRecentChanges;
use RebuildRecentchanges;
use UploadFromFile;

trait NukeIntegrationTest {

	/**
	 * Rebuild the recent changes table.
	 *
	 * @param int|null $from The timestamp to start rebuilding from.
	 * @param int|null $to The timestamp to stop rebuilding to.
	 * @return void
	 * @throws \JobQueueError
	 */
	private function rebuildRecentChanges( ?int $from = null, ?int $to = null ) {
		$rebuildRecentchanges = new RebuildRecentchanges();
		$rebuildRecentchanges->loadWithArgv( [ "--quiet", "--batch-size", "9999999" ] );
		$purgeRecentchanges = new PurgeRecentChanges();
		$purgeRecentchanges->loadWithArgv( [ "--quiet", "--batch-size", "9999999" ] );

		if ( $from !== null ) {
			$rebuildRecentchanges->setOption( 'from', $from );
		}
		if ( $to !== null ) {
			$rebuildRecentchanges->setOption( 'to', $to );
		}
		$purgeRecentchanges->execute();
		$rebuildRecentchanges->execute();

		$this->getServiceContainer()->getJobRunner()->run( [] );
		$connectionProvider = $this->getServiceContainer()->getConnectionProvider();
		$connectionProvider->commitAndWaitForReplication(
			__METHOD__,
			$connectionProvider->getEmptyTransactionTicket( __METHOD__ )
		);
	}

	/**
	 * Edit a page and also overwrite the timestamp for the revision.
	 *
	 * @param string $title
	 * @param string $content
	 * @param string $summary
	 * @param int $timestamp
	 * @param int|null $defaultNs
	 * @param Authority|null $performer
	 * @return void
	 */
	private function editPageAtTime(
		string $title,
		string $content,
		string $summary,
		int $timestamp,
		?int $defaultNs = NS_MAIN,
		?Authority $performer = null
	) {
		$pageStatus = $this->editPage( $title, $content, $summary, $defaultNs, $performer );
		if ( !$pageStatus->isGood() ) {
			$this->fail( "Failed to create page: $title" );
		}

		// Prepare database connection and update query builder
		$dbw = $this->getServiceContainer()->getConnectionProvider()->getPrimaryDatabase();
		$updateQueryBuilder = $dbw->newUpdateQueryBuilder()
			->update( 'revision' )
			->caller( __METHOD__ );

		// Manually change the rev_timestamp of the revision in the database.
		( clone $updateQueryBuilder )
			->set( [ 'rev_timestamp' => $dbw->timestamp( $timestamp ) ] )
			->where( [
				'rev_id' => $pageStatus->getNewRevision()->getId()
			] )->execute();
	}

	/**
	 * Upload a test file.
	 *
	 * @param ?User|null $user
	 * @return array Title object and page id
	 */
	private function uploadTestFile( ?User $user = null ): array {
		$exampleFilePath = realpath( __DIR__ . "/../assets/Example.png" );
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

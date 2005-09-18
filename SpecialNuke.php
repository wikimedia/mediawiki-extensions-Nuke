<?php

if( !defined( 'MEDIAWIKI' ) )
	die( 'Not an entry point.' );

$wgExtensionFunctions[] = 'wfSetupNuke';

$wgGroupPermissions['sysop']['nuke'] = true;
$wgAvailableRights[] = 'nuke';

function wfSetupNuke() {
	require_once( 'SpecialPage.php' );

	global $wgMessageCache;
	$wgMessageCache->addMessages(
		array(
			'nuke' => 'Mass delete',
			'nuke-nopages' => "No new pages by [[Special:Contributions/$1|$1]] in recent changes.",
			'nuke-list' => "The following pages were recently created by [[Special:Contributions/$1|$1]]; put in a comment and hit the button to delete them.",
			'nuke-defaultreason' => "Mass removal of pages added by $1",
		)
	);
	
	SpecialPage::addPage( new SpecialPage( 'Nuke', 'nuke', /*listed*/ true, /*function*/ false, /*file*/ false ) );
}

function wfSpecialNuke( $par = '' ) {
	global $wgRequest;
	$target = $wgRequest->getText( 'target', $par );
	$form = new NukeForm( $target, $wgRequest );
	$form->run();
}

class NukeForm {
	function NukeForm( $target, $request ) {
		global $wgUser;
		$this->mTarget = $target;
		$this->mReason = $request->getText( 'wpReason',
			wfMsgForContent( 'nuke-defaultreason', $target ) );
		$this->mPosted = $request->wasPosted() &&
			$wgUser->matchEditToken( $request->getVal( 'wpEditToken' ) );
		if( $this->mPosted ) {
			$this->mPages = $request->getArray( 'pages' );
		}
	}
	
	function run() {
		if( $this->mPosted && $this->mPages ) {
			return $this->doDelete( $this->mPages, $this->mReason );
		}
		if( $this->mTarget != '' ) {
			$this->listForm( $this->mTarget, $this->mReason );
		} else {
			$this->promptForm();
		}
	}
	
	function promptForm() {
		global $wgUser, $wgOut;
		$sk =& $wgUser->getSkin();
		
		$nuke = Title::makeTitle( NS_SPECIAL, 'Nuke' );
		$submit = wfElement( 'input', array( 'type' => 'submit' ) );
		
		$wgOut->addWikiText( "This tool allows for mass deletions of pages recently added by a given user or IP. Input the IP to get a list of things to delete:" );
		$wgOut->addHTML( wfElement( 'form', array(
				'action' => $nuke->getLocalURL( 'action=submit' ),
				'method' => 'post' ),
				null ) .
			wfElement( 'input', array(
				'type' => 'text',
				'size' => 40,
				'name' => 'target' ) ) .
			"\n$submit\n" );
		
		$wgOut->addHTML( "</form>" );
	}
	
	function listForm( $username, $reason ) {
		global $wgUser, $wgOut, $wgLang;

		$pages = $this->getNewPages( $username );
		$escapedName = wfEscapeWIkiText( $username );
		if( count( $pages ) == 0 ) {
			$wgOut->addWikiText( wfMsg( 'nuke-nopages', $escapedName ) );
			return $this->promptForm();
		}
		$wgOut->addWikiText( wfMsg( 'nuke-list', $escapedName ) );
		
		$nuke = Title::makeTitle( NS_SPECIAL, 'Nuke' );
		$submit = wfElement( 'input', array( 'type' => 'submit' ) );
		
		$wgOut->addHTML( wfElement( 'form', array(
			'action' => $nuke->getLocalURL( 'action=delete' ),
			'method' => 'post' ),
			null ) .
			"\n<div>" .
			wfMsgHtml( 'deletecomment' ) . ': ' .
			wfElement( 'input', array(
				'name' => 'wpReason',
				'value' => $reason,
				'size' => 60 ) ) .
			"</div>\n" .
			$submit .
			wfElement( 'input', array(
				'type' => 'hidden',
				'name' => 'wpEditToken',
				'value' => $wgUser->editToken() ) ) .
			"\n<ul>\n" );
		
		$sk =& $wgUser->getSkin();
		foreach( $pages as $info ) {
			list( $title, $edits ) = $info;
			$wgOut->addHTML( '<li>' .
				wfElement( 'input', array(
					'type' => 'checkbox',
					'name' => "pages[]",
					'value' => $title->getPrefixedDbKey(),
					'checked' => 'checked' ) ) .
				'&nbsp;' .
				$sk->makeKnownLinkObj( $title ) .
				'&nbsp;(' .
				$sk->makeKnownLinkObj( $title, wfMsgHtml( 'nchanges', $wgLang->formatNum( $edits ) ), 'action=history' ) .
				")</li>\n" );
		}
		$wgOut->addHTML( "</ul>\n$submit</form>" );
	}
	
	function getNewPages( $username ) {
		$fname = 'NukeForm::getNewPages';
		$dbr =& wfGetDB( DB_SLAVE );
		$result = $dbr->select( array( 'recentchanges', 'revision' ),
			array( 'rc_namespace', 'rc_title', 'rc_timestamp', 'COUNT(rev_id) AS edits' ),
			array(
				'rc_user_text' => $username,
				'rc_new' => 1,
				'rc_cur_id=rev_page' ),
			$fname,
			array(
				'ORDER BY' => 'rc_timestamp DESC',
				'GROUP BY' => 'rev_page' ) );
		$pages = array();
		while( $row = $dbr->fetchObject( $result ) ) {
			$pages[] = array( Title::makeTitle( $row->rc_namespace, $row->rc_title ), $row->edits );
		}
		$dbr->freeResult( $result );
		return $pages;
	}
	
	function doDelete( $pages, $reason ) {
		foreach( $pages as $page ) {
			$title = Title::newFromUrl( $page );
			$article = new Article( $title );
			$article->doDelete( $reason );
		}
	}
}

?>
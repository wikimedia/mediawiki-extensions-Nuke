<?php

class SpecialNuke extends SpecialPage {
    // Code imported from DeletePagesForGood extension (https://github.com/wikimedia/mediawiki-extensions-DeletePagesForGood)
    public function deletePermanently( Title $title ) {
		$ns = $title->getNamespace();
		$t = $title->getDBkey();
		$id = $title->getArticleID();
		$cats = $title->getParentCategories();

		$dbw = wfGetDB( DB_MASTER );

		$dbw->startAtomic( __METHOD__ );

		/*
		 * First delete entries, which are in direct relation with the page:
		 */

		# delete redirect...
		$dbw->delete( 'redirect', [ 'rd_from' => $id ], __METHOD__ );

		# delete external link...
		$dbw->delete( 'externallinks', [ 'el_from' => $id ], __METHOD__ );

		# delete language link...
		$dbw->delete( 'langlinks', [ 'll_from' => $id ], __METHOD__ );

		if ( !$GLOBALS['wgDBtype'] == "postgres" ) {
			# delete search index...
			$dbw->delete( 'searchindex', [ 'si_page' => $id ], __METHOD__ );
		}

		# Delete restrictions for the page
		$dbw->delete( 'page_restrictions', [ 'pr_page' => $id ], __METHOD__ );

		# Delete page Links
		$dbw->delete( 'pagelinks', [ 'pl_from' => $id ], __METHOD__ );

		# delete category links
		$dbw->delete( 'categorylinks', [ 'cl_from' => $id ], __METHOD__ );

		# delete template links
		$dbw->delete( 'templatelinks', [ 'tl_from' => $id ], __METHOD__ );

		# read text entries for all revisions and delete them.
		$res = $dbw->select( 'revision', 'rev_text_id', "rev_page=$id" );

		foreach ( $res as $row ) {
			$value = $row->rev_text_id;
			$dbw->delete( 'text', [ 'old_id' => $value ], __METHOD__ );
		}

		# In the table 'revision' : Delete all the revision of the page where 'rev_page' = $id
		$dbw->delete( 'revision', [ 'rev_page' => $id ], __METHOD__ );

		# delete image links
		$dbw->delete( 'imagelinks', [ 'il_from' => $id ], __METHOD__ );

		/*
		 * then delete entries which are not in direct relation with the page:
		 */

		# Clean up recentchanges entries...
		$dbw->delete( 'recentchanges', [
			'rc_namespace' => $ns,
			'rc_title' => $t
		], __METHOD__ );

		# read text entries for all archived pages and delete them.
		$res = $dbw->select( 'archive', 'ar_text_id', [
			'ar_namespace' => $ns,
			'ar_title' => $t
		] );

		foreach ( $res as $row ) {
			$value = $row->ar_text_id;
			$dbw->delete( 'text', [ 'old_id' => $value ], __METHOD__ );
		}

		# Clean archive entries...
		$dbw->delete( 'archive', [
			'ar_namespace' => $ns,
			'ar_title' => $t
		], __METHOD__ );

		# Clean up log entries...
		$dbw->delete( 'logging', [
			'log_namespace' => $ns,
			'log_title' => $t
		], __METHOD__ );

		# Clean up watchlist...
		$dbw->delete( 'watchlist', [
			'wl_namespace' => $ns,
			'wl_title' => $t
		], __METHOD__ );

		# In the table 'page' : Delete the page entry
		$dbw->delete( 'page', [ 'page_id' => $id ], __METHOD__ );

		/*
		 * If the article belongs to a category, update category counts
		 */
		if ( !empty( $cats ) ) {
			foreach ( $cats as $parentcat => $currentarticle ) {
				$catname = preg_split( '/:/', $parentcat, 2 );
				$cat = Category::newFromName( $catname[1] );
				if ( !is_object( $cat ) ) {
					// Blank error to allow us to continue
				} else {
					$cat->refreshCounts();
				}
			}
		}

		/*
		 * If an image is beeing deleted, some extra work needs to be done
		 */
		if ( $ns == NS_FILE ) {
			$file = wfFindFile( $t );

			if ( $file ) {
				# Get all filenames of old versions:
				$fields = OldLocalFile::selectFields();
				$res = $dbw->select( 'oldimage', $fields, [ 'oi_name' => $t ] );

				foreach ( $res as $row ) {
					$oldLocalFile = OldLocalFile::newFromRow( $row, $file->repo );
					$path = $oldLocalFile->getArchivePath() . '/' . $oldLocalFile->getArchiveName();

					try {
						unlink( $path );
					}
					catch ( Exception $e ) {
						return $e->getMessage();
					}
				}

				$path = $file->getLocalRefPath();

				try {
					$file->purgeThumbnails();
					unlink( $path );
				} catch ( Exception $e ) {
					return $e->getMessage();
				}
			}

			# clean the filearchive for the given filename:
			$dbw->delete( 'filearchive', [ 'fa_name' => $t ], __METHOD__ );

			# Delete old db entries of the image:
			$dbw->delete( 'oldimage', [ 'oi_name' => $t ], __METHOD__ );

			# Delete archive entries of the image:
			$dbw->delete( 'filearchive', [ 'fa_name' => $t ], __METHOD__ );

			# Delete image entry:
			$dbw->delete( 'image', [ 'img_name' => $t ], __METHOD__ );

			// $dbw->endAtomic( __METHOD__ );

			$linkCache = LinkCache::singleton();
			$linkCache->clear();
		}
		$dbw->endAtomic( __METHOD__ );
		return true;
	}
	
	public function __construct() {
		parent::__construct( 'Nuke', 'nuke' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * @param null|string $par
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->checkPermissions();
		$this->checkReadOnly();
		$this->outputHeader();

		$currentUser = $this->getUser();
		if ( $currentUser->isBlocked() ) {
			$block = $currentUser->getBlock();
			throw new UserBlockedError( $block );
		}

		$req = $this->getRequest();
		$target = trim( $req->getText( 'target', $par ) );

		// Normalise name
		if ( $target !== '' ) {
			$user = User::newFromName( $target );
			if ( $user ) {
				$target = $user->getName();
			}
		}

		$msg = $target === '' ?
			$this->msg( 'nuke-multiplepeople' )->inContentLanguage()->text() :
			$this->msg( 'nuke-defaultreason', $target )->
			inContentLanguage()->text();
		$reason = $req->getText( 'wpReason', $msg );

		$limit = $req->getInt( 'limit', 500 );
		$namespace = $req->getVal( 'namespace' );
		$namespace = ctype_digit( $namespace ) ? (int)$namespace : null;

		if ( $req->wasPosted()
			&& $currentUser->matchEditToken( $req->getVal( 'wpEditToken' ) )
		) {
			if ( $req->getVal( 'action' ) === 'delete' ) {
				$pages = $req->getArray( 'pages' );

				if ( $pages ) {
					$this->doDelete( $pages, $reason );

					return;
				}
			} elseif ( $req->getVal( 'action' ) === 'submit' ) {
				$this->listForm( $target, $reason, $limit, $namespace );
			} else {
				$this->promptForm();
			}
		} elseif ( $target === '' ) {
			$this->promptForm();
		} else {
			$this->listForm( $target, $reason, $limit, $namespace );
		}
	}

	/**
	 * Prompt for a username or IP address.
	 *
	 * @param string $userName
	 */
	protected function promptForm( $userName = '' ) {
		$out = $this->getOutput();

		$out->addWikiMsg( 'nuke-tools' );

		$formDescriptor = [
			'nuke-target' => [
				'id' => 'nuke-target',
				'default' => $userName,
				'label' => $this->msg( 'nuke-userorip' )->text(),
				'type' => 'user',
				'name' => 'target'
			],
			'nuke-pattern' => [
				'id' => 'nuke-pattern',
				'label' => $this->msg( 'nuke-pattern' )->text(),
				'maxLength' => 40,
				'type' => 'text',
				'name' => 'pattern'
			],
			'namespace' => [
				'id' => 'nuke-namespace',
				'type' => 'namespaceselect',
				'label' => $this->msg( 'nuke-namespace' )->text(),
				'all' => 'all',
				'name' => 'namespace'
			],
			'limit' => [
				'id' => 'nuke-limit',
				'maxLength' => 7,
				'default' => 500,
				'label' => $this->msg( 'nuke-maxpages' )->text(),
				'type' => 'int',
				'name' => 'limit'
			]
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setName( 'massdelete' )
			->setFormIdentifier( 'massdelete' )
			->setWrapperLegendMsg( 'nuke' )
			->setSubmitTextMsg( 'nuke-submit-user' )
			->setSubmitName( 'nuke-submit-user' )
			->setAction( $this->getPageTitle()->getLocalURL( 'action=submit' ) )
			->setMethod( 'post' )
			->addHiddenField( 'wpEditToken', $this->getUser()->getEditToken() )
			->prepareForm()
			->displayForm( false );
	}

	/**
	 * Display list of pages to delete.
	 *
	 * @param string $username
	 * @param string $reason
	 * @param int $limit
	 * @param int|null $namespace
	 */
	protected function listForm( $username, $reason, $limit, $namespace = null ) {
		$out = $this->getOutput();

		$pages = $this->getNewPages( $username, $limit, $namespace );

		if ( count( $pages ) === 0 ) {
			if ( $username === '' ) {
				$out->addWikiMsg( 'nuke-nopages-global' );
			} else {
				$out->addWikiMsg( 'nuke-nopages', $username );
			}

			$this->promptForm( $username );

			return;
		}

		$out->addModules( 'ext.nuke.confirm' );

		if ( $username === '' ) {
			$out->addWikiMsg( 'nuke-list-multiple' );
		} else {
			$out->addWikiMsg( 'nuke-list', $username );
		}

		$nuke = $this->getPageTitle();

		$out->addHTML(
			Xml::openElement( 'form', [
					'action' => $nuke->getLocalURL( 'action=delete' ),
					'method' => 'post',
					'name' => 'nukelist' ]
			) .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			Xml::tags( 'p',
				null,
				Xml::inputLabel(
					$this->msg( 'deletecomment' )->text(), 'wpReason', 'wpReason', 70, $reason
				)
			)
		);

		// Select: All, None, Invert
		// ListToggle was introduced in 1.27, old code kept for B/C
		if ( class_exists( 'ListToggle' ) ) {
			$listToggle = new ListToggle( $this->getOutput() );
			$selectLinks = $listToggle->getHTML();
		} else {
			$out->addModules( 'ext.nuke' );

			$links = [];
			$links[] = '<a href="#" id="toggleall">' .
				$this->msg( 'powersearch-toggleall' )->escaped() . '</a>';
			$links[] = '<a href="#" id="togglenone">' .
				$this->msg( 'powersearch-togglenone' )->escaped() . '</a>';
			$links[] = '<a href="#" id="toggleinvert">' .
				$this->msg( 'nuke-toggleinvert' )->escaped() . '</a>';

			$selectLinks = Xml::tags( 'p',
				null,
				$this->msg( 'nuke-select' )
					->rawParams( $this->getLanguage()->commaList( $links ) )->escaped()
			);
		}

		$out->addHTML(
			$selectLinks .
			'<ul>'
		);

		$wordSeparator = $this->msg( 'word-separator' )->escaped();
		$commaSeparator = $this->msg( 'comma-separator' )->escaped();

		$linkRenderer = $this->getLinkRenderer();
		foreach ( $pages as $info ) {
			/**
			 * @var $title Title
			 */
			list( $title, $userName ) = $info;

			$image = $title->inNamespace( NS_FILE ) ? wfLocalFile( $title ) : false;
			$thumb = $image && $image->exists() ?
				$image->transform( [ 'width' => 120, 'height' => 120 ], 0 ) :
				false;

			$userNameText = $userName ?
				$this->msg( 'nuke-editby', $userName )->parse() . $commaSeparator :
				'';
			$changesLink = $linkRenderer->makeKnownLink(
				$title,
				$this->msg( 'nuke-viewchanges' )->text(),
				[],
				[ 'action' => 'history' ]
			);
			$out->addHTML( '<li>' .
				Xml::check(
					'pages[]',
					true,
					[ 'value' => $title->getPrefixedDBkey() ]
				) . '&#160;' .
				( $thumb ? $thumb->toHtml( [ 'desc-link' => true ] ) : '' ) .
				$linkRenderer->makeKnownLink( $title ) . $wordSeparator .
				$this->msg( 'parentheses' )->rawParams( $userNameText . $changesLink )->escaped() .
				"</li>\n" );
		}

		$out->addHTML(
			"</ul>\n" .
			Xml::submitButton( $this->msg( 'nuke-submit-delete' )->text() ) .
			'</form>'
		);
	}

	/**
	 * Gets a list of new pages by the specified user or everyone when none is specified.
	 *
	 * @param string $username
	 * @param int $limit
	 * @param int|null $namespace
	 *
	 * @return array
	 */
	protected function getNewPages( $username, $limit, $namespace = null ) {
		$dbr = wfGetDB( DB_REPLICA );

		$what = [
			'rc_namespace',
			'rc_title',
			'rc_timestamp',
		];

		$where = [ "(rc_new = 1) OR (rc_log_type = 'upload' AND rc_log_action = 'upload')" ];

		if ( $username === '' ) {
			$what[] = 'rc_user_text';
		} else {
			$where['rc_user_text'] = $username;
		}

		if ( $namespace !== null ) {
			$where['rc_namespace'] = $namespace;
		}

		$pattern = $this->getRequest()->getText( 'pattern' );
		if ( !is_null( $pattern ) && trim( $pattern ) !== '' ) {
			// $pattern is a SQL pattern supporting wildcards, so buildLike
			// will not work.
			$where[] = 'rc_title LIKE ' . $dbr->addQuotes( $pattern );
		}
		$group = implode( ', ', $what );

		$result = $dbr->select( 'recentchanges',
			$what,
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'rc_timestamp DESC',
				'GROUP BY' => $group,
				'LIMIT' => $limit
			]
		);

		$pages = [];

		foreach ( $result as $row ) {
			$pages[] = [
				Title::makeTitle( $row->rc_namespace, $row->rc_title ),
				$username === '' ? $row->rc_user_text : false
			];
		}

		// Allows other extensions to provide pages to be nuked that don't use
		// the recentchanges table the way mediawiki-core does
		Hooks::run( 'NukeGetNewPages', [ $username, $pattern, $namespace, $limit, &$pages ] );

		// Re-enforcing the limit *after* the hook because other extensions
		// may add and/or remove pages. We need to make sure we don't end up
		// with more pages than $limit.
		if ( count( $pages ) > $limit ) {
			$pages = array_slice( $pages, 0, $limit );
		}

		return $pages;
	}

	/**
	 * Does the actual deletion of the pages.
	 *
	 * @param array $pages The pages to delete
	 * @param string $reason
	 * @throws PermissionsError
	 */
	protected function doDelete( array $pages, $reason ) {
		$res = [];

		foreach ( $pages as $page ) {
			$title = Title::newFromText( $page );

			$deletionResult = false;
			if ( !Hooks::run( 'NukeDeletePage', [ $title, $reason, &$deletionResult ] ) ) {
				if ( $deletionResult ) {
					$res[] = $this->msg( 'nuke-deleted', $title->getPrefixedText() )->parse();
				} else {
					$res[] = $this->msg( 'nuke-not-deleted', $title->getPrefixedText() )->parse();
				}
				continue;
			}

			$file = $title->getNamespace() === NS_FILE ? wfLocalFile( $title ) : false;
			$permission_errors = $title->getUserPermissionsErrors( 'delete', $this->getUser() );

			if ( $permission_errors !== [] ) {
				throw new PermissionsError( 'delete', $permission_errors );
			}

			if ( $file ) {
				$oldimage = null; // Must be passed by reference
				$ok = FileDeleteForm::doDelete( $title, $file, $oldimage, $reason, false )->isOK();
			} else {
				$article = new Article( $title, 0 );
				$ok = $article->doDeleteArticle( $reason );
			}

			if ( $ok ) {
				$res[] = $this->msg( 'nuke-deleted', $title->getPrefixedText() )->parse();
			} else {
				$res[] = $this->msg( 'nuke-not-deleted', $title->getPrefixedText() )->parse();
			}
			
			$this->deletePermanently($title);
		}

		$this->getOutput()->addHTML( "<ul>\n<li>" . implode( "</li>\n<li>", $res ) . "</li>\n</ul>\n" );
		$this->getOutput()->addWikiMsg( 'nuke-delete-more' );
	}

	/**
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		if ( !class_exists( 'UserNamePrefixSearch' ) ) { // check for version 1.27
			return [];
		}
		$user = User::newFromName( $search );
		if ( !$user ) {
			// No prefix suggestion for invalid user
			return [];
		}
		// Autocomplete subpage as user list - public to allow caching
		return UserNamePrefixSearch::search( 'public', $search, $limit, $offset );
	}

	protected function getGroupName() {
		return 'pagetools';
	}
}

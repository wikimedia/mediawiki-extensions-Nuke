<?php

namespace MediaWiki\Extension\Nuke;

use DateTime;
use DeletePageJob;
use ErrorPageError;
use JobQueueGroup;
use MediaWiki\CheckUser\Services\CheckUserTemporaryAccountsByIPLookup;
use MediaWiki\Extension\Nuke\Form\SpecialNukeHTMLFormUIRenderer;
use MediaWiki\Extension\Nuke\Form\SpecialNukeUIRenderer;
use MediaWiki\Extension\Nuke\Hooks\NukeHookRunner;
use MediaWiki\Language\Language;
use MediaWiki\Page\File\FileDeleteForm;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Request\WebRequest;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserNamePrefixSearch;
use MediaWiki\User\UserNameUtils;
use PermissionsError;
use RepoGroup;
use UserBlockedError;
use Wikimedia\IPUtils;
use Wikimedia\Rdbms\IConnectionProvider;

class SpecialNuke extends SpecialPage {

	/** @var NukeHookRunner|null */
	private $hookRunner;

	private JobQueueGroup $jobQueueGroup;
	private IConnectionProvider $dbProvider;
	private PermissionManager $permissionManager;
	private RepoGroup $repoGroup;
	private UserFactory $userFactory;
	private UserOptionsLookup $userOptionsLookup;
	private UserNamePrefixSearch $userNamePrefixSearch;
	private UserNameUtils $userNameUtils;
	private NamespaceInfo $namespaceInfo;
	private Language $contentLanguage;
	/** @var CheckUserTemporaryAccountsByIPLookup|null */
	private $checkUserTemporaryAccountsByIPLookup = null;

	/**
	 * Action keyword for the "prompt" step.
	 */
	public const ACTION_PROMPT = 'prompt';
	/**
	 * Action keyword for the "list" step.
	 */
	public const ACTION_LIST = 'list';
	/**
	 * Action keyword for the "confirm" step.
	 */
	public const ACTION_CONFIRM = 'confirm';
	/**
	 * Action keyword for the "delete/results" step.
	 */
	public const ACTION_DELETE = 'delete';

	/**
	 * Separator for the hidden "page list" fields.
	 */
	public const PAGE_LIST_SEPARATOR = '|';

	/**
	 * Separator for the namespace list. This constant comes from the separator used by
	 * HTMLNamespacesMultiselectField.
	 */
	public const NAMESPACE_LIST_SEPARATOR = "\n";

	/**
	 * @inheritDoc
	 */
	public function __construct(
		JobQueueGroup $jobQueueGroup,
		IConnectionProvider $dbProvider,
		PermissionManager $permissionManager,
		RepoGroup $repoGroup,
		UserFactory $userFactory,
		UserOptionsLookup $userOptionsLookup,
		UserNamePrefixSearch $userNamePrefixSearch,
		UserNameUtils $userNameUtils,
		NamespaceInfo $namespaceInfo,
		Language $contentLanguage,
		$checkUserTemporaryAccountsByIPLookup = null
	) {
		parent::__construct( 'Nuke', 'nuke' );
		$this->jobQueueGroup = $jobQueueGroup;
		$this->dbProvider = $dbProvider;
		$this->permissionManager = $permissionManager;
		$this->repoGroup = $repoGroup;
		$this->userFactory = $userFactory;
		$this->userOptionsLookup = $userOptionsLookup;
		$this->userNamePrefixSearch = $userNamePrefixSearch;
		$this->userNameUtils = $userNameUtils;
		$this->namespaceInfo = $namespaceInfo;
		$this->contentLanguage = $contentLanguage;
		$this->checkUserTemporaryAccountsByIPLookup = $checkUserTemporaryAccountsByIPLookup;
	}

	/**
	 * @inheritDoc
	 * @codeCoverageIgnore
	 */
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
		$this->addHelpLink( 'Help:Extension:Nuke' );

		$currentUser = $this->getUser();
		$block = $currentUser->getBlock();

		// appliesToRight is presently a no-op, since there is no handling for `delete`,
		// and so will return `null`. `true` will be returned if the block actively
		// applies to `delete`, and both `null` and `true` should result in an error
		if ( $block && ( $block->isSitewide() ||
			( $block->appliesToRight( 'delete' ) !== false ) )
		) {
			throw new UserBlockedError( $block );
		}

		$req = $this->getRequest();
		$nukeContext = $this->getNukeContextFromRequest( $par );

		if ( $nukeContext->validatePrompt() !== true ) {
			// Something is wrong with filters. Immediately return the prompt form again.
			$this->showPromptForm( $nukeContext );
			return;
		}

		switch ( $nukeContext->getAction() ) {
			case self::ACTION_DELETE:
			case self::ACTION_CONFIRM:
				if ( !$req->wasPosted()
					|| !$currentUser->matchEditToken( $req->getVal( 'wpEditToken' ) )
				) {
					// If the form was not posted or the edit token didn't match, something
					// must have gone wrong. Show the prompt form again.
					$this->showPromptForm( $nukeContext );
					break;
				}

				if ( !$nukeContext->hasPages() ) {
					if ( !$nukeContext->hasOriginalPages() ) {
						// No pages were requested. This is an early confirm attempt without having
						// listed the pages at all. Show the list form again.
						$this->showPromptForm( $nukeContext );
					} else {
						// Pages were not requested but a page list exists. The user did not select any
						// pages. Show the list form again.
						$this->showListForm( $nukeContext );
					}
					break;
				}

				if ( $nukeContext->getAction() === self::ACTION_DELETE ) {
					$deletedPageStatuses = $this->doDelete( $nukeContext );
					$this->showResultPage( $nukeContext, $deletedPageStatuses );
				} else {
					$this->showConfirmForm( $nukeContext );
				}
				break;
			case self::ACTION_LIST:
				$this->showListForm( $nukeContext );
				break;
			default:
				$this->showPromptForm( $nukeContext );
				break;
		}
	}

	/**
	 * Return a list of temporary accounts that are known to have edited from the context's target.
	 * Calls to this method result in a log entry being generated for the logged-in user account
	 * making the request.
	 *
	 * @param NukeContext $context
	 * @return string[] A list of temporary account usernames associated with the IP address
	 */
	protected function getTempAccounts( NukeContext $context ): array {
		if ( !$this->checkUserTemporaryAccountsByIPLookup ) {
			return [];
		}
		$status = $this->checkUserTemporaryAccountsByIPLookup->get(
			$context->getTarget(),
			$this->getAuthority(),
			true
		);
		if ( $status->isGood() ) {
			return $status->getValue();
		}
		return [];
	}

	/**
	 * Load the Nuke context from request data ({@link SpecialPage::getRequest}).
	 *
	 * @param string|null $par
	 * @return NukeContext
	 */
	protected function getNukeContextFromRequest( ?string $par ): NukeContext {
		$req = $this->getRequest();

		$target = trim( $req->getText( 'target', $par ?? '' ) );

		// Normalise name
		if ( $target !== '' ) {
			$user = $this->userFactory->newFromName( $target );
			if ( $user ) {
				$target = $user->getName();
			}
		}

		$namespaces = $this->loadNamespacesFromRequest( $req );
		// Set $namespaces to null if it's empty
		if ( count( $namespaces ) == 0 ) {
			$namespaces = null;
		}

		$action = $req->getRawVal( 'action' );
		if ( !$action ) {
			if ( $target !== '' ) {
				// Target was supplied but action was not. Imply 'list' action.
				$action = self::ACTION_LIST;
			} else {
				$action = self::ACTION_PROMPT;
			}
		}

		// This uses a string value to avoid having to generate hundreds of hidden <input>s.
		$originalPages = explode(
			self::PAGE_LIST_SEPARATOR,
			$req->getText( 'originalPageList' )
		);
		if ( count( $originalPages ) == 1 && $originalPages[0] == "" ) {
			$originalPages = [];
		}

		return new NukeContext( [
			'requestContext' => $this->getContext(),
			'useTemporaryAccounts' => $this->checkUserTemporaryAccountsByIPLookup != null,

			'action' => $action,
			'target' => $target,
			'listedTarget' => trim( $req->getText( 'listedTarget', $target ) ),
			'pattern' => $req->getText( 'pattern' ),
			'limit' => $req->getInt( 'limit', 500 ),
			'namespaces' => $namespaces,

			'dateFrom' => $req->getText( 'wpdateFrom' ),
			'dateTo' => $req->getText( 'wpdateTo' ),

			'pages' => $req->getArray( 'pages', [] ),
			'originalPages' => $originalPages
		] );
	}

	/**
	 * Get the UI renderer for a given type.
	 *
	 * @param NukeContext $context
	 * @return SpecialNukeUIRenderer
	 */
	protected function getUIRenderer(
		NukeContext $context
	): SpecialNukeUIRenderer {
		// Permit overriding the UI type with the `?nukeUI=` query parameter.
		$formType = $this->getRequest()->getText( 'nukeUI' );
		if ( !$formType ) {
			$formType = $this->getConfig()->get( NukeConfigNames::UIType ) ?? 'htmlform';
		}

		// Possible values: 'codex', 'htmlform'
		switch ( $formType ) {
			// case 'codex': to be implemented (T153988)
			case 'htmlform':
			default:
				return new SpecialNukeHTMLFormUIRenderer(
					$context,
					$this,
					$this->repoGroup,
					$this->getLinkRenderer(),
					$this->namespaceInfo,
					$this->getLanguage()
				);
		}
	}

	/**
	 * Load namespaces from the provided request and return them as an array. This also performs
	 * validation, ensuring that only valid namespaces are returned.
	 *
	 * @param WebRequest $req The request
	 * @return array An array of namespace IDs
	 */
	private function loadNamespacesFromRequest( WebRequest $req ): array {
		$validNamespaces = $this->namespaceInfo->getValidNamespaces();

		return array_map(
			'intval', array_filter(
				explode( self::NAMESPACE_LIST_SEPARATOR, $req->getText( "namespace" ) ),
				static function ( $ns ) use ( $validNamespaces ) {
					return is_numeric( $ns ) && in_array( intval( $ns ), $validNamespaces );
				}
			)
		);
	}

	/**
	 * Does the user have the appropriate permissions and have they enabled in preferences?
	 * Adapted from MediaWiki\CheckUser\Api\Rest\Handler\AbstractTemporaryAccountHandler::checkPermissions
	 *
	 * @param User $currentUser
	 *
	 * @throws PermissionsError if the user does not have the 'checkuser-temporary-account' right
	 * @throws ErrorPageError if the user has not enabled the 'checkuser-temporary-account-enabled' preference
	 */
	private function assertUserCanAccessTemporaryAccounts( User $currentUser ) {
		if (
			!$currentUser->isAllowed( 'checkuser-temporary-account-no-preference' )
		) {
			if (
				!$currentUser->isAllowed( 'checkuser-temporary-account' )
			) {
				throw new PermissionsError( 'checkuser-temporary-account' );
			}
			if (
				!$this->userOptionsLookup->getOption(
					$currentUser,
					'checkuser-temporary-account-enable'
				)
			) {
				throw new ErrorPageError(
					$this->msg( 'checkuser-ip-contributions-permission-error-title' ),
					$this->msg( 'checkuser-ip-contributions-permission-error-description' )
				);
			}
		}
	}

	/**
	 * Prompt for a username or IP address.
	 *
	 * @param NukeContext $context
	 */
	public function showPromptForm( NukeContext $context ): void {
		$this->getUIRenderer( $context )
			->showPromptForm();
	}

	/**
	 * Display the prompt form and a list of pages to delete.
	 *
	 * @param NukeContext $context
	 */
	public function showListForm( NukeContext $context ): void {
		// Check for temporary accounts, if applicable.
		$tempAccounts = [];
		if (
			$this->checkUserTemporaryAccountsByIPLookup &&
			IPUtils::isValid( $context->getTarget() )
		) {
			// if the target is an ip address and temp account lookup is available,
			// list pages created by the ip user or by temp accounts associated with the ip address
			$this->assertUserCanAccessTemporaryAccounts( $this->getUser() );
			$tempAccounts = $this->getTempAccounts( $context );
		}

		// Get list of pages to show the user.
		$pages = $this->getNewPages( $context, $tempAccounts );

		$this->getUIRenderer( $context )
			->showListForm( $pages );
	}

	/**
	 * Display a page confirming all pages to be deleted.
	 *
	 * @param NukeContext $context
	 *
	 * @return void
	 */
	public function showConfirmForm( NukeContext $context ): void {
		$this->getUIRenderer( $context )
			->showConfirmForm();
	}

	/**
	 * Show the result page, showing what pages were deleted and what pages were skipped by the
	 * user.
	 *
	 * @param NukeContext $context
	 *   deletion. Can be either `"job"` to indicate that the page was queued for deletion, a
	 *   {@link Status} to indicate if the page was successfully deleted, or `false` if the user
	 *   did not select the page for deletion.
	 * @param (Status|string|boolean)[] $deletedPageStatuses The status for each page queued for
	 * @return void
	 */
	public function showResultPage( NukeContext $context, array $deletedPageStatuses ): void {
		$this->getUIRenderer( $context )
			->showResultPage( $deletedPageStatuses );
	}

	/**
	 * Gets a list of new pages by the specified user or everyone when none is specified.
	 *
	 * @param NukeContext $context
	 * @param string[] $tempAccounts Temporary accounts to search for. This is passed directly
	 *   instead of through context to ensure permissions checks happen first.
	 *
	 * @return array{0:Title,1:string|false}[]
	 */
	protected function getNewPages( NukeContext $context, array $tempAccounts = [] ): array {
		$dbr = $this->dbProvider->getReplicaDatabase();

		$nukeMaxAge = $context->getNukeMaxAge();

		$min = $context->getDateFrom();
		if ( !$min || $min->getTimestamp() < time() - $nukeMaxAge ) {
			// Requested $min is way too far in the past (or null). Set it to the earliest possible
			// value.
			$min = time() - $nukeMaxAge;
		} else {
			$min = $min->getTimestamp();
		}

		$max = $context->getDateTo();
		if ( $max ) {
			// Increment by 1 day to include all edits from that day.
			$max = ( clone $max )
				->modify( "+1 day" )
				->getTimestamp();
		}
		// $min and $max are int|null here.

		if ( $max && $max < $min ) {
			// Impossible range. Skip the query and fail gracefully.
			return [];
		}
		if ( $min > time() ) {
			// Improbable range (since revisions cannot be in the future).
			// Skip the query and fail gracefully.
			return [];
		}
		$maxPossibleDate = ( new DateTime() )
			->modify( "+1 day" )
			->getTimestamp();
		if ( $max > $maxPossibleDate ) {
			// Truncate to the current day, since there shouldn't be any future revisions.
			$max = $maxPossibleDate;
		}

		$target = $context->getTarget();
		if ( $target ) {
			// Enable revision table searches only when a target has been specified.
			// Running queries on the revision table when there's no actor causes timeouts, since
			// the entirety of the `page` table needs to be scanned. (T380846)
			$nukeQueryBuilder = new NukeQueryBuilder(
				$dbr,
				$this->getConfig(),
				$this->namespaceInfo,
				$this->contentLanguage,
				NukeQueryBuilder::TABLE_REVISION
			);
		} else {
			// Switch to `recentchanges` table searching when running an all-user search. (T380846)
			$nukeQueryBuilder = new NukeQueryBuilder(
				$dbr,
				$this->getConfig(),
				$this->namespaceInfo,
				$this->contentLanguage,
				NukeQueryBuilder::TABLE_RECENTCHANGES
			);
		}

		// Follow the `$wgNukeMaxAge` config variable, or the user-specified minimum date.
		$nukeQueryBuilder->filterFromTimestamp( $min );

		// Follow the user-specified maximum date, if applicable.
		if ( $max ) {
			$nukeQueryBuilder->filterToTimestamp( $max );
		}

		// Limit the number of rows that can be returned by the query.
		$limit = $context->getLimit();
		$nukeQueryBuilder->limit( $limit );

		// Filter by actors, if applicable.
		$nukeQueryBuilder->filterActor( array_filter( [ $target, ...$tempAccounts ] ) );

		// Filter by namespace, if applicable
		$namespaces = $context->getNamespaces();
		$nukeQueryBuilder->filterNamespaces( $namespaces );

		// Filter by pattern, if applicable
		$pattern = $context->getPattern();
		$nukeQueryBuilder->filterPattern(
			$pattern,
			$namespaces
		);

		$result = $nukeQueryBuilder
			->build()
			->caller( __METHOD__ )
			->fetchResultSet();
		/** @var array{0:Title,1:string|false}[] $pages */
		$pages = [];
		foreach ( $result as $row ) {
			$pages[] = [
				Title::makeTitle( $row->page_namespace, $row->page_title ),
				$row->actor_name
			];
		}

		// Allows other extensions to provide pages to be mass-deleted that
		// don't use the revision table the way mediawiki-core does.
		if ( $namespaces ) {
			foreach ( $namespaces as $namespace ) {
				$this->getNukeHookRunner()->onNukeGetNewPages(
					$target,
					$pattern,
					$namespace,
					$limit,
					$pages
				);
			}
		} else {
			$this->getNukeHookRunner()->onNukeGetNewPages(
				$target,
				$pattern,
				null,
				$limit,
				$pages
			);
		}

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
	 * @return array An associative array of statuses (or the string "job") keyed by the page title
	 * @throws PermissionsError
	 */
	protected function doDelete( NukeContext $context ): array {
		$statuses = [];
		$jobs = [];
		$user = $this->getUser();

		$reason = $context->getDeleteReason();
		$localRepo = $this->repoGroup->getLocalRepo();
		foreach ( $context->getPages() as $page ) {
			$title = Title::newFromText( $page );

			$deletionResult = false;
			if ( !$this->getNukeHookRunner()->onNukeDeletePage( $title, $reason, $deletionResult ) ) {
				$statuses[$title->getPrefixedDBkey()] = $deletionResult ?
					Status::newGood() :
					Status::newFatal(
						$this->msg( 'nuke-not-deleted' )
					);
				continue;
			}

			$permission_errors = $this->permissionManager->getPermissionErrors( 'delete', $user, $title );

			if ( $permission_errors !== [] ) {
				throw new PermissionsError( 'delete', $permission_errors );
			}

			$file = $title->getNamespace() === NS_FILE ? $localRepo->newFile( $title ) : false;
			if ( $file ) {
				// Must be passed by reference
				$oldimage = null;
				$status = FileDeleteForm::doDelete(
					$title,
					$file,
					$oldimage,
					$reason,
					false,
					$user
				);
			} else {
				$job = new DeletePageJob( [
					'namespace' => $title->getNamespace(),
					'title' => $title->getDBKey(),
					'reason' => $reason,
					'userId' => $user->getId(),
					'wikiPageId' => $title->getId(),
					'suppress' => false,
					'tags' => '["nuke"]',
					'logsubtype' => 'delete',
				] );
				$jobs[] = $job;
				$status = 'job';
			}

			$statuses[$title->getPrefixedDBkey()] = $status;
		}

		if ( $jobs ) {
			$this->jobQueueGroup->push( $jobs );
		}

		return $statuses;
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
		$search = $this->userNameUtils->getCanonical( $search );
		if ( !$search ) {
			// No prefix suggestion for invalid user
			return [];
		}

		// Autocomplete subpage as user list - public to allow caching
		return $this->userNamePrefixSearch
			->search( UserNamePrefixSearch::AUDIENCE_PUBLIC, $search, $limit, $offset );
	}

	/**
	 * Group Special:Nuke with pagetools
	 *
	 * @codeCoverageIgnore
	 * @return string
	 */
	protected function getGroupName() {
		return 'pagetools';
	}

	private function getNukeHookRunner(): NukeHookRunner {
		$this->hookRunner ??= new NukeHookRunner( $this->getHookContainer() );
		return $this->hookRunner;
	}
}

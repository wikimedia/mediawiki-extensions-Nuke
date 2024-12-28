<?php

namespace MediaWiki\Extension\Nuke;

use DeletePageJob;
use ErrorPageError;
use HtmlArmor;
use JobQueueGroup;
use MediaWiki\CheckUser\Services\CheckUserTemporaryAccountsByIPLookup;
use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Extension\Nuke\Hooks\NukeHookRunner;
use MediaWiki\Html\Html;
use MediaWiki\Html\ListToggle;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Language\Language;
use MediaWiki\MainConfigNames;
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
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\LikeMatch;
use Wikimedia\Rdbms\LikeValue;
use Wikimedia\Rdbms\SelectQueryBuilder;

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
		$nukeContext = $this->loadNukeContextFromRequest( $par );

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
	public function getTempAccounts( NukeContext $context ): array {
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
	protected function loadNukeContextFromRequest( ?string $par ): NukeContext {
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

			'pages' => $req->getArray( 'pages', [] ),
			'originalPages' => $originalPages
		] );
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
	 * Get the prompt form to be shown to the user. Should appear when both asking for initial
	 * data or showing the page list.
	 *
	 * @param NukeContext $context
	 * @return string
	 */
	protected function getPromptForm( NukeContext $context ): string {
		$this->getOutput()->addModuleStyles( [ 'ext.nuke.styles' ] );

		$formDescriptor = [
			'nuke-target' => [
				'id' => 'nuke-target',
				'default' => $context->getTarget(),
				'label' => $this->msg( 'nuke-userorip' )->text(),
				'type' => 'user',
				'ipallowed' => true,
				'name' => 'target',
				'autofocus' => true,
				'autocomplete' => 'off'
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
				'type' => 'namespacesmultiselect',
				'label' => $this->msg( 'nuke-namespace' )->text(),
				'help-messages' => [
					new HtmlArmor( '<noscript>' ),
					'nuke-namespace-noscript',
					new HtmlArmor( '</noscript>' )
				],
				'help-inline' => true,
				'exists' => true,
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

		$promptForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setFormIdentifier( 'massdelete' )
			// Suppressing default submit button to manually control button order.
			->suppressDefaultSubmit()
			->addButton( [
				'label-message' => 'nuke-submit-list',
				'name' => 'action',
				'value' => self::ACTION_LIST
			] )
			->addButton( [
				'classes' => [ 'mw-htmlform-submit' ],
				'label-message' => 'nuke-submit-continue',
				'name' => 'action',
				'value' => self::ACTION_CONFIRM,
				'flags' => [ 'primary', 'progressive' ]
			] );

		$validationResult = $context->validate();
		if ( $validationResult !== true ) {
			$promptForm->addFooterHtml( strval(
				new \OOUI\MessageWidget( [
					'classes' => [ 'ext-nuke-promptform-error' ],
					'type' => 'error',
					'label' => $validationResult
				] )
			) );
		}

		$promptForm->prepareForm();

		return $this->getFormFieldsetHtml( $promptForm );
	}

	/**
	 * Prompt for a username or IP address.
	 *
	 * @param NukeContext $context
	 */
	protected function showPromptForm( NukeContext $context ): void {
		$out = $this->getOutput();

		if ( $this->checkUserTemporaryAccountsByIPLookup ) {
			$out->addWikiMsg( 'nuke-tools-tempaccount' );
		} else {
			$out->addWikiMsg( 'nuke-tools' );
		}
		$out->addWikiMsg( 'nuke-tools-prompt' );

		$out->enableOOUI();
		$out->addHTML(
			$this->wrapForm( $this->getPromptForm(
				$context
			) )
		);
	}

	/**
	 * Display list of pages to delete.
	 *
	 * @param NukeContext $context
	 */
	protected function showListForm( NukeContext $context ): void {
		$target = $context->getTarget();
		$out = $this->getOutput();

		$tempAccounts = [];
		if ( $this->checkUserTemporaryAccountsByIPLookup ) {
			$out->addWikiMsg( 'nuke-tools-tempaccount' );
			if ( IPUtils::isValid( $target ) ) {
				// if the target is an ip addresss and temp account lookup is available,
				// list pages created by the ip user or by temp accounts associated with the ip address
				$this->assertUserCanAccessTemporaryAccounts( $this->getUser() );
				$tempAccounts = $this->getTempAccounts( $context );
			}
		} else {
			$out->addWikiMsg( 'nuke-tools' );
		}
		$out->addWikiMsg( 'nuke-tools-prompt' );

		$out->addModuleStyles( [ 'ext.nuke.styles', 'mediawiki.interface.helpers.styles' ] );
		$out->enableOOUI();
		$body = $this->getPromptForm( $context );

		$pages = $this->getNewPages( $context, $tempAccounts );
		if ( !$pages ) {
			$out->addHTML(
				$this->wrapForm( $body )
			);

			$out->addHTML( new \OOUI\MessageWidget( [
				'type' => 'warning',
				'label' => $this->msg( 'nuke-nopages-global' )->text(),
			] ) );
			return;
		}

		$body .=
			// Select: All, None, Invert
			( new ListToggle( $this->getOutput() ) )->getHTML() .
			'<ul>';

		$titles = [];

		$wordSeparator = $this->msg( 'word-separator' )->escaped();

		$localRepo = $this->repoGroup->getLocalRepo();
		foreach ( $pages as [ $title, $userName ] ) {
			/**
			 * @var $title Title
			 */
			$titles[] = $title->getPrefixedDBkey();

			$image = $title->inNamespace( NS_FILE ) ? $localRepo->newFile( $title ) : false;
			$thumb = $image && $image->exists() ?
				$image->transform( [ 'width' => 120, 'height' => 120 ], 0 ) :
				false;

			$userNameText = $userName ?
				' <span class="mw-changeslist-separator"></span> ' . $this->msg( 'nuke-editby', $userName )->parse() :
				'';

			$body .= '<li>' .
				Html::check(
					'pages[]',
					true,
					[ 'value' => $title->getPrefixedDBkey() ]
				) . "\u{00A0}" .
				( $thumb ? $thumb->toHtml( [ 'desc-link' => true ] ) : '' ) .
				$this->getPageLinksHtml( $title ) .
				$wordSeparator .
				"<span class='ext-nuke-italicize'>" . $userNameText . "</span>" .
				"</li>\n";
		}

		$body .=
			"</ul>\n" .
			Html::hidden( 'originalPageList', implode(
				self::PAGE_LIST_SEPARATOR,
				$titles
			) ) .
			Html::hidden( 'listedTarget', $target );

		$out->addHTML(
			$this->wrapForm( $body )
		);
	}

	/**
	 * @param NukeContext $context
	 * @return void
	 */
	protected function showConfirmForm( NukeContext $context ) {
		$out = $this->getOutput();

		$out->enableOOUI();
		$out->addModuleStyles( [ 'ext.nuke.styles', 'mediawiki.interface.helpers.styles' ] );

		$otherKey = 'other';
		$options = Html::listDropdownOptions(
			$this->msg( 'deletereason-dropdown' )->inContentLanguage()->text(),
			[ $otherKey => $this->msg( 'deletereasonotherlist' )->inContentLanguage()->text() ]
		);

		$formDescriptor = [
			'wpDeleteReasonList' => [
				'id' => 'wpDeleteReasonList',
				'name' => 'wpDeleteReasonList',
				'type' => 'select',
				'label' => $this->msg( 'deletecomment' )->text(),
				'align' => 'top',
				'options' => $options,
				'default' => $otherKey
			],
			'wpReason' => [
				'id' => 'wpReason',
				'name' => 'wpReason',
				'type' => 'text',
				'label' => $this->msg( 'deleteotherreason' )->text(),
				'align' => 'top',
				'maxLength' => CommentStore::COMMENT_CHARACTER_LIMIT,
				'default' => $context->getDeleteReason(),
				'autofocus' => true
			]
		];

		$reasonForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setFormIdentifier( 'massdelete-reason' )
			->addHiddenField( 'action', self::ACTION_DELETE )
			->addHiddenField( 'originalPageList', implode(
				self::PAGE_LIST_SEPARATOR,
				$context->getOriginalPages()
			) )
			->setSubmitTextMsg( 'nuke-submit-delete' )
			->setSubmitDestructive()
			->prepareForm();

		$pageList = [];

		foreach ( $context->getPages() as $page ) {
			$title = Title::newFromText( $page );

			$pageList[] = '<li>' .
				$this->getPageLinksHtml( $title ) .
				Html::hidden( 'pages[]', $title->getPrefixedDBkey() ) .
				'</li>';
		}

		$out->addWikiMsg( 'nuke-tools-confirm', count( $pageList ) );
		$out->addHTML(
			$this->wrapForm(
				$this->getFormFieldsetHtml( $reasonForm ) .
				'<ul>' .
				implode( '', $pageList ) .
				'</ul>'
			)
		);
	}

	/**
	 * Show the result page, showing what pages were deleted and what pages were skipped by the
	 * user.
	 *
	 * @param NukeContext $context
	 * @param array $deletedPageStatuses The status for each page queued for deletion.
	 * @return void
	 */
	protected function showResultPage(
		NukeContext $context,
		array $deletedPageStatuses
	) {
		$out = $this->getOutput();

		$out->addModuleStyles( [ 'ext.nuke.styles', 'mediawiki.interface.helpers.styles' ] );

		// Determine what pages weren't deleted.
		// Deselected pages will have a value of `false`, anything else should be either the
		// string "job" or a Status object.
		$pageStatuses = array_fill_keys( $context->getOriginalPages(), false );
		foreach ( $deletedPageStatuses as $page => $value ) {
			$pageStatuses[ $page ] = $value;
		}

		$queuedCount = count( $deletedPageStatuses );
		$skippedCount = count( $pageStatuses ) - $queuedCount;

		$queued = [];
		$skipped = [];

		foreach ( $pageStatuses as $page => $status ) {
			$title = Title::newFromText( $page );
			if ( $status === false ) {
				$skipped[] = $this->getPageLinksHtml( $title );
			} elseif ( $status === 'job' ) {
				$queued[] = $this->msg(
					'nuke-deletion-queued',
					wfEscapeWikiText( $title->getPrefixedText() )
				)->parse();
			} else {
				$queued[] = $this->msg(
					$status->isOK() ? 'nuke-deleted' : 'nuke-not-deleted',
					wfEscapeWikiText( $title->getPrefixedText() )
				)->parse();
				if ( !$status->isOK() ) {
					// Reduce the queuedCount by 1 if it turns out that on of the Status objects
					// is not OK.
					$queuedCount--;
				}
			}
		}

		// Show the main summary, regardless of whether we deleted pages or not.
		$target = $context->getTarget();
		if ( $target ) {
			$out->addWikiMsg( 'nuke-delete-summary-user', $queuedCount, $target );
		} else {
			$out->addWikiMsg( 'nuke-delete-summary', $queuedCount );
		}
		if ( $queuedCount ) {
			$out->addHTML(
				"<ul>\n<li>" .
				implode( "</li>\n<li>", $queued ) .
				"</li>\n</ul>\n"
			);
		}
		if ( $skippedCount ) {
			$out->addWikiMsg( 'nuke-skipped-summary', $skippedCount );
			$out->addHTML(
				"<ul>\n<li>" .
				implode( "</li>\n<li>", $skipped ) .
				"</li>\n</ul>\n"
			);
		}
		$out->addWikiMsg( 'nuke-delete-more' );
	}

	/**
	 * Wraps HTML within <form> tags. Should be used in displaying the initial prompt
	 * form and the page list.
	 *
	 * Implementation derived from {@link \MediaWiki\HTMLForm\OOUIHTMLForm::wrapForm}
	 *
	 * @param string $content The HTML content to add inside the <form> tags.
	 * @return string
	 */
	protected function wrapForm( string $content ): string {
		// From \MediaWiki\HTMLForm\OOUIHTMLForm::wrapForm
		$form = new \OOUI\FormLayout( [
			'name' => 'massdelete',
			'action' => $this->getPageTitle()->getLocalURL(),
			'method' => 'POST',
			'enctype' => 'application/x-www-form-urlencoded',
			'classes' => [ 'mw-htmlform', 'mw-htmlform-ooui' ],
			'content' => new \OOUI\HtmlSnippet( $content ),
		] );
		return strval( $form );
	}

	/**
	 * Get the HTML for the given form, wrapped inside a fieldset and OOUI HTMLForm wrapper.
	 * This exists because there's no way to suppress the <form> element inside HTMLForm,
	 * which is required to let the design of the Special:Nuke form run properly without
	 * JavaScript.
	 *
	 * @param HTMLForm $form
	 * @return string
	 * @throws \OOUI\Exception
	 */
	protected function getFormFieldsetHtml( HTMLForm $form ): string {
		// Partly from \MediaWiki\HTMLForm\HTMLForm::getHTML
		$this->getOutput()->getMetadata()->setPreventClickjacking( true );
		$this->getOutput()->addModules( 'mediawiki.htmlform' );
		$this->getOutput()->addModuleStyles( 'mediawiki.htmlform.styles' );

		// Only used for validation.
		$form->setSubmitCallback( static function () {
			return true;
		} );

		$submitResult = $form->trySubmit();
		$html = $form->getHeaderHtml()
			. $form->getBody()
			. $form->getHiddenFields()
			. $form->getErrorsOrWarnings( $submitResult, 'error' )
			. $form->getButtons()
			. $form->getFooterHtml();

		// Partly from \MediaWiki\HTMLForm\OOUIHTMLForm::wrapForm
		return strval( new \OOUI\PanelLayout( [
			'classes' => [ 'mw-htmlform-ooui-wrapper' ],
			'expanded' => false,
			'padded' => true,
			'framed' => true,
			'content' => new \OOUI\FieldsetLayout( [
				'label' => $this->msg( 'nuke' )->text(),
				'items' => [
					new \OOUI\Widget( [
						'content' => new \OOUI\HtmlSnippet( $html )
					] ),
				],
			] ),
		] ) );
	}

	/**
	 * Render the page links. Returns a string in `Title (talk | history)` format.
	 *
	 * @param Title $title The title to render links of
	 * @return string
	 * @throws \MWException
	 */
	protected function getPageLinksHtml( Title $title ): string {
		$linkRenderer = $this->getLinkRenderer();

		$wordSeparator = $this->msg( 'word-separator' )->escaped();
		$pipeSeparator = $this->msg( 'pipe-separator' )->escaped();

		$talkPageText = $this->namespaceInfo->isTalk( $title->getNamespace() ) ?
			'' :
			$linkRenderer->makeLink(
				$this->namespaceInfo->getTalkPage( $title ),
				$this->msg( 'sp-contributions-talk' )->text()
			);
		$changesLink = $linkRenderer->makeKnownLink(
			$title,
			$this->msg( 'nuke-viewchanges' )->text(),
			[],
			[ 'action' => 'history' ]
		);

		$query = $title->isRedirect() ? [ 'redirect' => 'no' ] : [];
		$attributes = $title->isRedirect() ? [ 'class' => 'ext-nuke-italicize' ] : [];

		return $linkRenderer->makeKnownLink( $title, null, $attributes, $query ) .
			$wordSeparator .
			$this->msg( 'parentheses' )->rawParams(
				$talkPageText .
				$wordSeparator .
				$pipeSeparator .
				$changesLink
			)->escaped();
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

		$maxAge = $context->getNukeMaxAge();

		$target = $context->getTarget();
		$limit = $context->getLimit();
		$queryBuilder = $dbr->newSelectQueryBuilder()
			->select( [ 'page_title', 'page_namespace' ] )
			->from( 'revision' )
			->join( 'actor', null, 'actor_id=rev_actor' )
			->join( 'page', null, 'page_id=rev_page' )
			->where( [
				$dbr->expr( 'rev_parent_id', '=', 0 ),
				$dbr->expr( 'rev_timestamp', '>', $dbr->timestamp(
					time() - $maxAge
				) )
			] )
			->orderBy( 'rev_timestamp', SelectQueryBuilder::SORT_DESC )
			->distinct()
			->limit( $limit )
			->setMaxExecutionTime(
				$this->getConfig()->get( MainConfigNames::MaxExecutionTimeForExpensiveQueries )
			);

		$queryBuilder->field( 'actor_name' );
		$actorNames = array_filter( [ $target, ...$tempAccounts ] );
		if ( $actorNames ) {
			$queryBuilder->andWhere( [ 'actor_name' => $actorNames ] );
		}

		$namespaces = $context->getNamespaces();
		if ( $namespaces !== null ) {
			$namespaceConditions = array_map( static function ( $ns ) use ( $dbr ){
				return $dbr->expr( 'page_namespace', '=', $ns );
			}, $namespaces );
			$queryBuilder->andWhere( $dbr->orExpr( $namespaceConditions ) );
		}

		$pattern = $context->getPattern();
		if ( trim( $pattern ) !== '' ) {
			$addedWhere = false;

			$pattern = trim( $pattern );
			$pattern = preg_replace( '/ +/', '`_', $pattern );
			$pattern = preg_replace( '/\\\\([%_])/', '`$1', $pattern );

			$overriddenNamespaces = [];
			$capitalLinks = $this->getConfig()->get( 'CapitalLinks' );
			$capitalLinkOverrides = $this->getConfig()->get( 'CapitalLinkOverrides' );
			// If there are any capital-overridden namespaces, keep track of them. "overridden"
			// here means the namespace-specific value is not equal to $wgCapitalLinks.
			foreach ( $capitalLinkOverrides as $nsId => $nsOverridden ) {
				if ( $nsOverridden !== $capitalLinks && (
					$namespaces == null || in_array( $nsId, $namespaces )
				) ) {
					$overriddenNamespaces[] = $nsId;
				}
			}

			if ( count( $overriddenNamespaces ) ) {
				// If there are overridden namespaces, they have to be converted
				// on a case-by-case basis.

				// Our scope should only be limited to the namespaces selected by the user,
				// or all namespaces (when $namespaces == null).
				$validNamespaces = $namespaces == null ?
					$this->namespaceInfo->getValidNamespaces() :
					$namespaces;
				$nonOverriddenNamespaces = [];
				foreach ( $validNamespaces as $ns ) {
					if ( !in_array( $ns, $overriddenNamespaces ) ) {
						// Put all namespaces that aren't overridden in $nonOverriddenNamespaces.
						$nonOverriddenNamespaces[] = $ns;
					}
				}

				$patternSpecific = $this->namespaceInfo->isCapitalized( $overriddenNamespaces[0] ) ?
					$this->contentLanguage->ucfirst( $pattern ) : $pattern;
				$orConditions = [
					$dbr->expr(
						'page_title', IExpression::LIKE, new LikeValue(
							new LikeMatch( $patternSpecific )
						)
					)->and(
					// IN condition
						'page_namespace', '=', $overriddenNamespaces
					)
				];
				if ( count( $nonOverriddenNamespaces ) ) {
					$patternStandard = $this->namespaceInfo->isCapitalized( $nonOverriddenNamespaces[0] ) ?
						$this->contentLanguage->ucfirst( $pattern ) : $pattern;
					$orConditions[] = $dbr->expr(
						'page_title', IExpression::LIKE, new LikeValue(
							new LikeMatch( $patternStandard )
						)
					)->and(
					// IN condition, with the non-overridden namespaces.
					// If the default is case-sensitive namespaces, $pattern's first
					// character is turned lowercase. Otherwise, it is turned uppercase.
						'page_namespace', '=', $nonOverriddenNamespaces
					);
				}
				$queryBuilder->andWhere( $dbr->orExpr( $orConditions ) );
				$addedWhere = true;
			} else {
				// No overridden namespaces; just convert all titles.
				$pattern = $this->namespaceInfo->isCapitalized( NS_MAIN ) ?
					$this->contentLanguage->ucfirst( $pattern ) : $pattern;
			}

			if ( !$addedWhere ) {
				$queryBuilder->andWhere(
					$dbr->expr(
						'page_title',
						IExpression::LIKE,
						new LikeValue(
							new LikeMatch( $pattern )
						)
					)
				);
			}
		}

		$result = $queryBuilder->caller( __METHOD__ )->fetchResultSet();
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

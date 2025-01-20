<?php

namespace MediaWiki\Extension\Nuke\Form;

use HtmlArmor;
use MediaWiki\CommentStore\CommentStore;
use MediaWiki\Extension\Nuke\Form\HTMLForm\NukeDateTimeField;
use MediaWiki\Extension\Nuke\NukeContext;
use MediaWiki\Extension\Nuke\SpecialNuke;
use MediaWiki\Html\Html;
use MediaWiki\Html\ListToggle;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\Language\Language;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\NamespaceInfo;
use MediaWiki\Title\Title;
use OOUI\FieldsetLayout;
use OOUI\FormLayout;
use OOUI\HtmlSnippet;
use OOUI\MessageWidget;
use OOUI\PanelLayout;
use OOUI\Widget;
use RepoGroup;

class SpecialNukeHTMLFormUIRenderer extends SpecialNukeUIRenderer {

	/**
	 * The localized title of the current special page.
	 *
	 * @see {@link SpecialPage::getPageTitle}
	 */
	protected Title $pageTitle;

	private RepoGroup $repoGroup;
	private LinkRenderer $linkRenderer;
	private NamespaceInfo $namespaceInfo;
	private Language $interfaceLanguage;

	/** @inheritDoc */
	public function __construct(
		NukeContext $context,
		SpecialNuke $specialNuke,
		RepoGroup $repoGroup,
		LinkRenderer $linkRenderer,
		NamespaceInfo $namespaceInfo,
		Language $interfaceLanguage
	) {
		parent::__construct( $context );

		$this->pageTitle = $specialNuke->getPageTitle();

		// MediaWiki services
		$this->repoGroup = $repoGroup;
		$this->linkRenderer = $linkRenderer;
		$this->namespaceInfo = $namespaceInfo;
		$this->interfaceLanguage = $interfaceLanguage;
	}

	/**
	 * Get the prompt form to be shown to the user. Should appear when both asking for initial
	 * data or showing the page list.
	 *
	 * @param bool $canContinue Whether the form should show a 'Continue' button.
	 * @return string
	 */
	protected function getPromptForm( bool $canContinue = true ): string {
		$this->getOutput()->addModuleStyles( [ 'ext.nuke.styles' ] );

		$nukeMaxAge = $this->context->getNukeMaxAge();
		$minDate = date( 'Y-m-d', time() - $nukeMaxAge );

		$formDescriptor = [
			'nuke-target' => [
				'id' => 'nuke-target',
				'default' => $this->context->getTarget(),
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
			],
			'dateFrom' => [
				'id' => 'nuke-dateFrom',
				'class' => NukeDateTimeField::class,
				'cssclass' => 'ext-nuke-promptForm-dateFrom',
				'inline' => true,
				'label' => $this->msg( 'nuke-date-from' )->text(),
				'maxAge' => $nukeMaxAge,
				'default' => $minDate
			],
			'dateTo' => [
				'id' => 'nuke-dateTo',
				'class' => NukeDateTimeField::class,
				'cssclass' => 'ext-nuke-promptForm-dateTo',
				'inline' => true,
				'label' => $this->msg( 'nuke-date-to' )->text(),
				'maxAge' => $nukeMaxAge
			]
		];

		$rcMaxAge = $this->getRequestContext()->getConfig()->get( MainConfigNames::RCMaxAge );
		if ( $nukeMaxAge && $nukeMaxAge > $rcMaxAge ) {
			// On a pattern-only search (all-user search), we'll only be searching the
			// recentchanges table. Because of this, we can't fully respect $wgNukeMaxAge.
			// This breaks the expectation of users, so we need to show a note for it.
			$formDescriptor['nuke-pattern']['help-message'] = [
				'nuke-pattern-performance',
				$this->interfaceLanguage->formatTimePeriod( $rcMaxAge, [
					'avoid' => 'avoidhours',
					'noabbrevs' => true
				] )
			];
		}

		$promptForm = HTMLForm::factory(
			'ooui', $formDescriptor, $this->getRequestContext()
		)
			->setFormIdentifier( 'massdelete' )
			// Suppressing default submit button to manually control button order.
			->suppressDefaultSubmit()
			->addButton( [
				'label-message' => 'nuke-submit-list',
				'name' => 'action',
				'value' => SpecialNuke::ACTION_LIST
			] );

		// Show 'Continue' button only if we're not in the initial 'prompt' stage, and pages
		// are going to be listed.
		if (
			$canContinue &&
			$this->context->getAction() !== SpecialNuke::ACTION_PROMPT
		) {
			$promptForm->addButton( [
				'classes' => [ 'mw-htmlform-submit' ],
				'label-message' => 'nuke-submit-continue',
				'name' => 'action',
				'value' => SpecialNuke::ACTION_CONFIRM,
				'flags' => [ 'primary', 'progressive' ]
			] );
		}

		$validationResult = $this->context->validate();
		if ( $validationResult !== true ) {
			$promptForm->addFooterHtml( strval(
				new MessageWidget( [
					'classes' => [ 'ext-nuke-promptform-error' ],
					'type' => 'error',
					'label' => $validationResult
				] )
			) );
		}

		$promptForm->prepareForm();

		return $this->getFormFieldsetHtml( $promptForm );
	}

	/** @inheritDoc */
	public function showPromptForm(): void {
		$out = $this->getOutput();

		if ( $this->context->willUseTemporaryAccounts() ) {
			$out->addWikiMsg( 'nuke-tools-tempaccount' );
		} else {
			$out->addWikiMsg( 'nuke-tools' );
		}
		$out->addWikiMsg( 'nuke-tools-prompt' );

		$out->enableOOUI();
		$out->addHTML(
			$this->wrapForm( $this->getPromptForm() )
		);
	}

	/** @inheritDoc */
	public function showListForm( array $pages ): void {
		$target = $this->context->getTarget();
		$out = $this->getOutput();

		if ( $this->context->willUseTemporaryAccounts() ) {
			$out->addWikiMsg( 'nuke-tools-tempaccount' );
		} else {
			$out->addWikiMsg( 'nuke-tools' );
		}

		$out->addWikiMsg( 'nuke-tools-prompt' );
		$out->addModuleStyles( [ 'ext.nuke.styles', 'mediawiki.interface.helpers.styles' ] );
		$out->enableOOUI();
		if ( !$pages ) {
			$body = $this->getPromptForm( false );
			$out->addHTML(
				$this->wrapForm( $body )
			);

			$out->addHTML( new MessageWidget( [
				'type' => 'warning',
				'label' => $this->msg( 'nuke-nopages-global' )->text(),
			] ) );
			return;
		} else {
			$body = $this->getPromptForm();
		}

		$body .=
			// Select: All, None, Invert
			( new ListToggle( $out ) )->getHTML() .
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

			$userNameText = ' <span class="mw-changeslist-separator"></span> '
				. $this->msg( 'nuke-editby', $userName )->parse();

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
				SpecialNuke::PAGE_LIST_SEPARATOR,
				$titles
			) ) .
			Html::hidden( 'listedTarget', $target );

		$out->addHTML(
			$this->wrapForm( $body )
		);
	}

	/** @inheritDoc */
	public function showConfirmForm(): void {
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
				'default' => $this->context->getDeleteReason(),
				'autofocus' => true
			]
		];

		$reasonForm = HTMLForm::factory(
			'ooui', $formDescriptor, $this->getRequestContext()
		)
			->setFormIdentifier( 'massdelete-reason' )
			->addHiddenField( 'action', SpecialNuke::ACTION_DELETE )
			->addHiddenField( 'originalPageList', implode(
				SpecialNuke::PAGE_LIST_SEPARATOR,
				$this->context->getOriginalPages()
			) )
			->setSubmitTextMsg( 'nuke-submit-delete' )
			->setSubmitDestructive()
			->prepareForm();

		$pageList = [];

		foreach ( $this->context->getPages() as $page ) {
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

	/** @inheritDoc */
	public function showResultPage( array $deletedPageStatuses ): void {
		$out = $this->getOutput();

		$out->addModuleStyles( [ 'ext.nuke.styles', 'mediawiki.interface.helpers.styles' ] );

		// Determine what pages weren't deleted.
		// Deselected pages will have a value of `false`, anything else should be either the
		// string "job" or a Status object.
		$pageStatuses = array_fill_keys( $this->context->getOriginalPages(), false );
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
		$target = $this->context->getTarget();
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
		$form = new FormLayout( [
			'name' => 'massdelete',
			'action' => $this->pageTitle->getLocalURL(),
			'method' => 'POST',
			'enctype' => 'application/x-www-form-urlencoded',
			'classes' => [ 'mw-htmlform', 'mw-htmlform-ooui' ],
			'content' => new HtmlSnippet( $content ),
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
		$out = $this->getOutput();
		// Partly from \MediaWiki\HTMLForm\HTMLForm::getHTML
		$out->getMetadata()->setPreventClickjacking( true );
		$out->getOutput()->addModules( 'mediawiki.htmlform' );
		$out->getOutput()->addModuleStyles( 'mediawiki.htmlform.styles' );

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
		return strval( new PanelLayout( [
			'classes' => [ 'mw-htmlform-ooui-wrapper' ],
			'expanded' => false,
			'padded' => true,
			'framed' => true,
			'content' => new FieldsetLayout( [
				'label' => $this->msg( 'nuke' )->text(),
				'items' => [
					new Widget( [
						'content' => new HtmlSnippet( $html )
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
		$linkRenderer = $this->linkRenderer;

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

}

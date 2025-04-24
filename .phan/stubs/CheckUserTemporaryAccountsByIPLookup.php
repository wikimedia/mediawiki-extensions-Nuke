<?php

namespace MediaWiki\CheckUser\Services;

class CheckUserTemporaryAccountsByIPLookup {
	/**
	 * Allows Nuke to pass CI without CheckUser
	 */
	public const CONSTRUCTOR_OPTIONS = [];

	/**
	 * @param \MediaWiki\Config\ServiceOptions $serviceOptions
	 * @param \Wikimedia\Rdbms\IConnectionProvider $connectionProvider
	 * @param \MediaWiki\JobQueue\JobQueueGroup $jobQueueGroup
	 * @param \MediaWiki\User\TempUser\TempUserConfig $tempUserConfig
	 * @param \MediaWiki\User\UserFactory $userFactory
	 * @param \MediaWiki\Permissions\PermissionManager $permissionManager
	 * @param \MediaWiki\User\Options\UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		\MediaWiki\Config\ServiceOptions $serviceOptions,
		\Wikimedia\Rdbms\IConnectionProvider $connectionProvider,
		\MediaWiki\JobQueue\JobQueueGroup $jobQueueGroup,
		\MediaWiki\User\TempUser\TempUserConfig $tempUserConfig,
		\MediaWiki\User\UserFactory $userFactory,
		\MediaWiki\Permissions\PermissionManager $permissionManager,
		\MediaWiki\User\Options\UserOptionsLookup $userOptionsLookup
	) {
	}
}

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
	 * @param \JobQueueGroup $jobQueueGroup
	 * @param \MediaWiki\User\TempUser\TempUserConfig $tempUserConfig
	 * @param \MediaWiki\User\UserFactory $userFactory
	 * @param \MediaWiki\Permissions\PermissionManager $permissionManager
	 * @param \MediaWiki\User\UserOptionsLookup $userOptionsLookup
	 */
	public function __construct(
		\MediaWiki\Config\ServiceOptions $serviceOptions,
		\Wikimedia\Rdbms\IConnectionProvider $connectionProvider,
		\JobQueueGroup $jobQueueGroup,
		\MediaWiki\User\TempUser\TempUserConfig $tempUserConfig,
		\MediaWiki\User\UserFactory $userFactory,
		\MediaWiki\Permissions\PermissionManager $permissionManager,
		\MediaWiki\User\UserOptionsLookup $userOptionsLookup
	) {
	}
}

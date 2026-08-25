<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/CheckUser',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/CheckUser',
	]
);

// Don't stub CheckUser dependency if present
if ( file_exists( '../../extensions/CheckUser/src/Services/CheckUserTemporaryAccountsByIPLookup.php' ) ) {
	$cfg[ 'exclude_file_list' ] = array_merge(
		$cfg[ 'exclude_file_list' ],
		[ '.phan/stubs/CheckUserTemporaryAccountsByIPLookup.php' ]
	);
}

// Suppresses PhanParamTooMany for Codex::__construct(), which only accepts a
// localizer from wikimedia/codex 0.8.0 onwards. That suppression is needed while
// mediawiki/vendor pins 0.7.1 and unused once it ships 0.8.0, so it must not be reported as
// an unused suppression either way. PhanParamTooMany itself stays enabled everywhere.
// TODO: Remove once wikimedia/codex 0.8.0 is available everywhere (T434742).
$cfg['plugin_config']['unused_suppression_ignore_list'][] = 'PhanParamTooMany';

return $cfg;

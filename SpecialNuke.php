<?php

if( !defined( 'MEDIAWIKI' ) )
	die( 'Not an entry point.' );

$dir = dirname(__FILE__) . '/';

$wgExtensionMessagesFiles['Nuke'] = $dir . 'SpecialNuke.i18n.php';
$wgExtensionAliasesFiles['Nuke'] = $dir . 'Nuke.alias.php';

$wgExtensionCredits['specialpage'][] = array(
	'name'           => 'Nuke',
	'svn-date'       => '$LastChangedDate$',
	'svn-revision'   => '$LastChangedRevision$',
	'description'    => 'Gives sysops the ability to mass delete pages',
	'descriptionmsg' => 'nuke-desc',
	'author'         => 'Brion Vibber',
	'url'            => 'http://www.mediawiki.org/wiki/Extension:Nuke'
);

$wgGroupPermissions['sysop']['nuke'] = true;
$wgAvailableRights[] = 'nuke';

$wgAutoloadClasses['SpecialNuke'] = $dir . 'SpecialNuke_body.php';
$wgSpecialPages['Nuke'] = 'SpecialNuke';

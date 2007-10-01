<?php
#Internationalisation de l'extension mediawiki Nuke

function SpecialNukeMessages () {
	$messages = array(

# English messages by Brion Vibber
	'en' => array(
		'nuke' => 'Mass delete',
		'nuke-nopages' => "No new pages by [[Special:Contributions/$1|$1]] in recent changes.",
		'nuke-list' => "The following pages were recently created by [[Special:Contributions/$1|$1]]; put in a comment and hit the button to delete them.",
		'nuke-defaultreason' => "Mass removal of pages added by $1",
		'nuke-tools' => 'This tool allows for mass deletions of pages recently added by a given user or IP. Input the IP to get a list of things to delete:',
),

# French messages by Bertrand GRONDIN
	'fr' => array(
		'nuke' => 'Suppression en masse',			'nuke-nopages' => "Aucune nouvelle page crée par [[Special:Contributions/$1|$1]] dans la liste des changements récents.",
		'nuke-list' => "Les pages suivantes ont été créées récemment par [[Special:Contributions/$1|$1]]; Indiquer un commentaire et cliquer sur le bouton pour les supprimer.",
		'nuke-defaultreason' => "Suppression en masse des pages ajoutées par $1",
		'nuke-tools' => 'Cet outil autorise les suppressions en masse de pagess ajoutées récemment par un utilisateur enregistré ou par une IP. Indiquer l’IP afin d’obtenir la liste des pages à supprimer :',
	),
);

return $messages ;
}
?>

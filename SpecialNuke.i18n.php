<?php
/**
 * Internationalisation file for the Nuke extension
 *
 * @addtogroup Extensions
 * @author Brion Vibber
 */

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

# German messages by Raimond Spekking
	'de' => array(
		'nuke'               => 'Massen-Löschung',
		'nuke-nopages'       => "Es gibt in den Letzten Änderungen keine neuen Seiten von [[Special:Contributions/$1|$1]].",
		'nuke-list'          => "Die folgenden Seiten wurden von [[Special:Contributions/$1|$1]] erzeugt; gebe einen Kommentar ein und drücke auf den Lösch-Knopf.",
		'nuke-defaultreason' => "Massen-Löschung von Seiten, die von $1 angelegt wurden",
		'nuke-tools'         => 'Dieses Werkzeug ermöglicht die Massen-Löschung von Seiten, die von einer IP-Adresse oder einem Benutzer angelegt wurden. Gib die IP-Adresse/den Benutzernamen ein, um eine Liste zu erhalten:',
),

# French messages by Bertrand GRONDIN
	'fr' => array(
		'nuke' => 'Suppression en masse',
		'nuke-nopages' => "Aucune nouvelle page crée par [[Special:Contributions/$1|$1]] dans la liste des changements récents.",
		'nuke-list' => "Les pages suivantes ont été créées récemment par [[Special:Contributions/$1|$1]]; Indiquer un commentaire et cliquer sur le bouton pour les supprimer.",
		'nuke-defaultreason' => "Suppression en masse des pages ajoutées par $1",
		'nuke-tools' => 'Cet outil autorise les suppressions en masse des pages ajoutées récemment par un utilisateur enregistré ou par une IP. Indiquer l’adresse IP afin d’obtenir la liste des pages à supprimer :',
	),
);

return $messages ;
}

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
		'nuke-tools' => 'This tool allows for mass deletions of pages recently added by a given user or IP. Input the username or IP to get a list of pages to delete:',
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

# Dutch messages by Siebrand Mazeland
	'nl' => array(
		'nuke' => 'En masse verwijderen',
		'nuke-nopages' => "Geen nieuwe pagina's van [[Special:Contributions/$1|$1]] in de recente wijzigingen.",
		'nuke-list' => "De onderstaande pagina's zijn recentelijk aangemaakt door [[Special:Contributions/$1|$1]]; voer een reden in en klik op de knop om ze te verwijderen.",
		'nuke-defaultreason' => "En masse verwijderen van pagina's van $1",
		'nuke-tools' => 'Dit hulpmiddel maakt het mogelijk en masse pagina\'s te verwijderen die recentelijk zijn aangemaakt door een gebruiker of IP-adres. Voer de gebruikernaam of het IP-adres in voor een lijst van te verwijderen pagina\'s:',
	),
);

return $messages ;
}

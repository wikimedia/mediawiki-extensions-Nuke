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

	'ar' => array(
		'nuke'               => 'حذف ضخم',
		'nuke-nopages'       => 'لا صفحات جديدة بواسطة [[Special:Contributions/$1|$1]] في أحدث التغييرات.',
		'nuke-list'          => 'الصفحات التالية تم إنشاؤها حديثا بواسطة [[Special:Contributions/$1|$1]]؛ ضع تعليقا واضغط الزر لحذفهم.',
		'nuke-defaultreason' => 'حذف شامل للصفحات المضافة بواسطة $1',
		'nuke-tools'         => 'هذه الأداة تسمح بالحذف الضخم للصفحات المضافة حديثا بواسطة مستخدم أو أيبي معطى. أدخل اسم المستخدم أو الأيبي لعرض قائمة بالصفحات للحذف:',
	),

# German messages by Raimond Spekking
	'de' => array(
		'nuke'               => 'Massen-Löschung',
		'nuke-nopages'       => "Es gibt in den Letzten Änderungen keine neuen Seiten von [[Special:Contributions/$1|$1]].",
		'nuke-list'          => "Die folgenden Seiten wurden von [[Special:Contributions/$1|$1]] erzeugt; gebe einen Kommentar ein und drücke auf den Lösch-Knopf.",
		'nuke-defaultreason' => "Massen-Löschung von Seiten, die von $1 angelegt wurden",
		'nuke-tools'         => 'Dieses Werkzeug ermöglicht die Massen-Löschung von Seiten, die von einer IP-Adresse oder einem Benutzer angelegt wurden. Gib die IP-Adresse/den Benutzernamen ein, um eine Liste zu erhalten:',
	),

	'fi' => array(
		'nuke' => 'Massapoistaminen',
		'nuke-nopages' => "Ei käyttäjän [[Special:Contributions/$1|$1]] lisäämiä uusia sivuja tuoreissa muutoksissa.",
		'nuke-list' => "Käyttäjä [[Special:Contributions/$1|$1]] on äskettäin luonut seuraavat sivut.",
		'nuke-defaultreason' => "Käyttäjän $1 lisäämien sivujen massapoistaminen",
		'nuke-tools' => 'Tämä työkalu mahdollistaa äskettäin lisättyjen sivujen massapoistamisen käyttäjänimen tai IP:n perusteella. Kirjoita käyttäjänimi tai IP, niin saat listan poistettavista sivuista:',
	),
# French messages by Bertrand GRONDIN
	'fr' => array(
		'nuke'               => 'Suppression en masse',
		'nuke-nopages'       => 'Aucune nouvelle page crée par [[Special:Contributions/$1|$1]] dans la liste des changements récents.',
		'nuke-list'          => 'Les pages suivantes ont été créées récemment par [[Special:Contributions/$1|$1]]; Indiquer un commentaire et cliquer sur le bouton pour les supprimer.',
		'nuke-defaultreason' => 'Suppression en masse des pages ajoutées par $1',
		'nuke-tools'         => 'Cet outil autorise les suppressions en masse des pages ajoutées récemment par un utilisateur enregistré ou par une adresse IP. Indiquer l’adresse IP afin d’obtenir la liste des pages à supprimer :',
	),

	'hsb' => array(
		'nuke'               => 'Masowe wušmórnjenje',
		'nuke-nopages'       => 'W poslednich změnach njejsu nowe strony z [[Special:Contributions/$1|$1]].',
		'nuke-list'          => 'Slědowace strony buchu runje přez [[Special:Contributions/$1|$1]] wutworjene; zapodaj komentar a klikń na tłóčatko wušmórnjenja.',
		'nuke-defaultreason' => 'Masowe wušmórnjenje stronow, kotrež buchu wot $1 wutworjene',
		'nuke-tools'         => 'Tutón grat zmóžnja masowe wušmórnjenje stronow, kotrež buchu wot IP-adresy abo wužiwarja wutworjene. Zapodaj IP-adresu resp. wužiwarske mjeno, zo by lisćinu dóstał:',
	),

	'it' => array(
		'nuke'               => 'Cancellazione di massa',
		'nuke-nopages'       => 'Non sono state trovate nuove pagine create da [[Speciale:Contributi/$1|$1]] tra le modifiche recenti.',
		'nuke-defaultreason' => 'Cancellazione di massa delle pagine create da $1',
		'nuke-tools'         => 'Questo strumento permette la cancellazione in massa delle pagina create di recente da un determinato utente o IP. Inserisci il nome utente o l\'IP per la lista delle pagine da cancellare:',
	),

# Dutch messages by Siebrand Mazeland
	'nl' => array(
		'nuke' => 'En masse verwijderen',
		'nuke-nopages' => "Geen nieuwe pagina's van [[Special:Contributions/$1|$1]] in de recente wijzigingen.",
		'nuke-list' => "De onderstaande pagina's zijn recentelijk aangemaakt door [[Special:Contributions/$1|$1]]; voer een reden in en klik op de knop om ze te verwijderen.",
		'nuke-defaultreason' => "En masse verwijderen van pagina's van $1",
		'nuke-tools' => 'Dit hulpmiddel maakt het mogelijk en masse pagina\'s te verwijderen die recentelijk zijn aangemaakt door een gebruiker of IP-adres. Voer de gebruikernaam of het IP-adres in voor een lijst van te verwijderen pagina\'s:',
	),

	'no' => array(
		'nuke'               => 'Massesletting',
		'nuke-nopages'       => 'Ingen nye sider av [[Special:Contributions/$1|$1]] i siste endringer.',
		'nuke-list'          => 'Følgende sider ble nylig opprettet av [[Special:Contributions/$1|$1]]; skriv inn en slettingsgrunn og trykk på knappen for å slette alle sidene.',
		'nuke-defaultreason' => 'Massesletting av sider lagt inn av $1',
		'nuke-tools'         => 'Dette verktøyet muliggjør massesletting av sider som nylig er lagt inn av en gitt bruker eller IP. Skriv et brukernavn eller en IP for å få en liste over sider som slettes:',
	),
	'pl' => array(
		'nuke'               => 'Masowe usuwanie',
		'nuke-nopages'       => 'Brak nowych stron autorstwa [[Special:Contributions/$1|$1]] w ostatnich zmianach.',
		'nuke-list'          => 'Następujące strony zostały ostatnio stworzone przez [[Special:Contributions/$1|$1]]; wpisz komentarz i wciśnij przycisk by usunąć je.',
		'nuke-defaultreason' => 'Masowe usunięcie stron dodanych przez $1',
		'nuke-tools'         => 'To narzędzia pozwala na masowe kasowanie stron ostatnio dodanych przez zarejestrowanego lub anonimowego użytkownika. Wpis nazwę użytkownika lub adres IP by otrzymać listę stron do skasowania:',
	),
	'pms' => array(
		'nuke'               => 'Scancelament d\'amblé',
		'nuke-nopages'       => 'Gnun-a pàgine faite da [[Special:Contributions/$1|$1]] ant j\'ùltim cambiament.',
		'nuke-list'          => 'Ste pàgine-sì a son staite faite ant j\'ùltim temp da [[Special:Contributions/$1|$1]]; ch\'a lassa un coment e ch\'a-i daga \'n colp ansima al boton për gaveje via tute d\'amblé.',
		'nuke-defaultreason' => 'Scancelament d\'amblé dle pàgine faite da $1',
		'nuke-tools'         => 'St\'utiss-sì a lassa scancelé d\'amblé le pàgine gionta ant j\'ùltim temp da un chèich utent ò da \'nt na chèich adrëssa IP. Ch\'a buta lë stranòm ò l\'adrëssa IP për tiré giù na lista dle pàgine da scancelé:',
	),
);

return $messages ;
}

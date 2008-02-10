<?php
/**
 * Internationalisation file for the Nuke extension
 *
 * @addtogroup Extensions
 * @author Brion Vibber
 */

$messages = array();

# English messages by Brion Vibber
$messages['en'] = array(
	'nuke' => 'Mass delete',
	'nuke-nopages' => "No new pages by [[Special:Contributions/$1|$1]] in recent changes.",
	'nuke-list' => "The following pages were recently created by [[Special:Contributions/$1|$1]]; put in a comment and hit the button to delete them.",
	'nuke-defaultreason' => "Mass removal of pages added by $1",
	'nuke-tools' => 'This tool allows for mass deletions of pages recently added by a given user or IP. Input the username or IP to get a list of pages to delete:',
	'nuke-submit-user' => 'Go',
	'nuke-submit-delete' => 'Delete selected',
);

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'nuke'               => 'حذف كمي',
	'nuke-nopages'       => 'لا صفحات جديدة بواسطة [[Special:Contributions/$1|$1]] في أحدث التغييرات.',
	'nuke-list'          => 'الصفحات التالية تم إنشاؤها حديثا بواسطة [[Special:Contributions/$1|$1]]؛ ضع تعليقا واضغط الزر لحذفهم.',
	'nuke-defaultreason' => 'إزالة كمية للصفحات المضافة بواسطة $1',
	'nuke-tools'         => 'هذه الأداة تسمح بالحذف الضخم للصفحات المضافة حديثا بواسطة مستخدم أو أيبي معطى. أدخل اسم المستخدم أو الأيبي لعرض قائمة بالصفحات للحذف:',
	'nuke-submit-user'   => 'اذهب',
	'nuke-submit-delete' => 'احذف المختار',
);

/** Asturian (Asturianu)
 * @author Esbardu
 */
$messages['ast'] = array(
	'nuke'               => 'Borráu masivu',
	'nuke-nopages'       => 'Nun hai páxines nueves de [[Special:Contributions/$1|$1]] nos cambeos recientes.',
	'nuke-list'          => 'Les páxines siguientes foron creaes recién por [[Special:Contributions/$1|$1]]; escribi un comentariu y calca nel botón pa borrales.',
	'nuke-defaultreason' => 'Borráu masivu de páxines añadíes por $1',
	'nuke-tools'         => "Esta ferramienta permite borraos masivo de páxines añadíes recién por un usariu o una IP determinada. Escribi'l nome d'usuariu o la IP pa obtener una llista de páxines pa borrar:",
	'nuke-submit-user'   => 'Dir',
	'nuke-submit-delete' => 'Borrar seleicionaes',
);

$messages['bg'] = array(
	'nuke'               => 'Масово изтриване',
	'nuke-nopages'       => 'Сред последните промени не съществуват нови страници, създадени от [[Special:Contributions/$1|$1]].',
	'nuke-list'          => 'Следните страници са били наскоро създадени от [[Special:Contributions/$1|$1]]. Напишете коментар и щракнете бутона, за да ги изтриете.',
	'nuke-defaultreason' => 'Масово изтриване на страници, създадени от $1',
	'nuke-tools'         => 'Този инструмент позволява масовото изтриване на страници, създадени от даден регистриран или анонимен потребител. Въведете потребителско име или IP, за да получите списъка от страници за изтриване:',
);

/** Bengali (বাংলা)
 * @author Zaheen
 * @author Bellayet
 */
$messages['bn'] = array(
	'nuke'               => 'গণ মুছে ফেলা',
	'nuke-nopages'       => 'সাম্প্রতিক পরিবর্তনগুলিতে [[Special:Contributions/$1|$1]]-এর তৈরি কোন নতুন পাতা নেই।',
	'nuke-list'          => '[[Special:Contributions/$1|$1]] সাম্প্রতিক কালে নিচের পাতাগুলি সৃষ্টি করেছেন; একটি মন্তব্য দিন এবং বোতাম চেপে এগুলি মুছে ফেলুন।',
	'nuke-defaultreason' => '$1-এর যোগ করা পাতাগুলির গণ মুছে-ফেলা',
	'nuke-tools'         => 'এই সরঞ্জামটি ব্যবহার করে আপনি একটি প্রদত্ত ব্যবহারকারীর বা আইপি ঠিকানার যোগ করা পাতাগুলি গণ আকারে মুছে ফেলতে পারবেন। পাতাগুলির তালিকা পেতে ব্যবহারকারী নাম বা আইপি ঠিকানাটি ইনপুট করুন:',
	'nuke-submit-user'   => 'যাও',
	'nuke-submit-delete' => 'নির্বাচিত গুলো মুছে ফেলো',
);

/** Czech (Česky)
 * @author Li-sung
 */
$messages['cs'] = array(
	'nuke'               => 'Hromadné mazání',
	'nuke-nopages'       => 'V posledních změnách nejsou žádné nové stránky od uživatele [[Special:Contributions/$1|$1]].',
	'nuke-list'          => 'Následující stránky nedávno vytvořil uživatel [[Special:Contributions/$1|$1]]; vyplňte komentář a všechny smažte kliknutím na tlačítko.',
	'nuke-defaultreason' => 'Hromadné odstranění stránek, které vytvořil $1',
	'nuke-tools'         => 'Tento nástroj umožňuje hromadné smazání stránek nedávno vytvořených zadaným uživatelem na IP adresou. Zadejte uživatelské jméno nebo IP adresu, jejichž seznam stránek ke smazání chcete zobrazit:',
	'nuke-submit-user'   => 'Provést',
	'nuke-submit-delete' => 'Smazat vybrané',
);

/** German (Deutsch)
 * @author Raimond Spekking
 */
$messages['de'] = array(
	'nuke'               => 'Massen-Löschung',
	'nuke-nopages'       => "Es gibt in den Letzten Änderungen keine neuen Seiten von [[{{#special:Contributions}}/$1|$1]].",
	'nuke-list'          => "Die folgenden Seiten wurden von [[{{#special:Contributions}}/$1|$1]] erzeugt; gebe einen Kommentar ein und drücke auf den Lösch-Knopf.",
	'nuke-defaultreason' => "Massen-Löschung von Seiten, die von $1 angelegt wurden",
	'nuke-tools'         => 'Dieses Werkzeug ermöglicht die Massen-Löschung von Seiten, die von einer IP-Adresse oder einem Benutzer angelegt wurden. Gib die IP-Adresse/den Benutzernamen ein, um eine Liste zu erhalten:',
	'nuke-submit-user'   => 'Hole Liste',
	'nuke-submit-delete' => 'Löschen',
);

/** فارسی (فارسی)
 * @author Huji
 */
$messages['fa'] = array(
	'nuke'               => 'حذف دست‌جمعی',
	'nuke-nopages'       => 'صفحه‌ٔ جدیدی از [[Special:Contributions/$1|$1]] در تغییرات اخیر وجود ندارد.',
	'nuke-list'          => 'صفحه‌های زیر به تازگی توسط [[Special:Contributions/$1|$1]] ایجاد شده‌اند؛ توضیحی ارائه کنید و دکمه را بزنید تا این صحفه‌ها حذف شوند.',
	'nuke-defaultreason' => 'حذف دست‌جمعی صفحه‌هایی که توسط $1 ایجاد شده‌اند',
	'nuke-tools'         => 'این ابزار امکان حذف دست‌جمعی صفحه‌هایی که به تازگی توسط یک کاربر یا نشانی اینترنتی اضافه شده‌اند را فراهم می‌کند. نام کاربری یا نشانی اینترنتی موردنظر را وارد کنید تا فهرست صفحه‌هایی که حذف می‌شوند را ببینید:',
	'nuke-submit-user'   => 'برو',
	'nuke-submit-delete' => 'حذف موارد انتخاب شده',

);

/** Finnish (Suomi)
 * @author Crt
 */
$messages['fi'] = array(
	'nuke'               => 'Massapoistaminen',
	'nuke-nopages'       => 'Ei käyttäjän [[Special:Contributions/$1|$1]] lisäämiä uusia sivuja tuoreissa muutoksissa.',
	'nuke-list'          => 'Käyttäjä [[Special:Contributions/$1|$1]] on äskettäin luonut seuraavat sivut.',
	'nuke-defaultreason' => 'Käyttäjän $1 lisäämien sivujen massapoistaminen',
	'nuke-tools'         => 'Tämä työkalu mahdollistaa äskettäin lisättyjen sivujen massapoistamisen käyttäjänimen tai IP:n perusteella. Kirjoita käyttäjänimi tai IP, niin saat listan poistettavista sivuista:',
	'nuke-submit-user'   => 'Siirry',
	'nuke-submit-delete' => 'Poista valitut',
);

/** French (Français)
 * @author Grondin
 * @author Sherbrooke
 */
$messages['fr'] = array(
	'nuke'               => 'Suppression en masse',
	'nuke-nopages'       => 'Aucune nouvelle page créée par [[Special:Contributions/$1|$1]] dans la liste des changements récents.',
	'nuke-list'          => 'Les pages suivantes ont été créées récemment par [[Special:Contributions/$1|$1]]; Indiquer un commentaire et cliquer sur le bouton pour les supprimer.',
	'nuke-defaultreason' => 'Suppression en masse des pages ajoutées par $1',
	'nuke-tools'         => 'Cet outil autorise les suppressions en masse des pages ajoutées récemment par un utilisateur enregistré ou par une adresse IP. Indiquer l’adresse IP afin d’obtenir la liste des pages à supprimer :',
	'nuke-submit-user'   => 'Valider',
	'nuke-submit-delete' => 'Suppression sélectionnée',
);

/** Franco-Provençal (Arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'nuke'               => 'Suprèssion en massa',
	'nuke-nopages'       => 'Niona novèla pâge crèâ per [[Special:Contributions/$1|$1]] dens la lista des dèrriérs changements.',
	'nuke-list'          => 'Les pâges siuventes ont étâ crèâs dèrriérement per [[Special:Contributions/$1|$1]] ; endicâd un comentèro et pués clicâd sur lo boton por les suprimar.',
	'nuke-defaultreason' => 'Suprèssion en massa de les pâges apondues per $1',
	'nuke-tools'         => 'Ceti outil ôtorise les suprèssions en massa de les pâges apondues dèrriérement per un utilisator enregistrâ ou per una adrèce IP. Endicâd l’adrèce IP por obtegnir la lista de les pâges a suprimar :',
	'nuke-submit-user'   => 'Validar',
	'nuke-submit-delete' => 'Suprèssion sèlèccionâ',
);

/** Galician (Galego)
 * @author Alma
 * @author SPQRobin
 * @author Xosé
 */
$messages['gl'] = array(
	'nuke'               => 'Eliminar en masa',
	'nuke-nopages'       => 'Non hai novas páxinas feitas por [[Special:Contributions/$1|$1]] nos cambios recentes.',
	'nuke-list'          => 'As seguintes páxinas foron recentemente creadas por [[Special:Contributions/$1|$1]]; poña un comentario e prema o botón para borralos.',
	'nuke-defaultreason' => 'Eliminación en masa das páxinas engadidas por $1',
	'nuke-tools'         => 'Esta ferramenta permite supresións masivas das páxinas engadidas recentemente por un determinado usuario ou enderezo IP. Introduza o nome do usuario ou enderezo IP para obter unha listaxe das páxinas para borrar:',
	'nuke-submit-user'   => 'Adiante',
);

/** Croatian (Hrvatski)
 * @author SpeedyGonsales
 * @author Dnik
 */
$messages['hr'] = array(
	'nuke'               => 'Grupno brisanje',
	'nuke-nopages'       => 'Nema novih stranica suradnika [[Special:Contributions/$1|$1]] među nedavnim promjenama.',
	'nuke-list'          => 'Slijedeće stranice je stvorio suradnik [[Special:Contributions/$1|$1]]; napišite zaključak i kliknite gumb za njihovo brisanje.',
	'nuke-defaultreason' => 'Grupno brisanje stranica suradnika $1',
	'nuke-tools'         => 'Ova ekstenzija omogućava grupno brisanje stranica (članaka) nekog prijavljenog ili neprijavljenog suradnika. Upišite ime ili IP adresu za dobivanje popisa stranica koje je moguće obrisati:',
	'nuke-submit-user'   => 'Kreni',
	'nuke-submit-delete' => 'Obriši označeno',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'nuke'               => 'Masowe wušmórnjenje',
	'nuke-nopages'       => 'W poslednich změnach njejsu nowe strony z [[Special:Contributions/$1|$1]].',
	'nuke-list'          => 'Slědowace strony buchu runje přez [[Special:Contributions/$1|$1]] wutworjene; zapodaj komentar a klikń na tłóčatko wušmórnjenja.',
	'nuke-defaultreason' => 'Masowe wušmórnjenje stronow, kotrež buchu wot $1 wutworjene',
	'nuke-tools'         => 'Tutón grat zmóžnja masowe wušmórnjenje stronow, kotrež buchu wot IP-adresy abo wužiwarja wutworjene. Zapodaj IP-adresu resp. wužiwarske mjeno, zo by lisćinu dóstał:',
	'nuke-submit-user'   => 'W porjadku',
	'nuke-submit-delete' => 'Wušmórnyć',
);

/** Hungarian (Magyar)
 * @author Bdanee
 * @author KossuthRad
 * @author Dorgan
 */
$messages['hu'] = array(
	'nuke'               => 'Halmozott törlés',
	'nuke-nopages'       => 'Nincsenek új oldalak [[Special:Contributions/$1|$1]] az aktuális események között.',
	'nuke-list'          => 'Az alábbi lapokat nem rég készítette [[Special:Contributions/$1|$1]]; adj meg egy indoklást, és kattints a gombra a törlésükhöz.',
	'nuke-defaultreason' => '$1 által készített lapok tömeges eltávolítása',
	'nuke-tools'         => 'Ez az eszköz lehetővé teszi egy adott felhasználó vagy IP által nem rég készített lapok tömeges törlését. Add meg a felhasználónevet vagy az IP-címet, hogy lekérd a törlendő lapok listáját:',
	'nuke-submit-user'   => 'Menj',
	'nuke-submit-delete' => 'Kijelöltek törlése',
);

/** Icelandic (Íslenska)
 * @author S.Örvarr.S
 */
$messages['is'] = array(
	'nuke-submit-user' => 'Áfram',
);

/** Italian (Italiano)
 * @author .anaconda
 */
$messages['it'] = array(
	'nuke'               => 'Cancellazione di massa',
	'nuke-nopages'       => 'Non sono state trovate nuove pagine create da [[Speciale:Contributi/$1|$1]] tra le modifiche recenti.',
	'nuke-list'          => 'Le seguenti pagine sono state create di recente da [[Special:Contributions/$1|$1]]; inserisci un commento e conferma la cancellazione.',
	'nuke-defaultreason' => 'Cancellazione di massa delle pagine create da $1',
	'nuke-tools'         => "Questo strumento permette la cancellazione in massa delle pagina create di recente da un determinato utente o IP. Inserisci il nome utente o l'IP per la lista delle pagine da cancellare:",
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'nuke'               => 'Masse-Läschung',
	'nuke-nopages'       => 'Et gëtt bei de läschten Ännerunge keng nei Säite vum [[Special:Contributions/$1|$1]].',
	'nuke-list'          => 'Dës Säite goufe viru kuerzem vum [[Special:Contributions/$1|$1]] nei ugeluecht; gitt w.e.g. eng Bemierkung an an dréckt op de Kneppche Läschen.',
	'nuke-defaultreason' => 'Masse-Läschung vu Säiten, déi vum $1 ugefaang goufen',
	'nuke-tools'         => "Dësen tool erlaabt masse-Läschunge vu Säiten déi vun engem Benotzer oder vun enger IP-Adresse ugeluecht goufen. Gitt w.e.g. d'IP-Adress respektiv de Benotzer un fir eng Lescht ze kréien:",
	'nuke-submit-delete' => 'Ugewielt läschen',
);

/** Limburgish (Limburgs)
 * @author Matthias
 * @author Ooswesthoesbes
 */
$messages['li'] = array(
	'nuke'               => 'Massaal verwijdere',
	'nuke-nopages'       => "Gein nuje pagina's van [[special:Contributions/$1|$1]] in de recente wijziginge.",
	'nuke-list'          => "De onderstaonde pagina's zien recentelijk aangemaakt door [[Special:Contributions/$1|$1]]; voer 'n rede in en klik op de knop om ze te verwijdere/",
	'nuke-defaultreason' => "Massaal verwijdere van pagina's van $1",
	'nuke-tools'         => "Dit hulpmiddel maakt 't meugelik massaal pagina's te wisse die recentelijk zien aangemaakt door 'n gebroeker of IP-adres. Voer de gebroekersnaam of 't IP-adres in veur 'n lijst van te wisse pagina's:",
	'nuke-submit-user'   => 'Gao',
	'nuke-submit-delete' => 'Geslecteerd wisse',
);

/** Lithuanian (Lietuvių)
 * @author Matasg
 */
$messages['lt'] = array(
	'nuke-submit-user'   => 'Išsiųsti',
	'nuke-submit-delete' => 'Ištrinti pasirinktus(ą)',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'nuke'               => 'Massaal verwijderen',
	'nuke-nopages'       => "Geen nieuwe pagina's van [[Special:Contributions/$1|$1]] in de recente wijzigingen.",
	'nuke-list'          => "De onderstaande pagina's zijn recentelijk aangemaakt door [[Special:Contributions/$1|$1]]; voer een reden in en klik op de knop om ze te verwijderen.",
	'nuke-defaultreason' => "Massaal verwijderen van pagina's van $1",
	'nuke-tools'         => "Dit hulpmiddel maakt het mogelijk massaal pagina's te verwijderen die recentelijk zijn aangemaakt door een gebruiker of IP-adres. Voer de gebruikernaam of het IP-adres in voor een lijst van te verwijderen pagina's:",
	'nuke-submit-user'   => 'OK',
	'nuke-submit-delete' => 'Geselecteerde verwijderen',
);

/** Norwegian (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'nuke'               => 'Massesletting',
	'nuke-nopages'       => 'Ingen nye sider av [[Special:Contributions/$1|$1]] i siste endringer.',
	'nuke-list'          => 'Følgende sider ble nylig opprettet av [[Special:Contributions/$1|$1]]; skriv inn en slettingsgrunn og trykk på knappen for å slette alle sidene.',
	'nuke-defaultreason' => 'Massesletting av sider lagt inn av $1',
	'nuke-tools'         => 'Dette verktøyet muliggjør massesletting av sider som nylig er lagt inn av en gitt bruker eller IP. Skriv et brukernavn eller en IP for å få en liste over sider som slettes:',
	'nuke-submit-user'   => 'Gå',
	'nuke-submit-delete' => 'Slett valgte',
);

$messages['oc'] = array(
	'nuke'               => 'Supression en massa',
	'nuke-nopages'       => 'Cap de pagina novèla creada per [[Special:Contributions/$1|$1]] dins la lista dels darrièrs cambiaments.',
	'nuke-list'          => 'Las paginas seguentas son estadas creadas recentament per [[Special:Contributions/$1|$1]]; Indicatz un comentari e clicatz sul boton per los suprimir.',
	'nuke-defaultreason' => 'Supression en massa de las paginas ajustadas per $1',
	'nuke-tools'         => 'Aqueste esplech autoriza las supressions en massa de las paginas ajustadas recentament per un utilizaire enregistrat o per una adreça IP. Indicatz l’adreça IP per obténer la lista de las paginas de suprimir :',
);

/** Polish (Polski)
 * @author Derbeth
 * @author Sp5uhe
 */
$messages['pl'] = array(
	'nuke'               => 'Masowe usuwanie',
	'nuke-nopages'       => 'Brak nowych stron autorstwa [[Special:Contributions/$1|$1]] w ostatnich zmianach.',
	'nuke-list'          => 'Następujące strony zostały ostatnio stworzone przez [[Special:Contributions/$1|$1]]; wpisz komentarz i wciśnij przycisk by usunąć je.',
	'nuke-defaultreason' => 'Masowe usunięcie stron dodanych przez $1',
	'nuke-tools'         => 'To narzędzia pozwala na masowe kasowanie stron ostatnio dodanych przez zarejestrowanego lub anonimowego użytkownika. Wpis nazwę użytkownika lub adres IP by otrzymać listę stron do skasowania:',
	'nuke-submit-delete' => 'Usuń zaznaczone',
);

$messages['pms'] = array(
	'nuke'               => 'Scancelament d\'amblé',
	'nuke-nopages'       => 'Gnun-a pàgine faite da [[Special:Contributions/$1|$1]] ant j\'ùltim cambiament.',
	'nuke-list'          => 'Ste pàgine-sì a son staite faite ant j\'ùltim temp da [[Special:Contributions/$1|$1]]; ch\'a lassa un coment e ch\'a-i daga \'n colp ansima al boton për gaveje via tute d\'amblé.',
	'nuke-defaultreason' => 'Scancelament d\'amblé dle pàgine faite da $1',
	'nuke-tools'         => 'St\'utiss-sì a lassa scancelé d\'amblé le pàgine gionta ant j\'ùltim temp da un chèich utent ò da \'nt na chèich adrëssa IP. Ch\'a buta lë stranòm ò l\'adrëssa IP për tiré giù na lista dle pàgine da scancelé:',
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'nuke-submit-user' => 'ورځه',
);

/** Portuguese (Português)
 * @author 555
 */
$messages['pt'] = array(
	'nuke'               => 'Eliminação em massa',
	'nuke-nopages'       => 'Não há páginas criadas por [[Special:Contributions/$1|$1]] nas mudanças recentes.',
	'nuke-list'          => 'As páginas a seguir foram criadas recentemente por [[Special:Contributions/$1|$1]]; forneça uma justificativa e pressione o botão a seguir para eliminá-las.',
	'nuke-defaultreason' => 'Eliminação em massa de páginas criadas por $1',
	'nuke-tools'         => 'Esta ferramenta permite a eliminação em massa de páginas recentemente criadas por um utilizador ou IP em específico. Forneça o nome de utilizador ou IP para obter uma lista de páginas a eliminar:',
	'nuke-submit-user'   => 'Ir',
	'nuke-submit-delete' => 'Eliminar as seleccionadas',
);

/** Quechua (Runa Simi)
 * @author AlimanRuna
 */
$messages['qu'] = array(
	'nuke'               => 'Tawqa qulluy',
	'nuke-list'          => "Kay qatiq p'anqakunataqa [[Special:Contributions/$1|$1]] ruraqmi kamarirqun; imarayku nispa butunta ñit'iy tawqalla qullunapaq.",
	'nuke-defaultreason' => "$1-pa rurasqan p'anqakunata tawqalla qulluy",
	'nuke-tools'         => "Kay llamk'anawanqa huk ruraqpa kamarisqan p'anqakunata tawqalla qulluytam atinki. Ruraqpa sutinta icha IP huchhanta yaykuchiy qulluna p'anqakunata rikunaykipaq.",
	'nuke-submit-user'   => 'Riy',
	'nuke-submit-delete' => 'Akllasqata qulluy',
);

/** Russian (Русский)
 * @author VasilievVV
 * @author .:Ajvol:.
 * @author HalanTul
 */
$messages['ru'] = array(
	'nuke'               => 'Массовое удаление',
	'nuke-nopages'       => 'Созданий страниц участником [[Special:Contributions/$1|$1]] не найдено в свежих правках.',
	'nuke-list'          => 'Следующие страницы были недавно созданы участником [[Special:Contributions/$1|$1]]. Введите комментарий и нажмите на кнопку для того, чтобы удалить их.',
	'nuke-defaultreason' => 'Массовое удаление страниц, созданных участником $1',
	'nuke-tools'         => 'Эта страница позволяет массово удалять страницы, созданные определённым участником или IP. Введите имя участника или IP для того, чтобы получить список созданных им страниц.',
	'nuke-submit-user'   => 'Выполнить',
	'nuke-submit-delete' => 'Удалить выбранные',
);

/** Yakut (Саха тыла)
 * @author HalanTul
 */
$messages['sah'] = array(
	'nuke'               => 'Маассабай сотуу',
	'nuke-nopages'       => 'Кэнники көннөрүүлэр испииһэктэригэр [[Special:Contributions/$1|$1]] саҥа сирэйи оҥорбута көстүбэтэ.',
	'nuke-list'          => 'Бу сирэйдэри соторутааҕыта [[Special:Contributions/$1|$1]] кыттааччы оҥорбут. Сотуоххун баҕарар буоллаххына быһаарыыны оҥорон баран тимэҕи баттаа.',
	'nuke-defaultreason' => '$1 кыттааччы айбыт сирэйдэрин бүтүннүү суох оҥоруу',
	'nuke-tools'         => 'Бу сирэй көмөтүнэн ханнык эмэ кыттааччы эбэтэр IP оҥорбут көннөрүүлэрин бүтүннүү суох гынахха сөп. Кыттааччы аатын эбэтэр IP-тын киллэрдэххинэ оҥорбут көннөрүүлэрин испииһэгэ тахсыа:',
	'nuke-submit-user'   => 'Толор',
	'nuke-submit-delete' => 'Талыллыбыты сот',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'nuke'               => 'Hromadné mazanie',
	'nuke-nopages'       => 'V posledných zmenách sa nenachádzajú nové stránky od [[Special:Contributions/$1|$1]].',
	'nuke-list'          => '[[Special:Contributions/$1|$1]] nedávno vytvoril nasledovné nové stránky; vyplňte komentár a stlačením tlačidla ich vymažete.',
	'nuke-defaultreason' => 'Hromadné odstránenie stránok, ktoré pridal $1',
	'nuke-tools'         => 'Tento nástroj umožňuje hromadné odstránenie stránok, ktoré nedávno pridal zadaný používateľ alebo IP. Zadajte používateľa alebo IP a dostanente zoznam stránok na zmazanie:',
	'nuke-submit-user'   => 'Vykonať',
	'nuke-submit-delete' => 'Zmazať vybrané',
);

/** Seeltersk (Seeltersk)
 * @author Pyt
 */
$messages['stq'] = array(
	'nuke'               => 'Massen-Läskenge',
	'nuke-nopages'       => 'Dät rakt in do Lääste Annerengen neen näie Sieden fon [[Special:Contributions/$1|$1]].',
	'nuke-list'          => 'Do foulgjende Sieden wuuden fon [[Special:Contributions/$1|$1]] moaked; reek n Kommentoar ien un tai ap dän Läsk-Knoop.',
	'nuke-defaultreason' => 'Massen-Läskenge fon Sieden, do der fon $1 anlaid wuden',
	'nuke-tools'         => 'Disse Reewe moaket ju Massen-Läskenge muugelk fon Sieden, do der fon een IP-Adresse of aan Benutser anlaid wuuden. Reek ju IP-Adresse/die Benutsernoome ien, uum ne Lieste tou kriegen:',
	'nuke-submit-user'   => 'Hoalje Lieste',
	'nuke-submit-delete' => 'Läskje',
);

/** Swedish (Svenska)
 * @author Lejonel
 */
$messages['sv'] = array(
	'nuke'               => 'Massborttagning',
	'nuke-nopages'       => 'Inga nya sidor av [[Special:Contributions/$1|$1]] bland de senaste ändringarna.',
	'nuke-list'          => 'Följande sidor har nyligen skapats av [[Special:Contributions/$1|$1]]. Skriv en raderingskommentar och klicka på knappen för att ta bort dem.',
	'nuke-defaultreason' => 'Massradering av sidor skapade av $1',
	'nuke-tools'         => 'Det här verktyget gör det möjligt att massradera sidor som nyligen skapats av en vissa användare eller IP-adress. Ange ett användarnamn eller en IP-adress för att de en lista över sidor som kan tas bort:',
	'nuke-submit-user'   => 'Visa',
	'nuke-submit-delete' => 'Ta bort valda',

);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'nuke'               => 'సామూహిక తొలగింపు',
	'nuke-nopages'       => 'ఇటీవలి మార్పులలో [[Special:Contributions/$1|$1]] సృష్టించిన కొత్త పేజీలేమీ లేవు.',
	'nuke-defaultreason' => '$1 చేర్చిన పేజీల యొక్క సామూహిక తొలగింపు',
	'nuke-submit-user'   => 'వెళ్ళు',
	'nuke-submit-delete' => 'ఎంచుకున్నవి తొలగించు',
);

/** Tajik (Тоҷикӣ)
 * @author Ibrahim
 */
$messages['tg'] = array(
	'nuke-submit-user'   => 'Бирав',
	'nuke-submit-delete' => 'Интихобшудагон ҳазф шаванд',
);

/** Turkish (Türkçe)
 * @author Srhat
 * @author Erkan Yilmaz
 */
$messages['tr'] = array(
	'nuke-submit-user'   => 'Git',
	'nuke-submit-delete' => 'Seçileni sil',
);

/** Volapük (Volapük)
 * @author Smeira
 */
$messages['vo'] = array(
	'nuke'               => 'Moükön pademi',
	'nuke-nopages'       => 'Pads nonik fa geban: [[Special:Contributions/$1|$1]] pejaföls binons su lised votükamas nulik.',
	'nuke-list'          => 'Pads sököl pejafons brefabüo fa geban: [[Special:Contributions/$1|$1]]; penolös küpeti e klikolös gnobi ad moükön onis.',
	'nuke-defaultreason' => 'Moükam padas fa geban: $1 pejafölas',
	'nuke-tools'         => 'Stum at kanon moükön mödoti padas fa geban u ladet-IP semik brefabüo pejafölas. Penolös gebananemi u ladeti-IP ad dagetön lisedi padas moükovik:',
	'nuke-submit-user'   => 'Ledunolöd',
	'nuke-submit-delete' => 'Pevalöl ad pamoükön',
);


# Website 2.4 · new interface texts in all six languages (en, lv, ru, nl, de, sv). Run: python3 tr24.py  (merges into site/data/ui.json, keeps edits made in the admin)
import json
P = '/tmp/rfcms/site/data/ui.json'
L = ['en', 'lv', 'ru', 'nl', 'de', 'sv']
T = {
 # night preview
 'pv.kicker': ['Night preview', 'Nakts priekšskatījums', 'Ночной просмотр', 'Nachtvoorbeeld', 'Nachtvorschau', 'Nattförhandsvisning'],
 'pv.title': ['See your sign switched on', 'Apskatiet savu izkārtni ieslēgtu', 'Посмотрите свою вывеску включённой', 'Zie uw reclame aan', 'Sehen Sie Ihr Schild eingeschaltet', 'Se er skylt tänd'],
 'pv.lead': ['Type your text, choose the light and the colours, then switch it on. You can use a photo of your own facade: it stays on your device.', 'Ierakstiet tekstu, izvēlieties gaismu un krāsas un ieslēdziet. Varat izmantot savas fasādes fotoattēlu: tas paliek jūsu ierīcē.', 'Введите текст, выберите подсветку и цвета, затем включите. Можно взять фото своего фасада: оно остаётся на вашем устройстве.', 'Typ uw tekst, kies het licht en de kleuren en zet hem aan. U kunt een foto van uw eigen gevel gebruiken: die blijft op uw apparaat.', 'Text eingeben, Licht und Farben wählen, dann einschalten. Sie können ein Foto Ihrer eigenen Fassade verwenden: Es bleibt auf Ihrem Gerät.', 'Skriv er text, välj ljus och färger och tänd skylten. Ni kan använda ett foto av er egen fasad: det stannar på er enhet.'],
 'pv.text': ['Your text', 'Jūsu teksts', 'Ваш текст', 'Uw tekst', 'Ihr Text', 'Er text'],
 'pv.sample': ['YOUR SIGN', 'JŪSU IZKĀRTNE', 'ВАША ВЫВЕСКА', 'UW RECLAME', 'IHR SCHILD', 'ER SKYLT'],
 'pv.light': ['Light', 'Gaisma', 'Подсветка', 'Verlichting', 'Beleuchtung', 'Belysning'],
 'pv.face': ['Face', 'Fronte', 'Лицевая часть', 'Front', 'Front', 'Front'],
 'pv.led': ['LED colour', 'LED krāsa', 'Цвет светодиодов', 'LED-kleur', 'LED-Farbe', 'LED-färg'],
 'pv.ret': ['Returns', 'Sāni', 'Борта', 'Zijkanten', 'Zargen', 'Sidor'],
 'pv.wall': ['Wall', 'Siena', 'Стена', 'Gevel', 'Fassade', 'Fasad'],
 'pv.size': ['Size on the wall', 'Izmērs uz sienas', 'Размер на стене', 'Formaat op de gevel', 'Größe an der Wand', 'Storlek på väggen'],
 'pv.height': ['Letter height, mm', 'Burtu augstums, mm', 'Высота букв, мм', 'Letterhoogte, mm', 'Buchstabenhöhe, mm', 'Bokstavshöjd, mm'],
 'pv.on': ['Switch on', 'Ieslēgt', 'Включить', 'Aanzetten', 'Einschalten', 'Tänd'],
 'pv.off': ['Switch off', 'Izslēgt', 'Выключить', 'Uitzetten', 'Ausschalten', 'Släck'],
 'pv.png': ['Download picture', 'Lejupielādēt attēlu', 'Скачать картинку', 'Afbeelding downloaden', 'Bild herunterladen', 'Ladda ner bild'],
 'pv.request': ['Request this sign', 'Pieprasīt šo izkārtni', 'Запросить эту вывеску', 'Deze reclame aanvragen', 'Dieses Schild anfragen', 'Begär den här skylten'],
 'pv.attach': ['Attach to my request', 'Pievienot pieprasījumam', 'Приложить к запросу', 'Toevoegen aan mijn aanvraag', 'Zur Anfrage hinzufügen', 'Bifoga till min förfrågan'],
 'pv.attached': ['Night preview attached to your request.', 'Nakts priekšskatījums pievienots pieprasījumam.', 'Ночной просмотр приложен к запросу.', 'Nachtvoorbeeld toegevoegd aan uw aanvraag.', 'Nachtvorschau zur Anfrage hinzugefügt.', 'Nattförhandsvisningen är bifogad.'],
 'pv.from_profile': ['Your night preview from the profile page is attached. You can add more details below.', 'Jūsu nakts priekšskatījums no profila lapas ir pievienots. Zemāk varat pievienot vairāk informācijas.', 'Ваш ночной просмотр со страницы профиля приложен. Ниже можно добавить подробности.', 'Uw nachtvoorbeeld van de profielpagina is toegevoegd. Hieronder kunt u meer details geven.', 'Ihre Nachtvorschau von der Profilseite ist angehängt. Unten können Sie weitere Angaben machen.', 'Er nattförhandsvisning från profilsidan är bifogad. Lägg till fler detaljer nedan.'],
 'pv.drag': ['Drag the sign to move it, or use the arrow keys.', 'Velciet izkārtni, lai to pārvietotu, vai izmantojiet bultiņu taustiņus.', 'Перетащите вывеску или двигайте её стрелками.', 'Sleep de reclame om hem te verplaatsen of gebruik de pijltjestoetsen.', 'Schild ziehen oder mit den Pfeiltasten verschieben.', 'Dra skylten för att flytta den, eller använd piltangenterna.'],
 'pv.scale': ['Letters {h} mm high · readable from about {m} m', 'Burti {h} mm augsti · salasāmi no apm. {m} m', 'Буквы высотой {h} мм · читаются примерно с {m} м', 'Letters {h} mm hoog · leesbaar vanaf ca. {m} m', 'Buchstaben {h} mm hoch · lesbar aus ca. {m} m', 'Bokstäver {h} mm höga · läsbara från cirka {m} m'],
 'pv.figure': ['figure for scale: 1.8 m', 'mēroga figūra: 1,8 m', 'фигура для масштаба: 1,8 м', 'figuur voor schaal: 1,8 m', 'Figur zum Maßstab: 1,8 m', 'figur för skala: 1,8 m'],
 'pv.alt': ['Sign preview: “{text}”, {light}, {face} face, {led} light, {wall}, {state}.', 'Izkārtnes priekšskatījums: “{text}”, {light}, fronte {face}, gaisma {led}, {wall}, {state}.', 'Просмотр вывески: «{text}», {light}, лицо {face}, свет {led}, {wall}, {state}.', 'Voorbeeld: “{text}”, {light}, front {face}, licht {led}, {wall}, {state}.', 'Vorschau: „{text}“, {light}, Front {face}, Licht {led}, {wall}, {state}.', 'Förhandsvisning: ”{text}”, {light}, front {face}, ljus {led}, {wall}, {state}.'],
 'pv.state_on': ['switched on at night', 'ieslēgta naktī', 'включена ночью', 's nachts aan', 'nachts eingeschaltet', 'tänd på natten'],
 'pv.state_off': ['by day', 'dienā', 'днём', 'overdag', 'bei Tag', 'på dagen'],
 'pv.summary': ['Night preview: {light}; face {face}; LED {led}; returns {ret}; letters {h} mm', 'Nakts priekšskatījums: {light}; fronte {face}; LED {led}; sāni {ret}; burti {h} mm', 'Ночной просмотр: {light}; лицо {face}; светодиоды {led}; борта {ret}; буквы {h} мм', 'Nachtvoorbeeld: {light}; front {face}; LED {led}; zijkanten {ret}; letters {h} mm', 'Nachtvorschau: {light}; Front {face}; LED {led}; Zargen {ret}; Buchstaben {h} mm', 'Nattförhandsvisning: {light}; front {face}; LED {led}; sidor {ret}; bokstäver {h} mm'],
 'pv.note': ['For orientation only: the real sign is drawn to your artwork and your brand colours, and you approve a proof first.', 'Tikai orientācijai: īstā izkārtne tiek zīmēta pēc jūsu maketa un zīmola krāsām, un vispirms jūs apstiprināt maketu.', 'Только для ориентира: настоящая вывеска делается по вашему макету и фирменным цветам, и сначала вы утверждаете макет.', 'Alleen ter indicatie: de echte reclame wordt getekend naar uw ontwerp en huiskleuren, en u keurt eerst een proef goed.', 'Nur zur Orientierung: Das echte Schild entsteht nach Ihrer Vorlage und Ihren Markenfarben, und Sie geben zuerst einen Entwurf frei.', 'Endast som vägledning: den riktiga skylten ritas efter ert original och era profilfärger, och ni godkänner ett korrektur först.'],
 'pv.photo_hint': ['Your photo is not uploaded anywhere.', 'Jūsu foto netiek nekur augšupielādēts.', 'Ваше фото никуда не загружается.', 'Uw foto wordt nergens geüpload.', 'Ihr Foto wird nirgends hochgeladen.', 'Ert foto laddas inte upp någonstans.'],
 'pv.open': ['Night preview', 'Nakts priekšskatījums', 'Ночной просмотр', 'Nachtvoorbeeld', 'Nachtvorschau', 'Nattförhandsvisning'],
 'pv.close': ['Close', 'Aizvērt', 'Закрыть', 'Sluiten', 'Schließen', 'Stäng'],
 'pv.c.white': ['white', 'balta', 'белый', 'wit', 'weiß', 'vit'],
 'pv.c.red': ['red', 'sarkana', 'красный', 'rood', 'rot', 'röd'],
 'pv.c.yellow': ['yellow', 'dzeltena', 'жёлтый', 'geel', 'gelb', 'gul'],
 'pv.c.blue': ['blue', 'zila', 'синий', 'blauw', 'blau', 'blå'],
 'pv.c.black': ['black', 'melna', 'чёрный', 'zwart', 'schwarz', 'svart'],
 'pv.c.silver': ['aluminium', 'alumīnijs', 'алюминий', 'aluminium', 'Aluminium', 'aluminium'],
 'pv.c.warm': ['warm white', 'silti balta', 'тёплый белый', 'warmwit', 'warmweiß', 'varmvit'],
 'pv.c.cool': ['cool white', 'auksti balta', 'холодный белый', 'koelwit', 'kaltweiß', 'kallvit'],
 'pv.c.amber': ['amber', 'dzintara', 'янтарный', 'amber', 'bernstein', 'bärnsten'],
 'pv.w.brick': ['Brick', 'Ķieģelis', 'Кирпич', 'Baksteen', 'Ziegel', 'Tegel'],
 'pv.w.plaster': ['Plaster', 'Apmetums', 'Штукатурка', 'Stucwerk', 'Putz', 'Puts'],
 'pv.w.glass': ['Shop window', 'Skatlogs', 'Витрина', 'Etalage', 'Schaufenster', 'Skyltfönster'],
 'pv.w.panel': ['Graphite panel', 'Grafīta panelis', 'Графитовая панель', 'Grafiet paneel', 'Graphitpaneel', 'Grafitpanel'],
 'pv.w.photo': ['My facade photo', 'Mana fasādes foto', 'Фото моего фасада', 'Foto van mijn gevel', 'Foto meiner Fassade', 'Foto av min fasad'],
 # portal 2.4
 'p.cc': ['Also send updates to', 'Atjauninājumus sūtīt arī uz', 'Также отправлять уведомления на', 'Updates ook sturen naar', 'Benachrichtigungen auch an', 'Skicka även uppdateringar till'],
 'p.cc_hint': ['Up to 3 e-mail addresses, separated by commas (accounting, site manager …). They get updates on your requests, never login links.', 'Līdz 3 e-pasta adresēm, atdalītām ar komatiem (grāmatvedība, objekta vadītājs …). Tās saņem pieprasījumu atjauninājumus, nekad ne pieteikšanās saites.', 'До 3 адресов через запятую (бухгалтерия, прораб …). Они получают новости по запросам, но никогда не ссылки для входа.', 'Maximaal 3 e-mailadressen, gescheiden door komma’s (boekhouding, uitvoerder …). Zij krijgen updates over uw aanvragen, nooit inloglinks.', 'Bis zu 3 E-Mail-Adressen, durch Kommas getrennt (Buchhaltung, Bauleitung …). Sie erhalten Neuigkeiten zu Ihren Anfragen, nie Anmeldelinks.', 'Upp till 3 e-postadresser, åtskilda med kommatecken (ekonomi, platschef …). De får uppdateringar om era förfrågningar, aldrig inloggningslänkar.'],
 'p.cc_err': ['Please check the extra e-mail addresses (up to 3, separated by commas).', 'Lūdzu, pārbaudiet papildu e-pasta adreses (līdz 3, atdalītas ar komatiem).', 'Проверьте дополнительные адреса (до 3, через запятую).', 'Controleer de extra e-mailadressen (maximaal 3, gescheiden door komma’s).', 'Bitte prüfen Sie die zusätzlichen E-Mail-Adressen (bis zu 3, durch Kommas getrennt).', 'Kontrollera de extra e-postadresserna (upp till 3, åtskilda med kommatecken).'],
 'p.todo_install': ['Confirm the installation date: {date}', 'Apstipriniet montāžas datumu: {date}', 'Подтвердите дату монтажа: {date}', 'Bevestig de montagedatum: {date}', 'Montagetermin bestätigen: {date}', 'Bekräfta monteringsdatum: {date}'],
 'p.install_h': ['Installation', 'Montāža', 'Монтаж', 'Montage', 'Montage', 'Montering'],
 'p.install_crew': ['Crew', 'Brigāde', 'Бригада', 'Ploeg', 'Team', 'Montörer'],
 'p.install_st_proposed': ['Please confirm', 'Lūdzu, apstipriniet', 'Подтвердите, пожалуйста', 'Graag bevestigen', 'Bitte bestätigen', 'Bekräfta gärna'],
 'p.install_st_confirmed': ['Confirmed', 'Apstiprināts', 'Подтверждено', 'Bevestigd', 'Bestätigt', 'Bekräftat'],
 'p.install_st_change': ['New date asked', 'Lūgts cits datums', 'Запрошена другая дата', 'Andere datum gevraagd', 'Anderer Termin erbeten', 'Nytt datum önskat'],
 'p.install_wait': ['Thank you. We will propose another date shortly.', 'Paldies. Drīz piedāvāsim citu datumu.', 'Спасибо. Скоро предложим другую дату.', 'Dank u. We stellen binnenkort een andere datum voor.', 'Danke. Wir schlagen in Kürze einen anderen Termin vor.', 'Tack. Vi föreslår snart ett annat datum.'],
 'p.install_ics': ['Add to calendar', 'Pievienot kalendāram', 'Добавить в календарь', 'Aan agenda toevoegen', 'Zum Kalender hinzufügen', 'Lägg till i kalendern'],
 'p.install_ics_title': ['Sign installation {no}', 'Izkārtnes montāža {no}', 'Монтаж вывески {no}', 'Montage reclame {no}', 'Schildmontage {no}', 'Skyltmontering {no}'],
 'p.install_ok': ['Confirm this date', 'Apstiprināt šo datumu', 'Подтвердить эту дату', 'Deze datum bevestigen', 'Diesen Termin bestätigen', 'Bekräfta datumet'],
 'p.install_change': ['Ask for another date', 'Lūgt citu datumu', 'Попросить другую дату', 'Andere datum vragen', 'Anderen Termin erbitten', 'Be om ett annat datum'],
 'p.install_change_what': ['Which days and times suit you, and is there anything the crew should know (access, lift, parking)?', 'Kuras dienas un laiki jums der, un vai ir kas brigādei jāzina (piekļuve, pacēlājs, stāvvieta)?', 'Какие дни и время вам подходят, и что нужно знать бригаде (доступ, подъёмник, парковка)?', 'Welke dagen en tijden passen u, en moet de ploeg iets weten (toegang, hoogwerker, parkeren)?', 'Welche Tage und Zeiten passen Ihnen, und gibt es Hinweise für das Team (Zugang, Hubsteiger, Parken)?', 'Vilka dagar och tider passar, och finns det något montörerna bör veta (tillträde, lift, parkering)?'],
 'p.install_change_send': ['Send', 'Sūtīt', 'Отправить', 'Versturen', 'Senden', 'Skicka'],
 'p.install_need_note': ['Please tell us which days suit you.', 'Lūdzu, norādiet, kuras dienas jums der.', 'Напишите, какие дни вам подходят.', 'Laat ons weten welke dagen u passen.', 'Bitte nennen Sie uns passende Tage.', 'Berätta vilka dagar som passar.'],
 'p.install_ok_done': ['Thank you, the installation date is confirmed.', 'Paldies, montāžas datums ir apstiprināts.', 'Спасибо, дата монтажа подтверждена.', 'Dank u, de montagedatum is bevestigd.', 'Danke, der Montagetermin ist bestätigt.', 'Tack, monteringsdatumet är bekräftat.'],
 'p.install_change_done': ['Thank you, we will propose another date.', 'Paldies, piedāvāsim citu datumu.', 'Спасибо, мы предложим другую дату.', 'Dank u, we stellen een andere datum voor.', 'Danke, wir schlagen einen anderen Termin vor.', 'Tack, vi föreslår ett annat datum.'],
 'p.ev_install_proposed': ['Installation proposed for {date}', 'Montāža piedāvāta {date}', 'Предложена дата монтажа: {date}', 'Montage voorgesteld op {date}', 'Montage vorgeschlagen für {date}', 'Montering föreslagen {date}'],
 'p.ev_install_confirmed': ['Installation confirmed for {date}', 'Montāža apstiprināta {date}', 'Монтаж подтверждён: {date}', 'Montage bevestigd op {date}', 'Montage bestätigt für {date}', 'Montering bekräftad {date}'],
 'p.ev_install_change': ['Another installation date asked', 'Lūgts cits montāžas datums', 'Запрошена другая дата монтажа', 'Andere montagedatum gevraagd', 'Anderer Montagetermin erbeten', 'Annat monteringsdatum önskat'],
 'p.mail.what_install': ['We propose to install your sign on {date}. Please confirm the date in the portal, or ask for another one.', 'Piedāvājam uzstādīt jūsu izkārtni {date}. Lūdzu, apstipriniet datumu portālā vai lūdziet citu.', 'Предлагаем смонтировать вывеску {date}. Подтвердите дату в кабинете или попросите другую.', 'We stellen voor uw reclame te monteren op {date}. Bevestig de datum in het portaal of vraag een andere.', 'Wir schlagen vor, Ihr Schild am {date} zu montieren. Bitte bestätigen Sie den Termin im Portal oder erbitten Sie einen anderen.', 'Vi föreslår att montera er skylt {date}. Bekräfta datumet i portalen eller be om ett annat.'],
 'p.mail.what_install_booked': ['Installation of your sign is booked for {date}.', 'Jūsu izkārtnes montāža ir ieplānota {date}.', 'Монтаж вашей вывески назначен на {date}.', 'De montage van uw reclame staat gepland op {date}.', 'Die Montage Ihres Schildes ist für {date} gebucht.', 'Monteringen av er skylt är bokad {date}.'],
}
MON = [['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
       ['Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jūn', 'Jūl', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
       ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
       ['Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
       ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
       ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec']]
for m in range(12): T[f'p.mon.{m+1}'] = [MON[i][m] for i in range(6)]
d = json.load(open(P))
added = 0
for k, v in T.items():
    assert len(v) == 6, k
    if k not in d: d[k] = {}; added += 1
    for l, s in zip(L, v): d[k].setdefault(l, s) if d[k].get(l) else d[k].__setitem__(l, s)
json.dump(d, open(P, 'w'), ensure_ascii=False, indent=1)
print('ui keys:', len(d), 'added:', added)

#!/usr/bin/env python3
"""Website 2.5 · LUMIGRID page content (6 languages), taken from the LUMIGRID firmware repository
(LumigridLedNode 1.0.0-rc2 docs + SignageTypes.hpp). Writes site/data/pages/lumigrid.json and points the
menu item and the Signage Research button to the new page. Run from the package root:  python3 source/tools/lumigrid_page.py"""
import json, os, sys

ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'site', 'data')
L = ['en', 'lv', 'ru', 'nl', 'de', 'sv']


def I(*v):
    assert len(v) == 6, v
    return dict(zip(L, v))


head = {
    'kicker': I('Signage Research · made in Daugavpils', 'Signage Research · ražots Daugavpilī', 'Signage Research · сделано в Даугавпилсе',
                'Signage Research · gemaakt in Daugavpils', 'Signage Research · made in Daugavpils', 'Signage Research · tillverkad i Daugavpils'),
    'title': I('LUMIGRID', 'LUMIGRID', 'LUMIGRID', 'LUMIGRID', 'LUMIGRID', 'LUMIGRID'),
    'tagline': I('Light that knows its cue.', 'Gaisma, kas zina savu brīdi.', 'Свет, который знает свой выход.',
                 'Licht dat zijn moment kent.', 'Licht, das seinen Einsatz kennt.', 'Ljus som vet när det är dags.'),
    'lead': I(
        'Our own lighting control for signs. The LEDNode controller runs the LUMIGRID firmware: 16 outputs for pixel strips and dimmable light, saved scenes and schedules, all controlled from a browser on site.',
        'Mūsu pašu apgaismojuma vadība izkārtnēm. LEDNode kontrolleris darbina LUMIGRID programmatūru: 16 izejas pikseļu lentēm un regulējamai gaismai, saglabātas ainas un grafiki, viss vadāms no pārlūka uz vietas.',
        'Наше собственное управление светом для вывесок. Контроллер LEDNode работает на прошивке LUMIGRID: 16 выходов для пиксельных лент и диммируемого света, сохранённые сцены и расписания, управление из браузера прямо на объекте.',
        'Onze eigen lichtsturing voor signing. De LEDNode-controller draait de LUMIGRID-firmware: 16 uitgangen voor pixelstrips en dimbaar licht, opgeslagen scènes en schema’s, alles te bedienen vanuit een browser ter plaatse.',
        'Unsere eigene Lichtsteuerung für Werbeanlagen. Der LEDNode-Controller läuft mit der LUMIGRID-Firmware: 16 Ausgänge für Pixelstreifen und dimmbares Licht, gespeicherte Szenen und Zeitpläne, bedient im Browser vor Ort.',
        'Vår egen ljusstyrning för skyltar. LEDNode-styrenheten kör LUMIGRID-firmwaren: 16 utgångar för pixelslingor och dimbart ljus, sparade scener och scheman, allt styrt från en webbläsare på plats.'),
    'button': I('Plan a lit sign', 'Plānot izgaismotu izkārtni', 'Спланировать световую вывеску', 'Verlichte signing plannen', 'Leuchtwerbung planen', 'Planera en ljusskylt'),
    'button2': I('See the controller', 'Apskatīt kontrolleri', 'Посмотреть контроллер', 'Bekijk de controller', 'Zum Controller', 'Se styrenheten'),
    'canvas': I('The word LUMIGRID drawn in LED pixels that switch on', 'Vārds LUMIGRID no LED pikseļiem, kas iedegas', 'Слово LUMIGRID из светодиодных пикселей, которые зажигаются',
                'Het woord LUMIGRID in LED-pixels die aangaan', 'Das Wort LUMIGRID aus LED-Pixeln, die aufleuchten', 'Ordet LUMIGRID i LED-pixlar som tänds'),
}

stats = {'items': [
    {'value': '16', 'label': I('outputs per LEDNode', 'izejas vienā LEDNode', 'выходов на один LEDNode', 'uitgangen per LEDNode', 'Ausgänge pro LEDNode', 'utgångar per LEDNode')},
    {'value': '256', 'label': I('pixels per strip output', 'pikseļi uz lentes izeju', 'пикселей на выход ленты', 'pixels per stripuitgang', 'Pixel pro Streifenausgang', 'pixlar per slingutgång')},
    {'value': '24', 'label': I('effects in the firmware', 'efekti programmatūrā', 'эффекта в прошивке', 'effecten in de firmware', 'Effekte in der Firmware', 'effekter i firmwaren')},
    {'value': '32', 'label': I('saved scenes', 'saglabātas ainas', 'сохранённые сцены', 'opgeslagen scènes', 'gespeicherte Szenen', 'sparade scener')},
]}

system = {
    'kicker': I('The system', 'Sistēma', 'Система', 'Het systeem', 'Das System', 'Systemet'),
    'title': I('From our workshop to the façade', 'No mūsu darbnīcas līdz fasādei', 'Из нашего цеха — на фасад', 'Van onze werkplaats naar de gevel', 'Von unserer Werkstatt an die Fassade', 'Från vår verkstad till fasaden'),
    'text': I('LUMIGRID connects the signs we build with the way they are lit, so letters, neon and lightboxes work as one installation.',
              'LUMIGRID savieno mūsu izgatavotās izkārtnes ar to apgaismojumu, lai burti, neons un gaismas kastes darbotos kā viena instalācija.',
              'LUMIGRID связывает вывески, которые мы делаем, с тем, как они светятся: буквы, неон и лайтбоксы работают как одна установка.',
              'LUMIGRID verbindt de signing die we bouwen met de manier waarop die oplicht, zodat letters, neon en lichtbakken samenwerken als één installatie.',
              'LUMIGRID verbindet die Werbeanlagen, die wir bauen, mit ihrem Licht. So arbeiten Buchstaben, Neon und Leuchtkästen als eine Anlage.',
              'LUMIGRID kopplar ihop skyltarna vi bygger med hur de lyser, så att bokstäver, neon och ljuslådor fungerar som en installation.'),
    'items': [
        {'icon': 'box-letter', 'title': I('The sign', 'Izkārtne', 'Вывеска', 'De signing', 'Die Werbeanlage', 'Skylten'),
         'text': I('We build the letters, neon and lightboxes and plan the light for each part: what moves and what simply glows.',
                   'Mēs izgatavojam burtus, neonu un gaismas kastes un plānojam gaismu katrai daļai: kas kustas un kas vienkārši mirdz.',
                   'Мы делаем буквы, неон и лайтбоксы и планируем свет для каждой части: что движется, а что просто светится.',
                   'Wij bouwen de letters, het neon en de lichtbakken en plannen het licht per onderdeel: wat beweegt en wat gewoon gloeit.',
                   'Wir bauen Buchstaben, Neon und Leuchtkästen und planen das Licht für jedes Teil: was sich bewegt und was einfach leuchtet.',
                   'Vi bygger bokstäverna, neonet och ljuslådorna och planerar ljuset för varje del: vad som rör sig och vad som bara lyser.')},
        {'icon': 'controller', 'title': I('LEDNode', 'LEDNode', 'LEDNode', 'LEDNode', 'LEDNode', 'LEDNode'),
         'text': I('One controller inside the sign or its cabinet drives up to 16 outputs: 8 pixel strips and 8 dimmable channels.',
                   'Viens kontrolleris izkārtnē vai tās skapī vada līdz 16 izejām: 8 pikseļu lentes un 8 regulējamus kanālus.',
                   'Один контроллер в вывеске или её шкафу управляет 16 выходами: 8 пиксельных лент и 8 диммируемых каналов.',
                   'Eén controller in de signing of de kast stuurt tot 16 uitgangen aan: 8 pixelstrips en 8 dimbare kanalen.',
                   'Ein Controller in der Anlage oder ihrem Schaltschrank steuert bis zu 16 Ausgänge: 8 Pixelstreifen und 8 dimmbare Kanäle.',
                   'En styrenhet i skylten eller dess skåp driver upp till 16 utgångar: 8 pixelslingor och 8 dimbara kanaler.')},
        {'icon': 'globe', 'title': I('Your browser', 'Jūsu pārlūks', 'Ваш браузер', 'Uw browser', 'Ihr Browser', 'Din webbläsare'),
         'text': I('Scenes, schedules and live control run from the controller’s own page on your local network. No cloud account is needed.',
                   'Ainas, grafiki un tiešā vadība darbojas no paša kontrollera lapas jūsu lokālajā tīklā. Mākoņkonts nav vajadzīgs.',
                   'Сцены, расписания и ручное управление работают со страницы самого контроллера в вашей локальной сети. Облачный аккаунт не нужен.',
                   'Scènes, schema’s en live bediening draaien vanaf de eigen pagina van de controller in uw lokale netwerk. Een cloudaccount is niet nodig.',
                   'Szenen, Zeitpläne und Live-Steuerung laufen über die eigene Seite des Controllers im lokalen Netz. Ein Cloud-Konto ist nicht nötig.',
                   'Scener, scheman och direktstyrning körs från styrenhetens egen sida i ditt lokala nätverk. Inget molnkonto behövs.')},
        {'icon': 'layers', 'title': I('More nodes, more light', 'Vairāk kontrolleru, vairāk gaismas', 'Больше контроллеров — больше света', 'Meer nodes, meer licht', 'Mehr Nodes, mehr Licht', 'Fler noder, mer ljus'),
         'text': I('Several LEDNodes on one network can play matching shows, and a lighting desk can take over through Art-Net or sACN.',
                   'Vairāki LEDNode vienā tīklā var atskaņot saskaņotas programmas, un gaismas pults var pārņemt vadību caur Art-Net vai sACN.',
                   'Несколько LEDNode в одной сети играют согласованные шоу, а световой пульт может взять управление через Art-Net или sACN.',
                   'Meerdere LEDNodes in één netwerk spelen bij elkaar passende shows, en een lichttafel kan het overnemen via Art-Net of sACN.',
                   'Mehrere LEDNodes in einem Netz spielen abgestimmte Shows, und ein Lichtpult kann per Art-Net oder sACN übernehmen.',
                   'Flera LEDNoder i samma nätverk kan spela matchande shower, och ett ljusbord kan ta över via Art-Net eller sACN.')},
    ]}

node = {
    'kicker': I('The controller', 'Kontrolleris', 'Контроллер', 'De controller', 'Der Controller', 'Styrenheten'),
    'title': I('LEDNode', 'LEDNode', 'LEDNode', 'LEDNode', 'LEDNode', 'LEDNode'),
    'text': I('A compact ESP32 controller that runs LUMIGRID. One board mixes two kinds of light: animated pixel strips and classic dimmable LED.',
              'Kompakts ESP32 kontrolleris, kas darbina LUMIGRID. Viena plate apvieno divus gaismas veidus: animētas pikseļu lentes un klasisku regulējamu LED.',
              'Компактный контроллер на ESP32 с прошивкой LUMIGRID. Одна плата сочетает два вида света: анимированные пиксельные ленты и классический диммируемый LED.',
              'Een compacte ESP32-controller met LUMIGRID. Eén bord combineert twee soorten licht: geanimeerde pixelstrips en klassieke dimbare LED.',
              'Ein kompakter ESP32-Controller mit LUMIGRID. Eine Platine verbindet zwei Lichtarten: animierte Pixelstreifen und klassische dimmbare LED.',
              'En kompakt ESP32-styrenhet som kör LUMIGRID. Ett kort blandar två sorters ljus: animerade pixelslingor och klassisk dimbar LED.'),
    'specs': [
        {'label': I('Pixel outputs', 'Pikseļu izejas', 'Пиксельные выходы', 'Pixeluitgangen', 'Pixelausgänge', 'Pixelutgångar'),
         'value': I('8 (A1–A8) · WS2812 RGB or SK6812 RGBW · up to 256 pixels each', '8 (A1–A8) · WS2812 RGB vai SK6812 RGBW · līdz 256 pikseļiem katrā',
                    '8 (A1–A8) · WS2812 RGB или SK6812 RGBW · до 256 пикселей на каждом', '8 (A1–A8) · WS2812 RGB of SK6812 RGBW · tot 256 pixels per uitgang',
                    '8 (A1–A8) · WS2812 RGB oder SK6812 RGBW · bis zu 256 Pixel je Ausgang', '8 (A1–A8) · WS2812 RGB eller SK6812 RGBW · upp till 256 pixlar var')},
        {'label': I('Dimmer outputs', 'Regulējamās izejas', 'Диммерные выходы', 'Dimuitgangen', 'Dimmerausgänge', 'Dimmerutgångar'),
         'value': I('8 (P1–P8) · independent PWM channels', '8 (P1–P8) · neatkarīgi PWM kanāli', '8 (P1–P8) · независимые ШИМ-каналы', '8 (P1–P8) · onafhankelijke PWM-kanalen', '8 (P1–P8) · unabhängige PWM-Kanäle', '8 (P1–P8) · oberoende PWM-kanaler')},
        {'label': I('Processor', 'Procesors', 'Процессор', 'Processor', 'Prozessor', 'Processor'),
         'value': I('ESP32 · 8 MB flash', 'ESP32 · 8 MB zibatmiņa', 'ESP32 · 8 МБ флеш-памяти', 'ESP32 · 8 MB flash', 'ESP32 · 8 MB Flash', 'ESP32 · 8 MB flash')},
        {'label': I('Connection', 'Savienojums', 'Связь', 'Verbinding', 'Verbindung', 'Anslutning'),
         'value': I('Wi-Fi · own setup network · browser interface on the controller', 'Wi-Fi · savs iestatīšanas tīkls · pārlūka saskarne kontrollerī',
                    'Wi-Fi · собственная сеть для настройки · веб-интерфейс на самом контроллере', 'Wi-Fi · eigen installatienetwerk · browserinterface op de controller',
                    'WLAN · eigenes Einrichtungsnetz · Browseroberfläche auf dem Controller', 'Wi-Fi · eget installationsnätverk · webbgränssnitt på styrenheten')},
        {'label': I('Live input', 'Tiešā ievade', 'Внешнее управление', 'Live-invoer', 'Live-Eingang', 'Direktingång'),
         'value': I('Art-Net and sACN, off until you switch them on', 'Art-Net un sACN, izslēgti, līdz tos ieslēdzat', 'Art-Net и sACN, выключены, пока вы их не включите',
                    'Art-Net en sACN, uit tot u ze aanzet', 'Art-Net und sACN, aus, bis Sie sie einschalten', 'Art-Net och sACN, avstängda tills du slår på dem')},
        {'label': I('Sync', 'Sinhronizācija', 'Синхронизация', 'Synchronisatie', 'Synchronisation', 'Synkning'),
         'value': I('Up to 8 controllers on the same local network', 'Līdz 8 kontrolleriem vienā lokālajā tīklā', 'До 8 контроллеров в одной локальной сети',
                    'Tot 8 controllers in hetzelfde lokale netwerk', 'Bis zu 8 Controller im selben lokalen Netz', 'Upp till 8 styrenheter i samma lokala nätverk')},
        {'label': I('Updates', 'Atjauninājumi', 'Обновления', 'Updates', 'Updates', 'Uppdateringar'),
         'value': I('Over the network, with automatic rollback if a new version does not start', 'Caur tīklu, ar automātisku atgriešanos, ja jaunā versija nestartē',
                    'По сети, с автоматическим откатом, если новая версия не запускается', 'Via het netwerk, met automatisch terugzetten als een nieuwe versie niet start',
                    'Über das Netz, mit automatischer Rückkehr, wenn eine neue Version nicht startet', 'Via nätverket, med automatisk återgång om en ny version inte startar')},
        {'label': I('Start-up', 'Ieslēgšana', 'Запуск', 'Opstarten', 'Start', 'Uppstart'),
         'value': I('Outputs stay off until the controller is ready, then soft-start in 0.5 s', 'Izejas paliek izslēgtas, līdz kontrolleris ir gatavs, tad maigi ieslēdzas 0,5 s laikā',
                    'Выходы выключены, пока контроллер не готов, затем плавно включаются за 0,5 с', 'Uitgangen blijven uit tot de controller klaar is en starten dan zacht in 0,5 s',
                    'Ausgänge bleiben aus, bis der Controller bereit ist, dann Sanftstart in 0,5 s', 'Utgångarna är avstängda tills styrenheten är redo och mjukstartar sedan på 0,5 s')},
    ],
    'note': I('LEDNode is the controller. Power supplies, wiring and fusing are chosen for each installation, and the firmware keeps a power estimate for every output and supply.',
              'LEDNode ir kontrolleris. Barošanas blokus, vadus un drošinātājus izvēlas katrai instalācijai, un programmatūra aprēķina jaudu katrai izejai un barošanas avotam.',
              'LEDNode — это контроллер. Блоки питания, проводку и предохранители подбирают под каждую установку, а прошивка ведёт расчёт мощности по каждому выходу и источнику.',
              'LEDNode is de controller. Voedingen, bekabeling en zekeringen worden per installatie gekozen, en de firmware houdt per uitgang en voeding een vermogensschatting bij.',
              'LEDNode ist der Controller. Netzteile, Verkabelung und Absicherung werden für jede Anlage gewählt, und die Firmware schätzt die Leistung je Ausgang und Netzteil.',
              'LEDNode är styrenheten. Nätaggregat, kablage och säkringar väljs för varje installation, och firmwaren håller en effektuppskattning för varje utgång och aggregat.'),
    'strips': I('Pixel strips', 'Pikseļu lentes', 'Пиксельные ленты', 'Pixelstrips', 'Pixelstreifen', 'Pixelslingor'),
    'dimmers': I('Dimmers', 'Regulatori', 'Диммеры', 'Dimmers', 'Dimmer', 'Dimmers'),
    'figure': I('Drawing of the LEDNode board: 8 pixel outputs, 8 dimmer outputs and the ESP32 module', 'LEDNode plates zīmējums: 8 pikseļu izejas, 8 regulējamās izejas un ESP32 modulis',
                'Схема платы LEDNode: 8 пиксельных выходов, 8 диммерных выходов и модуль ESP32', 'Tekening van het LEDNode-bord: 8 pixeluitgangen, 8 dimuitgangen en de ESP32-module',
                'Zeichnung der LEDNode-Platine: 8 Pixelausgänge, 8 Dimmerausgänge und das ESP32-Modul', 'Ritning av LEDNode-kortet: 8 pixelutgångar, 8 dimmerutgångar och ESP32-modulen'),
}

# effect labels: firmware ids, names translated; short descriptions follow the firmware catalogue
FX = [
    ('solid', 'Steady color', 'Vienmērīga krāsa', 'Ровный цвет', 'Vaste kleur', 'Gleichmäßige Farbe', 'Jämn färg',
     'An even wash of one colour.', 'Vienmērīgs vienas krāsas apgaismojums.', 'Ровная заливка одним цветом.', 'Een egale gloed in één kleur.', 'Eine gleichmäßige Fläche in einer Farbe.', 'Ett jämnt sken i en färg.'),
    ('breathe', 'Smooth pulse', 'Maiga pulsācija', 'Плавный пульс', 'Zachte puls', 'Sanfter Puls', 'Mjuk puls',
     'Fades between a soft glow and full brightness.', 'Pāriet no maiga mirdzuma līdz pilnam spilgtumam.', 'Переходит от мягкого свечения к полной яркости.', 'Gaat van een zachte gloed naar volle helderheid.', 'Blendet zwischen sanftem Glimmen und voller Helligkeit.', 'Tonar mellan ett mjukt sken och full ljusstyrka.'),
    ('chase', 'Moving comet', 'Komēta', 'Комета', 'Komeet', 'Komet', 'Komet',
     'A bright head travels along the strip with a fading tail.', 'Spilgta galva skrien pa lenti ar dziestošu asti.', 'Яркая голова бежит по ленте с гаснущим хвостом.', 'Een felle kop loopt over de strip met een uitdovende staart.', 'Ein heller Kopf läuft mit verglühendem Schweif über den Streifen.', 'Ett ljust huvud rör sig längs slingan med en svans som bleknar.'),
    ('sweep', 'Color sweep', 'Krāsas aizpilde', 'Заполнение цветом', 'Kleurveeg', 'Farbwischer', 'Färgsvep',
     'The colour fills the strip from end to end.', 'Krāsa aizpilda lenti no gala līdz galam.', 'Цвет заполняет ленту от края до края.', 'De kleur vult de strip van begin tot eind.', 'Die Farbe füllt den Streifen von Ende zu Ende.', 'Färgen fyller slingan från ände till ände.'),
    ('reveal', 'Soft reveal', 'Maiga iedegšanās', 'Мягкое появление', 'Zacht oplichten', 'Sanftes Einblenden', 'Mjuk tändning',
     'The whole lane fades in for a polished sign-on.', 'Visa līnija maigi iedegas, kā izkārtne, kas ieslēdzas.', 'Вся линия плавно загорается, как включающаяся вывеска.', 'De hele lijn licht zacht op, als een signing die aangaat.', 'Die ganze Linie blendet ein, wie eine Anlage, die angeht.', 'Hela linjen tonar in, som en skylt som tänds.'),
    ('sparkle', 'Random twinkle', 'Dzirksteles', 'Искры', 'Fonkeling', 'Funkeln', 'Gnistor',
     'Crisp white sparks over a steady colour.', 'Spilgtas baltas dzirksteles uz vienmērīgas krāsas.', 'Чёткие белые искры поверх ровного цвета.', 'Heldere witte vonken boven een vaste kleur.', 'Klare weiße Funken über einer ruhigen Farbe.', 'Skarpa vita gnistor över en jämn färg.'),
    ('gradient', 'Flowing glow', 'Plūstošs mirdzums', 'Плывущее свечение', 'Stromende gloed', 'Fließendes Leuchten', 'Flytande sken',
     'A colour gradient flows with a soft wave of light.', 'Krāsu pāreja plūst ar maigu gaismas vilni.', 'Цветовой переход плывёт мягкой волной света.', 'Een kleurverloop stroomt mee met een zachte lichtgolf.', 'Ein Farbverlauf fließt mit einer weichen Lichtwelle.', 'En färgtoning flyter med en mjuk ljusvåg.'),
    ('strobe', 'Beat strobe', 'Stroboskops', 'Стробоскоп', 'Stroboscoop', 'Stroboskop', 'Stroboskop',
     'Regular flashes with an adjustable on-time.', 'Regulāri uzplaiksnījumi ar regulējamu ilgumu.', 'Ритмичные вспышки с настраиваемой длительностью.', 'Regelmatige flitsen met instelbare aan-tijd.', 'Regelmäßige Blitze mit einstellbarer Leuchtdauer.', 'Regelbundna blixtar med justerbar tändtid.'),
    ('fire', 'Living flame', 'Dzīva liesma', 'Живое пламя', 'Levende vlam', 'Lebendige Flamme', 'Levande låga',
     'A flickering flame texture along the strip.', 'Mirgojoša liesmas faktūra pa visu lenti.', 'Мерцающая фактура пламени по ленте.', 'Een flakkerende vlamtextuur over de strip.', 'Eine flackernde Flammenstruktur entlang des Streifens.', 'En fladdrande lågstruktur längs slingan.'),
    ('aurora', 'Aurora glow', 'Ziemeļblāzma', 'Северное сияние', 'Noorderlicht', 'Polarlicht', 'Norrsken',
     'The colour shifts gently across the whole lane.', 'Krāsa maigi mainās visā līnijā.', 'Цвет мягко меняется по всей линии.', 'De kleur verschuift zacht over de hele lijn.', 'Die Farbe verschiebt sich sanft über die ganze Linie.', 'Färgen skiftar mjukt över hela linjen.'),
    ('colorwave', 'Color wave', 'Krāsu vilnis', 'Цветовая волна', 'Kleurgolf', 'Farbwelle', 'Färgvåg',
     'A repeating wave of colour and light moves along.', 'Atkārtots krāsas un gaismas vilnis virzās uz priekšu.', 'Повторяющаяся волна цвета и света движется вдоль ленты.', 'Een herhalende golf van kleur en licht beweegt mee.', 'Eine wiederkehrende Welle aus Farbe und Licht wandert entlang.', 'En upprepad våg av färg och ljus rör sig framåt.'),
    ('staticpulse', 'Static pulse', 'Vienmērīgs pulss', 'Ровный пульс', 'Rustige puls', 'Ruhiger Puls', 'Lugn puls',
     'A slow brightness pulse in one fixed colour.', 'Lēna spilgtuma pulsācija vienā krāsā.', 'Медленный пульс яркости в одном цвете.', 'Een langzame helderheidspuls in één vaste kleur.', 'Ein langsamer Helligkeitspuls in einer festen Farbe.', 'En långsam ljuspuls i en fast färg.'),
    ('rainbow', 'Rainbow flow', 'Varavīksne', 'Радуга', 'Regenboog', 'Regenbogen', 'Regnbåge',
     'The full spectrum moves along the strip.', 'Viss spektrs virzās pa lenti.', 'Весь спектр движется по ленте.', 'Het volledige spectrum beweegt over de strip.', 'Das ganze Spektrum wandert über den Streifen.', 'Hela spektrumet rör sig längs slingan.'),
    ('colorfade', 'Color fade', 'Krāsu pāreja', 'Смена цвета', 'Kleurovergang', 'Farbübergang', 'Färgövergång',
     'The lane blends through the palette and back.', 'Līnija pāriet cauri paletei un atpakaļ.', 'Линия проходит через палитру и обратно.', 'De lijn loopt door het palet en terug.', 'Die Linie läuft durch die Palette und zurück.', 'Linjen glider genom paletten och tillbaka.'),
    ('scanner', 'Soft scanner', 'Skeneris', 'Сканер', 'Scanner', 'Scanner', 'Skanner',
     'A soft spotlight runs to the end and back.', 'Maigs gaismas punkts aizskrien līdz galam un atpakaļ.', 'Мягкое пятно света бежит до конца и обратно.', 'Een zachte spot loopt naar het eind en terug.', 'Ein weicher Lichtpunkt läuft ans Ende und zurück.', 'En mjuk ljuspunkt löper till slutet och tillbaka.'),
    ('theater', 'Marquee chase', 'Afišas gaismas', 'Бегущие огни', 'Lichtkrant', 'Lauflicht', 'Löpljus',
     'Spaced lights travel along, like a theatre marquee.', 'Gaismas ar atstarpēm skrien kā teātra afišā.', 'Огни с промежутками бегут, как на театральной афише.', 'Lampjes op afstand lopen mee, als bij een theaterluifel.', 'Lichter mit Abstand laufen mit, wie an einem Theatervordach.', 'Ljus med mellanrum vandrar, som på en teaterskylt.'),
    ('twinkle', 'Soft twinkle', 'Maiga mirgošana', 'Мягкое мерцание', 'Zacht twinkelen', 'Sanftes Funkeln', 'Mjukt glitter',
     'Coloured points gently fade in and out over black.', 'Krāsaini punkti maigi iedegas un izdziest uz melna.', 'Цветные точки мягко зажигаются и гаснут на чёрном.', 'Gekleurde punten lichten zacht op en doven uit.', 'Farbige Punkte blenden sanft ein und aus.', 'Färgade punkter tonar mjukt in och ut mot svart.'),
    ('candle', 'Candlelight', 'Svece', 'Свеча', 'Kaarslicht', 'Kerzenlicht', 'Levande ljus',
     'An even glow with a gentle, irregular flicker.', 'Vienmērīgs mirdzums ar maigu, nevienmērīgu mirgošanu.', 'Ровное свечение с мягким неровным мерцанием.', 'Een egale gloed met een zacht, onregelmatig flakkeren.', 'Ein ruhiges Leuchten mit sanftem, unregelmäßigem Flackern.', 'Ett jämnt sken med ett mjukt, oregelbundet fladder.'),
    ('heartbeat', 'Heartbeat', 'Sirdspuksti', 'Сердцебиение', 'Hartslag', 'Herzschlag', 'Hjärtslag',
     'Two pulses, then a quiet rest.', 'Divi pulsi, tad klusa pauze.', 'Два удара, затем тихая пауза.', 'Twee pulsen, dan een rustige pauze.', 'Zwei Pulse, dann eine ruhige Pause.', 'Två pulser, sedan en lugn paus.'),
    ('ripple', 'Center ripple', 'Viļņi no centra', 'Круги от центра', 'Rimpeling', 'Wellen aus der Mitte', 'Ringar från mitten',
     'Soft fronts spread from the centre to both ends.', 'Maigi viļņi izplešas no centra uz abiem galiem.', 'Мягкие волны расходятся от центра к обоим концам.', 'Zachte golven lopen vanuit het midden naar beide uiteinden.', 'Weiche Wellen laufen von der Mitte zu beiden Enden.', 'Mjuka vågor sprids från mitten mot båda ändar.'),
    ('dissolve', 'Dissolve', 'Izkliede', 'Растворение', 'Oplossen', 'Auflösen', 'Upplösning',
     'Pixels change colour in a shuffled order, then back.', 'Pikseļi maina krāsu jauktā secībā un atpakaļ.', 'Пиксели меняют цвет в перемешанном порядке и обратно.', 'Pixels wisselen van kleur in willekeurige volgorde, en terug.', 'Pixel wechseln die Farbe in gemischter Reihenfolge und zurück.', 'Pixlar byter färg i blandad ordning och tillbaka.'),
    ('plasma', 'Plasma flow', 'Plazma', 'Плазма', 'Plasma', 'Plasma', 'Plasma',
     'Overlapping waves form a moving colour texture.', 'Pārklājoši viļņi veido kustīgu krāsu faktūru.', 'Наложенные волны создают живую цветовую фактуру.', 'Overlappende golven vormen een bewegende kleurtextuur.', 'Überlagerte Wellen bilden eine bewegte Farbstruktur.', 'Överlappande vågor bildar en rörlig färgstruktur.'),
    ('lightning', 'Lightning', 'Zibens', 'Молния', 'Bliksem', 'Blitz', 'Blixt',
     'A burst of flashes, then a dark pause.', 'Uzplaiksnījumu sērija, tad tumša pauze.', 'Серия вспышек, затем тёмная пауза.', 'Een reeks flitsen, dan een donkere pauze.', 'Eine Blitzfolge, dann eine dunkle Pause.', 'En skur av blixtar, sedan en mörk paus.'),
    ('confetti', 'Confetti', 'Konfeti', 'Конфетти', 'Confetti', 'Konfetti', 'Konfetti',
     'Colourful sparks appear and leave fading glows.', 'Krāsainas dzirksteles parādās un atstāj dziestošu mirdzumu.', 'Цветные искры вспыхивают и оставляют гаснущий след.', 'Kleurige vonken verschijnen en laten een uitdovende gloed achter.', 'Bunte Funken erscheinen und hinterlassen verglühendes Licht.', 'Färgglada gnistor dyker upp och lämnar ett bleknande sken.'),
]
PAL = [  # firmware palette ids 0-13
    ('Custom', 'Pielāgota', 'Своя', 'Eigen', 'Eigene', 'Egen'), ('Rainbow', 'Varavīksne', 'Радуга', 'Regenboog', 'Regenbogen', 'Regnbåge'),
    ('Ocean', 'Okeāns', 'Океан', 'Oceaan', 'Ozean', 'Hav'), ('Lava', 'Lava', 'Лава', 'Lava', 'Lava', 'Lava'),
    ('Forest', 'Mežs', 'Лес', 'Bos', 'Wald', 'Skog'), ('Sunset', 'Saulriets', 'Закат', 'Zonsondergang', 'Sonnenuntergang', 'Solnedgång'),
    ('Amber', 'Dzintars', 'Янтарь', 'Amber', 'Bernstein', 'Bärnsten'), ('Ice', 'Ledus', 'Лёд', 'IJs', 'Eis', 'Is'),
    ('Neon', 'Neons', 'Неон', 'Neon', 'Neon', 'Neon'), ('Lavender', 'Lavanda', 'Лаванда', 'Lavendel', 'Lavendel', 'Lavendel'),
    ('Candy', 'Konfektes', 'Леденец', 'Snoep', 'Bonbon', 'Godis'), ('Festive', 'Svētki', 'Праздник', 'Feestelijk', 'Festlich', 'Festlig'),
    ('Ember', 'Ogles', 'Угли', 'Gloed', 'Glut', 'Glöd'), ('Twilight', 'Krēsla', 'Сумерки', 'Schemering', 'Dämmerung', 'Skymning'),
]
lab = {
    'kicker': I('Effect lab', 'Efektu laboratorija', 'Лаборатория эффектов', 'Effectenlab', 'Effektlabor', 'Effektlabb'),
    'title': I('24 effects, straight from the firmware', '24 efekti tieši no programmatūras', '24 эффекта прямо из прошивки', '24 effecten, rechtstreeks uit de firmware', '24 Effekte, direkt aus der Firmware', '24 effekter, direkt från firmwaren'),
    'text': I('Pick an effect and a palette. Your browser draws all 16 outputs with the same maths the controller uses.',
              'Izvēlieties efektu un paleti. Jūsu pārlūks zīmē visas 16 izejas ar tiem pašiem aprēķiniem, ko izmanto kontrolleris.',
              'Выберите эффект и палитру. Браузер рисует все 16 выходов по тем же формулам, что и контроллер.',
              'Kies een effect en een palet. Uw browser tekent alle 16 uitgangen met dezelfde berekening als de controller.',
              'Wählen Sie einen Effekt und eine Palette. Ihr Browser zeichnet alle 16 Ausgänge mit derselben Mathematik wie der Controller.',
              'Välj en effekt och en palett. Din webbläsare ritar alla 16 utgångar med samma matematik som styrenheten.'),
    'note': I('An illustration in your browser, not a connection to a controller. Real colour and brightness depend on the LEDs and the power supply. The eight strips run one effect, each a little later than the one above.',
              'Ilustrācija jūsu pārlūkā, nevis savienojums ar kontrolleri. Īstā krāsa un spilgtums ir atkarīgi no LED un barošanas. Astoņas lentes rāda vienu efektu, katra nedaudz vēlāk par iepriekšējo.',
              'Это иллюстрация в браузере, а не связь с контроллером. Настоящие цвет и яркость зависят от светодиодов и блока питания. Восемь лент играют один эффект, каждая чуть позже предыдущей.',
              'Een illustratie in uw browser, geen verbinding met een controller. Echte kleur en helderheid hangen af van de LED’s en de voeding. De acht strips spelen één effect, elk iets later dan de vorige.',
              'Eine Darstellung im Browser, keine Verbindung zu einem Controller. Echte Farbe und Helligkeit hängen von den LEDs und dem Netzteil ab. Die acht Streifen spielen einen Effekt, jeder etwas später als der vorige.',
              'En illustration i webbläsaren, ingen anslutning till en styrenhet. Verklig färg och ljusstyrka beror på lysdioderna och nätaggregatet. De åtta slingorna spelar en effekt, var och en lite senare än den ovan.'),
    'l_effect': I('Effect', 'Efekts', 'Эффект', 'Effect', 'Effekt', 'Effekt'),
    'l_palette': I('Palette', 'Palete', 'Палитра', 'Palet', 'Palette', 'Palett'),
    'l_speed': I('Speed', 'Ātrums', 'Скорость', 'Snelheid', 'Tempo', 'Hastighet'),
    'l_pwm_yes': I('Also works on a dimmer', 'Der arī regulatoram', 'Подходит и для диммера', 'Werkt ook op een dimmer', 'Auch für Dimmer', 'Fungerar även på en dimmer'),
    'l_pwm_no': I('Made for pixel strips', 'Paredzēts pikseļu lentēm', 'Для пиксельных лент', 'Gemaakt voor pixelstrips', 'Für Pixelstreifen gemacht', 'Gjord för pixelslingor'),
    'l_strips': I('Pixel strips A1–A8', 'Pikseļu lentes A1–A8', 'Пиксельные ленты A1–A8', 'Pixelstrips A1–A8', 'Pixelstreifen A1–A8', 'Pixelslingor A1–A8'),
    'l_dimmers': I('Dimmers P1–P8', 'Regulatori P1–P8', 'Диммеры P1–P8', 'Dimmers P1–P8', 'Dimmer P1–P8', 'Dimmers P1–P8'),
    'l_canvas': I('Preview of the 16 outputs running the selected effect', '16 izeju priekšskatījums ar izvēlēto efektu', 'Предпросмотр 16 выходов с выбранным эффектом',
                  'Voorbeeld van de 16 uitgangen met het gekozen effect', 'Vorschau der 16 Ausgänge mit dem gewählten Effekt', 'Förhandsvisning av de 16 utgångarna med vald effekt'),
    'effects': [{'id': f[0], 'name': I(*f[1:7]), 'text': I(*f[7:13])} for f in FX],
    # one field per language (keeps the admin form small): names in firmware order 0-13, separated by commas
    'palette_names': dict(zip(L, [', '.join(p[k] for p in PAL) for k in range(6)])),
}

show = {
    'kicker': I('Show control', 'Programmas vadība', 'Управление шоу', 'Showsturing', 'Showsteuerung', 'Showstyrning'),
    'title': I('Scenes, schedules and cues', 'Ainas, grafiki un komandas', 'Сцены, расписания и сигналы', 'Scènes, schema’s en cues', 'Szenen, Zeitpläne und Cues', 'Scener, scheman och cues'),
    'text': I('Light that changes with the hour, the season or the event, with nobody standing at the switch.',
              'Gaisma, kas mainās līdz ar stundu, sezonu vai notikumu, bez neviena pie slēdža.',
              'Свет, который меняется по часам, сезону или событию — без человека у выключателя.',
              'Licht dat meegaat met het uur, het seizoen of het evenement, zonder dat iemand bij de schakelaar staat.',
              'Licht, das sich mit Uhrzeit, Saison oder Anlass ändert, ohne dass jemand am Schalter steht.',
              'Ljus som skiftar med timmen, säsongen eller evenemanget, utan att någon står vid strömbrytaren.'),
    'items': [
        {'icon': 'layers', 'title': I('Scenes', 'Ainas', 'Сцены', 'Scènes', 'Szenen', 'Scener'),
         'text': I('32 saved scenes, with up to 3 layers on every output, mixed as normal, add, multiply or screen.', '32 saglabātas ainas, līdz 3 slāņiem katrā izejā, sajaukti kā normal, add, multiply vai screen.',
                   '32 сохранённые сцены, до 3 слоёв на каждом выходе, смешивание normal, add, multiply или screen.', '32 opgeslagen scènes, tot 3 lagen per uitgang, gemengd als normal, add, multiply of screen.',
                   '32 gespeicherte Szenen, bis zu 3 Ebenen je Ausgang, gemischt als Normal, Add, Multiply oder Screen.', '32 sparade scener, upp till 3 lager per utgång, blandade som normal, add, multiply eller screen.')},
        {'icon': 'sites', 'title': I('Groups', 'Grupas', 'Группы', 'Groepen', 'Gruppen', 'Grupper'),
         'text': I('Up to 8 zones, so one command reaches every letter of a word.', 'Līdz 8 zonām, lai viena komanda sasniegtu katru vārda burtu.', 'До 8 зон: одна команда доходит до каждой буквы слова.',
                   'Tot 8 zones, zodat één opdracht elke letter van een woord bereikt.', 'Bis zu 8 Zonen, damit ein Befehl jeden Buchstaben eines Wortes erreicht.', 'Upp till 8 zoner, så att ett kommando når varje bokstav i ett ord.')},
        {'icon': 'repeat', 'title': I('Timelines', 'Laika secības', 'Таймлайны', 'Tijdlijnen', 'Zeitleisten', 'Tidslinjer'),
         'text': I('Up to 16 steps that play scenes in order, with motion between them.', 'Līdz 16 soļiem, kas atskaņo ainas pēc kārtas ar pārejām starp tām.', 'До 16 шагов, которые проигрывают сцены по порядку с переходами.',
                   'Tot 16 stappen die scènes op volgorde afspelen, met beweging ertussen.', 'Bis zu 16 Schritte, die Szenen der Reihe nach abspielen, mit Bewegung dazwischen.', 'Upp till 16 steg som spelar scener i ordning, med rörelse mellan dem.')},
        {'icon': 'play', 'title': I('Cue lists', 'Komandu saraksti', 'Списки сигналов', 'Cuelijsten', 'Cue-Listen', 'Cuelistor'),
         'text': I('8 lists of 16 cues for events, with a manual crossfade.', '8 saraksti pa 16 komandām pasākumiem, ar manuālu pāreju.', '8 списков по 16 сигналов для мероприятий, с ручным кроссфейдом.',
                   '8 lijsten van 16 cues voor evenementen, met een handmatige crossfade.', '8 Listen mit je 16 Cues für Veranstaltungen, mit manuellem Überblenden.', '8 listor med 16 cues för evenemang, med manuell övertoning.')},
        {'icon': 'calendar', 'title': I('Schedules', 'Grafiki', 'Расписания', 'Schema’s', 'Zeitpläne', 'Scheman'),
         'text': I('Switch scenes by time of day, in the controller’s own time zone.', 'Mainiet ainas pēc diennakts laika kontrollera laika joslā.', 'Смена сцен по времени суток в часовом поясе контроллера.',
                   'Wissel scènes op tijdstip, in de eigen tijdzone van de controller.', 'Szenen nach Uhrzeit wechseln, in der Zeitzone des Controllers.', 'Byt scen efter tid på dygnet, i styrenhetens egen tidszon.')},
        {'icon': 'bolt', 'title': I('Live input', 'Tiešā vadība', 'Живое управление', 'Live-invoer', 'Live-Eingang', 'Direktstyrning'),
         'text': I('A lighting desk can take over through Art-Net or sACN. When its signal stops, the saved scene takes over again.',
                   'Gaismas pults var pārņemt vadību caur Art-Net vai sACN. Kad signāls beidzas, atkal darbojas saglabātā aina.',
                   'Световой пульт может взять управление через Art-Net или sACN. Когда сигнал пропадает, снова играет сохранённая сцена.',
                   'Een lichttafel kan het overnemen via Art-Net of sACN. Valt het signaal weg, dan neemt de opgeslagen scène het weer over.',
                   'Ein Lichtpult kann per Art-Net oder sACN übernehmen. Endet das Signal, übernimmt wieder die gespeicherte Szene.',
                   'Ett ljusbord kan ta över via Art-Net eller sACN. När signalen upphör tar den sparade scenen över igen.')},
    ]}

setup = {
    'kicker': I('Getting started', 'Kā sākt', 'Как начать', 'Aan de slag', 'Erste Schritte', 'Kom igång'),
    'title': I('First light in four steps', 'Pirmā gaisma četros soļos', 'Первый свет за четыре шага', 'Eerste licht in vier stappen', 'Erstes Licht in vier Schritten', 'Första ljuset i fyra steg'),
    'items': [
        {'title': I('Connect the light', 'Pieslēdziet gaismu', 'Подключите свет', 'Sluit het licht aan', 'Licht anschließen', 'Anslut ljuset'),
         'text': I('Power stays off while wiring. We match the LEDs, power supply and wiring to the sign.', 'Vadu pieslēgšanas laikā barošana ir izslēgta. Mēs saskaņojam LED, barošanu un vadus ar izkārtni.',
                   'Во время монтажа питание выключено. Светодиоды, блок питания и проводку мы подбираем под вывеску.', 'Tijdens het bedraden blijft de stroom uit. Wij stemmen LED’s, voeding en bekabeling af op de signing.',
                   'Beim Verdrahten bleibt der Strom aus. LEDs, Netzteil und Verkabelung stimmen wir auf die Anlage ab.', 'Strömmen är av under kopplingen. Vi anpassar lysdioder, nätaggregat och kablage till skylten.')},
        {'title': I('Join the setup network', 'Pievienojieties iestatīšanas tīklam', 'Подключитесь к сети настройки', 'Verbind met het installatienetwerk', 'Mit dem Einrichtungsnetz verbinden', 'Anslut till installationsnätverket'),
         'text': I('A new LEDNode opens its own Wi-Fi, LumiGrid-Setup. Its unique password is shown during commissioning.', 'Jauns LEDNode atver savu Wi-Fi tīklu LumiGrid-Setup. Tā unikālā parole tiek parādīta nodošanas laikā.',
                   'Новый LEDNode открывает свою сеть Wi-Fi LumiGrid-Setup. Уникальный пароль показывается при пусконаладке.', 'Een nieuwe LEDNode opent een eigen wifi-netwerk, LumiGrid-Setup. Het unieke wachtwoord verschijnt bij de inbedrijfstelling.',
                   'Ein neuer LEDNode öffnet ein eigenes WLAN, LumiGrid-Setup. Das individuelle Passwort wird bei der Inbetriebnahme angezeigt.', 'En ny LEDNode öppnar ett eget Wi-Fi, LumiGrid-Setup. Det unika lösenordet visas vid driftsättningen.')},
        {'title': I('Create your access key', 'Izveidojiet piekļuves atslēgu', 'Создайте ключ доступа', 'Maak uw toegangssleutel', 'Zugangsschlüssel anlegen', 'Skapa din åtkomstnyckel'),
         'text': I('Open 192.168.4.1 and set a private admin key of 16 to 64 characters. Operators and installers can have their own keys.',
                   'Atveriet 192.168.4.1 un iestatiet privātu administratora atslēgu no 16 līdz 64 rakstzīmēm. Operatoriem un uzstādītājiem var būt savas atslēgas.',
                   'Откройте 192.168.4.1 и задайте личный ключ администратора длиной от 16 до 64 символов. У операторов и монтажников могут быть свои ключи.',
                   'Open 192.168.4.1 en stel een eigen beheerderssleutel van 16 tot 64 tekens in. Operators en installateurs kunnen eigen sleutels krijgen.',
                   'Öffnen Sie 192.168.4.1 und legen Sie einen privaten Admin-Schlüssel mit 16 bis 64 Zeichen fest. Bediener und Installateure können eigene Schlüssel bekommen.',
                   'Öppna 192.168.4.1 och ange en privat adminnyckel på 16 till 64 tecken. Operatörer och installatörer kan få egna nycklar.')},
        {'title': I('Test, then save a scene', 'Pārbaudiet un saglabājiet ainu', 'Проверьте и сохраните сцену', 'Test en sla een scène op', 'Testen, dann Szene speichern', 'Testa och spara en scen'),
         'text': I('Name an output, try it at low brightness and save your first scene. Add schedules when you need them.', 'Nosauciet izeju, pārbaudiet to ar mazu spilgtumu un saglabājiet pirmo ainu. Grafikus pievienojiet, kad tie vajadzīgi.',
                   'Назовите выход, проверьте его на малой яркости и сохраните первую сцену. Расписания добавьте, когда понадобятся.', 'Geef een uitgang een naam, test op lage helderheid en sla uw eerste scène op. Voeg schema’s toe wanneer u ze nodig hebt.',
                   'Benennen Sie einen Ausgang, testen Sie ihn mit wenig Helligkeit und speichern Sie die erste Szene. Zeitpläne kommen dazu, wenn Sie sie brauchen.', 'Namnge en utgång, prova den på låg ljusstyrka och spara din första scen. Lägg till scheman när du behöver dem.')},
    ]}

local = {
    'kicker': I('On your network', 'Jūsu tīklā', 'В вашей сети', 'In uw netwerk', 'In Ihrem Netz', 'I ditt nätverk'),
    'title': I('Your light stays yours', 'Jūsu gaisma paliek jūsu', 'Ваш свет остаётся вашим', 'Uw licht blijft van u', 'Ihr Licht bleibt Ihres', 'Ditt ljus förblir ditt'),
    'text': I('LUMIGRID runs on the controller itself. Everyday control needs no cloud account and no subscription.', 'LUMIGRID darbojas pašā kontrollerī. Ikdienas vadībai nav vajadzīgs ne mākoņkonts, ne abonements.',
              'LUMIGRID работает на самом контроллере. Для повседневного управления не нужны ни облачный аккаунт, ни подписка.', 'LUMIGRID draait op de controller zelf. Voor dagelijkse bediening is geen cloudaccount en geen abonnement nodig.',
              'LUMIGRID läuft auf dem Controller selbst. Für die tägliche Steuerung braucht es weder Cloud-Konto noch Abo.', 'LUMIGRID körs på själva styrenheten. Vardaglig styrning kräver varken molnkonto eller abonnemang.'),
    'items': [
        {'icon': 'lock', 'title': I('Keys and roles', 'Atslēgas un lomas', 'Ключи и роли', 'Sleutels en rollen', 'Schlüssel und Rollen', 'Nycklar och roller'),
         'text': I('Operator, installer and admin each have their own access. Browser sessions end after 30 minutes without use.', 'Operatoram, uzstādītājam un administratoram ir sava piekļuve. Pārlūka sesija beidzas pēc 30 minūtēm bez darbības.',
                   'У оператора, монтажника и администратора свой доступ. Сеанс в браузере завершается после 30 минут бездействия.', 'Operator, installateur en beheerder hebben elk eigen toegang. Browsersessies stoppen na 30 minuten zonder gebruik.',
                   'Bediener, Installateur und Admin haben jeweils eigenen Zugang. Browsersitzungen enden nach 30 Minuten ohne Nutzung.', 'Operatör, installatör och admin har var sin åtkomst. Webbsessioner avslutas efter 30 minuter utan användning.')},
        {'icon': 'repeat', 'title': I('Safe updates', 'Droši atjauninājumi', 'Безопасные обновления', 'Veilige updates', 'Sichere Updates', 'Säkra uppdateringar'),
         'text': I('A new firmware version is checked before it starts. If it does not start properly, the controller returns to the previous one.', 'Jaunā programmatūras versija tiek pārbaudīta pirms palaišanas. Ja tā nestartē pareizi, kontrolleris atgriežas pie iepriekšējās.',
                   'Новая версия прошивки проверяется перед запуском. Если она не стартует как надо, контроллер возвращается к предыдущей.', 'Een nieuwe firmwareversie wordt gecontroleerd voordat die start. Start ze niet goed, dan keert de controller terug naar de vorige.',
                   'Eine neue Firmware-Version wird vor dem Start geprüft. Startet sie nicht richtig, kehrt der Controller zur vorherigen zurück.', 'En ny firmwareversion kontrolleras innan den startar. Startar den inte som den ska går styrenheten tillbaka till den förra.')},
        {'icon': 'download', 'title': I('Backup and restore', 'Rezerves kopija', 'Резервная копия', 'Back-up en herstel', 'Sicherung und Wiederherstellung', 'Säkerhetskopia'),
         'text': I('Export the controller’s settings and scenes, and bring them back after a change or a replacement.', 'Eksportējiet kontrollera iestatījumus un ainas un atjaunojiet tos pēc izmaiņām vai nomaiņas.',
                   'Выгрузите настройки и сцены контроллера и верните их после изменений или замены.', 'Exporteer de instellingen en scènes van de controller en zet ze terug na een wijziging of vervanging.',
                   'Exportieren Sie Einstellungen und Szenen des Controllers und spielen Sie sie nach einer Änderung oder einem Tausch zurück.', 'Exportera styrenhetens inställningar och scener och återställ dem efter en ändring eller ett byte.')},
        {'icon': 'lab', 'title': I('Where it stands', 'Pašreizējais stāvoklis', 'Где мы сейчас', 'Waar het staat', 'Aktueller Stand', 'Var det står'),
         'text': I('LUMIGRID firmware 1.0 is a release candidate, in bench testing at our research department. Talk to us about what your project needs.',
                   'LUMIGRID programmatūra 1.0 ir laidiena kandidāts, ko testējam mūsu pētniecības nodaļā. Pastāstiet mums, kas vajadzīgs jūsu projektam.',
                   'Прошивка LUMIGRID 1.0 — релиз-кандидат, она проходит стендовые испытания в нашем исследовательском отделе. Расскажите нам, что нужно вашему проекту.',
                   'LUMIGRID-firmware 1.0 is een release candidate en wordt getest op de testbank van onze onderzoeksafdeling. Vertel ons wat uw project nodig heeft.',
                   'Die LUMIGRID-Firmware 1.0 ist ein Release Candidate und wird in unserer Forschungsabteilung auf dem Prüfstand getestet. Sprechen Sie mit uns über Ihr Projekt.',
                   'LUMIGRID-firmware 1.0 är en releasekandidat som bänktestas på vår forskningsavdelning. Berätta vad ditt projekt behöver.')},
    ]}

cta = {
    'title': I('Planning a sign that should move?', 'Plānojat izkārtni, kurai jākustas?', 'Планируете вывеску, которая должна оживать?', 'Signing gepland die moet bewegen?', 'Sie planen Werbung, die sich bewegen soll?', 'Planerar du en skylt som ska röra sig?'),
    'text': I('Tell us about the sign and how it should behave. Signage Research plans the light with you.', 'Pastāstiet par izkārtni un to, kā tai jāuzvedas. Signage Research kopā ar jums saplānos gaismu.',
              'Расскажите о вывеске и о том, как она должна себя вести. Signage Research спланирует свет вместе с вами.', 'Vertel ons over de signing en hoe die zich moet gedragen. Signage Research plant het licht met u.',
              'Erzählen Sie uns von der Anlage und wie sie wirken soll. Signage Research plant das Licht mit Ihnen.', 'Berätta om skylten och hur den ska bete sig. Signage Research planerar ljuset med dig.'),
    'button': I('Talk to Signage Research', 'Sazināties ar Signage Research', 'Связаться с Signage Research', 'Neem contact op met Signage Research', 'Signage Research kontaktieren', 'Kontakta Signage Research'),
    'docs': I('Technical guides on GitHub', 'Tehniskās pamācības GitHub', 'Техническая документация на GitHub', 'Technische handleidingen op GitHub', 'Technische Anleitungen auf GitHub', 'Tekniska guider på GitHub'),
    'docs_url': 'https://github.com/slafftrosheen/lumigrid',
}

seo = {'title': I('LUMIGRID lighting control and LEDNode controller', 'LUMIGRID apgaismojuma vadība un LEDNode kontrolleris', 'Управление светом LUMIGRID и контроллер LEDNode',
                  'LUMIGRID-lichtsturing en LEDNode-controller', 'LUMIGRID-Lichtsteuerung und LEDNode-Controller', 'LUMIGRID ljusstyrning och LEDNode-styrenhet'),
       'description': I('LUMIGRID by Reclame Fabriek: the LEDNode controller drives 8 pixel strips and 8 dimmers with 24 effects, scenes and schedules from a browser on your local network.',
                        'Reclame Fabriek LUMIGRID: LEDNode kontrolleris vada 8 pikseļu lentes un 8 regulatorus ar 24 efektiem, ainām un grafikiem no pārlūka lokālajā tīklā.',
                        'LUMIGRID от Reclame Fabriek: контроллер LEDNode управляет 8 пиксельными лентами и 8 диммерами — 24 эффекта, сцены и расписания из браузера в локальной сети.',
                        'LUMIGRID van Reclame Fabriek: de LEDNode-controller stuurt 8 pixelstrips en 8 dimmers aan met 24 effecten, scènes en schema’s vanuit een browser in uw netwerk.',
                        'LUMIGRID von Reclame Fabriek: Der LEDNode-Controller steuert 8 Pixelstreifen und 8 Dimmer mit 24 Effekten, Szenen und Zeitplänen im Browser im lokalen Netz.',
                        'LUMIGRID från Reclame Fabriek: LEDNode-styrenheten driver 8 pixelslingor och 8 dimmers med 24 effekter, scener och scheman från en webbläsare i ditt nätverk.')}

page = {'seo': seo, 'head': head, 'stats': stats, 'system': system, 'node': node, 'lab': lab, 'show': show, 'setup': setup, 'local': local, 'cta': cta}

# sanity: every translatable value has all six languages
def walk(v, p=''):
    if isinstance(v, dict):
        if set(v) == set(L):
            for k in L: assert isinstance(v[k], str) and v[k].strip(), (p, k)
        else:
            for k, x in v.items(): walk(x, p + '.' + k)
    elif isinstance(v, list):
        for i, x in enumerate(v): walk(x, f'{p}[{i}]')
walk(page)

pf = os.path.join(ROOT, 'pages', 'lumigrid.json')
old = json.load(open(pf, encoding='utf-8')) if os.path.exists(pf) else None
if old is None or '--force' in sys.argv:
    json.dump(page, open(pf, 'w', encoding='utf-8'), ensure_ascii=False, indent=4)
    print('wrote', pf)
else:
    print('kept existing', pf, '(use --force to overwrite the admin\'s edits)')

# menu + Signage Research button: the old lander address is dead; both now open the page on this site
sf = os.path.join(ROOT, 'settings.json'); s = json.load(open(sf, encoding='utf-8'))
if 'lumigrid-lander' in (s.get('lumigrid_url') or '') or not s.get('lumigrid_url'):
    s['lumigrid_url'] = '/lumigrid/'; json.dump(s, open(sf, 'w', encoding='utf-8'), ensure_ascii=False, indent=4); print('menu link -> /lumigrid/')
rf = os.path.join(ROOT, 'pages', 'signage-research.json'); r = json.load(open(rf, encoding='utf-8'))
if 'lumigrid-lander' in (r.get('lumigrid', {}).get('url') or ''):
    r['lumigrid']['url'] = '/lumigrid/'; json.dump(r, open(rf, 'w', encoding='utf-8'), ensure_ascii=False, indent=4); print('research button -> /lumigrid/')

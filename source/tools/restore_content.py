#!/usr/bin/env python3
"""Website 2.2 · put back everything the 2.1 migration condensed or dropped (audited against the live WordPress site).
All restored texts come with LV / RU / NL / DE / SV translations."""
import json, os
D = '/tmp/rfcms/site/data/'
def jr(p): return json.load(open(D + p))
def jw(p, o): json.dump(o, open(D + p, 'w'), ensure_ascii=False, indent=1)
def T(en, lv, ru, nl, de, sv): return {'en': en, 'lv': lv, 'ru': ru, 'nl': nl, 'de': de, 'sv': sv}
media = jr('media.json')
def need(*ids):
    for i in ids: assert i in media, 'missing media ' + i

# ---------------------------------------------------------------- interface texts
ui = jr('ui.json')
ui.update({
 'custom_depth': T("Custom letter depths are also available upon request.",
   "Pēc pieprasījuma pieejams arī individuāls burtu dziļums.",
   "По запросу возможна и нестандартная глубина букв.",
   "Andere letterdieptes zijn op aanvraag ook mogelijk.",
   "Individuelle Buchstabentiefen sind auf Anfrage ebenfalls möglich.",
   "Andra bokstavsdjup finns också på begäran."),
 'no_custom_depth': T("Custom letter depths are not available for this type of profile.",
   "Šim profila veidam individuāls burtu dziļums nav pieejams.",
   "Для этого типа профиля нестандартная глубина букв невозможна.",
   "Voor dit type profiel zijn geen andere letterdieptes mogelijk.",
   "Für diesen Profiltyp sind keine individuellen Buchstabentiefen möglich.",
   "För den här profiltypen finns inga andra bokstavsdjup."),
 'custom_text': T("Additional materials, components and finishes are available upon special request, subject to price and availability. Depending on the specific requirements, design or usage, certain components or construction aspects may be modified in consultation with the customer.",
   "Papildu materiāli, komponenti un apdares pieejami pēc īpaša pieprasījuma atkarībā no cenas un pieejamības. Atkarībā no konkrētajām prasībām, dizaina vai lietojuma atsevišķus komponentus vai konstrukcijas elementus var mainīt, vienojoties ar klientu.",
   "Дополнительные материалы, комплектующие и варианты отделки доступны по специальному запросу, в зависимости от цены и наличия. В зависимости от конкретных требований, дизайна или условий эксплуатации отдельные комплектующие или элементы конструкции могут быть изменены по согласованию с заказчиком.",
   "Andere materialen, componenten en afwerkingen zijn op speciaal verzoek beschikbaar, afhankelijk van prijs en beschikbaarheid. Afhankelijk van de specifieke eisen, het ontwerp of het gebruik kunnen bepaalde componenten of constructiedetails in overleg met de klant worden aangepast.",
   "Weitere Materialien, Komponenten und Oberflächen sind auf besondere Anfrage erhältlich, je nach Preis und Verfügbarkeit. Je nach Anforderungen, Design oder Einsatz können einzelne Komponenten oder Konstruktionsdetails in Absprache mit dem Kunden angepasst werden.",
   "Fler material, komponenter och ytbehandlingar finns på särskild begäran, beroende på pris och tillgänglighet. Beroende på krav, design eller användning kan vissa komponenter eller konstruktionsdetaljer ändras i samråd med kunden."),
 'construction': T("Construction", "Konstrukcija", "Конструкция", "Constructie", "Aufbau", "Konstruktion"),
 'ral_title': T("Colours: RAL and NCS", "Krāsas: RAL un NCS", "Цвета: RAL и NCS", "Kleuren: RAL en NCS", "Farben: RAL und NCS", "Kulörer: RAL och NCS"),
 'ral_alt': T("RAL colour guide", "RAL krāsu karte", "Палитра цветов RAL", "RAL-kleurenwaaier", "RAL-Farbkarte", "RAL-färgkarta"),
 'customisation': T("Customisation", "Pielāgošana", "Индивидуальные решения", "Maatwerk", "Individuelle Anpassung", "Anpassning"),
})
jw('ui.json', ui)

# ---------------------------------------------------------------- settings: RAL image used on every profile
s = jr('settings.json'); s['ral_image'] = 'ral-colour-guide-image-v1-01'; need(s['ral_image']); jw('settings.json', s)

# ---------------------------------------------------------------- profiles: custom depth yes/no + construction drawing
NO_CUSTOM = {'profile-7-rcp', 'profile-7-dtf', 'profile-7-tgp'}
DIAGRAM = {'profile-1-2': 'profile-1-diagram', 'profile-2': 'profile-2-diagram', 'profile-4': 'profile-4-diagram', 'profile-5': 'profile-5-diagram',
           'profile-7-3': 'profile-7-3-diagram', 'profile-7-dtf': 'profile-7-dtf-diagram', 'profile-7-rcp': 'profile-7-rcp-diagram',
           'profile-7-tgp': 'profile-7-tgp-diagram', 'profile-8': 'profile-8-diagram'}
for f in os.listdir(D + 'profiles'):
    p = jr('profiles/' + f); slug = p['slug']
    p['custom_depth'] = slug not in NO_CUSTOM
    if slug in DIAGRAM: need(DIAGRAM[slug]); p['diagram'] = DIAGRAM[slug]
    else: p.setdefault('diagram', '')
    jw('profiles/' + f, p)

# ---------------------------------------------------------------- home
h = jr('pages/home.json')
hero_imgs = [h['hero']['image'], '67062340-1106435422887498-8875652871845052416-n', '434575192-940810241383816-5151635588363468865-n-1', 'kfc-restaurant-2']
need(*hero_imgs); h['hero']['images'] = hero_imgs[1:]          # extra slides; 'image' stays the first slide (and the LCP image)
svc = {it['icon']: it for it in h['services']['items']}
SV = h['services']['items']
SV[0]['text'] = T(
 "Our core division specialises in the design, manufacturing and installation of custom signage solutions, including 3D box letters, LED displays and lightboxes, and architectural signage. We are committed to providing a personalised signage service, ensuring your project is handled with the utmost care and professionalism. Well-established logistics allow us to partner with customers from Europe and overseas.",
 "Mūsu galvenā nodaļa specializējas individuālu izkārtņu projektēšanā, ražošanā un uzstādīšanā: 3D tilpuma burti, LED ekrāni un gaismas kastes, arhitektūras izkārtnes. Mēs nodrošinām personīgu apkalpošanu un rūpējamies, lai jūsu projekts tiktu īstenots ar vislielāko rūpību un profesionalitāti. Labi izveidota loģistika ļauj mums sadarboties ar klientiem no Eiropas un aiz okeāna.",
 "Наше основное подразделение специализируется на проектировании, производстве и монтаже вывесок на заказ: объёмные 3D-буквы, LED-экраны и световые короба, архитектурные вывески. Мы работаем с каждым клиентом лично, чтобы ваш проект был выполнен с максимальной тщательностью и профессионализмом. Отлаженная логистика позволяет нам сотрудничать с клиентами из Европы и других континентов.",
 "Onze kernafdeling is gespecialiseerd in het ontwerpen, produceren en monteren van signing op maat: 3D-doosletters, LED-displays en lichtbakken, architectonische signing. We bieden een persoonlijke service en zorgen dat uw project met de grootste zorg en professionaliteit wordt uitgevoerd. Dankzij een goed georganiseerde logistiek werken we samen met klanten in heel Europa en daarbuiten.",
 "Unser Kernbereich ist auf Planung, Fertigung und Montage individueller Werbeanlagen spezialisiert: 3D-Profilbuchstaben, LED-Displays und Leuchtkästen, Architektur-Beschilderung. Wir betreuen jedes Projekt persönlich und setzen es mit größter Sorgfalt und Professionalität um. Dank eingespielter Logistik arbeiten wir mit Kunden in ganz Europa und in Übersee.",
 "Vår huvudavdelning är specialiserad på att konstruera, tillverka och montera skyltar på beställning: 3D-bokstäver, LED-skärmar och ljuslådor, arkitektonisk skyltning. Vi erbjuder personlig service och ser till att ditt projekt hanteras med största omsorg och professionalism. Väl etablerad logistik gör att vi samarbetar med kunder i Europa och utomlands.")
SV[1]['text'] = T(
 "Through a strategic partnership with BCAMCNC, a globally recognised leader in CNC technology, we offer high-performance routers and laser cutting systems for industries such as woodworking, metal fabrication and signage production. CubeCNC’s services include installation, comprehensive training and ongoing support, ensuring that clients can maximise the productivity and accuracy of their CNC machinery.",
 "Stratēģiskā partnerībā ar BCAMCNC, pasaulē atzītu CNC tehnoloģiju līderi, piedāvājam augstas veiktspējas frēzes un lāzergriešanas sistēmas kokapstrādei, metālapstrādei un izkārtņu ražošanai. CubeCNC pakalpojumi ietver uzstādīšanu, pilnu apmācību un pastāvīgu atbalstu, lai klienti varētu maksimāli izmantot savu CNC iekārtu ražīgumu un precizitāti.",
 "В стратегическом партнёрстве с BCAMCNC, признанным мировым лидером в технологиях ЧПУ, мы поставляем высокопроизводительные фрезерные станки и системы лазерной резки для деревообработки, металлообработки и производства вывесок. Услуги CubeCNC включают установку, полное обучение и постоянную поддержку, чтобы клиенты использовали производительность и точность своих станков с ЧПУ по максимуму.",
 "Via een strategisch partnerschap met BCAMCNC, wereldwijd erkend leider in CNC-technologie, leveren we krachtige freesmachines en lasersnijsystemen voor houtbewerking, metaalbewerking en signingproductie. De diensten van CubeCNC omvatten installatie, uitgebreide training en doorlopende ondersteuning, zodat klanten het maximale halen uit de productiviteit en nauwkeurigheid van hun CNC-machines.",
 "In strategischer Partnerschaft mit BCAMCNC, einem weltweit anerkannten Marktführer für CNC-Technologie, liefern wir leistungsstarke Fräsmaschinen und Laserschneidanlagen für Holzbearbeitung, Metallverarbeitung und Werbetechnik. Die Leistungen von CubeCNC umfassen Installation, umfassende Schulung und laufenden Support, damit Kunden Produktivität und Präzision ihrer CNC-Maschinen voll ausschöpfen.",
 "Genom ett strategiskt partnerskap med BCAMCNC, en globalt erkänd ledare inom CNC-teknik, levererar vi högpresterande fräsar och laserskärsystem för träbearbetning, metallbearbetning och skylttillverkning. CubeCNC:s tjänster omfattar installation, grundlig utbildning och löpande support, så att kunderna får ut maximal produktivitet och precision av sina CNC-maskiner.")
SV[2]['text'] = T(
 "Our in-house Signage Research Department continually develops advanced products such as fully customisable, waterproof LED Neon NEW GEN tubes and other special signage products, powered by custom-built LED controllers, so you stay ahead of the competition in your industry.",
 "Mūsu pašu Izkārtņu izpētes nodaļa nepārtraukti izstrādā progresīvus produktus, piemēram, pilnībā pielāgojamas, ūdensizturīgas LED Neon NEW GEN caurules un citas īpašas izkārtnes ar pašu būvētiem LED kontrolleriem, lai jūs vienmēr būtu soli priekšā konkurentiem savā nozarē.",
 "Наш собственный отдел исследований вывесок постоянно разрабатывает передовые продукты: полностью настраиваемые водонепроницаемые трубки LED Neon NEW GEN и другие специальные вывески на собственных LED-контроллерах, чтобы вы всегда были на шаг впереди конкурентов в своей отрасли.",
 "Onze eigen afdeling Onderzoek & Ontwikkeling ontwikkelt voortdurend geavanceerde producten, zoals volledig aanpasbare, waterdichte LED Neon NEW GEN-buizen en andere speciale signing, aangestuurd door zelfgebouwde LED-controllers, zodat u de concurrentie in uw branche altijd een stap voor bent.",
 "Unsere hauseigene Abteilung Forschung & Entwicklung entwickelt laufend fortschrittliche Produkte wie vollständig anpassbare, wasserdichte LED Neon NEW GEN-Röhren und weitere Spezialanlagen, gesteuert von selbst gebauten LED-Controllern, damit Sie Ihrer Konkurrenz stets einen Schritt voraus sind.",
 "Vår egen avdelning för forskning och utveckling tar hela tiden fram avancerade produkter som helt anpassningsbara, vattentäta LED Neon NEW GEN-rör och andra specialskyltar, styrda av egenbyggda LED-styrenheter, så att du ligger steget före konkurrenterna i din bransch.")
SV[3]['title'] = T("CubeMöbel · transforming custom furniture design", "CubeMöbel · mēbeles pēc individuāla dizaina", "CubeMöbel · мебель по индивидуальному дизайну",
                   "CubeMöbel · meubelontwerp op maat", "CubeMöbel · Möbeldesign nach Maß", "CubeMöbel · möbeldesign på beställning")
SV[3]['text'] = T(
 "At CubeMöbel, we specialise in creating high-quality custom furniture designed to enhance corporate environments and HORECA spaces. Whether it’s elegant office solutions, bespoke hotel furnishings or functional restaurant setups, our team is dedicated to delivering tailor-made designs that blend style with durability. Using premium materials and innovative craftsmanship, CubeMöbel transforms your spaces into refined, functional environments that reflect your brand’s identity.",
 "CubeMöbel specializējas augstas kvalitātes individuālu mēbeļu izgatavošanā, kas uzlabo biroju vidi un HORECA telpas. Vai tie būtu eleganti biroja risinājumi, viesnīcu mēbeles pēc pasūtījuma vai funkcionāls restorāna aprīkojums, mūsu komanda rada pielāgotus dizainus, kas apvieno stilu un izturību. Izmantojot augstākās kvalitātes materiālus un inovatīvu meistarību, CubeMöbel pārvērš jūsu telpas izsmalcinātā, funkcionālā vidē, kas atspoguļo jūsu zīmola identitāti.",
 "CubeMöbel специализируется на создании высококачественной мебели на заказ для офисов и пространств HORECA. Элегантные офисные решения, мебель для отелей по индивидуальному проекту или функциональное оснащение ресторанов: наша команда создаёт дизайн, в котором стиль сочетается с долговечностью. Благодаря премиальным материалам и мастерству CubeMöbel превращает ваши помещения в продуманную, функциональную среду, отражающую характер вашего бренда.",
 "CubeMöbel is gespecialiseerd in hoogwaardig meubilair op maat voor kantoren en HORECA-ruimtes. Of het nu gaat om elegante kantooroplossingen, hotelinrichting op maat of functionele restaurantopstellingen: ons team levert ontwerpen op maat die stijl en duurzaamheid combineren. Met hoogwaardige materialen en vakmanschap maakt CubeMöbel van uw ruimtes verfijnde, functionele omgevingen die de identiteit van uw merk weerspiegelen.",
 "CubeMöbel ist auf hochwertige Möbel nach Maß für Büros und HORECA-Räume spezialisiert. Ob elegante Bürolösungen, maßgefertigte Hotelmöbel oder funktionale Restaurantausstattung: Unser Team entwickelt individuelle Entwürfe, die Stil und Langlebigkeit verbinden. Mit hochwertigen Materialien und handwerklichem Können verwandelt CubeMöbel Ihre Räume in durchdachte, funktionale Umgebungen, die die Identität Ihrer Marke widerspiegeln.",
 "CubeMöbel är specialiserat på högkvalitativa möbler på beställning för kontor och HORECA-miljöer. Oavsett om det gäller eleganta kontorslösningar, skräddarsydd hotellinredning eller funktionella restauranginredningar tar vårt team fram design som förenar stil och hållbarhet. Med förstklassiga material och gediget hantverk förvandlar CubeMöbel dina lokaler till genomtänkta, funktionella miljöer som speglar ditt varumärkes identitet.")
# the photos that sat next to each service on the old home page
for it, mid in zip(SV, ['', '09-01', 'service-3', 'mobel-2-1']):
    if mid: need(mid)
    it['image'] = mid
h['team']['title'] = T("Strong team – fulfilling all your wishes", "Spēcīga komanda – izpildām visas jūsu vēlmes", "Сильная команда — исполняем все ваши пожелания",
                      "Een sterk team dat al uw wensen waarmaakt", "Ein starkes Team, das all Ihre Wünsche erfüllt", "Ett starkt team som uppfyller alla dina önskemål")
# full factory gallery, in the old site's order (2.1 showed 8 of 28)
FACT = ['24', '16', '15', '14', '12d', '12c', '12b', '12a', '12', '11', '17', '18', '19', '20', '21', '22', '23', '10', '9', '8', '7', '6', '5', '4', '3', '1', '2-1', '2']
need(*FACT); h['factory']['gallery'] = FACT
# product profiles on the home page: the old site showed every profile in a carousel
h['profiles']['show'] = 'all'
jw('pages/home.json', h)

# ---------------------------------------------------------------- page header photos (they were CSS backgrounds on the old site)
for pid, mid in {'product-profiles': '29356837-776801649184212-908443522212823040-n', 'projects': '433120922-940810311383809-3480163619584564884-n-2',
                 'signage-research': '434575192-940810241383816-5151635588363468865-n-1', 'contact': '67062340-1106435422887498-8875652871845052416-n'}.items():
    need(mid); p = jr(f'pages/{pid}.json'); p['head']['image'] = mid; jw(f'pages/{pid}.json', p)

# ---------------------------------------------------------------- Signage Research: the full original text
r = jr('pages/signage-research.json')
r['intro']['body'] = T(
"""Over the past five years, Cube has faced a growing number of requests for highly specialised, custom projects, each requiring a unique touch, advanced development and precise attention to detail. To meet this demand and continue pushing the boundaries of signage innovation, we launched the **Signage Research Department** at Reclame Fabriek: a dedicated space where cutting-edge techniques, materials and technologies come together to bring groundbreaking ideas to life.

This department represents the next chapter in Cube’s evolution, allowing us to develop tailor-made signage solutions for our largest clients, especially in the HORECA sector and corporate environments.

The creation of the Signage Research Department was a logical step in our company’s growth, driven by the complexity of the orders we were receiving. These projects were no longer just about producing signs: they involved intricate designs, advanced materials and unique installations. The Cube within Reclame Fabriek became our innovation hub, where our team of experts quickly grew from a few core members into a dedicated group that handles these advanced projects from concept to completion. While many were involved along the way, only the most innovative and skilled stayed to continue shaping the department.

We’ve proudly showcased our work at major expos in **Stockholm** and **Finland**, where we displayed our groundbreaking signage solutions, further establishing Cube as a leader in the industry.""",
"""Pēdējo piecu gadu laikā Cube ir saņēmis arvien vairāk pieprasījumu pēc ļoti specializētiem, individuāliem projektiem, un katram no tiem vajadzīga īpaša pieeja, padziļināta izstrāde un precīza uzmanība detaļām. Lai apmierinātu šo pieprasījumu un turpinātu paplašināt izkārtņu inovāciju robežas, mēs Reclame Fabriek izveidojām **Izkārtņu izpētes nodaļu**: īpašu vietu, kur apvienojas modernākās tehnikas, materiāli un tehnoloģijas, lai īstenotu novatoriskas idejas.

Šī nodaļa ir nākamā nodaļa Cube attīstībā. Tā ļauj mums izstrādāt pielāgotus izkārtņu risinājumus lielākajiem klientiem, īpaši HORECA nozarē un korporatīvajā vidē.

Izkārtņu izpētes nodaļas izveide bija loģisks solis mūsu uzņēmuma izaugsmē, ko noteica saņemto pasūtījumu sarežģītība. Šie projekti vairs nebija tikai izkārtņu ražošana: tajos bija sarežģīti dizaini, progresīvi materiāli un unikālas instalācijas. Cube Reclame Fabriek ietvaros kļuva par mūsu inovāciju centru, kur ekspertu komanda ātri izauga no dažiem galvenajiem dalībniekiem par īpašu grupu, kas vada šos sarežģītos projektus no koncepcijas līdz pabeigšanai. Lai gan ceļā bija iesaistīti daudzi, palika tikai paši novatoriskākie un prasmīgākie, lai turpinātu veidot nodaļu.

Ar lepnumu esam prezentējuši savu darbu lielās izstādēs **Stokholmā** un **Somijā**, kur demonstrējām savus novatoriskos izkārtņu risinājumus un vēl vairāk nostiprinājām Cube kā nozares līdera pozīcijas.""",
"""За последние пять лет Cube получал всё больше запросов на узкоспециализированные проекты на заказ, и каждый из них требовал особого подхода, глубокой проработки и точного внимания к деталям. Чтобы ответить на этот спрос и продолжать раздвигать границы инноваций в вывесках, мы открыли в Reclame Fabriek **отдел исследований вывесок**: особое пространство, где передовые технологии, материалы и методы объединяются, чтобы воплощать новаторские идеи.

Этот отдел — новая глава в развитии Cube. Он позволяет нам разрабатывать индивидуальные решения для наших крупнейших клиентов, особенно в сфере HORECA и в корпоративной среде.

Создание отдела исследований вывесок стало логичным шагом в росте компании, продиктованным сложностью поступающих заказов. Эти проекты были уже не просто производством вывесок: они включали сложный дизайн, современные материалы и уникальный монтаж. Cube внутри Reclame Fabriek стал нашим центром инноваций, где команда экспертов быстро выросла из нескольких ключевых сотрудников в отдельную группу, которая ведёт такие сложные проекты от концепции до сдачи. Хотя по пути в работе участвовали многие, остались только самые изобретательные и умелые, чтобы и дальше развивать отдел.

Мы с гордостью представляли свои работы на крупных выставках в **Стокгольме** и **Финляндии**, где показали наши новаторские решения и укрепили позицию Cube как лидера отрасли.""",
"""De afgelopen vijf jaar kreeg Cube steeds meer aanvragen voor zeer gespecialiseerde projecten op maat, die elk een eigen aanpak, geavanceerde ontwikkeling en nauwkeurige aandacht voor detail vroegen. Om aan die vraag te voldoen en de grenzen van innovatie in signing te blijven verleggen, zijn we bij Reclame Fabriek gestart met de **afdeling Onderzoek & Ontwikkeling**: een eigen plek waar de nieuwste technieken, materialen en technologieën samenkomen om baanbrekende ideeën werkelijkheid te maken.

Deze afdeling is het volgende hoofdstuk in de ontwikkeling van Cube. Ze stelt ons in staat signingoplossingen op maat te ontwikkelen voor onze grootste klanten, vooral in de HORECA en zakelijke omgevingen.

De oprichting van de afdeling was een logische stap in de groei van ons bedrijf, ingegeven door de complexiteit van de opdrachten die we ontvingen. Die projecten gingen niet meer alleen over het maken van signing: ze vroegen om ingewikkelde ontwerpen, geavanceerde materialen en unieke installaties. Cube binnen Reclame Fabriek werd ons innovatiecentrum, waar ons team van experts snel uitgroeide van een paar kernleden tot een vaste groep die deze geavanceerde projecten van concept tot oplevering begeleidt. Velen hebben onderweg meegewerkt, maar alleen de meest innovatieve en vakkundige mensen zijn gebleven om de afdeling verder vorm te geven.

We hebben ons werk met trots getoond op grote beurzen in **Stockholm** en **Finland**, waar we onze baanbrekende signingoplossingen presenteerden en de positie van Cube als marktleider verder versterkten.""",
"""In den letzten fünf Jahren erhielt Cube immer mehr Anfragen nach hochspezialisierten Sonderprojekten, von denen jedes eine eigene Handschrift, aufwendige Entwicklung und präzise Liebe zum Detail verlangte. Um diese Nachfrage zu bedienen und die Grenzen der Innovation in der Werbetechnik weiter zu verschieben, haben wir bei Reclame Fabriek die **Abteilung Forschung & Entwicklung** gegründet: einen eigenen Bereich, in dem modernste Techniken, Materialien und Technologien zusammenkommen, um bahnbrechende Ideen umzusetzen.

Die Abteilung ist das nächste Kapitel in der Entwicklung von Cube. Mit ihr entwickeln wir maßgeschneiderte Lösungen für unsere größten Kunden, vor allem in der HORECA-Branche und im Unternehmensumfeld.

Die Gründung der Abteilung war ein logischer Schritt im Wachstum unseres Unternehmens, getrieben von der Komplexität der Aufträge, die wir erhielten. Diese Projekte bestanden nicht mehr nur aus der Fertigung von Werbeanlagen: Sie umfassten anspruchsvolle Designs, fortschrittliche Materialien und einzigartige Installationen. Cube innerhalb von Reclame Fabriek wurde zu unserem Innovationszentrum, in dem unser Expertenteam schnell von wenigen Kernmitgliedern zu einer eigenen Gruppe wuchs, die solche Projekte vom Konzept bis zur Fertigstellung betreut. Viele haben unterwegs mitgewirkt, geblieben sind die innovativsten und fähigsten Köpfe, die die Abteilung weiter prägen.

Wir haben unsere Arbeit mit Stolz auf großen Messen in **Stockholm** und **Finnland** gezeigt, unsere bahnbrechenden Lösungen präsentiert und die Position von Cube als Branchenführer weiter gefestigt.""",
"""Under de senaste fem åren har Cube fått allt fler förfrågningar om mycket specialiserade projekt på beställning, där varje projekt krävt en egen prägel, avancerad utveckling och noggrann uppmärksamhet på detaljer. För att möta efterfrågan och fortsätta flytta gränserna för innovation inom skyltning startade vi **avdelningen för forskning och utveckling** på Reclame Fabriek: en egen plats där de senaste teknikerna, materialen och metoderna möts för att förverkliga banbrytande idéer.

Avdelningen är nästa kapitel i Cubes utveckling. Den gör att vi kan ta fram skräddarsydda skyltlösningar för våra största kunder, särskilt inom HORECA och företagsmiljöer.

Att skapa avdelningen var ett logiskt steg i vår tillväxt, drivet av hur komplexa uppdragen hade blivit. Projekten handlade inte längre bara om att tillverka skyltar: de omfattade invecklad design, avancerade material och unika installationer. Cube inom Reclame Fabriek blev vårt innovationsnav, där vårt expertteam snabbt växte från några få kärnmedlemmar till en egen grupp som driver de avancerade projekten från idé till färdigt resultat. Många har varit med på vägen, men de mest innovativa och skickliga stannade kvar för att fortsätta forma avdelningen.

Vi har stolt visat vårt arbete på stora mässor i **Stockholm** och **Finland**, där vi presenterade våra banbrytande skyltlösningar och ytterligare stärkte Cubes position som ledande i branschen.""")
g = r['intro'].get('gallery', []); r['intro']['gallery'] = list(dict.fromkeys(g + ['20231220-1208-bilde3']))
r['materials']['text'] = T(
"""We pride ourselves on our ability to turn client visions into reality. Whether it’s replicating the grain of wood, the sheen of polished metal or the rough texture of stone, our material imitation technologies at Cube & Reclame Fabriek allow us to produce signage that not only looks authentic but also stands up to the elements. Our solutions are often used in spaces where the look and feel of premium materials are required, but without the associated costs or maintenance challenges.

For corporate spaces, retail environments or high-end hospitality locations, we provide everything from durable outdoor signs to polished indoor displays, all with custom finishes and materials designed to make a lasting impression.""",
"""Mēs lepojamies ar spēju pārvērst klientu ieceres realitātē. Vai tā būtu koka šķiedras, pulēta metāla spīduma vai raupjas akmens tekstūras atdarināšana, mūsu materiālu imitācijas tehnoloģijas Cube un Reclame Fabriek ļauj radīt izkārtnes, kas ne tikai izskatās autentiski, bet arī iztur laikapstākļus. Mūsu risinājumus bieži izmanto vietās, kur vajadzīgs premium materiālu izskats un sajūta, bet bez ar tiem saistītajām izmaksām un apkopes grūtībām.

Biroju telpām, mazumtirdzniecībai vai augstas klases viesmīlības vietām mēs piedāvājam visu, sākot no izturīgām āra izkārtnēm līdz eleganti apdarinātiem iekštelpu displejiem, ar individuālu apdari un materiāliem, kas atstāj paliekošu iespaidu.""",
"""Мы гордимся тем, что умеем превращать замыслы клиентов в реальность. Будь то текстура дерева, блеск полированного металла или шероховатость камня, наши технологии имитации материалов в Cube и Reclame Fabriek позволяют делать вывески, которые не только выглядят натурально, но и выдерживают любую погоду. Наши решения часто выбирают там, где нужен вид и ощущение премиальных материалов, но без их стоимости и сложностей в уходе.

Для офисов, торговых пространств и заведений премиального гостеприимства мы делаем всё: от прочных уличных вывесок до безупречно отделанных интерьерных конструкций, с индивидуальной отделкой и материалами, которые производят сильное впечатление.""",
"""We zijn er trots op dat we de visie van klanten werkelijkheid kunnen maken. Of het nu gaat om de nerf van hout, de glans van gepolijst metaal of de ruwe structuur van steen: met onze materiaalimitatie bij Cube & Reclame Fabriek maken we signing die er niet alleen echt uitziet, maar ook bestand is tegen weer en wind. Onze oplossingen worden vaak gebruikt waar de uitstraling van hoogwaardige materialen gewenst is, maar zonder de bijbehorende kosten of het onderhoud.

Voor kantoren, winkels en high-end horeca leveren we alles, van duurzame buitensigning tot verfijnde binnendisplays, met afwerkingen en materialen op maat die een blijvende indruk maken.""",
"""Wir sind stolz darauf, die Visionen unserer Kunden Wirklichkeit werden zu lassen. Ob die Maserung von Holz, der Glanz von poliertem Metall oder die raue Struktur von Stein: Mit unseren Technologien zur Materialimitation bei Cube & Reclame Fabriek fertigen wir Werbeanlagen, die nicht nur echt aussehen, sondern auch jedem Wetter standhalten. Unsere Lösungen kommen oft dort zum Einsatz, wo Optik und Haptik hochwertiger Materialien gefragt sind, aber ohne deren Kosten oder Pflegeaufwand.

Für Büros, Einzelhandel oder gehobene Hotellerie und Gastronomie liefern wir alles von robusten Außenanlagen bis zu edlen Innendisplays, mit individuellen Oberflächen und Materialien, die einen bleibenden Eindruck hinterlassen.""",
"""Vi är stolta över att kunna förverkliga våra kunders visioner. Oavsett om det gäller att återskapa träets ådring, den polerade metallens glans eller stenens grova struktur gör vår materialimitering på Cube & Reclame Fabriek det möjligt att tillverka skyltar som inte bara ser äkta ut utan också står emot väder och vind. Våra lösningar används ofta där man vill ha utseendet och känslan av exklusiva material, men utan kostnaderna eller underhållet.

För kontor, butiker och exklusiva hotell och restauranger levererar vi allt från tåliga utomhusskyltar till påkostade inomhusdisplayer, med ytbehandlingar och material på beställning som gör ett bestående intryck.""")
r['materials']['tech_title'] = T("Advanced technologies and materials at Cube & Reclame Fabriek", "Progresīvas tehnoloģijas un materiāli Cube un Reclame Fabriek",
  "Передовые технологии и материалы Cube и Reclame Fabriek", "Geavanceerde technologieën en materialen bij Cube & Reclame Fabriek",
  "Fortschrittliche Technologien und Materialien bei Cube & Reclame Fabriek", "Avancerad teknik och avancerade material på Cube & Reclame Fabriek")
r['materials']['tech_text'] = T(
 "To fulfil the unique requests of our clients, the Signage Research Department relies on a range of advanced technologies and techniques at Cube & Reclame Fabriek that allow us to create visually striking, durable and functional signage for a variety of applications.",
 "Lai izpildītu klientu unikālos pieprasījumus, Izkārtņu izpētes nodaļa izmanto virkni progresīvu tehnoloģiju un paņēmienu Cube un Reclame Fabriek, kas ļauj radīt vizuāli iespaidīgas, izturīgas un funkcionālas izkārtnes visdažādākajiem pielietojumiem.",
 "Чтобы выполнять уникальные запросы клиентов, отдел исследований вывесок опирается на целый ряд передовых технологий и методов Cube и Reclame Fabriek. Они позволяют создавать эффектные, долговечные и функциональные вывески для самых разных задач.",
 "Om de unieke wensen van onze klanten te vervullen, werkt de afdeling Onderzoek & Ontwikkeling met een reeks geavanceerde technologieën en technieken bij Cube & Reclame Fabriek, waarmee we opvallende, duurzame en functionele signing maken voor allerlei toepassingen.",
 "Um die besonderen Wünsche unserer Kunden zu erfüllen, setzt die Abteilung Forschung & Entwicklung bei Cube & Reclame Fabriek auf eine Reihe fortschrittlicher Technologien und Verfahren, mit denen wir auffällige, langlebige und funktionale Werbeanlagen für die unterschiedlichsten Einsätze fertigen.",
 "För att uppfylla våra kunders unika önskemål använder avdelningen för forskning och utveckling en rad avancerade tekniker och metoder på Cube & Reclame Fabriek, som gör att vi kan skapa iögonfallande, tåliga och funktionella skyltar för många olika användningsområden.")
IT = r['materials']['items']
IT[0]['text'] = T(
 "Our state-of-the-art 3D printing technology at Reclame Fabriek allows us to produce custom components at full scale. What once started as prototyping has evolved into large-scale production, where we bring highly detailed and complex designs to life. This capability is especially useful in crafting intricate logos, lettering and decorative elements for both interior and exterior signage.",
 "Mūsu modernā 3D drukas tehnoloģija Reclame Fabriek ļauj izgatavot individuālus komponentus pilnā izmērā. Tas, kas sākās kā prototipēšana, ir izaudzis par liela mēroga ražošanu, kurā īstenojam ļoti detalizētus un sarežģītus dizainus. Tas īpaši noder sarežģītu logotipu, burtu un dekoratīvo elementu izgatavošanā gan iekštelpu, gan āra izkārtnēm.",
 "Современная технология 3D-печати в Reclame Fabriek позволяет изготавливать детали на заказ в натуральную величину. То, что начиналось как прототипирование, выросло в крупносерийное производство, где мы воплощаем очень детализированные и сложные проекты. Это особенно ценно при изготовлении сложных логотипов, букв и декоративных элементов для интерьерных и уличных вывесок.",
 "Met onze geavanceerde 3D-printtechnologie bij Reclame Fabriek produceren we onderdelen op maat op ware grootte. Wat begon als prototyping is uitgegroeid tot productie op grote schaal, waarin we zeer gedetailleerde en complexe ontwerpen tot leven brengen. Dat is vooral handig voor ingewikkelde logo’s, letters en decoratieve elementen voor signing binnen en buiten.",
 "Mit unserer hochmodernen 3D-Drucktechnik bei Reclame Fabriek fertigen wir individuelle Bauteile in Originalgröße. Was als Prototypenbau begann, ist zur Serienfertigung gewachsen, in der wir hochdetaillierte, komplexe Entwürfe umsetzen. Besonders wertvoll ist das für filigrane Logos, Schriftzüge und Dekorelemente für Innen- und Außenanlagen.",
 "Med vår moderna 3D-utskriftsteknik på Reclame Fabriek tillverkar vi komponenter på beställning i full skala. Det som började som prototyptillverkning har vuxit till storskalig produktion, där vi förverkligar mycket detaljerade och komplexa designer. Det är särskilt användbart för invecklade logotyper, bokstäver och dekorativa element till skyltar inomhus och utomhus.")
IT[1]['text'] = T(
 "Our custom painting services at Reclame Fabriek provide any finish imaginable. We specialise in decorative coatings that range from matte, gloss or textured effects to enhance the aesthetic and durability of our signage. These coatings can include protective finishes as well as custom colours, which ensure that our signs remain vibrant and weather-resistant. We can also create material imitations, such as wood, metal and stone finishes, allowing clients to achieve the look they desire without the associated costs or weight.",
 "Mūsu individuālās krāsošanas pakalpojumi Reclame Fabriek nodrošina jebkuru iedomājamu apdari. Specializējamies dekoratīvos pārklājumos ar matētu, spīdīgu vai tekstūras efektu, kas uzlabo izkārtņu izskatu un izturību. Pārklājumi var ietvert aizsargapdari un individuālus toņus, lai izkārtnes saglabātu spilgtumu un būtu izturīgas pret laikapstākļiem. Varam radīt arī materiālu imitācijas, piemēram, koka, metāla un akmens apdari, lai klienti iegūtu vēlamo izskatu bez ar to saistītajām izmaksām vai svara.",
 "Наша покрасочная служба в Reclame Fabriek выполнит любую отделку, какую только можно представить. Мы специализируемся на декоративных покрытиях с матовым, глянцевым или фактурным эффектом, которые улучшают внешний вид и долговечность вывесок. Покрытия могут быть защитными и в любом цвете, чтобы вывески оставались яркими и устойчивыми к погоде. Мы также делаем имитацию материалов, например дерева, металла и камня, чтобы клиент получил нужный вид без лишних затрат и веса.",
 "Onze spuiterij bij Reclame Fabriek levert elke denkbare afwerking. We zijn gespecialiseerd in decoratieve coatings, van mat en glanzend tot structuureffecten, die de uitstraling en duurzaamheid van onze signing verbeteren. Die coatings kunnen beschermend zijn en in elke kleur worden gemaakt, zodat de signing kleurrijk en weerbestendig blijft. We maken ook materiaalimitaties, zoals hout-, metaal- en steenafwerkingen, zodat klanten de gewenste uitstraling krijgen zonder de kosten of het gewicht.",
 "Unsere Lackiererei bei Reclame Fabriek liefert jede erdenkliche Oberfläche. Wir sind auf dekorative Beschichtungen spezialisiert, von matt und glänzend bis strukturiert, die Optik und Haltbarkeit unserer Anlagen verbessern. Die Beschichtungen können schützend wirken und in jeder Wunschfarbe ausgeführt werden, damit die Anlagen leuchtend und wetterfest bleiben. Außerdem imitieren wir Materialien wie Holz, Metall und Stein, sodass Kunden die gewünschte Optik ohne die damit verbundenen Kosten oder das Gewicht erhalten.",
 "Vår lackering på Reclame Fabriek klarar alla tänkbara ytbehandlingar. Vi är specialiserade på dekorativa beläggningar, från matt och blank till strukturerad yta, som förbättrar skyltarnas utseende och hållbarhet. Beläggningarna kan vara skyddande och i valfri kulör, så att skyltarna förblir färgstarka och väderbeständiga. Vi kan också imitera material som trä, metall och sten, så att kunderna får det utseende de vill ha utan kostnaden eller vikten.")
IT[2]['text'] = T(
 "We’ve taken traditional neon signage and upgraded it with Next-Gen LED Neon Tubes. These LED solutions offer the same vibrant glow and flexibility as classic neon, but with added durability, energy efficiency and design versatility. They’re perfect for projects requiring intricate designs or long-lasting installations, offering our clients an eye-catching and sustainable alternative to traditional neon.",
 "Esam pilnveidojuši tradicionālās neona izkārtnes ar jaunās paaudzes LED neona caurulēm. Šie LED risinājumi nodrošina tādu pašu spilgtu mirdzumu un elastību kā klasiskais neons, bet ar lielāku izturību, energoefektivitāti un dizaina daudzpusību. Tie ir ideāli projektiem ar sarežģītu dizainu vai ilgmūžīgām instalācijām un piedāvā klientiem acīm tīkamu un ilgtspējīgu alternatīvu tradicionālajam neonam.",
 "Мы взяли классические неоновые вывески и усовершенствовали их с помощью LED-неоновых трубок нового поколения. Они дают такое же яркое свечение и гибкость, как классический неон, но отличаются большей прочностью, энергоэффективностью и свободой дизайна. Это идеальный выбор для сложных проектов и долговечных установок: эффектная и экологичная альтернатива традиционному неону.",
 "We hebben traditionele neonsigning vernieuwd met LED-neonbuizen van de nieuwe generatie. Deze LED-oplossingen bieden dezelfde levendige gloed en flexibiliteit als klassiek neon, maar zijn duurzamer, energiezuiniger en veelzijdiger in ontwerp. Ze zijn ideaal voor ingewikkelde ontwerpen of installaties die lang mee moeten, en bieden onze klanten een opvallend en duurzaam alternatief voor traditioneel neon.",
 "Wir haben klassische Neonwerbung mit LED-Neonröhren der neuen Generation weiterentwickelt. Diese LED-Lösungen bieten dasselbe lebendige Leuchten und dieselbe Flexibilität wie klassisches Neon, sind aber langlebiger, energieeffizienter und gestalterisch vielseitiger. Sie eignen sich ideal für filigrane Designs und langlebige Installationen und bieten unseren Kunden eine auffällige und nachhaltige Alternative zu herkömmlichem Neon.",
 "Vi har tagit den traditionella neonskylten och uppgraderat den med LED-neonrör av nästa generation. LED-lösningarna ger samma livfulla sken och flexibilitet som klassisk neon, men med bättre hållbarhet, energieffektivitet och designfrihet. De passar perfekt för invecklade designer och långlivade installationer och ger våra kunder ett iögonfallande och hållbart alternativ till traditionell neon.")
IT[3]['text'] = T(
 "Developed in-house at Reclame Fabriek, our CUBE LED DIGI controllers provide unmatched control over LED lighting systems. These controllers enable us to create dynamic, customisable light displays with effects like colour changes, fades and animations. Clients looking for something truly interactive can benefit from the integration of CUBE LED DIGI, ensuring that their signage adapts to different environments and audiences in real time.",
 "Mūsu pašu Reclame Fabriek izstrādātie CUBE LED DIGI kontrolleri nodrošina nepārspējamu LED apgaismojuma sistēmu vadību. Ar tiem varam radīt dinamiskus, pielāgojamus gaismas efektus, piemēram, krāsu maiņu, pārejas un animācijas. Klienti, kas meklē patiesi interaktīvu risinājumu, var izmantot CUBE LED DIGI integrāciju, lai izkārtne reāllaikā pielāgotos dažādai videi un auditorijai.",
 "Контроллеры CUBE LED DIGI, разработанные в Reclame Fabriek, дают непревзойдённый контроль над LED-подсветкой. С ними мы создаём динамичные настраиваемые световые эффекты: смену цвета, плавные переходы и анимацию. Клиентам, которым нужно что-то по-настоящему интерактивное, интеграция CUBE LED DIGI позволяет вывеске подстраиваться под обстановку и аудиторию в реальном времени.",
 "Onze CUBE LED DIGI-controllers, door Reclame Fabriek zelf ontwikkeld, geven ongeëvenaarde controle over LED-verlichting. Daarmee maken we dynamische, aanpasbare lichtshows met effecten als kleurwisselingen, overgangen en animaties. Klanten die iets echt interactiefs zoeken, kunnen CUBE LED DIGI laten integreren, zodat hun signing zich in realtime aanpast aan de omgeving en het publiek.",
 "Unsere bei Reclame Fabriek selbst entwickelten CUBE LED DIGI-Controller bieten unübertroffene Kontrolle über LED-Beleuchtung. Damit gestalten wir dynamische, individuell anpassbare Lichtinszenierungen mit Farbwechseln, Überblendungen und Animationen. Kunden, die etwas wirklich Interaktives suchen, profitieren von der Integration von CUBE LED DIGI: Die Anlage passt sich in Echtzeit an Umgebung und Publikum an.",
 "Våra egenutvecklade CUBE LED DIGI-styrenheter från Reclame Fabriek ger oöverträffad kontroll över LED-belysning. Med dem skapar vi dynamiska, anpassningsbara ljusspel med effekter som färgskiften, övertoningar och animationer. Kunder som vill ha något verkligt interaktivt kan integrera CUBE LED DIGI, så att skylten anpassar sig efter miljö och publik i realtid.")
r['lumigrid']['text2'] = T(
"""The Signage Research Department at Reclame Fabriek isn’t just about delivering beautiful signs; it’s about leading the way in modern signage technology. We’ve combined 3D printing, decorative coatings, custom painting and our in-house developed LED technologies to create signage that sets new standards for design, durability and interaction.

Our journey continues as we explore new materials, fabrication techniques and custom solutions for our clients. With our Next-Gen LED Neon Tubes, advanced CUBE LED DIGI controllers and cutting-edge 3D printing capabilities, Cube is at the forefront of an industry that demands both creativity and technical precision.""",
"""Izkārtņu izpētes nodaļa Reclame Fabriek nav tikai skaistu izkārtņu piegāde: tā ir mūsdienu izkārtņu tehnoloģiju attīstības priekšgalā. Mēs apvienojam 3D druku, dekoratīvos pārklājumus, individuālu krāsošanu un pašu izstrādātas LED tehnoloģijas, lai radītu izkārtnes, kas nosaka jaunus dizaina, izturības un mijiedarbības standartus.

Mūsu ceļš turpinās, pētot jaunus materiālus, izgatavošanas paņēmienus un individuālus risinājumus klientiem. Ar jaunās paaudzes LED neona caurulēm, progresīvajiem CUBE LED DIGI kontrolleriem un modernām 3D drukas iespējām Cube ir nozares priekšgalā, kur nepieciešama gan radošums, gan tehniska precizitāte.""",
"""Отдел исследований вывесок Reclame Fabriek — это не только красивые вывески, но и лидерство в современных технологиях. Мы объединили 3D-печать, декоративные покрытия, индивидуальную покраску и собственные LED-разработки, чтобы создавать вывески, которые задают новые стандарты дизайна, долговечности и интерактивности.

Наш путь продолжается: мы осваиваем новые материалы, технологии изготовления и индивидуальные решения для клиентов. С LED-неоновыми трубками нового поколения, контроллерами CUBE LED DIGI и передовой 3D-печатью Cube находится в авангарде отрасли, где нужны и творческий подход, и техническая точность.""",
"""De afdeling Onderzoek & Ontwikkeling van Reclame Fabriek draait niet alleen om mooie signing, maar om voorop lopen in moderne signingtechnologie. We combineren 3D-printen, decoratieve coatings, spuitwerk op maat en zelf ontwikkelde LED-technologie tot signing die nieuwe normen zet voor ontwerp, duurzaamheid en interactie.

Onze reis gaat door: we verkennen nieuwe materialen, productietechnieken en oplossingen op maat voor onze klanten. Met onze LED-neonbuizen van de nieuwe generatie, geavanceerde CUBE LED DIGI-controllers en vooruitstrevende 3D-printmogelijkheden staat Cube aan de top van een branche die zowel creativiteit als technische precisie vraagt.""",
"""Bei der Abteilung Forschung & Entwicklung von Reclame Fabriek geht es nicht nur um schöne Werbeanlagen, sondern darum, die moderne Werbetechnik anzuführen. Wir verbinden 3D-Druck, dekorative Beschichtungen, individuelle Lackierung und selbst entwickelte LED-Technologien zu Anlagen, die neue Maßstäbe für Design, Langlebigkeit und Interaktion setzen.

Unser Weg geht weiter: Wir erkunden neue Materialien, Fertigungstechniken und individuelle Lösungen für unsere Kunden. Mit LED-Neonröhren der neuen Generation, fortschrittlichen CUBE LED DIGI-Controllern und modernstem 3D-Druck steht Cube an der Spitze einer Branche, die Kreativität und technische Präzision gleichermaßen verlangt.""",
"""Avdelningen för forskning och utveckling på Reclame Fabriek handlar inte bara om att leverera vackra skyltar, utan om att leda utvecklingen inom modern skyltteknik. Vi har kombinerat 3D-utskrift, dekorativa beläggningar, lackering på beställning och egenutvecklad LED-teknik för att skapa skyltar som sätter nya standarder för design, hållbarhet och interaktion.

Resan fortsätter när vi utforskar nya material, tillverkningstekniker och lösningar på beställning för våra kunder. Med LED-neonrör av nästa generation, avancerade CUBE LED DIGI-styrenheter och modern 3D-utskrift ligger Cube i framkant i en bransch som kräver både kreativitet och teknisk precision.""")
jw('pages/signage-research.json', r)

# ---------------------------------------------------------------- old WordPress addresses that had no page
red = jr('redirects.json')
red.update({'shop-2': 'product-profiles', 'cart-2': 'contact', 'checkout-2': 'contact', 'my-account': 'portal'})
jw('redirects.json', red)
print('restored')

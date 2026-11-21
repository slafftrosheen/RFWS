#!/usr/bin/env python3
"""2.2: story bodies in all six languages + new interface texts for motion, the request basket and the letter anatomy."""
import json, re, sys
sys.path.insert(0, '/tmp/rfcms/source/tools')
from stories_tr_a import S as A
from stories_tr_b import S as B
D = '/tmp/rfcms/site/data/'
S = {**A, **B}
for slug, tr in S.items():
    f = D + f'stories/{slug}.json'; s = json.load(open(f))
    en = s['body']['en']
    en = re.sub(r'\s+([,.;:!?])', r'\1', en)            # stray spaces left by the old bold tags
    en = re.sub(r'\s+—\s*', ' — ', en).replace('Vodka — a', 'Vodka: a')
    en = re.sub(r'(\d)mm\b', r'\1 mm', en).replace('-30°C', '-30 °C')
    s['body'] = {'en': en, **tr}
    for l, t in tr.items(): assert len(t) > 0.6 * len(en), (slug, l)
    json.dump(s, open(f, 'w'), ensure_ascii=False, indent=1)
print('stories:', len(S))
def T(en, lv, ru, nl, de, sv): return {'en': en, 'lv': lv, 'ru': ru, 'nl': nl, 'de': de, 'sv': sv}
ui = json.load(open(D + 'ui.json'))
ui.update({
 'nav.portal': T("Client portal", "Klientu portāls", "Кабинет клиента", "Klantportaal", "Kundenportal", "Kundportal"),
 'motion.pause': T("Pause motion", "Apturēt kustību", "Остановить анимацию", "Beweging pauzeren", "Bewegung anhalten", "Pausa rörelse"),
 'motion.play': T("Play motion", "Atsākt kustību", "Включить анимацию", "Beweging afspelen", "Bewegung abspielen", "Spela rörelse"),
 'film_label': T("Our factory in photos", "Mūsu rūpnīca fotogrāfijās", "Наше производство в фотографиях", "Onze fabriek in beeld", "Unsere Fertigung in Bildern", "Vår fabrik i bilder"),
 'basket.add': T("Add to request", "Pievienot pieprasījumam", "Добавить в запрос", "Toevoegen aan aanvraag", "Zur Anfrage hinzufügen", "Lägg till i förfrågan"),
 'basket.in': T("In your request", "Jūsu pieprasījumā", "В вашем запросе", "In uw aanvraag", "In Ihrer Anfrage", "I din förfrågan"),
 'anat.kicker': T("Inside the letter", "Burta iekšpuse", "Внутри буквы", "Binnenin de letter", "Im Inneren des Buchstabens", "Inuti bokstaven"),
 'anat.title': T("Anatomy of {x}", "{x} uzbūve", "Устройство: {x}", "Opbouw van {x}", "Aufbau von {x}", "Så är {x} uppbyggd"),
 'anat.text': T("Every layer of this profile, from the back panel to the face. Scroll, or take it apart yourself.",
   "Katrs šī profila slānis – no aizmugures paneļa līdz priekšējai virsmai. Ritiniet vai izjauciet to paši.",
   "Каждый слой этого профиля, от задней стенки до лицевой панели. Прокрутите страницу или разберите букву сами.",
   "Elke laag van dit profiel, van achterwand tot front. Scroll, of haal hem zelf uit elkaar.",
   "Jede Schicht dieses Profils, von der Rückwand bis zur Front. Scrollen Sie, oder nehmen Sie ihn selbst auseinander.",
   "Varje lager i den här profilen, från baksida till front. Skrolla, eller ta isär den själv."),
 'anat.apart': T("Take it apart", "Izjaukt", "Разобрать", "Uit elkaar halen", "Zerlegen", "Ta isär"),
 'anat.together': T("Put it together", "Salikt kopā", "Собрать", "In elkaar zetten", "Zusammensetzen", "Sätt ihop"),
 'anat.back': T("Back", "Aizmugure", "Задняя стенка", "Achterkant", "Rückwand", "Baksida"),
 'anat.led': T("LED modules", "LED moduļi", "LED-модули", "LED-modules", "LED-Module", "LED-moduler"),
 'anat.sides': T("Sides", "Sāni", "Борта", "Zijkanten", "Zarge", "Sidor"),
 'anat.trim': T("Trim", "Apmale", "Окантовка", "Rand", "Kantenprofil", "Kantlist"),
 'anat.face': T("Face", "Priekšējā virsma", "Лицевая панель", "Front", "Front", "Front"),
})
json.dump(ui, open(D + 'ui.json', 'w'), ensure_ascii=False, indent=1)
print('ui keys', len(ui))

<?php
/* What can be edited, and how. Each field: [type, label, options]. 'i18n' => true means one value per site language.
   Types: text, textarea, rich, url, image, gallery, list, select, checkbox, profiles, stories, video, icon, slug, date. */
declare(strict_types=1);

function F(string $type, string $label, array $o = []): array { return ['type' => $type, 'label' => $label] + $o; }
function T(string $label, array $o = []): array { return F('text', $label, ['i18n' => true] + $o); }
function TA(string $label, array $o = []): array { return F('textarea', $label, ['i18n' => true] + $o); }
function R(string $label, array $o = []): array { return F('rich', $label, ['i18n' => true] + $o); }
function SEO(): array { return ['label' => 'Поиск и соцсети (SEO)', 'open' => false, 'fields' => [
    'title' => T('Заголовок вкладки', ['hint' => 'Если пусто — используется название страницы.']),
    'description' => TA('Описание для Google и соцсетей', ['hint' => 'До 155 символов. Если пусто — берётся из текста.', 'max' => 300])]]; }
function CTA(): array { return ['label' => 'Тёмная полоса «Есть идея вывески?» внизу', 'fields' => ['title' => T('Заголовок'), 'text' => TA('Текст')]]; }

require_once dirname(__DIR__) . '/app/icons.php';
const ICON_GROUPS_RU = ['Products' => 'Продукция', 'Light types' => 'Подсветка', 'Production' => 'Производство', 'Company' => 'Компания', 'Interface' => 'Интерфейс'];
const MOTION_RU = ['full' => 'Полная — неон в заголовке, бегущая строка, анимации', 'calm' => 'Спокойная — анимации один раз, без бегущей строки', 'off' => 'Выключена — всё статично'];
const LIGHT_RU = ['frontlit' => 'Лицевая подсветка', 'corona' => 'Контражур (гало)', 'neon' => 'Под неон', 'front_corona' => 'Лицевая + контражур',
                  'front_side' => 'Лицевая + боковая', 'unlit' => 'Без подсветки', 'flat' => 'Плоские, без подсветки', 'lightbox' => 'Световой короб'];
const GROUP_RU = ['letters' => 'Объёмные буквы', 'lightbox' => 'Световые короба'];

function page_schemas(): array {
    return [
    'home' => ['title' => 'Главная', 'sections' => [
        'hero' => ['label' => 'Первый экран', 'fields' => [
            'eyebrow' => T('Надпись над заголовком'), 'title' => T('Главный заголовок'), 'lead' => TA('Подзаголовок'),
            'image' => F('image', 'Фоновое фото (первый слайд)', ['hint' => 'Лучше всего — тёмный кадр светящейся вывески, горизонтальный.']),
            'images' => F('gallery', 'Ещё слайды', ['hint' => 'Фото сменяются плавно каждые 7 секунд. Пусто — одно фото.']),
            'btn1' => T('Кнопка 1 (ведёт к профилям)'), 'btn2' => T('Кнопка 2 (ведёт к проектам)')]],
        'ticker' => ['label' => 'Бегущая строка (светодиодное табло)', 'fields' => ['items' => F('list', 'Слова на табло', ['item' => 'слово', 'hint' => 'Пусто — табло не показывается.', 'fields' => ['text' => T('Текст')]])]],
        'stats' => ['label' => 'Цифры под первым экраном', 'fields' => ['items' => F('list', 'Цифры', ['item' => 'цифру', 'fields' => ['value' => F('text', 'Число'), 'label' => T('Подпись')]])]],
        'services' => ['label' => 'Услуги', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'),
            'items' => F('list', 'Услуги', ['item' => 'услугу', 'fields' => ['icon' => F('icon', 'Значок'), 'image' => F('image', 'Фото к услуге'), 'title' => T('Название'), 'text' => R('Описание'), 'link_label' => T('Текст ссылки'), 'link_url' => F('url', 'Ссылка', ['hint' => 'Страница сайта (/projects/) или полный адрес https://…'])]])]],
        'clients' => ['label' => 'Почему мы / клиенты', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'link_label' => T('Текст ссылки'), 'image' => F('image', 'Изображение с логотипами клиентов')]],
        'profiles' => ['label' => 'Профили на главной', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'),
            'show' => F('select', 'Что показывать', ['options' => ['all' => 'Все опубликованные профили (лента с прокруткой)', 'picked' => 'Только выбранные ниже']]),
            'letters' => F('profiles', 'Объёмные буквы (если «только выбранные»)', ['group' => 'letters']), 'lightboxes' => F('profiles', 'Световые короба (если «только выбранные»)', ['group' => 'lightbox'])]],
        'special' => ['label' => 'Особые проекты', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'stories' => F('stories', 'Истории на главной'), 'button' => T('Кнопка «Все проекты»')]],
        'factory' => ['label' => 'Производство и станки', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'machines_title' => T('Заголовок списка станков'),
            'machines' => F('list', 'Станочный парк', ['item' => 'станок', 'fields' => ['text' => T('Станок')]]), 'gallery' => F('gallery', 'Фото производства (лента)', ['hint' => 'Показываются двумя медленно плывущими рядами; по клику открываются крупно.'])]],
        'process' => ['label' => 'Как делается вывеска (6 шагов, анимации)', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'items' => F('list', 'Шаги', ['item' => 'шаг', 'hint' => 'Анимация у шага выбирается по порядку: чертёж, раскрой, гибка, покраска, свет, монтаж/доставка.', 'fields' => ['title' => T('Шаг'), 'text' => T('Описание')]])]],
        'team' => ['label' => 'Команда', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'gallery' => F('gallery', 'Фото сотрудников', ['hint' => 'Вертикальные портреты, примерно 3:4.'])]],
        'subscribe' => ['label' => 'Подписка', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст')]],
        'cta' => CTA(), 'seo' => SEO()]],
    'product-profiles' => ['title' => 'Профили продукции (общая страница)', 'sections' => [
        'head' => ['label' => 'Верх страницы', 'fields' => ['title' => T('Заголовок'), 'lead' => R('Вступление'), 'image' => F('image', 'Фото в шапке страницы', ['hint' => 'Затемняется, текст поверх. Пусто — светлая шапка.'])]],
        'common' => ['label' => 'Общее для всех профилей', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'),
            'rows' => F('list', 'Строки', ['item' => 'строку', 'fields' => ['label' => T('Параметр'), 'text' => R('Описание')]])]],
        'cta' => CTA(), 'seo' => SEO()]],
    'projects' => ['title' => 'Проекты', 'sections' => [
        'head' => ['label' => 'Верх страницы', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'lead' => R('Вступление'), 'image' => F('image', 'Фото в шапке страницы', ['hint' => 'Затемняется, текст поверх. Пусто — светлая шапка.'])]],
        'stories' => ['label' => 'Блок историй', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), '_note' => F('note', 'Сами истории и их порядок — в разделе «Истории проектов».')]],
        'film' => ['label' => 'Видео о производстве', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'video' => F('video', 'Видео YouTube'), 'poster' => F('image', 'Обложка видео')]],
        'gallery' => ['label' => 'Галерея', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'images' => F('gallery', 'Фото галереи')]],
        'partners' => ['label' => 'Крупнейшие партнёры', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'image' => F('image', 'Изображение с логотипами')]],
        'cta' => CTA(), 'seo' => SEO()]],
    'signage-research' => ['title' => 'Исследования (Signage Research)', 'sections' => [
        'head' => ['label' => 'Верх страницы', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'lead' => R('Вступление'), 'image' => F('image', 'Фото в шапке страницы', ['hint' => 'Затемняется, текст поверх. Пусто — светлая шапка.'])]],
        'intro' => ['label' => 'О отделе', 'fields' => ['title' => T('Заголовок'), 'body' => R('Текст', ['rows' => 12]), 'gallery' => F('gallery', 'Фото')]],
        'materials' => ['label' => 'Материалы и технологии', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'tech_title' => T('Заголовок «Технологии»'), 'tech_text' => R('Вступление к технологиям'),
            'items' => F('list', 'Технологии', ['item' => 'технологию', 'fields' => ['icon' => F('icon', 'Значок'), 'title' => T('Название'), 'text' => R('Описание')]])]],
        'lumigrid' => ['label' => 'LUMIGRID и будущее', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'button' => T('Текст кнопки'), 'url' => F('url', 'Ссылка кнопки'),
            'title2' => T('Заголовок справа'), 'text2' => R('Текст справа')]],
        'cta' => CTA(), 'seo' => SEO()]],
    'lumigrid' => ['title' => 'LUMIGRID (контроллер LEDNode)', 'sections' => [
        'head' => ['label' => 'Первый экран', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Название (рисуется светодиодами)', ['hint' => 'Короткое слово, до 14 букв. На экране оно собирается из светящихся точек.']),
            'tagline' => T('Подзаголовок'), 'lead' => R('Вступление'), 'button' => T('Кнопка заявки'), 'button2' => T('Кнопка «к контроллеру»')]],
        'stats' => ['label' => 'Цифры под первым экраном', 'fields' => ['items' => F('list', 'Цифры', ['item' => 'цифру', 'hint' => 'Цифры взяты из прошивки LUMIGRID 1.0. Меняйте их вместе с прошивкой.', 'fields' => ['value' => F('text', 'Число'), 'label' => T('Подпись')]])]],
        'system' => ['label' => 'Система (4 шага)', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'items' => F('list', 'Шаги', ['item' => 'шаг', 'fields' => ['icon' => F('icon', 'Значок'), 'title' => T('Название'), 'text' => R('Описание')]])]],
        'node' => ['label' => 'Контроллер LEDNode', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'specs' => F('list', 'Характеристики', ['item' => 'характеристику', 'fields' => ['label' => T('Параметр'), 'value' => T('Значение')]]),
            'note' => R('Примечание под характеристиками'), 'strips' => T('Подпись на схеме: ленты'), 'dimmers' => T('Подпись на схеме: диммеры'), 'figure' => T('Описание схемы для незрячих')]],
        'lab' => ['label' => 'Лаборатория эффектов', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'), 'note' => R('Примечание под окном'),
            'l_effect' => T('Подпись «Эффект»'), 'l_palette' => T('Подпись «Палитра»'), 'l_speed' => T('Подпись «Скорость»'), 'l_pwm_yes' => T('Метка «подходит для диммера»'), 'l_pwm_no' => T('Метка «только для лент»'),
            'l_strips' => T('Подпись в окне: ленты'), 'l_dimmers' => T('Подпись в окне: диммеры'), 'l_canvas' => T('Описание окна для незрячих'),
            'effects' => F('list', 'Эффекты (названия)', ['item' => 'эффект', 'hint' => 'Код эффекта (id) — из прошивки, не меняйте его. Можно менять названия, описания и порядок.', 'fields' => ['id' => F('text', 'Код эффекта (id)'), 'name' => T('Название'), 'text' => T('Описание')]]),
            'palette_names' => TA('Названия 14 палитр', ['hint' => 'Через запятую, строго в порядке прошивки (0–13): Своя, Радуга, Океан, Лава, Лес, Закат, Янтарь, Лёд, Неон, Лаванда, Леденец, Праздник, Угли, Сумерки.'])]],
        'show' => ['label' => 'Сцены, расписания, сигналы', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'items' => F('list', 'Карточки', ['item' => 'карточку', 'fields' => ['icon' => F('icon', 'Значок'), 'title' => T('Название'), 'text' => R('Описание')]])]],
        'setup' => ['label' => 'Как начать (шаги)', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'),
            'items' => F('list', 'Шаги', ['item' => 'шаг', 'fields' => ['title' => T('Название'), 'text' => R('Описание')]])]],
        'local' => ['label' => 'В вашей сети / безопасность', 'fields' => ['kicker' => T('Надзаголовок'), 'title' => T('Заголовок'), 'text' => R('Текст'),
            'items' => F('list', 'Карточки', ['item' => 'карточку', 'fields' => ['icon' => F('icon', 'Значок'), 'title' => T('Название'), 'text' => R('Описание')]])]],
        'cta' => ['label' => 'Тёмная полоса внизу', 'fields' => ['title' => T('Заголовок'), 'text' => TA('Текст'), 'button' => T('Кнопка заявки'), 'docs' => T('Текст ссылки на документацию'), 'docs_url' => F('url', 'Ссылка на документацию', ['hint' => 'Пусто — ссылка не показывается.'])]],
        'seo' => SEO()]],
    'contact' => ['title' => 'Контакты', 'sections' => [
        'head' => ['label' => 'Верх страницы', 'fields' => ['title' => T('Заголовок'), 'lead' => R('Вступление'), 'image' => F('image', 'Фото в шапке страницы', ['hint' => 'Затемняется, текст поверх. Пусто — светлая шапка.']), '_note' => F('note', 'Люди, телефоны, адрес и почта для заявок — в разделе «Настройки».')]],
        'seo' => SEO()]],
    'privacy-policy' => ['title' => 'Политика конфиденциальности', 'sections' => [
        'head' => ['label' => 'Заголовок', 'fields' => ['title' => T('Заголовок')]],
        'body' => ['label' => 'Текст', 'fields' => ['text' => R('Текст политики', ['rows' => 18])]], 'seo' => SEO()]],
    ];
}

function profile_schema(): array {
    return ['sections' => [
        'main' => ['label' => 'Основное', 'flat' => true, 'fields' => [
            'title' => T('Название', ['required' => true]), 'subtitle' => T('Подзаголовок (тип, материал)'),
            'group' => F('select', 'Группа', ['options' => GROUP_RU]), 'light' => F('select', 'Подсветка', ['options' => LIGHT_RU, 'hint' => 'Показывается как метка и используется в фильтре на сайте.']),
            'cover' => F('image', 'Главное фото')]],
        'spec' => ['label' => 'Спецификация', 'flat' => true, 'fields' => [
            'size' => F('text', 'Высота букв', ['hint' => 'Например: 100–1500 mm. Пусто — строка не показывается.']),
            'depth' => F('text', 'Стандартная глубина', ['hint' => 'Например: 30, 40, 50, 60 mm']),
            'custom_depth' => F('checkbox', 'Нестандартная глубина возможна', ['hint' => 'Если снять галочку, на сайте будет написано, что для этого профиля нестандартная глубина невозможна.']),
            'specs' => F('list', 'Характеристики', ['item' => 'характеристику', 'fields' => ['label' => T('Параметр'), 'value' => T('Значение')]]),
            'note' => R('Примечание', ['hint' => 'Показывается под спецификацией. Если характеристик нет, а примечание пустое — показывается стандартный текст «Изготовим по вашему чертежу».'])]],
        'media' => ['label' => 'Видео и фото', 'flat' => true, 'fields' => ['diagram' => F('image', 'Схема конструкции (разрез профиля)'), 'video' => F('video', 'Видео YouTube'), 'gallery' => F('gallery', 'Галерея работ')]],
        'seo' => ['label' => 'Поиск и соцсети (SEO)', 'flat' => true, 'open' => false, 'fields' => ['description' => TA('Описание для Google', ['max' => 300])]],
    ]];
}
function story_schema(): array {
    return ['sections' => [
        'main' => ['label' => 'Основное', 'flat' => true, 'fields' => [
            'title' => T('Заголовок', ['required' => true]), 'kicker' => T('Подпись над заголовком', ['hint' => 'Клиент · место, например «DEPO · 15 ТЦ, Балтия».']),
            'date' => F('date', 'Дата'), 'cover' => F('image', 'Обложка')]],
        'text' => ['label' => 'Текст истории', 'flat' => true, 'fields' => ['body' => R('Текст', ['rows' => 16, 'hint' => 'Если перевода нет, на сайте показывается английский текст с пометкой.'])]],
        'media' => ['label' => 'Видео и фото', 'flat' => true, 'fields' => ['video' => F('video', 'Видео YouTube'), 'gallery' => F('gallery', 'Фото')]],
        'seo' => ['label' => 'Поиск и соцсети (SEO)', 'flat' => true, 'open' => false, 'fields' => ['description' => TA('Описание для Google', ['max' => 300])]],
    ]];
}
function settings_schema(): array {
    return ['sections' => [
        'company' => ['label' => 'Компания', 'flat' => true, 'fields' => [
            'company' => F('text', 'Юридическое название (в подвале)'), 'founded' => F('text', 'Год основания'),
            'address' => TA('Адрес', ['hint' => 'Каждая строка — с новой строки.']), 'maps_query' => F('text', 'Адрес для карты Google'),
            'hero_phone' => F('text', 'Телефон в тёмных полосах «Оставить запрос»')]],
        'contacts' => ['label' => 'Контактные лица', 'flat' => true, 'fields' => ['contacts' => F('list', 'Контакты (в подвале и на странице «Контакты»)', ['item' => 'контакт', 'fields' => ['role' => T('Отдел / роль'), 'name' => F('text', 'Имя'), 'phone' => F('text', 'Телефон'), 'email' => F('text', 'E-mail')]])]],
        'forms' => ['label' => 'Заявки с сайта', 'flat' => true, 'fields' => ['form_to' => F('text', 'Куда отправлять заявки (e-mail)'), 'form_from' => F('text', 'Адрес отправителя', ['hint' => 'Должен быть на вашем домене, иначе письма попадут в спам.'])]],
        'links' => ['label' => 'Ссылки и соцсети', 'flat' => true, 'fields' => ['lumigrid_url' => F('url', 'Ссылка на LUMIGRID (в меню)'),
            'social' => F('list', 'Соцсети', ['item' => 'ссылку', 'fields' => ['name' => F('text', 'Название'), 'url' => F('url', 'Адрес')]]),
            'partner_logos' => F('gallery', 'Логотипы партнёров в подвале (LIAA, ОЭЗ Латгале…)')]],
        'hours' => ['label' => 'Часы работы (страница «Контакты»)', 'flat' => true, 'fields' => ['hours_from' => F('text', 'Открыто с (ЧЧ:ММ)', ['hint' => 'Например 08:00. Оставьте пустым, чтобы не показывать «Сейчас открыто».']), 'hours_to' => F('text', 'Открыто до (ЧЧ:ММ)'),
            'hours_days' => F('select', 'Дни', ['options' => ['1-5' => 'Понедельник–пятница', '1-6' => 'Понедельник–суббота', '1-7' => 'Каждый день']])]],
        'catalog' => ['label' => 'Каталог', 'flat' => true, 'fields' => ['ral_image' => F('image', 'Палитра RAL на страницах профилей', ['hint' => 'Показывается под спецификацией каждого профиля.'])]],
        'motion' => ['label' => 'Анимация на сайте', 'flat' => true, 'fields' => ['motion' => F('select', 'Уровень анимации', ['options' => MOTION_RU, 'hint' => 'Посетители, у которых в системе включено «уменьшить движение», всегда видят статичную версию.'])]],
        'site' => ['label' => 'Сайт', 'flat' => true, 'fields' => ['site_url' => F('url', 'Адрес сайта', ['hint' => 'Для ссылок в Google и соцсетях. Без косой черты в конце.'])]],
    ]];
}

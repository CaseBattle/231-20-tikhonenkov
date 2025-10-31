
# Определение персонажей
define l = Character("ЛИЗА", color="#6d5157")
define d = Character("ДИМА", color="#796edd")
define m = Character("МАКСИМ", color="#6bd179")
define o = Character("ОЛЬГА ВИКТОРОВНА", color="#b15e5e")
define s = Character("СВЕТА", color="#814b7d")
define narrator = Character("", color="#FFFFFF")

# Определение изображений
image bg office = "living_room.png"         
image bg meeting_room = "mishas_room_night.png"
image bg hr_office = "sofia_room.png"
image black = "#000000"

# Спрайты персонажей
image lisa = "anna.png"
image dima = "misha.png"
image maxim = "alexey.png"
image olga = "sofia.png"
image sveta = "kate.png"

init python:
    # Система очков
    social_points = 0
    max_social_points = 10
    insisted_on_etiquette = False

# Начало игры
label start:
    # Запуск фоновой музыки
    play music "game_song.mp3" fadein 2.0

    # Эпизод 1: Первый рабочий день
    scene bg office
    show lisa at center
    with fade

    l "(про себя) Первый день на новой работе. Кофе холодный, Дима вечно опаздывает, а у Максима опять важная презентация. Чувствую себя не в своей тарелке."

    show dima at left
    with moveinleft

    d "Лиза, привет! Смотри, какой прикол в телефоне!"

    show dima at right
    with move
    l "Дим, тише! Мы же в общественном месте!"

    show dima at right
    "Дима недовольно хмурится."

    # Выбор игрока 1
    menu:
        "Резко сделать замечание.":
            l "Сколько раз можно повторять! Здесь люди работают!"
            show dima at right
            "Дима обиженно отворачивается."
            $ social_points -= 1

        "Объяснить спокойно.":
            l "Здесь принято соблюдать тишину, особенно в рабочее время, договорились?"
            show dima at right
            "Дима кивает."
            $ social_points += 1

    show maxim at left
    with moveinleft

    m "Всё, я на совещание. Документы... Ноутбук... Лиза, ты не видела мой планшет?"
    l "На столе в переговорной. Как всегда."
    m "Спасибо. О, и опять кто-то оставил крошки на столе. Неприятно."
    "Максим начинает громко возмущаться по поводу беспорядка."

    l "Макс, подожди! Может, просто убрать?"
    show maxim at left
    m "Да нет, надо чтобы все знали! Как можно быть такими неаккуратными!"

    hide maxim
    with moveoutleft
    "Максим уходит. Лиза с беспокойством смотрит на расстроенных коллег."

    scene black
    with dissolve
    narrator "Напряженная атмосфера..."
    with Pause(2)

# Эпизод 1.5: Совет подруги (исправленная метка)
label friend_advice:
    scene bg office
    show lisa at center
    with fade

    "Вечером того же дня Лиза звонит своей подруге Свете."
    
    show lisa at right
    show sveta at left with moveinleft

    s "Привет, Лиз! Как первый день?"
    l "Да сложно... И с Максимом опять конфликт из-за поведения в офисе."
    s "Ох, это серьезно. Я тебе как-то рассказывала, у моей коллеги был похожий случай..."

    menu:
        "Расспросить подробности":
            l "Что случилось? Расскажи!"
            s "Они тоже думали, что можно вести себя как дома. В итоге коллега устроил скандал из-за громкого разговора..."
            l "Боже... И чем закончилось?"
            s "Его попросили соблюдать правила офисного этикета."
            $ social_points += 2

        "Отшутиться":
            l "Да уж... Но я же стараюсь быть вежливой."
            s "Все так думают, пока не случается конфликт."
            $ social_points += 0

        "Сменить тему":
            l "Ладно, не будем о грустном."
            s "Понимаю..."
            $ social_points -= 1    

    hide sveta with moveoutleft

# Эпизод 2: Важное мероприятие (исправленная метка)
label important_event:
    scene bg office
    show lisa at center
    with fade

    "Лиза готовится к важной встрече с клиентом. Дима слушает музыку без наушников."

    show screen important_announcement
    pause 5
    hide screen important_announcement

    show lisa at center
    "Лиза замирает. Она смотрит на Диму, который совершенно не замечает проблему. Сердце у неё сжимается."
    $ social_points += 1
    "*Звонок телефона*"
    show olga at left
    with moveinleft
    o "Лиза, извините за беспокойство. У вас сегодня встреча... Клиент очень важный. Лучше подготовьтесь."
    l "Спасибо, Ольга Викторовна, я как раз готовлюсь."
    o "Вы знаете... в этом отделе... до вас работал молодой человек. Перспективный. Хорошо себя показывал."

    hide olga
    with moveoutleft
    "Ольга Викторовна вздыхает и уходит, не договорив. Лизу охватывает странное беспокойство."

    scene bg office
    show lisa at center

    "Вечер. Максим возвращается с совещания уставший."
    l "Макс, нам нужно срочно поговорить о правилах поведения в офисе. Я сегодня заметила... И начальница намекнула, что тут до нас..."

    show maxim at left with moveinleft
    
    m "Опять ты с этими правилами! У всех свой стиль работы. Надо просто работать хорошо! Какие условности?"

    # Выбор игрока 2
    menu:
        "Настоять на своём.":
            l "Это не условности, это профессиональная этика! Я настаиваю!"
            m "Ладно, ладно... Завтра поговорим. Сейчас я слишком устал."
            $ social_points += 2
            $ insisted_on_etiquette = True

        "Отступить.":
            l "Ладно, поговорим в другой раз."
            show maxim at left
            m "Вот и хорошо. Не надо усложнять."
            $ social_points -= 2
            $ insisted_on_etiquette = False

# Эпизод 3: Тайна раскрывается
label olga_visit:
    scene bg hr_office
    show lisa at right
    show olga at left
    with fade

    o "Я знала, что вы придёте. Тот молодой человек... Андрей."

    menu:
        "Вежливо выслушать":
            l "Расскажите, пожалуйста... Мне кажется, это важно."
            o "Он был перспективным сотрудником. Но слишком прямолинейным, как многие молодые..."
            $ social_points += 1

        "Настоять на подробностях":
            l "Ольга Викторовна, я должна знать правду!"
            o "Вы правы... Карьера - вещь хрупкая."
            $ social_points += 2

        "Проявить скепсис":
            l "Может, вы преувеличиваете?"
            o "Молодая... Не говори так."
            $ social_points += 0

    o "Андрей считал, что талант важнее манер... Он грубил клиентам, не уважал коллег..."
    
    menu:
        "Спросить о деталях поведения":
            l "А у него были проблемы с этикетом?"
            o "Постоянные! Не следил за речью, перебивал, опаздывал."
            $ social_points += 2

        "Расспросить о карьерных последствиях":
            l "И что... как это отразилось на его карьере?"
            o "Его уволили после скандала с важным клиентом."
            $ social_points += 1

        "Поскорее уйти":
            l "Спасибо... мне пора."
            o "Учитесь на чужих ошибках."
            $ social_points += 0

# Эпизод 4: Момент истины
label climax:
    scene bg meeting_room
    with fade
    # Убрал музыку - файлов нет
    # play music "tension.mp3" fadein 1.0

    "Лиза возвращается в офис после обеда. Непривычная тишина."
    "Дверь в переговорную приоткрыта."
    
    show bg meeting_room:
        zoom 1.0
        linear 0.5 zoom 1.1

    "Лиза заглядывает внутрь. Сердце замирает."

    "Дима разговаривает с клиентом, развалившись на стуле и жуя жвачку."
    d "(громко) Да ладно, это же мелочи! Главное - результат!"

    # Решающий выбор зависит от накопленных очков
    if social_points >= 6:
        jump good_reaction 
    elif social_points >= 3:
        menu:
            "Резко одернуть Диму":
                $ social_points -= 1
                jump bad_reaction
            "Тактично вмешаться в разговор":
                $ social_points += 1
                jump good_reaction
            "Громко сделать замечание":
                $ social_points -= 2
                jump panic_reaction
    else:
        jump panic_reaction

label good_reaction:
    hide cg dima_at_meeting
    with dissolve
    show lisa at center
    show dima at left
    "Лиза, не привлекая лишнего внимания, мягко вступает в разговор и корректирует поведение Димы."
    l "(вежливо) Извините, мы как раз хотели обсудить этот вопрос более детально..."
    d "Ой, да я просто..."
    "Лиза сохраняет профессиональное отношение и спасает ситуацию."
    $ social_points += 3
    jump ending_calculation

label bad_reaction:
    hide cg dima_at_meeting
    with dissolve
    show lisa at center
    show dima at left
    "Лиза резко одергивает Диму. Клиент смущается, Дима злится."
    l "Я же говорила! Так с клиентами не разговаривают!"
    d "Лиза, не позорь меня!"
    "Клиент недоволен, атмосфера испорчена."
    $ social_points += 1
    jump ending_calculation

label panic_reaction:
    show cg dima_at_meeting:
        linear 0.5 zoom 1.2
    # Убрал звук - файла нет
    # play sound "scandal.wav"
    scene black
    with vpunch
    # stop music fadeout 0.5
    "СКАНДАЛ..."
    pause(2)
    "Тишина."
    jump bad_ending

# Система расчета концовки
label ending_calculation:
    scene black
    with fade
    
    show text "Уровень вашей социальной адаптации: [social_points] из [max_social_points]" with dissolve
    pause(2)
    hide text with dissolve

    if social_points >= 8:
        jump perfect_ending
    elif social_points >= 5:
        jump good_ending
    elif social_points >= 3:
        jump neutral_ending
    else:
        jump bad_ending

# Идеальная концовка
label perfect_ending:
    scene bg office
    show lisa at right
    show maxim at left
    show dima at center
    with fade

    "Неделю спустя. В офисе внедрены правила этикета, все сотрудники прошли тренинг."
    m "Я понял всё. Профессиональное поведение - это не условности, а необходимость."
    l "Мы научились уважать друг друга и клиентов."
    "Коллектив стал сплоченнее, осознав важность корпоративной культуры."
    
    call screen etiquette_info_perfect
    return

# Хорошая концовка
label good_ending:
    scene bg office
    show lisa at right
    show maxim at left
    with fade

    "Правила внедрены, но в коллективе осталась напряженность."
    m "Ладно, приняли мы твои правила. Довольна?"
    l "Да... Спасибо."
    "Лиза понимает, что предстоит долгий путь к взаимопониманию."
    
    call screen etiquette_info_good
    return

# Нейтральная концовка
label neutral_ending:
    scene bg office
    show lisa at center
    with fade

    "Лиза сама старается соблюдать этикет. Коллеги так и не поняли её стремления."
    l "Главное, что я делаю всё правильно... Остальное не важно."
    "Она продолжает работать над собой в одиночку."
    
    call screen etiquette_info_neutral
    return

# Плохая концовка
label bad_ending:
    scene black
    with fade
    # Убрал музыку - файла нет
    # play music "sad.mp3" fadein 3.0

    "..."

    show text "Случилось самое страшное.\nКлиент разорвал контракт." with dissolve
    pause(3)
    hide text with dissolve

    show text "По данным HR-исследований:" with dissolve
    pause(2)
    hide text with dissolve

    show text "Более 60% увольнений происходят из-за нарушения корпоративной этики\nи неумения работать в команде." with dissolve
    pause(4)
    hide text with dissolve

    show text "Основная причина — непонимание важности социальных норм в профессиональной среде." with dissolve
    pause(4)
    hide text with dissolve

    call screen etiquette_info_bad
    return

# Экран для важного объявления
screen important_announcement():
    modal False
    frame:
        xalign 0.5
        yalign 0.2
        xpadding 30
        ypadding 20
        background "#2C5F9D"
        vbox:
            text "ВАЖНОЕ ОБЪЯВЛЕНИЕ!" size 30 bold True
            text "Компания теряет клиентов из-за непрофессионального поведения сотрудников..." size 24
            text "Напоминаем: соблюдение делового этикета обязательно для всех!" size 24 bold True

# Экраны концовок (остаются без изменений)
screen etiquette_info_perfect():
    modal True
    frame:
        xalign 0.5
        yalign 0.5
        xmaximum 1000
        background "#1a2b3c"
        vbox:
            spacing 20
            text "ОТЛИЧНЫЙ РЕЗУЛЬТАТ!" size 40 xalign 0.5
            text "Вы освоили искусство социального взаимодействия!" size 28
            text "• Вы научились предвидеть конфликты" size 24
            text "• Вы нашли подход к коллегам" size 24
            text "• Ваша карьера идет вверх" size 24
            text "Помните: этикет - это ключ к успеху в обществе!" size 28 bold True
            textbutton "Завершить":
                xalign 0.5
                action Return()

screen etiquette_info_good():
    modal True
    frame:
        xalign 0.5
        yalign 0.5
        xmaximum 1000
        background "#1a2b3c"
        vbox:
            spacing 20
            text "ХОРОШИЙ РЕЗУЛЬТАТ" size 40 xalign 0.5
            text "Вы усвоили основы этикета, но есть возможности для улучшения." size 28
            text "• Правила соблюдаются" size 24
            text "• Но отношения с коллегами требуют работы" size 24
            text "• Продолжайте развивать социальные навыки" size 24
            text "Продолжайте работать над коммуникацией!" size 28 bold True
            textbutton "Завершить":
                xalign 0.5
                action Return()

screen etiquette_info_neutral():
    modal True
    frame:
        xalign 0.5
        yalign 0.5
        xmaximum 1000
        background "#1a2b3c"
        vbox:
            spacing 20
            text "НЕЙТРАЛЬНЫЙ РЕЗУЛЬТАТ" size 40 xalign 0.5
            text "Вы усвоили некоторые правила, но есть над чем работать." size 28
            text "• Основы этикета усвоены" size 24
            text "• Но не все ситуации проработаны" size 24
            text "• В коллективе осталось недопонимание" size 24
            text "Помните: социальные навыки требуют постоянной практики!" size 28 bold True
            textbutton "Завершить":
                xalign 0.5
                action Return()

screen etiquette_info_bad():
    modal True
    frame:
        xalign 0.5
        yalign 0.5
        xmaximum 1000
        background "#1a2b3c"
        vbox:
            spacing 20
            text "КРИТИЧЕСКАЯ СИТУАЦИЯ" size 40 xalign 0.5
            text "К сожалению, вы не смогли адаптироваться в коллективе." size 28
            text "• Социальные нормы не усвоены" size 24
            text "• Профессиональные отношения нарушены" size 24
            text "• Карьерный рост под угрозой" size 24
            text "Помните: эти ошибки можно исправить, работая над собой!" size 28 bold True
            textbutton "Завершить":
                xalign 0.5
                action Return()


<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$pdo = getPDO();

$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS properties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    address TEXT NOT NULL,
    price REAL NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('rent', 'sale')),
    description TEXT NOT NULL DEFAULT '',
    rooms INTEGER NOT NULL DEFAULT 1,
    area TEXT NOT NULL DEFAULT '',
    floor TEXT NOT NULL DEFAULT '',
    district TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    payment_type TEXT NOT NULL DEFAULT 'both',
    image TEXT NOT NULL DEFAULT 'images/demo-property.jpg',
    status TEXT NOT NULL DEFAULT 'available'
)");

/**
 * Ensure schema upgrades are applied for existing databases.
 */
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll();
    foreach ($columns as $col) {
        if (($col['name'] ?? '') === $column) {
            return;
        }
    }
    $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
}

ensureColumn($pdo, 'properties', 'description', "TEXT NOT NULL DEFAULT ''");
ensureColumn($pdo, 'properties', 'rooms', "INTEGER NOT NULL DEFAULT 1");
ensureColumn($pdo, 'properties', 'area', "TEXT NOT NULL DEFAULT ''");
ensureColumn($pdo, 'properties', 'floor', "TEXT NOT NULL DEFAULT ''");
ensureColumn($pdo, 'properties', 'district', "TEXT NOT NULL DEFAULT ''");
ensureColumn($pdo, 'properties', 'phone', "TEXT NOT NULL DEFAULT ''");
ensureColumn($pdo, 'properties', 'payment_type', "TEXT NOT NULL DEFAULT 'both'");

$pdo->exec("
CREATE TABLE IF NOT EXISTS requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    property_id INTEGER NOT NULL,
    comment TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS feedback (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("
CREATE TABLE IF NOT EXISTS favorites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    property_id INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, property_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
)");

$adminEmail = 'admin@mail.com';
$adminPass = 'admin123';
$checkAdmin = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$checkAdmin->execute([':email' => $adminEmail]);
if (!$checkAdmin->fetch()) {
    $insertAdmin = $pdo->prepare(
        'INSERT INTO users(name, phone, email, password, role) VALUES(:name, :phone, :email, :password, :role)'
    );
    $insertAdmin->execute([
        ':name' => 'Администратор',
        ':phone' => '+7 (000) 000-00-00',
        ':email' => $adminEmail,
        ':password' => password_hash($adminPass, PASSWORD_DEFAULT),
        ':role' => 'admin',
    ]);
}

$seed = $pdo->prepare(
    'INSERT INTO properties(title, address, price, type, description, rooms, area, floor, district, phone, payment_type, image, status)
     VALUES(:title, :address, :price, :type, :description, :rooms, :area, :floor, :district, :phone, :payment_type, :image, :status)'
);

$items = [
    ['Студия у метро Девяткино', 'Санкт-Петербург, ул. Шувалова, 6', 34000, 'rent', 'Светлая студия с лоджией в новом доме, 8 минут пешком до метро.', 1, '28 м²', '12/25', 'Девяткино', '+7 (900) 101-10-10', 'both', 'images/properties/property-1.jpg', 'available'],
    ['1-комнатная квартира на Парнасе', 'Санкт-Петербург, ул. Михаила Дудина, 25', 39000, 'rent', 'Квартира с мебелью и техникой, рядом ТЦ и парк.', 1, '36 м²', '7/19', 'Парнас', '+7 (900) 102-10-10', 'cash', 'images/properties/property-2.jpg', 'reserved'],
    ['2-комнатная в Московском районе', 'Санкт-Петербург, ул. Варшавская, 18', 62000, 'rent', 'Просторная двухкомнатная квартира рядом с метро Московская.', 2, '54 м²', '5/12', 'Московский', '+7 (900) 103-10-10', 'online', 'images/properties/property-3.jpg', 'available'],
    ['3-комнатная квартира в центре', 'Санкт-Петербург, Лиговский пр., 92', 19800000, 'sale', 'Исторический центр, высокие потолки, дизайнерский ремонт.', 3, '96 м²', '3/6', 'Центральный', '+7 (900) 104-10-10', 'both', 'images/properties/property-4.jpg', 'available'],
    ['Апартаменты с ремонтом', 'Санкт-Петербург, наб. Обводного канала, 108', 8900000, 'sale', 'Апартаменты под сдачу с готовым интерьером и техникой.', 1, '33 м²', '9/14', 'Адмиралтейский', '+7 (900) 105-10-10', 'online', 'images/properties/property-5.jpg', 'available'],
    ['Квартира у Парка Победы', 'Санкт-Петербург, ул. Бассейная, 37', 14500000, 'sale', 'Тихий двор, окна на парк, документы готовы к сделке.', 2, '61 м²', '4/9', 'Московский', '+7 (900) 106-10-10', 'cash', 'images/properties/property-6.jpg', 'sold'],
    ['Евродвушка на Васильевском', 'Санкт-Петербург, 26-я линия В.О., 15', 69000, 'rent', 'Современная евродвушка рядом с набережной и метро.', 2, '49 м²', '10/17', 'Василеостровский', '+7 (900) 107-10-10', 'both', 'images/properties/property-7.jpg', 'available'],
    ['Семейная квартира в Купчино', 'Санкт-Петербург, ул. Будапештская, 76', 11000000, 'sale', 'Уютная квартира для семьи, рядом школы и детские сады.', 3, '74 м²', '8/10', 'Фрунзенский', '+7 (900) 108-10-10', 'cash', 'images/properties/property-8.jpg', 'reserved'],
    ['Студия в Приморском районе', 'Санкт-Петербург, Комендантский пр., 68', 30000, 'rent', 'Компактная студия в новом ЖК с охраной и закрытым двором.', 1, '24 м²', '13/24', 'Приморский', '+7 (900) 109-10-10', 'online', 'images/properties/property-9.jpg', 'available'],
    ['Двухуровневый лофт', 'Санкт-Петербург, ул. Розенштейна, 21', 17500000, 'sale', 'Лофт с панорамными окнами и вторым светом.', 2, '88 м²', '6/6', 'Кировский', '+7 (900) 110-10-10', 'both', 'images/properties/property-10.jpg', 'available'],
    ['Квартира рядом с Финским заливом', 'Санкт-Петербург, ул. Адмирала Трибуца, 7', 53000, 'rent', 'Вид на залив, теплые полы, паркинг в доме.', 2, '58 м²', '11/20', 'Красносельский', '+7 (900) 111-10-10', 'both', 'images/properties/property-1.jpg', 'available'],
    ['Дом в Курортном районе', 'Санкт-Петербург, п. Репино, ул. Луговая, 11', 32500000, 'sale', 'Кирпичный дом с участком, баней и зоной барбекю.', 5, '220 м²', '2', 'Курортный', '+7 (900) 112-10-10', 'cash', 'images/properties/property-2.jpg', 'available'],
    ['1-комнатная на Звездной', 'Санкт-Петербург, Дунайский пр., 24', 42000, 'rent', 'Светлая квартира с новой кухней и бытовой техникой.', 1, '38 м²', '14/16', 'Московский', '+7 (900) 113-10-10', 'online', 'images/properties/property-3.jpg', 'rented'],
    ['Квартира у метро Лесная', 'Санкт-Петербург, ул. Кантемировская, 22', 12800000, 'sale', 'Хорошая транспортная доступность и развитая инфраструктура.', 2, '57 м²', '6/12', 'Выборгский', '+7 (900) 114-10-10', 'both', 'images/properties/property-4.jpg', 'available'],
    ['Студия в Мурино', 'Ленинградская обл., Мурино, Воронцовский б-р, 14', 28000, 'rent', 'Бюджетный вариант для одного человека рядом с метро.', 1, '22 м²', '16/18', 'Мурино', '+7 (900) 115-10-10', 'cash', 'images/properties/property-5.jpg', 'available'],
    ['Трехкомнатная у Невы', 'Санкт-Петербург, Октябрьская наб., 84', 15900000, 'sale', 'Видовая квартира с лоджией и местом в подземном паркинге.', 3, '89 м²', '15/22', 'Невский', '+7 (900) 116-10-10', 'online', 'images/properties/property-6.jpg', 'reserved'],
    ['Евро-3 в новостройке', 'Санкт-Петербург, Пулковское ш., 73', 14750000, 'sale', 'Квартира с предчистовой отделкой в новом ЖК.', 3, '79 м²', '9/23', 'Московский', '+7 (900) 117-10-10', 'both', 'images/properties/property-7.jpg', 'available'],
    ['2-комнатная у метро Проспект Просвещения', 'Санкт-Петербург, пр. Просвещения, 67', 58000, 'rent', 'Полностью меблирована, можно с детьми.', 2, '52 м²', '4/9', 'Выборгский', '+7 (900) 118-10-10', 'cash', 'images/properties/property-8.jpg', 'available'],
    ['Апартаменты рядом с ИТМО', 'Санкт-Петербург, Кронверкский пр., 65', 49000, 'rent', 'Идеально для студента или молодой пары.', 1, '31 м²', '8/12', 'Петроградский', '+7 (900) 119-10-10', 'online', 'images/properties/property-9.jpg', 'available'],
    ['Пентхаус на Крестовском', 'Санкт-Петербург, Морской пр., 29', 74200000, 'sale', 'Премиум-пентхаус с террасой и видом на воду.', 4, '182 м²', '13/13', 'Петроградский', '+7 (900) 120-10-10', 'both', 'images/properties/property-10.jpg', 'available'],
    ['1-комнатная у метро Международная', 'Санкт-Петербург, ул. Белы Куна, 19', 37000, 'rent', 'Косметический ремонт, сдача на длительный срок.', 1, '34 м²', '5/9', 'Фрунзенский', '+7 (900) 121-10-10', 'cash', 'images/properties/property-1.jpg', 'available'],
    ['Семейная квартира в Калининском районе', 'Санкт-Петербург, ул. Софьи Ковалевской, 7', 13200000, 'sale', 'Три изолированные комнаты и большая кухня.', 3, '81 м²', '2/9', 'Калининский', '+7 (900) 122-10-10', 'both', 'images/properties/property-2.jpg', 'available'],
    ['Студия с видом на парк', 'Санкт-Петербург, ул. Оптиков, 38', 32000, 'rent', 'Современная студия, в доме есть фитнес и коворкинг.', 1, '26 м²', '17/25', 'Приморский', '+7 (900) 123-10-10', 'online', 'images/properties/property-3.jpg', 'reserved'],
    ['2-комнатная в Зеленогорске', 'Санкт-Петербург, г. Зеленогорск, пр. Ленина, 28', 9100000, 'sale', 'Квартира у залива для спокойной жизни за городом.', 2, '59 м²', '3/5', 'Курортный', '+7 (900) 124-10-10', 'cash', 'images/properties/property-4.jpg', 'available'],
    ['Квартира для инвестиций', 'Санкт-Петербург, ул. Типанова, 10', 10400000, 'sale', 'Высокий спрос на аренду, хороший арендный поток.', 1, '41 м²', '11/16', 'Московский', '+7 (900) 125-10-10', 'online', 'images/properties/property-5.jpg', 'available'],
    ['Двушка рядом с метро Ладожская', 'Санкт-Петербург, Заневский пр., 55', 56000, 'rent', 'Новая мебель, возможность заселения в день просмотра.', 2, '53 м²', '7/14', 'Красногвардейский', '+7 (900) 126-10-10', 'both', 'images/properties/property-6.jpg', 'available'],
    ['Просторная 4-комнатная квартира', 'Санкт-Петербург, ул. Савушкина, 143', 26500000, 'sale', 'Большая гостиная, гардеробная, 2 санузла.', 4, '126 м²', '12/16', 'Приморский', '+7 (900) 127-10-10', 'both', 'images/properties/property-7.jpg', 'available'],
    ['Студия в Невском районе', 'Санкт-Петербург, ул. Подвойского, 28', 29500, 'rent', 'Теплая студия в тихом дворе, рядом ТЦ и метро.', 1, '23 м²', '9/17', 'Невский', '+7 (900) 128-10-10', 'cash', 'images/properties/property-8.jpg', 'rented'],
    ['3-комнатная у метро Академическая', 'Санкт-Петербург, Гражданский пр., 85', 15100000, 'sale', 'Капитальный ремонт, полностью заменена электрика.', 3, '92 м²', '6/12', 'Калининский', '+7 (900) 129-10-10', 'online', 'images/properties/property-9.jpg', 'available'],
    ['Апартаменты бизнес-класса', 'Санкт-Петербург, ул. Пионерская, 50', 82000, 'rent', 'Премиальная отделка, сервис и охрана 24/7.', 2, '61 м²', '18/21', 'Петроградский', '+7 (900) 130-10-10', 'both', 'images/properties/property-10.jpg', 'available'],
];

$existsStmt = $pdo->prepare('SELECT id FROM properties WHERE title = :title LIMIT 1');
foreach ($items as $item) {
    $existsStmt->execute([':title' => $item[0]]);
    if ($existsStmt->fetch()) {
        continue;
    }

    $seed->execute([
        ':title' => $item[0],
        ':address' => $item[1],
        ':price' => $item[2],
        ':type' => $item[3],
        ':description' => $item[4],
        ':rooms' => $item[5],
        ':area' => $item[6],
        ':floor' => $item[7],
        ':district' => $item[8],
        ':phone' => $item[9],
        ':payment_type' => $item[10],
        ':image' => $item[11],
        ':status' => $item[12],
    ]);
}

$pdo->exec("UPDATE properties SET status = 'available' WHERE status = 'active'");
$pdo->exec("UPDATE properties SET payment_type = 'both' WHERE payment_type NOT IN ('cash', 'online', 'both')");

echo 'База данных инициализирована успешно.';


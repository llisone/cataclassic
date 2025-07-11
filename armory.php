<?php
try {
    // Подключаемся к базе данных
    $db = new PDO('mysql:host=localhost;dbname=characters', 'mangos', 'mangos');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем список всех персонажей
    $characters = $db->query("SELECT guid, name, race, class, gender, level, money FROM characters ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Если выбран конкретный персонаж
    $selectedCharacter = null;
    $slots = array_fill(0, 19, null); // Инициализируем массив слотов
    
    if (isset($_GET['guid'])) {
        // Получаем данные персонажа
        $stmt = $db->prepare("SELECT * FROM characters WHERE guid = ?");
        $stmt->execute([$_GET['guid']]);
        $selectedCharacter = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Получаем экипировку персонажа
        try {
            $stmt = $db->prepare("SELECT slot, item_template FROM character_inventory WHERE guid = ? AND slot < 19");
            $stmt->execute([$_GET['guid']]);
            $equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Организуем экипировку по слотам
            foreach ($equipment as $item) {
                $slots[$item['slot']] = $item;
            }
        } catch (PDOException $e) {
            // Таблица экипировки не найдена - это нормально
            error_log("Ошибка при получении экипировки: " . $e->getMessage());
        }
    }

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Функция для получения полной информации о предмете
function getItemInfo($itemId, $db) {
    if (!$itemId) return false;
    
    try {
        // Проверяем, есть ли таблица item_template
        $db->query("SELECT 1 FROM item_template LIMIT 1");
        
        $stmt = $db->prepare("SELECT * FROM item_template WHERE entry = ?");
        $stmt->execute([$itemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Если таблицы нет или произошла ошибка, используем заглушку
        $items = [
            12345 => ['name' => 'Меч ярости', 'Quality' => 3, 'InventoryType' => 17, 'ItemLevel' => 120],
            23456 => ['name' => 'Щит командора', 'Quality' => 3, 'InventoryType' => 14, 'ItemLevel' => 110],
            34567 => ['name' => 'Шлем рыцаря', 'Quality' => 2, 'InventoryType' => 1, 'ItemLevel' => 100],
        ];
        return $items[$itemId] ?? ['name' => "Предмет #$itemId", 'Quality' => 1, 'InventoryType' => 0, 'ItemLevel' => 1];
    }
}

// Функция для определения цвета качества предмета
function getQualityColor($quality) {
    $colors = [
        0 => '#9d9d9d', // Серый
        1 => '#ffffff', // Белый
        2 => '#1eff00', // Зеленый
        3 => '#0070dd', // Синий
        4 => '#a335ee', // Фиолетовый
        5 => '#ff8000', // Оранжевый
    ];
    return $colors[$quality] ?? '#ffffff';
}

// Функция для получения пути к изображению расы
function getRaceImage($raceId, $gender) {
    $raceImages = [
        1 => ['male' => 'human_male.jpg', 'female' => 'human_female.jpg'],
        2 => ['male' => 'орк.png', 'female' => 'орк.png'],
        3 => ['male' => 'dwarf_male.jpg', 'female' => 'dwarf_female.jpg'],
        4 => ['male' => 'ночной эльф.png', 'female' => 'ночной эльф.png'],
        5 => ['male' => 'андед.png', 'female' => 'андед.png'],
        6 => ['male' => 'таурен.png', 'female' => 'таурен.png'],
        7 => ['male' => 'гном.png', 'female' => 'гном.png'],
        8 => ['male' => 'троль.png', 'female' => 'троль.png'],
        10 => ['male' => 'эльфийка.png', 'female' => 'эльфийка.png'],
        11 => ['male' => 'дреней.png', 'female' => 'дреней.png']
    ];
    
    $defaultImage = 'default_character.jpg';
    return isset($raceImages[$raceId][$gender == 0 ? 'male' : 'female']) ? 
           'image/' . $raceImages[$raceId][$gender == 0 ? 'male' : 'female'] : 
           'image/' . $defaultImage;
}

// Функция для получения названия слота экипировки
function getSlotName($slotId) {
    $slots = [
        0 => 'Голова',
        1 => 'Шея',
        2 => 'Плечи',
        3 => 'Рубашка',
        4 => 'Грудь',
        5 => 'Пояс',
        6 => 'Ноги',
        7 => 'Ступни',
        8 => 'Запястья',
        9 => 'Кисти рук',
        10 => 'Пальцы',
        11 => 'Аксессуар',
        12 => 'Одноручное',
        13 => 'Щит',
        14 => 'Двуручное',
        15 => 'Плащ',
        16 => 'Оружие дальнего боя',
        17 => 'Спина',
        18 => 'Двуручное'
    ];
    return $slots[$slotId] ?? 'Неизвестный слот';
}

// Функция для получения названия предмета
function getItemName($itemId, $db) {
    if (!$itemId) return false;
    
    try {
        // Проверяем, есть ли таблица item_template
        $db->query("SELECT 1 FROM item_template LIMIT 1");
        
        $stmt = $db->prepare("SELECT name FROM item_template WHERE entry = ?");
        $stmt->execute([$itemId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Если таблицы нет или произошла ошибка, используем заглушку
        $items = [
            12345 => 'Меч ярости',
            23456 => 'Щит командора',
            34567 => 'Шлем рыцаря',
        ];
        return $items[$itemId] ?? "Предмет #$itemId";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оружейная</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1a1a1a;
            color: #e0e0e0;
            margin: 0;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(78, 204, 163, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 70, 85, 0.1) 0%, transparent 50%),
                linear-gradient(to bottom, #0a0a0a, #1a1a1a);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 2px solid #3a3a3a;
            margin-bottom: 30px;
        }
        .back-button {
            background: linear-gradient(145deg, #ff4655, #f7b733);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(255, 70, 85, 0.3);
        }
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 70, 85, 0.4);
        }
        .character-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .character-card {
            background: rgba(30, 30, 30, 0.7);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #d4af37;
            transition: all 0.3s;
            cursor: pointer;
        }
        .character-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .character-details {
            background: rgba(30, 30, 30, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .character-header {
            display: flex;
            gap: 20px;
            align-items: center;
            margin-bottom: 20px;
        }
        .character-image {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            object-fit: cover;
            border: 3px solid #d4af37;
        }
        .character-info {
            flex: 1;
        }
        h1, h2, h3 {
            color: #d4af37;
            margin-top: 0;
        }
        .race-class {
            font-size: 0.9em;
            color: #aaa;
        }
        .level {
            display: inline-block;
            background: #d4af37;
            color: #1a1a1a;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 0.8em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            color: #d4af37;
        }
        
        .equipment-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .equipment-column {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .equipment-slot {
            background: rgba(40, 40, 40, 0.8);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 60px;
        }
        
        .equipment-slot h4 {
            margin: 0 0 5px 0;
            color: #d4af37;
            font-size: 0.9em;
        }
        
        .equipment-item {
            color: #e0e0e0;
            font-size: 0.9em;
        }
        
        .empty-slot {
            color: #777;
            font-style: italic;
            font-size: 0.8em;
        }
        
        /* Специфичные стили для разных слотов */
        .slot-head { background: rgba(30, 30, 60, 0.6); }
        .slot-neck { background: rgba(60, 30, 60, 0.6); }
        .slot-shoulders { background: rgba(30, 60, 30, 0.6); }
        .slot-chest { background: rgba(60, 30, 30, 0.6); }
        .slot-waist { background: rgba(30, 60, 60, 0.6); }
        .slot-legs { background: rgba(60, 60, 30, 0.6); }
        .slot-feet { background: rgba(30, 30, 30, 0.8); }
        .slot-wrists { background: rgba(60, 60, 60, 0.6); }
        .slot-hands { background: rgba(40, 40, 40, 0.8); }
        .slot-finger { background: rgba(80, 60, 40, 0.6); }
        .slot-trinket { background: rgba(40, 80, 80, 0.6); }
        .slot-back { background: rgba(80, 40, 80, 0.6); }
        .slot-mainhand { background: rgba(80, 40, 40, 0.6); }
        .slot-offhand { background: rgba(40, 80, 40, 0.6); }
        .slot-ranged { background: rgba(40, 40, 80, 0.6); }
        
        .equipment-row {
            grid-column: span 4;
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        
        @media (max-width: 768px) {
            .character-list {
                grid-template-columns: 1fr;
            }
            .character-header {
                flex-direction: column;
                text-align: center;
            }
            .character-image {
                margin-bottom: 15px;
            }
            .equipment-container {
                grid-template-columns: 1fr;
            }
            .equipment-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Оружейная</h1>
            <button class="back-button" onclick="window.location.href='index.html'">На главную</button>
        </div>

        <h2>Список персонажей</h2>
        <div class="character-list">
            <?php foreach ($characters as $char): ?>
                <div class="character-card" onclick="window.location.href='?guid=<?= $char['guid'] ?>'">
                    <h3><?= htmlspecialchars($char['name']) ?> <span class="level">Ур. <?= $char['level'] ?></span></h3>
                    <div class="race-class">
                        <?= getRaceName($char['race']) ?> - <?= getClassName($char['class']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($selectedCharacter): ?>
        <div class="character-details">
            <div class="character-header">
                <img src="<?= getRaceImage($selectedCharacter['race'], $selectedCharacter['gender']) ?>" 
                     alt="<?= getRaceName($selectedCharacter['race']) ?>" 
                     class="character-image">
                <div class="character-info">
                    <h2><?= htmlspecialchars($selectedCharacter['name']) ?></h2>
                    <div class="race-class">
                        <?= getRaceName($selectedCharacter['race']) ?> - <?= getClassName($selectedCharacter['class']) ?>
                        <span class="level">Уровень <?= $selectedCharacter['level'] ?></span>
                    </div>
                </div>
            </div>

            <h3>Основная информация</h3>
            <table>
                <tr>
                    <th>Здоровье</th>
                    <td><?= $selectedCharacter['health'] ?? 'Н/Д' ?></td>
                </tr>
                <tr>
                    <th>Сила</th>
                    <td><?= $selectedCharacter['strength'] ?? 'Н/Д' ?></td>
                </tr>
                <tr>
                    <th>Ловкость</th>
                    <td><?= $selectedCharacter['agility'] ?? 'Н/Д' ?></td>
                </tr>
                <tr>
                    <th>Интеллект</th>
                    <td><?= $selectedCharacter['intellect'] ?? 'Н/Д' ?></td>
                </tr>
                <tr>
                    <th>Дух</th>
                    <td><?= $selectedCharacter['spirit'] ?? 'Н/Д' ?></td>
                </tr>
                <tr>
                    <th>Золото</th>
                    <td><?= formatMoney($selectedCharacter['money']) ?></td>
                </tr>
            </table>

            <h3>Экипировка</h3>
            <div class="equipment-container">
                <!-- Первая колонка -->
                <div class="equipment-column">
                    <div class="equipment-slot slot-head">
                        <h4>Голова</h4>
                        <?php if (isset($slots[0]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[0]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-neck">
                        <h4>Шея</h4>
                        <?php if (isset($slots[1]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[1]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-shoulders">
                        <h4>Плечи</h4>
                        <?php if (isset($slots[2]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[2]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Вторая колонка -->
                <div class="equipment-column">
                    <div class="equipment-slot slot-chest">
                        <h4>Грудь</h4>
                        <?php if (isset($slots[4]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[4]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-waist">
                        <h4>Пояс</h4>
                        <?php if (isset($slots[5]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[5]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-legs">
                        <h4>Ноги</h4>
                        <?php if (isset($slots[6]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[6]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Третья колонка -->
                <div class="equipment-column">
                    <div class="equipment-slot slot-feet">
                        <h4>Ступни</h4>
                        <?php if (isset($slots[7]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[7]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-wrists">
                        <h4>Запястья</h4>
                        <?php if (isset($slots[8]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[8]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-hands">
                        <h4>Кисти рук</h4>
                        <?php if (isset($slots[9]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[9]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Четвертая колонка -->
                <div class="equipment-column">
                    <div class="equipment-slot slot-finger">
                        <h4>Пальцы</h4>
                        <?php if (isset($slots[10]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[10]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                        <?php if (isset($slots[11]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[11]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-trinket">
                        <h4>Аксессуар</h4>
                        <?php if (isset($slots[12]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[12]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                        <?php if (isset($slots[13]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[13]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-back">
                        <h4>Спина</h4>
                        <?php if (isset($slots[15]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[15]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Оружие (отдельная строка) -->
                <div class="equipment-row">
                    <div class="equipment-slot slot-mainhand">
                        <h4>Правая рука</h4>
                        <?php if (isset($slots[16]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[16]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-offhand">
                        <h4>Левая рука</h4>
                        <?php if (isset($slots[17]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[17]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="equipment-slot slot-ranged">
                        <h4>Оружие дальнего боя</h4>
                        <?php if (isset($slots[18]['item_template'])): ?>
                            <div class="equipment-item"><?= htmlspecialchars(getItemName($slots[18]['item_template'], $db)) ?></div>
                        <?php else: ?>
                            <div class="empty-slot">Пусто</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <h3>Достижения</h3>
            <p>Информация о достижениях скоро будет добавлена</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Вспомогательные функции
function getRaceName($raceId) {
    $races = [
        1 => 'Человек',
        2 => 'Орк',
        3 => 'Дворф',
        4 => 'Ночной эльф',
        5 => 'Нежить',
        6 => 'Таурен',
        7 => 'Гном',
        8 => 'Тролль',
        10 => 'Кровавый эльф',
        11 => 'Дреней'
    ];
    return $races[$raceId] ?? 'Неизвестно';
}

function getClassName($classId) {
    $classes = [
        1 => 'Воин',
        2 => 'Паладин',
        3 => 'Охотник',
        4 => 'Разбойник',
        5 => 'Жрец',
        6 => 'Рыцарь смерти',
        7 => 'Шаман',
        8 => 'Маг',
        9 => 'Чернокнижник',
        11 => 'Друид'
    ];
    return $classes[$classId] ?? 'Неизвестно';
}

function formatMoney($copper) {
    $gold = floor($copper / 10000);
    $silver = floor(($copper % 10000) / 100);
    $copper = $copper % 100;
    return sprintf("%d <span style='color:gold'>з</span> %d <span style='color:silver'>с</span> %d <span style='color:copper'>м</span>", $gold, $silver, $copper);
}
?>
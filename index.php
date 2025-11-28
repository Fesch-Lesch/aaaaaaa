<?php
declare(strict_types=1);

$jsonFile = __DIR__ . '/data.json';
if (!file_exists($jsonFile)) {
    http_response_code(500);
    echo 'Файл data.json не найден';
    exit;
}
$raw = file_get_contents($jsonFile);
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(500);
    echo 'Некорректный JSON';
    exit;
}

// Ожидаемые поля (без IDE)
$columns = [
    'group' => 'Номер группы',
    'index' => 'Порядковый номер',
    'fio'   => 'ФИО'
];

// Экранирование
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Функция для форматирования значения
function formatValue($value): string {
    if ($value === null || $value === '') {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Функция для получения инициалов из ФИО
function getInitials(?string $fio): string {
    if (!$fio) return '?';
    $parts = explode(' ', $fio);
    $initials = '';
    foreach ($parts as $part) {
        if (mb_strlen($part) > 0) {
            $initials .= mb_substr($part, 0, 1);
        }
    }
    return mb_strtoupper($initials);
}

// Определяем режим работы
$isExport = isset($_GET['export']) && $_GET['export'] === 'pdf';
$searchQuery = $_GET['search'] ?? '';

// Фильтрация данных
if ($searchQuery) {
    $filteredData = array_filter($data, function($row) use ($searchQuery) {
        return !$searchQuery || 
            stripos($row['fio'] ?? '', $searchQuery) !== false ||
            stripos($row['group'] ?? '', $searchQuery) !== false;
    });
    $data = array_values($filteredData);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $isExport ? 'Экспорт в PDF' : 'Студенты гр. ИС-235.1' ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="<?= $isExport ? 'print-mode' : '' ?>">
  <?php if ($isExport): ?>
    <div class="no-print" style="text-align: center; margin: 20px;">
        <button class="btn btn-print" onclick="window.print()">
            <span>🖨️</span> Печать / Сохранить как PDF
        </button>
        <a class="btn btn-secondary" href="?">
            <span>←</span> Назад к таблице
        </a>
    </div>

    <div class="container">
        <div class="header">
            <h1>Список студентов</h1>
            <p>Группа ИС-235.1 - Информация о студентах</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div>Всего студентов: <strong><?= count($data) ?></strong></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📅</div>
                    <div>Сгенерировано: <strong><?= date('d.m.Y H:i:s') ?></strong></div>
                </div>
            </div>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <div class="icon">📭</div>
                    <p>Данных нет</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Группа</th>
                                <th>Порядковый номер</th>
                                <th>ФИО</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                            <tr>
                                <td><?= formatValue($row['group'] ?? '') ?></td>
                                <td><?= formatValue($row['index'] ?? '') ?></td>
                                <td><?= formatValue($row['fio'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Отчёт сгенерирован автоматически • Группа ИС-235.1</p>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>

  <?php else: ?>
    <div class="container">
        <div class="header">
            <h1>🎓 Список студентов</h1>
            <p>Группа ИС-235.1 - База данных студентов</p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div>Всего студентов: <strong><?= count($data) ?></strong></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📚</div>
                    <div>Уникальных групп: <strong><?= count(array_unique(array_column($data, 'group'))) ?></strong></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">⭐</div>
                    <div>Обновлено: <strong><?= date('d.m.Y H:i') ?></strong></div>
                </div>
            </div>

            <!-- Поиск -->
            <div class="toolbar">
                <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div style="position: relative;">
                        <input type="text" name="search" value="<?= h($searchQuery) ?>" 
                               placeholder="Поиск по ФИО или группе..." 
                               style="padding: 14px 45px 14px 16px; border: 2px solid var(--border); 
                                      border-radius: 12px; font-size: 15px; width: 350px; 
                                      background: var(--light);">
                        <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
                                    color: var(--primary); font-size: 18px;">🔍</span>
                    </div>
                    
                    <button type="submit" class="btn">
                        <span>🔍</span> Найти
                    </button>
                    
                    <?php if ($searchQuery): ?>
                        <a href="?" class="btn btn-secondary">
                            <span>🗑️</span> Сбросить поиск
                        </a>
                    <?php endif; ?>
                </form>
                
                <div style="display: flex; gap: 15px; margin-left: auto;">
                    <a class="btn" href="?export=pdf">
                        <span>📄</span> Экспорт в PDF
                    </a>
                    <button class="btn btn-secondary" onclick="location.reload()">
                        <span>🔄</span> Обновить
                    </button>
                </div>
            </div>

            <?php if (empty($data)): ?>
                <div class="no-data">
                    <div class="icon">📭</div>
                    <p>Нет данных для отображения</p>
                    <?php if ($searchQuery): ?>
                        <p style="margin-top: 10px; font-size: 1rem;">Попробуйте изменить поисковый запрос</p>
                    <?php else: ?>
                        <p style="margin-top: 10px; font-size: 1rem;">Проверьте файл data.json</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <?php foreach ($columns as $key => $title): ?>
                                    <th><?= h($title) ?></th>
                                <?php endforeach; ?>
                                <th>Аватар</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $index => $row): ?>
                                <tr class="<?= $index % 2 === 0 ? 'highlight' : '' ?>">
                                    <td>
                                        <span style="font-weight: 700; color: var(--primary);">
                                            <?= formatValue($row['group'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="background: var(--accent); color: var(--dark); 
                                              padding: 6px 12px; border-radius: 10px; font-weight: 700;
                                              display: inline-block; min-width: 40px; text-align: center;">
                                            <?= formatValue($row['index'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; font-size: 16px;">
                                        <?= formatValue($row['fio'] ?? '') ?>
                                    </td>
                                    <td>
                                        <div class="student-avatar" title="<?= h($row['fio'] ?? '') ?>">
                                            <?= getInitials($row['fio'] ?? '') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Информация о результате поиска -->
                <?php if ($searchQuery): ?>
                    <div class="stats" style="margin-top: 25px; background: #f0f9ff; border-left: 5px solid #0ea5e9;">
                        <div class="stat-item">
                            <div class="stat-icon" style="background: #0ea5e9;">🔍</div>
                            <div>
                                Найдено студентов: <strong><?= count($data) ?></strong>
                                <?php if ($searchQuery): ?>
                                    по запросу: "<strong><?= h($searchQuery) ?></strong>"
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="footer">
                <p>✨ Система управления студентами • Группа ИС-235.1 • <?= date('Y') ?> ✨</p>
            </div>
        </div>
    </div>
  <?php endif; ?>
</body>
</html>
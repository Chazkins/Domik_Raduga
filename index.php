<?php
session_start();

// Пользователи
$users = [
    'teacher' => ['password' => 'teacher123', 'role' => 'teacher'],
    'admin'   => ['password' => 'admin123',   'role' => 'admin']
];

// Функция для валидации телефона
function validatePhone($phone) {
    // Удаляем все пробелы для проверки
    $cleanPhone = preg_replace('/\s+/', '', $phone);
    
    // Проверяем на запретные символы (только цифры, +, -, скобки, точка разрешены)
    if (!preg_match('/^[0-9+\-\(\)\.]+$/', $cleanPhone)) {
        return false;
    }
    
    // Дополнительная проверка: номер должен содержать минимум 10 цифр
    $digitsOnly = preg_replace('/[^\d]/', '', $cleanPhone);
    if (strlen($digitsOnly) < 10) {
        return false;
    }
    
    // Проверяем, что номер не состоит только из одних повторяющихся символов
    if (preg_match('/^(\+)\1+$|^(-)\1+$|^(\.)\1+$/', $cleanPhone)) {
        return false;
    }
    
    return true;
}

// Обработка выхода
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Обработка входа
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($users[$login]) && $users[$login]['password'] === $password) {
        $_SESSION['user'] = $login;
        $_SESSION['role'] = $users[$login]['role'];

        // Инициализируем массив кружков при первом входе админа
        if ($login === 'admin' && !isset($_SESSION['programs'])) {
            $_SESSION['programs'] = [
                ['title' => 'Художественная студия', 'age' => '6–14 лет', 'desc' => 'Рисование, лепка, аппликация. Развиваем творческое мышление.', 'img' => 'photos/1.jpg'],
                ['title' => 'Танцевальный кружок', 'age' => '5–12 лет', 'desc' => 'Современные и народные танцы. Грация и ритм!', 'img' => 'photos/2.jpg'],
                ['title' => 'Робототехника и программирование', 'age' => '8–15 лет', 'desc' => 'LEGO-роботы, Scratch, Python.', 'img' => 'photos/3.jpg'],
                ['title' => 'Театральная студия', 'age' => '7–16 лет', 'desc' => 'Актерское мастерство, постановки спектаклей.', 'img' => 'photos/4.jpg']
            ];
        }

        header('Location: index.php');
        exit;
    } else {
        $login_error = "Неверный логин или пароль!";
    }
}

// Текущая роль
$role = $_SESSION['role'] ?? 'parent';
$is_staff = in_array($role, ['teacher', 'admin']);

// === ИНИЦИАЛИЗАЦИЯ ДАННЫХ КРУЖКОВ ПРИ ПЕРВОМ ВХОДЕ АДМИНА ===
if ($role === 'admin' && !isset($_SESSION['programs'])) {
    $_SESSION['programs'] = [
        ['title' => 'Художественная студия', 'age' => '6–14 лет', 'desc' => 'Рисование, лепка, аппликация. Развиваем творческое мышление.', 'img' => 'photos/1.jpg'],
        ['title' => 'Танцевальный кружок', 'age' => '5–12 лет', 'desc' => 'Современные и народные танцы. Грация и ритм!', 'img' => 'photos/2.jpg'],
        ['title' => 'Робототехника и программирование', 'age' => '8–15 лет', 'desc' => 'LEGO-роботы, Scratch, Python.', 'img' => 'photos/3.jpg'],
        ['title' => 'Театральная студия', 'age' => '7–16 лет', 'desc' => 'Актерское мастерство, постановки спектаклей.', 'img' => 'photos/4.jpg']
    ];
}

// === ОПРЕДЕЛЕНИЕ МАССИВА КРУЖКОВ ===
if ($role === 'admin') {
    $programs = $_SESSION['programs'];  // Всегда из сессии для админа
} else {
    // Для родителя и педагога — статичный или из сессии админа (если есть)
    $programs = $_SESSION['programs'] ?? [
        ['title' => 'Художественная студия', 'age' => '6–14 лет', 'desc' => 'Рисование, лепка, аппликация. Развиваем творческое мышление.', 'img' => 'photos/1.jpg'],
        ['title' => 'Танцевальный кружок', 'age' => '5–12 лет', 'desc' => 'Современные и народные танцы. Грация и ритм!', 'img' => 'photos/2.jpg'],
        ['title' => 'Робототехника и программирование', 'age' => '8–15 лет', 'desc' => 'LEGO-роботы, Scratch, Python.', 'img' => 'photos/3.jpg'],
        ['title' => 'Театральная студия', 'age' => '7–16 лет', 'desc' => 'Актерское мастерство, постановки спектаклей.', 'img' => 'photos/4.jpg']
    ];
}

// === CRUD ОПЕРАЦИИ ДЛЯ АДМИНА ===
if ($role === 'admin') {
    // Удаление
    if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
        $index = (int)$_GET['delete'];
        if (isset($_SESSION['programs'][$index])) {
            array_splice($_SESSION['programs'], $index, 1);
        }
        header('Location: index.php');
        exit;
    }

    // Добавление / Редактирование
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_program'])) {
        $new_program = [
            'title' => trim($_POST['title']),
            'age'   => trim($_POST['age']),
            'desc'  => trim($_POST['desc']),
            'img'   => trim($_POST['img']) ?: 'photos/default.jpg'
        ];

        if (isset($_POST['edit_index']) && $_POST['edit_index'] !== '') {
            $index = (int)$_POST['edit_index'];
            $_SESSION['programs'][$index] = $new_program;
        } else {
            $_SESSION['programs'][] = $new_program;
        }
        header('Location: index.php');
        exit;
    }
}

// Для редактирования — получаем данные
$edit_program = null;
$edit_index = -1;
if ($role === 'admin' && isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_index = (int)$_GET['edit'];
    if (isset($_SESSION['programs'][$edit_index])) {
        $edit_program = $_SESSION['programs'][$edit_index];
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ДДТ "Радуга" — Мир творчества</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background: white; border-radius: var(--radius); box-shadow: var(--shadow); width: 90%; max-width: 600px; overflow: hidden; }
        .modal-header { background: var(--primary-color); color: white; padding: 1.5rem; text-align: center; position: relative; }
        .close-modal { position: absolute; right: 1.5rem; top: 1rem; font-size: 1.8rem; cursor: pointer; }
        .modal-body { padding: 2rem; }
        .confirm-delete { background: #e74c3c; color: white; padding: 1rem; border-radius: 12px; text-align: center; margin-bottom: 1rem; }
        .error-message { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; text-align: center; }
        .success-message { background: #d4edda; color: #155724; padding: 1rem; border-radius: 12px; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

<!-- Верхняя панель -->
<div class="role-switcher">
    <?php if ($is_staff): ?>
        <div style="color:white; margin-left:20px; font-weight:600;">
            <?= $role == 'teacher' ? 'Педагог' : 'Администратор' ?>: <strong><?= ucfirst($_SESSION['user']) ?></strong>
        </div>
        <a href="?logout=1" style="background:#e74c3c; margin-right:20px;">Выйти</a>
    <?php else: ?>
        <button id="openLogin" style="background:none; border:none; color:white; font-weight:600; cursor:pointer; margin-left:auto; margin-right:20px;">
            Вход для педагогов и администраторов
        </button>
    <?php endif; ?>
</div>

<header class="role-header-<?= $role ?>">
    <div class="header-content">
        <div class="logo-icon"><i class="fas fa-rainbow"></i></div>
        <h1>Дом детского творчества "Радуга"</h1>
        <p>Творчество • Развитие • Радость</p>
        <?php if ($role == 'parent'): ?>
            <a href="#enroll" class="btn btn-white">Записаться сейчас</a>
        <?php endif; ?>
    </div>
    <div class="wave"></div>
</header>

<div class="container">

    <?php if ($role == 'parent'): ?>
        <!-- Родительский интерфейс (как раньше) -->
        <section id="programs">
            <div class="section-header">
                <h2>Наши кружки и секции</h2>
            </div>
            <div class="cards">
                <?php foreach ($programs as $prog): ?>
                    <div class="card">
                        <div class="card-img" style="background-image: url('<?= $prog['img'] ?>');"></div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($prog['title']) ?></h3>
                            <p><strong>Возраст:</strong> <?= htmlspecialchars($prog['age']) ?></p>
                            <p><?= htmlspecialchars($prog['desc']) ?></p>
                            <a href="#enroll" class="btn">Записаться</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="enroll">
            <!-- Форма записи с улучшенной валидацией телефона -->
            <div class="form-wrapper">
                <div class="form-text">
                    <h2>Запись на кружок</h2>
                    <p>Оставьте заявку — мы свяжемся с вами</p>
                </div>
                <div class="form-container">
                    <?php 
                    // Обработка формы записи
                    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['child_name'])): 
                        $phone = $_POST['phone'] ?? '';
                        
                        // Валидация телефона с помощью нашей функции
                        if (!validatePhone($phone)) {
                            echo '<div class="error-message">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <strong>Ошибка в номере телефона!</strong><br>
                                        Допустимые символы: цифры 0-9, плюс (+), дефис (-), скобки (), точка (.)<br>
                                        Номер должен содержать минимум 10 цифр
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="success-message">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <strong>Заявка принята!</strong><br>
                                        Ждём '.htmlspecialchars($_POST['child_name']).' на занятия.<br>
                                        Мы свяжемся с вами по номеру '.htmlspecialchars($phone).'
                                    </div>
                                  </div>';
                        }
                    endif; 
                    ?>
                    
                    <form method="POST" action="#enroll">
                        <div class="form-group">
                            <label>ФИО ребёнка</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-child"></i>
                                <input type="text" name="child_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Возраст</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-birthday-cake"></i>
                                <input type="number" name="age" min="5" max="18" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Кружок</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-shapes"></i>
                                <select name="program" required>
                                    <option value="">— Выберите —</option>
                                    <?php foreach ($programs as $prog): ?>
                                        <option><?= htmlspecialchars($prog['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ФИО родителя</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-user"></i>
                                <input type="text" name="parent_name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Телефон</label>
                            <div class="input-icon-wrap">
                                <i class="fas fa-mobile-alt"></i>
                                <input type="tel" name="phone" 
                                       pattern="^[0-9+\-\(\)\.\s]+$"
                                       title="Допустимые символы: цифры 0-9, +, -, (), . и пробелы. Минимум 10 цифр."
                                       required>
                            </div>
                            <small style="display: block; margin-top: 5px; color: #666;">
                                Примеры: +7 (999) 123-45-67, 8-999-123-45-67, 89991234567
                            </small>
                        </div>
                        <button type="submit" class="btn btn-primary">Отправить заявку</button>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($role == 'teacher'): ?>
        <!-- Педагог (без изменений) -->
        <section>
            <div class="section-header"><h2>Мои группы</h2></div>
            <div class="group-card">
                <h3>Художественная студия (младшая)</h3>
                <p><strong>Расписание:</strong> Пн, Ср 15:00–16:30</p>
                <table class="attendance-table">
                    <thead><tr><th>Ребёнок</th><th>Присутствует</th></tr></thead>
                    <tbody>
                        <tr><td>Иванов Александр</td><td><input type="checkbox" checked></td></tr>
                        <tr><td>Петрова Мария</td><td><input type="checkbox"></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($role == 'admin'): ?>
        <section>
            <div class="section-header">
                <h2>Управление кружками</h2>
            </div>
            <div class="stats-grid">
                <div class="stat-card"><h3><?= count($programs) ?></h3><p>Кружков</p></div>
                <div class="stat-card"><h3>124</h3><p>Детей</p></div>
                <div class="stat-card"><h3>8</h3><p>Педагогов</p></div>
            </div>

            <div class="admin-controls">
                <button id="openAddModal" class="btn btn-primary">Добавить новый кружок</button>
            </div>

            <div class="cards">
                <?php foreach ($programs as $i => $prog): ?>
                    <div class="card">
                        <div class="card-img" style="background-image: url('<?= htmlspecialchars($prog['img']) ?>');"></div>
                        <div class="card-body">
                            <h3><?= htmlspecialchars($prog['title']) ?></h3>
                            <p><strong>Возраст:</strong> <?= htmlspecialchars($prog['age']) ?></p>
                            <p><?= htmlspecialchars($prog['desc']) ?></p>
                            <div style="margin-top:auto; display:flex; gap:10px;">
                                <a href="?edit=<?= $i ?>" class="btn btn-small btn-edit">Редактировать</a>
                                <a href="?delete=<?= $i ?>" onclick="return confirm('Удалить кружок «<?= htmlspecialchars($prog['title']) ?>»?')" class="btn btn-small btn-delete">Удалить</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<!-- Модальное окно авторизации -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-modal" id="closeLogin">×</span>
            <h2>Вход для сотрудников</h2>
        </div>
        <div class="modal-body">
            <?php if (isset($login_error)): ?>
                <div style="background:#f8d7da;color:#721c24;padding:1rem;border-radius:12px;margin-bottom:1rem;text-align:center;">
                    <?= $login_error ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group"><label>Логин</label><div class="input-icon-wrap"><i class="fas fa-user"></i><input type="text" name="login" required></div></div>
                <div class="form-group"><label>Пароль</label><div class="input-icon-wrap"><i class="fas fa-lock"></i><input type="password" name="password" required></div></div>
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>
            <div style="margin-top:1rem; font-size:0.9rem; color:#636e72; text-align:center;">
                teacher / teacher123<br>admin / admin123
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления/редактирования кружка -->
<div id="programModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close-modal" id="closeProgram">×</span>
            <h2><?= $edit_program ? 'Редактировать кружок' : 'Добавить новый кружок' ?></h2>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="edit_index" value="<?= $edit_index >= 0 ? $edit_index : '' ?>">
                <div class="form-group">
                    <label>Название кружка</label>
                    <input type="text" name="title" value="<?= $edit_program['title'] ?? '' ?>" required style="width:100%; padding:0.9rem; border-radius:12px; border:2px solid #e1e5ea;">
                </div>
                <div class="form-group">
                    <label>Возраст</label>
                    <input type="text" name="age" value="<?= $edit_program['age'] ?? '' ?>" required style="width:100%; padding:0.9rem; border-radius:12px; border:2px solid #e1e5ea;">
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="desc" rows="4" required style="width:100%; padding:0.9rem; border-radius:12px; border:2px solid #e1e5ea;"><?= $edit_program['desc'] ?? '' ?></textarea>
                </div>
                <div class="form-group">
                    <label>Ссылка на фото (путь)</label>
                    <input type="text" name="img" value="<?= $edit_program['img'] ?? 'photos/new.jpg' ?>" required style="width:100%; padding:0.9rem; border-radius:12px; border:2px solid #e1e5ea;">
                </div>
                <button type="submit" name="save_program" class="btn btn-primary" style="width:100%;">Сохранить</button>
            </form>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2025 Дом детского творчества "Радуга"</p>
</footer>

<script>
    // Элементы модалок
    const loginModal = document.getElementById('loginModal');
    const programModal = document.getElementById('programModal');
    const openLogin = document.getElementById('openLogin');
    const openAdd = document.getElementById('openAddModal');
    const closeLogin = document.getElementById('closeLogin');
    const closeProgram = document.getElementById('closeProgram');

    // Открытие модалки логина
    if (openLogin) {
        openLogin.onclick = () => loginModal.style.display = 'flex';
    }
    if (closeLogin) {
        closeLogin.onclick = () => loginModal.style.display = 'none';
    }

    // Открытие модалки добавления
    if (openAdd) {
        openAdd.onclick = () => {
            // Очищаем форму от предыдущего редактирования
            document.querySelector('#programModal form').reset();
            document.querySelector('input[name="edit_index"]').value = '';
            document.querySelector('#programModal h2').textContent = 'Добавить новый кружок';
            programModal.style.display = 'flex';
        };
    }

    // Закрытие модалки программы
    if (closeProgram) {
        closeProgram.onclick = () => {
            programModal.style.display = 'none';
            // Убираем параметр edit из URL без перезагрузки
            if (window.location.search.includes('edit=')) {
                history.replaceState(null, '', window.location.pathname);
            }
        };
    }

    // Автоматическое открытие модалки при редактировании
    <?php if ($edit_program): ?>
        programModal.style.display = 'flex';
        document.querySelector('#programModal h2').textContent = 'Редактировать кружок';
    <?php endif; ?>

    // Закрытие по клику вне модалки
    window.onclick = (e) => {
        if (e.target === loginModal) {
            loginModal.style.display = 'none';
        }
        if (e.target === programModal) {
            programModal.style.display = 'none';
            if (window.location.search.includes('edit=')) {
                history.replaceState(null, '', window.location.pathname);
            }
        }
    };
</script>

</body>
</html>
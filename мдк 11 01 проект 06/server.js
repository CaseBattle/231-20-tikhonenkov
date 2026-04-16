const express = require('express');
const path = require('path');
const sqlite3 = require('sqlite3').verbose();
const session = require('express-session');

const app = express();
const PORT = process.env.PORT || 3000;

const db = new sqlite3.Database(path.join(__dirname, 'rent.db'), (err) => {
  if (err) {
    console.error('Ошибка подключения к БД:', err.message);
  } else {
    console.log('Подключение к SQLite успешно');
  }
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(
  session({
    secret: 'green-service-secret-key',
    resave: false,
    saveUninitialized: false,
    cookie: {
      maxAge: 1000 * 60 * 60 * 4
    }
  })
);

app.use(express.static(path.join(__dirname, 'public')));

// Проверка доступа администратора
function requireAdmin(req, res, next) {
  if (req.session && req.session.isAdmin) {
    return next();
  }
  return res.status(401).json({ error: 'Нет доступа' });
}

// Главная страница
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Страница входа
app.get('/login.html', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

// Страница админки
app.get('/admin.html', (req, res) => {
  if (!req.session || !req.session.isAdmin) {
    return res.redirect('/login.html');
  }
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

// Проверка сервера
app.get('/api/ping', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString()
  });
});

// Проверка текущей сессии
app.get('/me', (req, res) => {
  res.json({
    isAdmin: !!(req.session && req.session.isAdmin),
    adminName: req.session?.adminName || null
  });
});

// Вход в систему
app.post('/login', (req, res) => {
  const { email, password } = req.body;

  // Учебная учетная запись администратора
  if (email === 'admin@mail.com' && password === 'admin123') {
    req.session.isAdmin = true;
    req.session.adminName = 'Администратор';

    return res.json({
      success: true,
      redirect: '/admin.html'
    });
  }

  // Если захочешь потом подключить обычных пользователей из БД,
  // можно расширить эту часть
  return res.status(401).json({
    success: false,
    message: 'Неверный логин или пароль'
  });
});

// Выход
app.post('/logout', (req, res) => {
  req.session.destroy(() => {
    res.json({ success: true });
  });
});

// Получение объявлений
app.get('/properties', (req, res) => {
  db.all(
    `SELECT * FROM Property WHERE available = 1 ORDER BY id DESC`,
    [],
    (err, rows) => {
      if (err) {
        console.error('Ошибка при получении объектов:', err.message);
        return res.status(500).json({ error: 'Ошибка базы данных' });
      }
      res.json(rows);
    }
  );
});

// Отправка заявки
app.post('/request', (req, res) => {
  const { name, phone, email, comment } = req.body;

  console.log('Новая заявка:', { name, phone, email, comment });

  res.json({
    success: true,
    message: 'Заявка успешно отправлена'
  });
});

// ---------------- АДМИН-ПАНЕЛЬ ----------------

// Получение всех объявлений для админки
app.get('/admin/properties', requireAdmin, (req, res) => {
  db.all(`SELECT * FROM Property ORDER BY id DESC`, [], (err, rows) => {
    if (err) {
      console.error('Ошибка списка объявлений:', err.message);
      return res.status(500).json({ error: 'Ошибка базы данных' });
    }

    res.json(rows);
  });
});

// Добавление объявления
app.post('/admin/properties', requireAdmin, (req, res) => {
  const { title, address, price, available } = req.body;

  if (!title || !address || !price) {
    return res.status(400).json({
      success: false,
      message: 'Заполните все обязательные поля'
    });
  }

  db.run(
    `INSERT INTO Property (landlord_id, title, address, price, available)
     VALUES (?, ?, ?, ?, ?)`,
    [1, title, address, Number(price), available ? 1 : 1],
    function (err) {
      if (err) {
        console.error('Ошибка добавления объявления:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка добавления'
        });
      }

      res.json({
        success: true,
        id: this.lastID
      });
    }
  );
});

// Удаление объявления
app.delete('/admin/properties/:id', requireAdmin, (req, res) => {
  const { id } = req.params;

  db.run(`DELETE FROM Property WHERE id = ?`, [id], function (err) {
    if (err) {
      console.error('Ошибка удаления объявления:', err.message);
      return res.status(500).json({
        success: false,
        message: 'Ошибка удаления'
      });
    }

    res.json({
      success: true,
      deleted: this.changes
    });
  });
});

app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
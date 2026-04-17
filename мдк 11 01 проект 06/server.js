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

db.serialize(() => {
  db.run(`ALTER TABLE User ADD COLUMN password TEXT`, (err) => {
    if (err && !err.message.toLowerCase().includes('duplicate column')) {
      console.error('Ошибка добавления password:', err.message);
    }
  });

  db.run(`ALTER TABLE User ADD COLUMN role TEXT`, (err) => {
    if (err && !err.message.toLowerCase().includes('duplicate column')) {
      console.error('Ошибка добавления role:', err.message);
    }
  });

  db.run(`
    CREATE TABLE IF NOT EXISTS Request (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      property_id INTEGER,
      user_id INTEGER,
      user_name TEXT NOT NULL,
      phone TEXT NOT NULL,
      email TEXT NOT NULL,
      comment TEXT,
      status TEXT NOT NULL DEFAULT 'pending',
      created_at TEXT NOT NULL
    )
  `);

  db.run(`ALTER TABLE Request ADD COLUMN user_id INTEGER`, (err) => {
    if (err && !err.message.toLowerCase().includes('duplicate column')) {
      console.error('Ошибка добавления user_id:', err.message);
    }
  });

  db.run(`ALTER TABLE Request ADD COLUMN status TEXT NOT NULL DEFAULT 'pending'`, (err) => {
    if (err && !err.message.toLowerCase().includes('duplicate column')) {
      console.error('Ошибка добавления status:', err.message);
    }
  });

  db.run(`
    CREATE TABLE IF NOT EXISTS Feedback (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL,
      message TEXT NOT NULL,
      created_at TEXT NOT NULL
    )
  `);
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

function requireAdmin(req, res, next) {
  if (req.session && req.session.isAdmin) {
    return next();
  }
  return res.status(401).json({ error: 'Нет доступа' });
}

function requireAuth(req, res, next) {
  if (req.session && req.session.userId) {
    return next();
  }
  return res.status(401).json({
    success: false,
    message: 'Для выполнения действия необходимо войти в систему'
  });
}

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/login.html', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'login.html'));
});

app.get('/register.html', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'register.html'));
});

app.get('/admin.html', (req, res) => {
  if (!req.session || !req.session.isAdmin) {
    return res.redirect('/login.html');
  }
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

app.get('/my-requests.html', (req, res) => {
  if (!req.session || !req.session.userId || req.session.isAdmin) {
    return res.redirect('/login.html');
  }
  res.sendFile(path.join(__dirname, 'public', 'my-requests.html'));
});

app.get('/api/ping', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString()
  });
});

app.get('/me', (req, res) => {
  res.json({
    isAdmin: !!(req.session && req.session.isAdmin),
    userId: req.session?.userId || null,
    userName: req.session?.userName || null,
    userEmail: req.session?.userEmail || null
  });
});

// Регистрация
app.post('/register', (req, res) => {
  const { name, email, phone, password } = req.body;

  if (!name || !email || !phone || !password) {
    return res.status(400).json({
      success: false,
      message: 'Заполните все поля'
    });
  }

  db.get('SELECT id FROM User WHERE email = ?', [email], (err, existingUser) => {
    if (err) {
      console.error('Ошибка проверки пользователя:', err.message);
      return res.status(500).json({
        success: false,
        message: 'Ошибка базы данных'
      });
    }

    if (existingUser) {
      return res.status(400).json({
        success: false,
        message: 'Пользователь с таким email уже существует'
      });
    }

    db.run(
      `INSERT INTO User (email, name, phone, type, password, role)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [email, name, phone, 'client', password, 'user'],
      function (insertErr) {
        if (insertErr) {
          console.error('Ошибка регистрации:', insertErr.message);
          return res.status(500).json({
            success: false,
            message: 'Не удалось зарегистрировать пользователя'
          });
        }

        return res.json({
          success: true,
          message: 'Регистрация выполнена',
          redirect: '/login.html'
        });
      }
    );
  });
});

// Вход
app.post('/login', (req, res) => {
  const { email, password } = req.body;

  if (email === 'admin@mail.com' && password === 'admin123') {
    req.session.isAdmin = true;
    req.session.userId = -1;
    req.session.userName = 'Администратор';
    req.session.userEmail = 'admin@mail.com';

    return res.json({
      success: true,
      redirect: '/admin.html'
    });
  }

  db.get('SELECT * FROM User WHERE email = ?', [email], (err, user) => {
    if (err) {
      console.error('Ошибка входа:', err.message);
      return res.status(500).json({
        success: false,
        message: 'Ошибка базы данных'
      });
    }

    if (!user) {
      return res.status(401).json({
        success: false,
        message: 'Пользователь не найден'
      });
    }

    if (user.password !== password) {
      return res.status(401).json({
        success: false,
        message: 'Неверный логин или пароль'
      });
    }

    req.session.isAdmin = user.role === 'admin';
    req.session.userId = user.id;
    req.session.userName = user.name;
    req.session.userEmail = user.email;

    return res.json({
      success: true,
      redirect: user.role === 'admin' ? '/admin.html' : '/'
    });
  });
});

app.post('/logout', (req, res) => {
  req.session.destroy(() => {
    res.json({ success: true });
  });
});

// Объявления
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

// Заявка на объект
app.post('/request', requireAuth, (req, res) => {
  const { phone, comment, propertyId } = req.body;

  if (!phone || !propertyId) {
    return res.status(400).json({
      success: false,
      message: 'Заполните обязательные поля заявки'
    });
  }

  const name = req.session.userName || 'Пользователь';
  const email = req.session.userEmail || '';

  db.run(
    `INSERT INTO Request (property_id, user_id, user_name, phone, email, comment, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      propertyId,
      req.session.userId,
      name,
      phone,
      email,
      comment || '',
      'pending',
      new Date().toISOString()
    ],
    function (err) {
      if (err) {
        console.error('Ошибка сохранения заявки:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка сохранения заявки'
        });
      }

      res.json({
        success: true,
        message: 'Заявка успешно отправлена',
        id: this.lastID
      });
    }
  );
});

// Мои заявки
app.get('/my/requests', requireAuth, (req, res) => {
  db.all(
    `
    SELECT 
      Request.id,
      Request.property_id,
      Request.user_name,
      Request.phone,
      Request.email,
      Request.comment,
      Request.status,
      Request.created_at,
      Property.title AS property_title,
      Property.address AS property_address,
      Property.price AS property_price
    FROM Request
    LEFT JOIN Property ON Request.property_id = Property.id
    WHERE Request.user_id = ?
    ORDER BY Request.id DESC
    `,
    [req.session.userId],
    (err, rows) => {
      if (err) {
        console.error('Ошибка загрузки заявок пользователя:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка базы данных'
        });
      }

      res.json(rows);
    }
  );
});

// Обратная связь
app.post('/feedback', (req, res) => {
  const { name, email, message } = req.body;

  if (!name || !email || !message) {
    return res.status(400).json({
      success: false,
      message: 'Заполните все поля формы'
    });
  }

  db.run(
    `INSERT INTO Feedback (name, email, message, created_at)
     VALUES (?, ?, ?, ?)`,
    [name, email, message, new Date().toISOString()],
    function (err) {
      if (err) {
        console.error('Ошибка сохранения обратной связи:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка сохранения сообщения'
        });
      }

      res.json({
        success: true,
        message: 'Сообщение успешно отправлено',
        id: this.lastID
      });
    }
  );
});

// Админка: объявления
app.get('/admin/properties', requireAdmin, (req, res) => {
  db.all(`SELECT * FROM Property ORDER BY id DESC`, [], (err, rows) => {
    if (err) {
      console.error('Ошибка списка объявлений:', err.message);
      return res.status(500).json({ error: 'Ошибка базы данных' });
    }
    res.json(rows);
  });
});

app.post('/admin/properties', requireAdmin, (req, res) => {
  const { title, address, price, available, type } = req.body;

  if (!title || !address || !price) {
    return res.status(400).json({
      success: false,
      message: 'Заполните все обязательные поля'
    });
  }

  db.run(
    `INSERT INTO Property (landlord_id, title, address, price, type, available)
     VALUES (?, ?, ?, ?, ?, ?)`,
    [1, title, address, Number(price), type || 'rent', available ? 1 : 1],
    function (err) {
      if (err) {
        db.run(
          `INSERT INTO Property (landlord_id, title, address, price, available)
           VALUES (?, ?, ?, ?, ?)`,
          [1, title, address, Number(price), available ? 1 : 1],
          function (fallbackErr) {
            if (fallbackErr) {
              console.error('Ошибка добавления объявления:', fallbackErr.message);
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
        return;
      }

      res.json({
        success: true,
        id: this.lastID
      });
    }
  );
});

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

// Админка: заявки
app.get('/admin/requests', requireAdmin, (req, res) => {
  db.all(
    `
    SELECT 
      Request.*,
      Property.title AS property_title,
      Property.address AS property_address
    FROM Request
    LEFT JOIN Property ON Request.property_id = Property.id
    ORDER BY Request.id DESC
    `,
    [],
    (err, rows) => {
      if (err) {
        console.error('Ошибка списка заявок:', err.message);
        return res.status(500).json({ error: 'Ошибка базы данных' });
      }
      res.json(rows);
    }
  );
});

app.patch('/admin/requests/:id/approve', requireAdmin, (req, res) => {
  const { id } = req.params;

  db.run(
    `UPDATE Request SET status = 'approved' WHERE id = ?`,
    [id],
    function (err) {
      if (err) {
        console.error('Ошибка одобрения заявки:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка одобрения заявки'
        });
      }

      res.json({
        success: true,
        updated: this.changes
      });
    }
  );
});

app.patch('/admin/requests/:id/delete', requireAdmin, (req, res) => {
  const { id } = req.params;

  db.run(
    `UPDATE Request SET status = 'deleted' WHERE id = ?`,
    [id],
    function (err) {
      if (err) {
        console.error('Ошибка обновления статуса заявки:', err.message);
        return res.status(500).json({
          success: false,
          message: 'Ошибка обновления статуса заявки'
        });
      }

      res.json({
        success: true,
        updated: this.changes
      });
    }
  );
});

// Админка: обратная связь
app.get('/admin/feedback', requireAdmin, (req, res) => {
  db.all(`SELECT * FROM Feedback ORDER BY id DESC`, [], (err, rows) => {
    if (err) {
      console.error('Ошибка списка сообщений:', err.message);
      return res.status(500).json({ error: 'Ошибка базы данных' });
    }
    res.json(rows);
  });
});

app.delete('/admin/feedback/:id', requireAdmin, (req, res) => {
  const { id } = req.params;

  db.run(`DELETE FROM Feedback WHERE id = ?`, [id], function (err) {
    if (err) {
      console.error('Ошибка удаления сообщения:', err.message);
      return res.status(500).json({
        success: false,
        message: 'Ошибка удаления сообщения'
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
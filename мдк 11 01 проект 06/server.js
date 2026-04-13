const express = require('express');
const path = require('path');
const sqlite3 = require('sqlite3').verbose();

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
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

app.get('/api/ping', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
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

app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});
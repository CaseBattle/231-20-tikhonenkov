const express = require('express');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Статические файлы из папки public
app.use(express.static(path.join(__dirname, 'public')));

// Маршрут для главной страницы
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Пример простого API-эндпоинта (можно расширить при необходимости)
app.get('/api/ping', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.listen(PORT, () => {
  console.log(`Server is running on http://localhost:${PORT}`);
});






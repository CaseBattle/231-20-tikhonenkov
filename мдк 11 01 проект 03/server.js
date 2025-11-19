const { spawn } = require('child_process');
const path = require('path');

const publicDir = path.join(__dirname, 'public');
const phpExecutable = process.env.PHP_PATH || 'php';
const host = process.env.HOST || '127.0.0.1';
const port = process.env.PORT || '8080';

const server = spawn(phpExecutable, ['-S', `${host}:${port}`, '-t', publicDir], {
    stdio: 'inherit',
});

console.log(`PHP сервер запущен на http://${host}:${port} (директория: ${publicDir})`);

const shutdown = () => {
    server.kill('SIGINT');
};

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
server.on('close', (code) => process.exit(code ?? 0));


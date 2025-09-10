import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import { createSubscriber } from './subscriber.js';

const app = express();
const httpServer = createServer(app);
const io = new Server(httpServer, { cors: { origin: '*' } });

const PORT = process.env.SOCKET_PORT || 4000;
const CHANNEL = process.env.EVENT_CHANNEL || 'skylink:events';

app.get('/health', (_, res) => res.json({ ok: true }));

io.on('connection', (socket) => {
  console.log('WS client connected', socket.id);
});

// Redis Subscriber → broadcast a todos los clientes
const sub = createSubscriber({
  host: process.env.REDIS_HOST || '127.0.0.1',
  port: Number(process.env.REDIS_PORT || 6379),
});

console.log('Suscribiendo a Redis en canal:', CHANNEL);
sub.on('connect', () => console.log('Redis conectado (Node)'));
sub.on('error', (err) => console.error('Redis error:', err));

await sub.subscribe(CHANNEL, (message) => {
  console.log('Mensaje recibido de Redis:', message);
  try {
    if (!message) throw new Error('Empty message');
    const payload = JSON.parse(message);
    if (!payload || typeof payload.event !== 'string') throw new Error('Missing event');
    io.emit(payload.event, payload.data);
    console.log('Broadcasted:', payload.event);
  } catch (e) {
    console.error('Invalid message', message, e);
  }
});

httpServer.listen(PORT, () => console.log(`Realtime on :${PORT}`));

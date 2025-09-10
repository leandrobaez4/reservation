import { io } from 'socket.io-client';

const url = process.env.SOCKET_URL || 'ws://localhost:4000';
const socket = io(url, { transports: ['websocket'] });

['reservation.created','reservation.updated'].forEach(evt => {
  socket.on(evt, (data) => {
    console.log(JSON.stringify({ event: evt, data }, null, 2));
  });
});

console.log('Dashboard conectado a', url);

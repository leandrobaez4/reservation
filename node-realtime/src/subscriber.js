import Redis from 'ioredis';

export function createSubscriber({ host, port }) {
  const sub = new Redis({ host, port, maxRetriesPerRequest: null });
  return sub;
}

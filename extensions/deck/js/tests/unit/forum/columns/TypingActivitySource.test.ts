import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import TypingActivitySource from '../../../../src/forum/columns/TypingActivitySource';
import { boot } from '../helpers';

const CHANNEL = 'private-typing-activity';
const EVENT = 'typing-activity';
const MINUTE = 60 * 1000;

beforeAll(() => boot());

beforeEach(() => jest.useFakeTimers());

afterEach(() => {
  delete (app as any).websocket;
  jest.useRealTimers();
});

function fakeSocket() {
  const channel = { bind: jest.fn(), unbind: jest.fn() };
  const socket = { subscribe: jest.fn(() => channel), unsubscribe: jest.fn() };

  return { socket, channel };
}

/** Starts a source on a fake socket; returns what arrives on its channel. */
function started() {
  const { socket, channel } = fakeSocket();
  (app as any).websocket = socket;

  const source = new TypingActivitySource();
  source.start();

  const send = channel.bind.mock.calls[0][1] as (data: any) => void;
  const entries = () => (source as any).entries.map((entry: any) => entry.key);

  return { source, socket, channel, send, entries };
}

const typing = (userId: number | null, discussionId: number | null = null) => ({ userId, displayName: `User ${userId}`, discussionId, tagIds: null });

describe('subscribing', () => {
  it('subscribes to the typing-activity channel', () => {
    const { socket, channel } = started();

    expect(socket.subscribe).toHaveBeenCalledWith(CHANNEL);
    expect(channel.bind).toHaveBeenCalledWith(EVENT, expect.any(Function));
  });

  // pusher-js's unbind(event) without the handler removes every listener for
  // that event, including other code's.
  it('unbinds exactly the handler it bound', () => {
    const { source, socket, channel } = started();

    source.stop();

    expect(channel.unbind).toHaveBeenCalledWith(EVENT, channel.bind.mock.calls[0][1]);
    expect(socket.unsubscribe).toHaveBeenCalledWith(CHANNEL);
  });

  it('does nothing when started again on the same socket', () => {
    const { source, socket } = started();

    source.start();

    expect(socket.subscribe).toHaveBeenCalledTimes(1);
  });

  it('moves to a new socket after realtime reconnects', () => {
    const { source, channel } = started();
    const next = fakeSocket();
    (app as any).websocket = next.socket;

    source.start();

    expect(channel.unbind).toHaveBeenCalled();
    expect(next.socket.subscribe).toHaveBeenCalledWith(CHANNEL);
  });

  it('does nothing without realtime', () => {
    const source = new TypingActivitySource();

    expect(() => source.start()).not.toThrow();
    expect((source as any).socket).toBeNull();
  });
});

describe('activity', () => {
  it('lists the latest first, one entry per member and place', () => {
    const { send, entries } = started();

    send(typing(2));
    send(typing(3));
    send(typing(2));

    expect(entries()).toEqual(['2:new', '3:new']);
  });

  it('keeps a bounded history', () => {
    const { send, entries } = started();

    for (let user = 1; user <= 40; user++) send(typing(user));

    expect(entries()).toHaveLength(30);
    expect(entries()[0]).toBe('40:new');
  });

  it('drops typing older than 15 minutes, even while nothing else arrives', () => {
    const { send, entries } = started();

    send(typing(2));
    jest.advanceTimersByTime(10 * MINUTE);
    send(typing(3));

    jest.advanceTimersByTime(6 * MINUTE);

    expect(entries()).toEqual(['3:new']);
  });

  it('stops pruning once stopped', () => {
    const { source, send, entries } = started();

    send(typing(2));
    source.stop();
    jest.advanceTimersByTime(20 * MINUTE);

    expect(entries()).toEqual(['2:new']);
  });
});

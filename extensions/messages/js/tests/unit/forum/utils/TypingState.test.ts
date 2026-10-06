import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import type User from 'flarum/common/models/User';
import TypingState, { TYPING_TIMEOUT_MS, announces } from '../../../../src/forum/utils/TypingState';

const ME = '1';
const THEM = '2';
const STRANGER = '3';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  app.store.pushPayload({
    data: [
      { type: 'users', id: ME, attributes: { displayName: 'Me' } },
      // Hides their online status: in a one-to-one conversation that must not hide their typing.
      { type: 'users', id: THEM, attributes: { displayName: 'Them', preferences: { discloseOnline: false } } },
      { type: 'users', id: STRANGER, attributes: { displayName: 'Stranger' } },
    ],
  } as any);
});

const user = (id: string) => app.store.getById<User>('users', id)!;

function state() {
  return new TypingState(
    () => [user(ME), user(THEM)],
    () => ME
  );
}

describe('TypingState', () => {
  it('shows another participant typing, whatever their online-status preference', () => {
    const typing = state();

    expect(typing.received(TypingState.signal(THEM), 1000)).toBe(true);
    expect(typing.users(1000).map((u) => u.displayName())).toEqual(['Them']);
  });

  it('ignores its own signals, people outside the conversation, and junk', () => {
    const typing = state();

    expect(typing.received(TypingState.signal(ME), 1000)).toBe(false);
    expect(typing.received(TypingState.signal(STRANGER), 1000)).toBe(false);
    expect(typing.received({ displayName: 'Admin' }, 1000)).toBe(false);
    expect(typing.received(null, 1000)).toBe(false);
    expect(typing.users(1000)).toEqual([]);
  });

  it('forgets someone it has not heard from for a while, on its own clock', () => {
    const typing = state();

    typing.received(TypingState.signal(THEM), 1000);

    expect(typing.users(1000 + TYPING_TIMEOUT_MS - 1)).toHaveLength(1);
    expect(typing.msUntilNextExpiry(1000)).toBe(TYPING_TIMEOUT_MS);
    expect(typing.users(1000 + TYPING_TIMEOUT_MS)).toEqual([]);
    expect(typing.msUntilNextExpiry(1000 + TYPING_TIMEOUT_MS)).toBeNull();
  });

  it('keeps someone who keeps typing', () => {
    const typing = state();

    typing.received(TypingState.signal(THEM), 1000);
    typing.received(TypingState.signal(THEM), 3000);

    expect(typing.users(3000 + TYPING_TIMEOUT_MS - 1)).toHaveLength(1);
  });
});

describe('announces', () => {
  it('announces typing when the draft changes and has something in it', () => {
    expect(announces('', 'H')).toBe(true);
    expect(announces('H', 'He')).toBe(true);
  });

  it('stays quiet for an unchanged draft, or one just cleared by sending', () => {
    expect(announces('He', 'He')).toBe(false);
    expect(announces('He', '')).toBe(false);
    expect(announces('', '')).toBe(false);
  });
});

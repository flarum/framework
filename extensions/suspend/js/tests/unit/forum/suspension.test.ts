import { jest } from '@jest/globals';
import dayjs from 'dayjs';
import m from 'mithril';
import mq from 'mithril-query';
import app from 'flarum/forum/app';
import Notification from 'flarum/common/models/Notification';
import SuspensionInfoModal from '../../../src/forum/components/SuspensionInfoModal';
import UserSuspendedNotification from '../../../src/forum/components/UserSuspendedNotification';
import SuspendUserModal from '../../../src/forum/components/SuspendUserModal';
import { getPermanentSuspensionDate, isPermanentSuspensionDate } from '../../../src/forum/helpers/suspensionHelper';

beforeEach(() => {
  globalThis.dayjs = dayjs;
  jest.useFakeTimers().setSystemTime(new Date('2026-11-08T12:00:00Z'));
});

afterEach(() => jest.useRealTimers());

it('keeps the existing permanent suspension date', () => {
  expect(getPermanentSuspensionDate().toISOString()).toBe('2038-01-01T00:00:00.000Z');
});

it.each([
  ['2038-01-01T00:00:00Z', true],
  ['2037-12-31T19:00:00-05:00', true],
  ['9999-01-01T00:00:00Z', true],
  ['9999-11-08T12:00:00Z', true],
  ['2038-01-01T00:00:01Z', false],
  ['2038-06-01T00:00:00Z', false],
  ['2026-11-15T12:00:00Z', false],
  ['invalid', false],
])('recognises %s as permanent: %s', (date, expected) => {
  expect(isPermanentSuspensionDate(new Date(date))).toBe(expected);
});

it.each([
  ['2038-01-01T00:00:00Z', 'indefinitely'],
  ['9999-11-08T12:00:00Z', 'indefinitely'],
  ['2026-11-15T12:00:00Z', 'limited'],
  ['2026-11-01T12:00:00Z', null],
  [null, null],
])('opens a suspension ending %s with status %s', (date, expected) => {
  const modal = new SuspendUserModal();
  const attrs = {
    user: {
      suspendedUntil: () => (date ? new Date(date) : null),
      suspendReason: () => 'Moderator reason',
      suspendMessage: () => 'Member message',
    },
  };

  modal.oninit({ attrs });

  expect(modal.status()).toBe(expected);
  expect(modal.reason()).toBe('Moderator reason');
  expect(modal.message()).toBe('Member message');
});

it.each([
  ['2038-01-01T00:00:00Z', 'indefinitely', '2038-01-01T00:00:00.000Z'],
  ['9999-11-08T12:00:00Z', 'indefinitely', '2038-01-01T00:00:00.000Z'],
  ['2038-01-01T00:00:00Z', null, null],
  ['2026-11-15T12:00:00Z', 'limited', '2026-11-15T12:00:00.000Z'],
])('saves %s with status %s as %s', async (date, status, expected) => {
  const save = jest
    .fn<(attributes: { suspendedUntil: Date | null; suspendReason: string; suspendMessage: string }) => Promise<void>>()
    .mockResolvedValue();
  const modal = new SuspendUserModal();
  modal.oninit({
    attrs: {
      user: {
        suspendedUntil: () => new Date(date),
        suspendReason: () => 'Updated reason',
        suspendMessage: () => 'Updated message',
        save,
      },
    },
  });
  modal.hide = jest.fn();
  modal.status(status);
  if (status === 'limited') modal.daysRemaining(7);

  modal.onsubmit({ preventDefault() {} });
  await Promise.resolve();

  const attributes = save.mock.calls[0][0];
  expect(attributes.suspendedUntil?.toISOString() ?? null).toBe(expected);
  expect(attributes.suspendReason).toBe('Updated reason');
  expect(attributes.suspendMessage).toBe('Updated message');
  expect(modal.hide).toHaveBeenCalledTimes(1);
});

it.each(['2038-01-01T00:00:00Z', '9999-11-08T12:00:00Z'])('renders indefinite wording for a suspension ending %s', (date) => {
  globalThis.m = m;
  const translate = jest.spyOn(app.translator, 'trans').mockImplementation(<T>(key: string): T => key as T);
  try {
    const info = new SuspensionInfoModal();
    info.oninit({ attrs: { message: 'Member message', until: new Date(date) } });
    const output = mq(info.content());
    expect(output).toContainRaw('Member message');
    expect(output).toContainRaw('flarum-suspend.forum.suspension_info.indefinite');
    expect(output).not.toContainRaw('flarum-suspend.forum.suspension_info.limited');

    const notification = new UserSuspendedNotification();
    notification.attrs = {
      notification: new Notification({ type: 'notifications', id: '1', attributes: { content: date, createdAt: '2026-11-08T12:00:00Z' } }, app.store),
    };
    expect(notification.content()).toBe('flarum-suspend.forum.notifications.user_suspended_indefinite_text');
  } finally {
    translate.mockRestore();
  }
});

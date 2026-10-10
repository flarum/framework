import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import app from 'flarum/forum/app';
import ModalManagerState from 'flarum/common/states/ModalManagerState';
import FlagUserModal from '../src/forum/components/FlagUserModal';
import UserFlagsModal from '../src/forum/components/UserFlagsModal';
import { bootFlags, resources, flag, settle } from './fixtures';

beforeEach(() => {
  delete (FlagUserModal as any).$$reentrantLock$$;
  delete (UserFlagsModal as any).$$reentrantLock$$;
  bootFlags();
});
afterEach(() => jest.restoreAllMocks());

function attrs(user: any) {
  return {
    user,
    state: new ModalManagerState(),
    // Mount the real modal without running its unrelated focus/animation
    // callbacks, which depend on browser layout through jQuery.
    animateShow: () => {},
    animateHide: () => {},
  };
}

function text(element: Element) {
  return (element.textContent || '').replace(/\s+/g, ' ').trim();
}

async function review(targetUser: any, reports: any[]) {
  targetUser.pushData({ relationships: { flags: reports } });
  const find = jest.spyOn(app.store, 'find').mockResolvedValue(targetUser);
  jest.spyOn(m, 'redraw').mockImplementation(() => {});

  const modal = mq(UserFlagsModal, attrs(targetUser));
  await settle();
  modal.redraw();

  expect(find).toHaveBeenCalledWith('users', targetUser.id(), { include: 'flags,flags.user' });
  return modal;
}

describe('account report translations with real Flarum user models', () => {
  it('renders the reported account name in the report modal title', () => {
    const { targetUser } = resources();

    const modal = mq(FlagUserModal, attrs(targetUser));

    expect(text(modal.first('.Modal-header h3'))).toBe("Flag Reported account's account");
    expect(text(modal.first('.Modal-header .username'))).toBe('Reported account');
  });

  it('renders the reported account name in the moderation modal title after loading its reports', async () => {
    const { targetUser } = resources();

    const modal = await review(targetUser, []);

    expect(text(modal.first('.Modal-header h3'))).toBe("Flags on Reported account's account");
    expect(text(modal.first('.Modal-header .username'))).toBe('Reported account');
  });

  it('renders the reporter name and detail in the review row while the title names the reported account', async () => {
    const { targetUser } = resources();
    const report = flag('401', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } }, 'Unsolicited account messages');

    const modal = await review(targetUser, [report]);

    expect(text(modal.first('.Modal-body li p'))).toContain('Flagged by Reporter');
    expect(text(modal.first('.Modal-body li .username'))).toBe('Reporter');
    expect(text(modal.first('.Modal-header .username'))).toBe('Reported account');
    expect(text(modal.first('.Modal-body li'))).toContain('Unsolicited account messages');
    expect(text(modal.first('.Modal-body li strong'))).toBe('Spam');
  });

  it('renders the core deleted-user fallback when the reporting actor is unavailable', async () => {
    const { targetUser } = resources();
    const report = flag('402', {
      post: { data: null },
      targetUser: { data: { type: 'users', id: '5' } },
      user: { data: null },
    });

    const modal = await review(targetUser, [report]);

    expect(text(modal.first('.Modal-body li p'))).toContain('Flagged by [deleted]');
    expect(text(modal.first('.Modal-body li .username'))).toBe('[deleted]');
    expect(text(modal.first('.Modal-header .username'))).toBe('Reported account');
  });
});

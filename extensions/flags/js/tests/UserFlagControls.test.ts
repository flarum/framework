import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import UserControls from 'flarum/forum/utils/UserControls';
import addUserFlagControl from '../src/forum/addUserFlagControl';
import { bootFlags, resources, flag } from './fixtures';

const originalUserControls = UserControls.userControls;
const originalModerationControls = UserControls.moderationControls;

beforeAll(addUserFlagControl);
beforeEach(bootFlags);
afterEach(() => jest.restoreAllMocks());
afterAll(() => {
  UserControls.userControls = originalUserControls;
  UserControls.moderationControls = originalModerationControls;
});

describe('account report profile controls', () => {
  it('offers reporting only when the target has canFlagUser permission and passes that account to the modal', () => {
    const { targetUser } = resources();
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});
    const allowed = UserControls.userControls(targetUser, {});

    expect(allowed.has('flagUser')).toBe(true);
    allowed.get('flagUser').attrs.onclick();
    expect(show).toHaveBeenCalledWith(expect.any(Function), { user: targetUser });

    targetUser.pushAttributes({ canFlagUser: false });
    expect(UserControls.userControls(targetUser, {}).has('flagUser')).toBe(false);
  });

  it('does not expose the reporting button when canFlagUser is absent', () => {
    const { reporter } = resources();

    expect(UserControls.userControls(reporter, {}).has('flagUser')).toBe(false);
  });

  it('shows moderation review only with permission and reports about the target account', () => {
    const { targetUser } = resources();
    flag('201', { targetUser: { data: { type: 'users', id: '5' } } });
    targetUser.pushData({ relationships: { flags: { data: [{ type: 'flags', id: '201' }] } } });
    targetUser.pushAttributes({ canViewUserFlags: false });
    expect(UserControls.moderationControls(targetUser, {}).has('viewUserFlags')).toBe(false);

    targetUser.pushAttributes({ canViewUserFlags: true });
    const show = jest.spyOn(app.modal, 'show').mockImplementation(() => {});
    const allowed = UserControls.moderationControls(targetUser, {});
    expect(allowed.has('viewUserFlags')).toBe(true);
    allowed.get('viewUserFlags').attrs.onclick();
    expect(show).toHaveBeenCalledWith(expect.any(Function), { user: targetUser });

    targetUser.pushData({ relationships: { flags: { data: [] } } });
    expect(UserControls.moderationControls(targetUser, {}).has('viewUserFlags')).toBe(false);
  });
});

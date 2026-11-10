import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../../src/forum';
import SignUpModal from '../../../../src/forum/components/SignUpModal';
import { ColorScheme } from '../../../../src/common/components/ThemeMode';
import Stream from '../../../../src/common/utils/Stream';

beforeAll(() => bootstrapForum());

function submitData(): Record<string, unknown> {
  const modal = new SignUpModal();

  (modal as any).attrs = {};
  modal.username = Stream('ada');
  modal.email = Stream('ada@machine.local');
  modal.password = Stream('too-obscure');

  return modal.submitData();
}

describe('SignUpModal', () => {
  beforeAll(() => app.boot());

  beforeEach(() => {
    app.allowUserColorScheme = true;
    app.colorScheme = ColorScheme.Auto;
  });

  test('carries the colour scheme a guest chose into the new account', () => {
    app.colorScheme = ColorScheme.LightHighContrast;

    expect(submitData()).toEqual({
      username: 'ada',
      email: 'ada@machine.local',
      password: 'too-obscure',
      colorScheme: ColorScheme.LightHighContrast,
    });
  });

  test('sends no colour scheme when the guest follows the system', () => {
    expect(submitData()).not.toHaveProperty('colorScheme');
  });

  test('sends no colour scheme when the forum does not let users choose', () => {
    app.allowUserColorScheme = false;
    app.colorScheme = ColorScheme.Dark;

    expect(submitData()).not.toHaveProperty('colorScheme');
  });
});

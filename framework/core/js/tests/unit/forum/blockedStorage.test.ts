import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../src/forum/app';
import Pane from '../../../src/forum/utils/Pane';
import WelcomeHero from '../../../src/forum/components/WelcomeHero';
import Composer from '../../../src/forum/components/Composer';
import ThemeSwitcher from '../../../src/forum/components/ThemeSwitcher';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

// In a browser that blocks site data, touching `localStorage` or
// `sessionStorage` throws: Chrome throws a SecurityError, and Safari can have
// no `localStorage` at all. The forum must still work, without remembering
// what it would have stored.
describe('when the browser blocks storage', () => {
  beforeEach(() => {
    const denied = () => {
      throw new DOMException('Access is denied for this document.', 'SecurityError');
    };

    jest.spyOn(window, 'localStorage', 'get').mockImplementation(denied);
    jest.spyOn(window, 'sessionStorage', 'get').mockImplementation(denied);
  });

  afterEach(() => jest.restoreAllMocks());

  test('the forum mounts its discussion list pane, which can still be pinned', () => {
    const pane = new Pane(document.getElementById('app'));

    expect(pane.pinned).toBe(false);

    pane.togglePinned();

    expect(pane.pinned).toBe(true);
  });

  test('the welcome hero still shows, and can be hidden', () => {
    app.forum.pushAttributes({ welcomeTitle: 'Welcome' });

    expect(WelcomeHero.prototype.isHidden.call({})).toBe(false);
    expect(() => WelcomeHero.prototype.hide.call({})).not.toThrow();
  });

  test('the composer still gets a height, and can be resized', () => {
    const composer = { state: { height: null }, defaultHeight: () => 300, updateHeight() {} };

    Composer.prototype.initializeHeight.call(composer);

    expect(composer.state.height).toBe(300);

    Composer.prototype.changeHeight.call(composer, 400);

    expect(composer.state.height).toBe(400);
  });

  test('a guest can still have and choose a colour scheme', () => {
    // The test environment's matchMedia can't be listened to.
    jest.spyOn(app, 'watchSystemColorSchemePreference').mockImplementation(() => {});

    const user = app.session.user;
    app.session.user = null;

    try {
      expect(() => (app as any).initColorScheme()).not.toThrow();

      ThemeSwitcher.prototype.select.call({}, 'dark');

      expect(app.colorScheme).toBe('dark');
    } finally {
      app.session.user = user;
    }
  });
});

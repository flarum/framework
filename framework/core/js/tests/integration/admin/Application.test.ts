import { jest } from '@jest/globals';
import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import { app } from '../../../src/admin';

beforeAll(() => bootstrapAdmin());

describe('Application.initialize()', () => {
  test('error closure captures the throwing extension name, not the last initializer name', () => {
    // Register two initializers: the first throws, the second succeeds.
    app.initializers.add('flarum/bad-ext', () => {
      throw new Error('intentional failure');
    });
    app.initializers.add('flarum/good-ext', () => {});

    const errors: CallableFunction[] = (app as any).initialize();

    // One error should have been caught.
    expect(errors).toHaveLength(1);

    // The closure should reference 'bad-ext', not 'good-ext' (which ran last).
    // fireApplicationError calls console.group with the consoleTitle.
    const consoleSpy = jest.spyOn(console, 'group').mockImplementation(() => {});
    jest.spyOn(console, 'error').mockImplementation(() => {});
    jest.spyOn(console, 'groupEnd').mockImplementation(() => {});

    errors[0]();

    // console.group is called as: console.group('%c<title>', '<css>')
    // The first argument contains both the format specifier and the extension name.
    expect(consoleSpy).toHaveBeenCalledWith(
      expect.stringContaining('bad-ext'),
      expect.any(String)
    );
    expect(consoleSpy).not.toHaveBeenCalledWith(
      expect.stringContaining('good-ext'),
      expect.any(String)
    );

    consoleSpy.mockRestore();
  });
});

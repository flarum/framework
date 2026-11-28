import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import { app } from '../../../../src/forum';
import ComposerState from '../../../../src/forum/states/ComposerState';
import Component from '../../../../src/common/Component';

beforeAll(() => bootstrapForum());

describe('ComposerState leaving unsaved content', () => {
  let composer: ComposerState;
  let changed: boolean;

  beforeEach(() => {
    composer = new ComposerState();
    composer.position = ComposerState.Position.NORMAL;
    changed = true;
    composer.preventClosingWhen(() => changed, 'Discard your reply?');
  });

  afterEach(() => jest.restoreAllMocks());

  it('asks in a Flarum dialog, not the browser', async () => {
    const confirm = jest.spyOn(app, 'confirm').mockResolvedValue(false);
    const native = jest.spyOn(window, 'confirm');

    await composer.close();

    expect(confirm).toHaveBeenCalledWith(expect.objectContaining({ message: 'Discard your reply?', danger: true }));
    expect(native).not.toHaveBeenCalled();
  });

  it('stays open when the user keeps writing', async () => {
    jest.spyOn(app, 'confirm').mockResolvedValue(false);

    await composer.close();

    expect(composer.isVisible()).toBe(true);
  });

  it('closes when the user discards', async () => {
    jest.spyOn(app, 'confirm').mockResolvedValue(true);

    await composer.close();

    expect(composer.isVisible()).toBe(false);
  });

  it('closes without asking when nothing would be lost', async () => {
    const confirm = jest.spyOn(app, 'confirm');
    changed = false;

    await composer.close();

    expect(confirm).not.toHaveBeenCalled();
    expect(composer.isVisible()).toBe(false);
  });

  it('closes at once when nothing would be lost', () => {
    changed = false;

    composer.close();

    expect(composer.isVisible()).toBe(false);
  });

  it('asks once while a question is open', async () => {
    let answer!: (confirmed: boolean) => void;
    const confirm = jest.spyOn(app, 'confirm').mockReturnValue(new Promise((resolve) => (answer = resolve)));

    const first = composer.close();
    const second = composer.close();
    answer(false);
    await Promise.all([first, second]);

    expect(confirm).toHaveBeenCalledTimes(1);

    await composer.close();
    expect(confirm).toHaveBeenCalledTimes(2);
  });

  it('loads a body at once when nothing would be lost', () => {
    changed = false;
    class OtherBody extends Component {}

    composer.load(OtherBody as any, {});

    expect(composer.bodyMatches(OtherBody)).toBe(true);
  });

  it('does not close a body loaded while the same question was open', async () => {
    let answer!: (confirmed: boolean) => void;
    jest.spyOn(app, 'confirm').mockReturnValue(new Promise((resolve) => (answer = resolve)));
    class OtherBody extends Component {}

    const loading = composer.load(OtherBody as any, {});
    const closing = composer.close();
    answer(true);
    await Promise.all([loading, closing]);

    expect(composer.bodyMatches(OtherBody)).toBe(true);
  });

  it('keeps the content when loading another body is cancelled', async () => {
    jest.spyOn(app, 'confirm').mockResolvedValue(false);
    const body = composer.body;

    await composer.load(class OtherBody extends Component {} as any, {});

    expect(composer.body).toBe(body);
    expect(composer.isVisible()).toBe(true);
  });
});

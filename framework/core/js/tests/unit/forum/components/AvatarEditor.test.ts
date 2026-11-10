import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import AvatarEditor from '../../../../src/forum/components/AvatarEditor';

beforeAll(() => bootstrapForum());

describe('AvatarEditor upload body', () => {
  function capture(file: any): FormData | undefined {
    let captured: FormData | undefined;

    // @ts-ignore - minimal stub of the pieces upload() touches
    global.app.forum = { attribute: () => 'https://example.com/api' };
    // @ts-ignore
    global.app.request = (options: any) => {
      captured = options.body;
      return new Promise(() => {}); // never settles: we only care about the outgoing body
    };

    const editor = Object.create(AvatarEditor.prototype);
    editor.attrs = { user: { id: () => 2 } };
    editor.loading = false;

    editor.upload(file);

    return captured;
  }

  test('a real File produces a file part', () => {
    const file = new File(['x'], 'avatar.png', { type: 'image/png' });

    const entry = capture(file).get('avatar');

    expect(entry).toBeInstanceOf(File);
  });

  test.each([
    ['undefined', undefined],
    ['null', null],
  ])('%s sends no request at all', (_label, value) => {
    expect(capture(value)).toBeUndefined();
  });
});

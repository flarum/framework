import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { insertMention } from '../../../../src/forum/utils/reply';

beforeAll(() => {
  bootstrapForum();
  (app as any).mentionFormats = { mentionable: () => ({ replacement: () => '@"User"#p12' }) };
});

async function inserted(quote: string): Promise<string> {
  let text = '';
  const composer = {
    editorReady: async () => {},
    fields: { content: () => '' },
    body: { attrs: {} },
    editor: { getSelectionRange: () => [0, 0], insertAtCursor: (value: string) => (text = value) },
  };

  await insertMention({}, composer, quote);

  return text;
}

describe('insertMention', () => {
  test('puts a quote on the mention line', async () => {
    expect(await inserted('one\ntwo')).toBe('> @"User"#p12 one\n> two\n\n');
  });

  test('starts a quote that opens with a block spoiler on its own line', async () => {
    expect(await inserted('>! one\n>! two\n')).toBe('> @"User"#p12 \n> >! one\n> >! two\n\n');
  });

  test('starts a quote that opens with a block spoiler with an escaped !< on its own line', async () => {
    expect(await inserted('>! a !\\< b\n')).toBe('> @"User"#p12 \n> >! a !\\< b\n\n');
  });

  test('keeps a quote that opens with an inline spoiler on the mention line', async () => {
    expect(await inserted('>!secret!< public')).toBe('> @"User"#p12 >!secret!< public\n\n');
    expect(await inserted('>!2 \\> 1!< public')).toBe('> @"User"#p12 >!2 \\> 1!< public\n\n');
  });
});

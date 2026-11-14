import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Composer from '../../../../src/forum/components/Composer';
import ReplyComposer from '../../../../src/forum/components/ReplyComposer';
import EditPostComposer from '../../../../src/forum/components/EditPostComposer';

beforeAll(() => bootstrapForum());

describe('Composer focus', () => {
  // jsdom lays nothing out, so jQuery's :visible would match nothing. Give the
  // elements a width so they count as visible, as they would in a browser.
  const visible = <T extends HTMLElement>(el: T): T => {
    Object.defineProperty(el, 'offsetWidth', { configurable: true, get: () => 100 });
    return el;
  };

  // A composer whose header holds a button added by an extension, rendered
  // before the text editor, as in the real composer.
  const mount = (componentClass: unknown) => {
    const root = document.createElement('div');
    root.className = 'Composer';
    root.innerHTML = `
      <div class="Composer-content">
        <ul class="ComposerBody-header"><li><button type="button" class="Button">Add poll</button></li></ul>
        <div class="TextEditor"><textarea class="FormControl Composer-flexible TextEditor-editor"></textarea></div>
      </div>
    `;
    document.body.appendChild(root);
    root.querySelectorAll<HTMLElement>('button, textarea').forEach(visible);

    const composer = Object.create(Composer.prototype);
    composer.element = root;
    composer.attrs = { state: { body: { componentClass } } };

    return { root, composer };
  };

  afterEach(() => {
    document.body.innerHTML = '';
  });

  test.each([
    ['ReplyComposer', ReplyComposer],
    ['EditPostComposer', EditPostComposer],
  ])('%s focuses the text editor, not a button in its header', (_name, componentClass) => {
    const { root, composer } = mount(componentClass);

    composer.focus();

    expect(document.activeElement).toBe(root.querySelector('.TextEditor-editor'));
  });
});

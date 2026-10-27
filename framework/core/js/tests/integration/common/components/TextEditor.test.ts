import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import m from 'mithril';
import { app } from '../../../../src/forum';
import TextEditor from '../../../../src/common/components/TextEditor';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => {
  jest.restoreAllMocks();
  document.body.innerHTML = '';
});

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

/**
 * Mounts an editor whose loading finishes only when the test says so, the
 * way an extension's loader (an editor driver, say) holds it up.
 */
function mountEditor() {
  let finishLoading!: () => void;
  const loading = new Promise<void>((resolve) => (finishLoading = resolve));

  const oninit = TextEditor.prototype.oninit;
  jest.spyOn(TextEditor.prototype, 'oninit').mockImplementation(function (this: any, vnode: any) {
    oninit.call(this, vnode);
    this._loaders.push(() => loading);
  });

  const root = document.createElement('div');
  document.body.appendChild(root);

  const composer: any = { editor: null };
  const onTextEditorBuilt = jest.fn();

  m.mount(root, { view: () => m(TextEditor as any, { composer, onTextEditorBuilt, value: '' }) });

  return { root, composer, onTextEditorBuilt, finishLoading, remove: () => m.mount(root, null) };
}

describe('TextEditor', () => {
  // The composer can be closed, or the page left, while the editor is still
  // loading. The editor then has nothing on the page to build into.
  it('builds nothing once it has been removed while loading', async () => {
    const buildEditor = jest.spyOn(TextEditor.prototype, 'buildEditor').mockImplementation(() => ({} as any));
    const editor = mountEditor();

    editor.remove();
    editor.finishLoading();
    await flushPromises();

    expect(buildEditor).not.toHaveBeenCalled();
    expect(editor.composer.editor).toBeNull();
    expect(editor.onTextEditorBuilt).not.toHaveBeenCalled();
  });

  it('builds into its container once loaded', async () => {
    const driver = {};
    const buildEditor = jest.spyOn(TextEditor.prototype, 'buildEditor').mockImplementation(() => driver as any);
    const editor = mountEditor();

    editor.finishLoading();
    await flushPromises();

    expect(buildEditor).toHaveBeenCalledWith(editor.root.querySelector('.TextEditor-editorContainer'));
    expect(editor.composer.editor).toBe(driver);
    expect(editor.onTextEditorBuilt).toHaveBeenCalledTimes(1);

    editor.remove();
  });
});

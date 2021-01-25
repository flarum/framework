import { baseKeymap } from 'prosemirror-commands';
import { keymap } from 'prosemirror-keymap';
import { Schema } from 'prosemirror-model';
import { EditorState } from 'prosemirror-state';
import { EditorView } from 'prosemirror-view';

export default class ProseMirrorView {
  constructor(target, content) {
    this.build(target, content);
  }

  build(target, content) {
    this.view = new EditorView(target, this.buildEditorProps());
  }

  buildEditorProps() {
    return {
      state: this.buildEditorState(),
    };
  }

  buildEditorState() {
    window.play = this.buildEditorStateConfig();
    return EditorState.create(this.buildEditorStateConfig());
  }

  buildEditorStateConfig() {
    return {
      schema: new Schema(this.buildEditorStateSchemaConfig()),
      plugins: [keymap(baseKeymap)],
    };
  }

  buildEditorStateSchemaConfig() {
    // The simplest possible schema config that supports line breaks.
    // This is intended to be overriden by extensions.
    const nodes = {
      doc: {
        content: 'block+',
      },

      paragraph: {
        content: 'inline*',
        group: 'block',
        parseDOM: [{ tag: 'p' }],
        toDOM() {
          return ['p', 0];
        },
      },

      text: {
        group: 'inline',
      },
    };

    return {
      nodes,
    };
  }

  focus() {
    this.view.focus();
  }
  destroy() {
    this.view.destroy();
  }

  addCssClass(className) {
    this.view.dom.classList.add(className);
  }
}

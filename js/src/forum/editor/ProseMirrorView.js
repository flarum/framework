import { baseKeymap } from 'prosemirror-commands';
import { keymap } from 'prosemirror-keymap';
import { Schema } from 'prosemirror-model';
import { EditorState } from 'prosemirror-state';
import { EditorView } from 'prosemirror-view';
import ItemList from '../../common/utils/ItemList';
import PlaintextFormatter from './PlaintextFormatter';

export default class ProseMirrorView {
  constructor(target, attrs) {
    this.build(target, attrs);
  }

  build(target, attrs) {
    this.attrs = attrs;
    this.schema = new Schema(this.buildSchemaConfig());
    this.state = EditorState.create(this.buildEditorStateConfig());
    this.view = new EditorView(target, this.buildEditorProps());
  }

  buildSchemaConfig() {
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

  buildEditorStateConfig() {
    return {
      doc: this.parseInitialValue(this.attrs.value, this.schema),
      schema: this.schema,
      plugins: this.buildPluginItems().toArray(),
    };
  }

  buildPluginItems() {
    const items = new ItemList();

    items.add('baseKeymap', keymap(baseKeymap));

    items.add('shiftEnterSameAsEnter', keymap({ 'Shift-Enter': baseKeymap['Enter'] }));

    return items;
  }

  buildEditorProps() {
    const self = this;

    return {
      state: this.state,
      dispatchTransaction(transaction) {
        let newState = this.state.apply(transaction);
        this.updateState(newState);

        const newDoc = this.state.doc;
        const newDocPlaintext = self.serializeContent(newDoc, self.schema);
        self.attrs.oninput(newDocPlaintext);
      },
    };
  }

  parseInitialValue(text, schema) {
    return new PlaintextFormatter(schema).parse(text);
  }

  serializeContent(doc, schema) {
    return new PlaintextFormatter(schema).serialize(doc);
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

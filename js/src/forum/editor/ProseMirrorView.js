import { baseKeymap } from 'prosemirror-commands';
import { undo, redo, history } from 'prosemirror-history';
import { keymap } from 'prosemirror-keymap';
import { Schema } from 'prosemirror-model';
import { EditorState, TextSelection } from 'prosemirror-state';
import { EditorView } from 'prosemirror-view';
import ItemList from '../../common/utils/ItemList';
import placeholderPlugin from './placeholderPlugin';
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

    const cssClasses = attrs.classNames || [];
    cssClasses.forEach((className) => this.view.dom.classList.add(className));
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

    items.add('submit', keymap({ 'Mod-Enter': this.attrs.onsubmit }));

    items.add('baseKeymap', keymap(baseKeymap));

    items.add('shiftEnterSameAsEnter', keymap({ 'Shift-Enter': baseKeymap['Enter'] }));

    items.add('placeholder', placeholderPlugin(this.attrs.placeholder));

    items.add('history', history());

    items.add('historyKeymap', keymap({ 'Mod-z': undo, 'Mod-y': redo }));

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

  // External Control Stuff

  /**
   * Focus the textarea and place the cursor at the given index.
   *
   * @param {number} position
   */
  moveCursorTo(position) {
    this.setSelectionRange(position, position);
  }

  /**
   * Get the selected range of the textarea.
   *
   * @return {Array}
   */
  getSelectionRange() {
    return [this.view.state.selection.from, this.view.state.selection.to];
  }

  /**
   * Insert content into the textarea at the position of the cursor.
   *
   * @param {String} text
   */
  insertAtCursor(text) {
    this.insertAt(this.getSelectionRange()[0], text);
  }

  /**
   * Insert content into the textarea at the given position.
   *
   * @param {number} pos
   * @param {String} text
   */
  insertAt(pos, text) {
    this.insertBetween(pos, pos, text);
  }

  /**
   * Insert content into the textarea between the given positions.
   *
   * If the start and end positions are different, any text between them will be
   * overwritten.
   *
   * @param start
   * @param end
   * @param text
   */
  insertBetween(start, end, text) {
    this.view.dispatch(this.view.state.tr.insertText(text, start, end));

    // Move the textarea cursor to the end of the content we just inserted.
    this.moveCursorTo(start + text.length);
  }

  /**
   * Replace existing content from the start to the current cursor position.
   *
   * @param start
   * @param text
   */
  replaceBeforeCursor(start, text) {
    this.insertBetween(start, this.getSelectionRange()[0], text);
  }

  /**
   * Set the selected range of the textarea.
   *
   * @param {number} start
   * @param {number} end
   * @private
   */
  setSelectionRange(start, end) {
    const $start = this.view.state.tr.doc.resolve(start);
    const $end = this.view.state.tr.doc.resolve(end);

    this.view.dispatch(this.view.state.tr.setSelection(new TextSelection($start, $end)));
    this.focus();
  }

  focus() {
    this.view.focus();
  }
  destroy() {
    this.view.destroy();
  }
}

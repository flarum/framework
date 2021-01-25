import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import Button from '../../common/components/Button';
import ProseMirrorEditor from '../editor/ProseMirrorEditor';

/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Attrs
 *
 * - `composer`
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 * - `preview`
 */
export default class TextEditor extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The value of the textarea.
     *
     * @type {String}
     */
    this.value = this.attrs.value || '';
  }

  view() {
    return (
      <div className="TextEditor">
        <div className="ProseEditor"></div>

        <ul className="TextEditor-controls Composer-footer">
          {listItems(this.controlItems().toArray())}
          <li className="TextEditor-toolbar">{this.toolbarItems().toArray()}</li>
        </ul>
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.attrs.composer.editor = this.buildEditor(this.$('.ProseEditor')[0]);
  }

  buildEditorAttrs() {
    return {
      classNames: ['FormControl', 'Composer-flexible'],
      disabled: !!this.attrs.disabled,
      placeholder: this.attrs.placeholder || '',
      value: this.value,
      oninput: this.oninput.bind(this),
      onsubmit: () => {
        this.onsubmit();
        m.redraw();
      },
    };
  }

  buildEditor(dom) {
    return new ProseMirrorEditor(dom, this.buildEditorAttrs());
  }

  /**
   * Build an item list for the text editor controls.
   *
   * @return {ItemList}
   */
  controlItems() {
    const items = new ItemList();

    items.add(
      'submit',
      Button.component(
        {
          icon: 'fas fa-paper-plane',
          className: 'Button Button--primary',
          itemClassName: 'App-primaryControl',
          onclick: this.onsubmit.bind(this),
        },
        this.attrs.submitLabel
      )
    );

    if (this.attrs.preview) {
      items.add(
        'preview',
        Button.component({
          icon: 'far fa-eye',
          className: 'Button Button--icon',
          onclick: this.attrs.preview,
          title: app.translator.trans('core.forum.composer.preview_tooltip'),
          oncreate: (vnode) => $(vnode.dom).tooltip(),
        })
      );
    }

    return items;
  }

  /**
   * Build an item list for the toolbar controls.
   *
   * @return {ItemList}
   */
  toolbarItems() {
    return new ItemList();
  }

  /**
   * Handle input into the textarea.
   *
   * @param {String} value
   */
  oninput(value) {
    this.value = value;

    this.attrs.onchange(this.value);
  }

  /**
   * Handle the submit button being clicked.
   */
  onsubmit() {
    this.attrs.onsubmit(this.value);
  }
}

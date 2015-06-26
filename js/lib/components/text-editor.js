import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';
import ActionButton from 'flarum/components/action-button';

/**
  A text editor. Contains a textarea and an item list of `controls`, including
  a submit button.
 */
export default class TextEditor extends Component {
  constructor(props) {
    props.submitLabel = props.submitLabel || 'Submit';

    super(props);

    this.value = m.prop(this.props.value || '');
  }

  view() {
    return m('div.text-editor', [
      m('textarea.form-control.flexible-height', {
        config: this.configTextarea.bind(this),
        oninput: m.withAttr('value', this.oninput.bind(this)),
        placeholder: this.props.placeholder || '',
        disabled: !!this.props.disabled,
        value: this.value()
      }),
      m('ul.text-editor-controls', listItems(this.controlItems().toArray()))
    ]);
  }

  configTextarea(element, isInitialized) {
    if (isInitialized) { return; }

    $(element).bind('keydown', 'meta+return', this.onsubmit.bind(this));
  }

  controlItems() {
    var items = new ItemList();

    items.add('submit',
      ActionButton.component({
        label: this.props.submitLabel,
        icon: 'check',
        className: 'btn btn-primary',
        onclick: this.onsubmit.bind(this)
      })
    );

    return items;
  }

  setContent(content) {
    this.value(content);
    this.$('textarea').val(content).trigger('input');
  }

  setSelectionRange(start, end) {
    var $textarea = this.$('textarea');
    $textarea[0].setSelectionRange(start, end);
    $textarea.focus();
  }

  getSelectionRange() {
    var $textarea = this.$('textarea');
    return [$textarea[0].selectionStart, $textarea[0].selectionEnd];
  }

  insertAtCursor(insert) {
    var textarea = this.$('textarea')[0];
    var content = this.value();
    var index = textarea ? textarea.selectionStart : content.length;
    this.setContent(content.slice(0, index)+insert+content.slice(index));
    if (textarea) {
      var pos = index + insert.length;
      this.setSelectionRange(pos, pos);
    }
  }

  oninput(value) {
    this.value(value);
    this.props.onchange(this.value());

    m.redraw.strategy('none');
  }

  onsubmit() {
    this.props.onsubmit(this.value());
  }
}

import Component from 'flarum/component';
import LoadingIndicator from 'flarum/components/loading-indicator';
import TextEditor from 'flarum/components/text-editor';
import avatar from 'flarum/helpers/avatar';
import listItems from 'flarum/helpers/list-items';

export default class ComposerBody extends Component {
  constructor(props) {
    super(props);

    this.loading = m.prop(false);
    this.disabled = m.prop(false);
    this.content = m.prop(this.props.originalContent);
    this.editor = new TextEditor({
      submitLabel: this.props.submitLabel,
      placeholder: this.props.placeholder,
      disabled: this.loading(),
      onchange: this.content,
      onsubmit: this.onsubmit.bind(this),
      value: this.content()
    });
  }

  view() {
    return m('div', {config: this.element}, [
      avatar(this.props.user, {className: 'composer-avatar'}),
      m('div.composer-body', [
        m('ul.composer-header', listItems(this.headerItems().toArray())),
        m('div.composer-editor', this.editor.view())
      ]),
      LoadingIndicator.component({className: 'composer-loading'+(this.loading() ? ' active' : '')})
    ]);
  }

  focus() {
    this.$().find(':input:enabled:visible:first').focus();
  }

  preventExit() {
    return this.content() != this.props.originalContent && !confirm(this.props.confirmExit);
  }

  onsubmit(value) {
    //
  }
}

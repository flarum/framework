import Component from 'flarum/component';
import humanTime from 'flarum/utils/human-time';
import ItemList from 'flarum/utils/item-list';
import classList from 'flarum/utils/class-list';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import DropdownButton from 'flarum/components/dropdown-button';
import ActionButton from 'flarum/components/action-button';
import listItems from 'flarum/helpers/list-items';

export default class UserBio extends Component {
  constructor(props) {
    super(props);

    this.editing = m.prop(false);
  }

  view() {
    var user = this.props.user;

    return m('div.user-bio', {
      className: classList({editable: this.isEditable(), editing: this.editing()}),
      onclick: this.edit.bind(this),
      config: this.element
    }, [
      this.editing()
        ? m('textarea.form-control', {value: user.bio()})
        : m('div.bio-content', [
          user.bioHtml()
            ? m.trust(user.bioHtml())
            : (this.props.editable ? m('p', 'Write something about yourself...') : '')
        ])
    ]);
  }

  isEditable() {
    return this.props.user.canEdit() && this.props.editable;
  }

  edit() {
    if (!this.isEditable()) { return; }

    this.editing(true);
    var height = this.$().height();

    m.redraw();

    var self = this;
    var save = function(e) {
      if (e.shiftKey) { return; }
      e.preventDefault();
      self.save($(this).val());
    };
    this.$('textarea').css('height', height).focus().bind('blur', save).bind('keydown', 'return', save);
  }

  save(value) {
    this.editing(false);

    this.props.user.save({bio: value}).then(() => m.redraw());
    m.redraw();
  }
}

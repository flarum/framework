import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import Badge from 'flarum/components/Badge';
import Group from 'flarum/models/Group';

/**
 * The `EditGroupModal` component shows a modal dialog which allows the user
 * to create or edit a group.
 */
export default class EditGroupModal extends Modal {
  constructor(...args) {
    super(...args);

    this.group = this.props.group || app.store.createRecord('groups');

    this.nameSingular = m.prop(this.group.nameSingular() || '');
    this.namePlural = m.prop(this.group.namePlural() || '');
    this.icon = m.prop(this.group.icon() || '');
    this.color = m.prop(this.group.color() || '');
  }

  className() {
    return 'EditGroupModal Modal--small';
  }

  title() {
    return [
      this.color() || this.icon() ? Badge.component({
        icon: this.icon(),
        style: {backgroundColor: this.color()}
      }) : '',
      ' ',
      this.namePlural() || 'Create Group'
    ];
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Name</label>
            <div className="EditGroupModal-name-input">
              <input className="FormControl" placeholder="Singular (e.g. Mod)" value={this.nameSingular()} oninput={m.withAttr('value', this.nameSingular)}/>
              <input className="FormControl" placeholder="Plural (e.g. Mods)" value={this.namePlural()} oninput={m.withAttr('value', this.namePlural)}/>
            </div>
          </div>

          <div className="Form-group">
            <label>Color</label>
            <input className="FormControl" placeholder="#aaaaaa" value={this.color()} oninput={m.withAttr('value', this.color)}/>
          </div>

          <div className="Form-group">
            <label>Icon</label>
            <div className="helpText">
              Enter the name of any <a href="http://fortawesome.github.io/Font-Awesome/icons/" tabindex="-1">FontAwesome</a> icon class, <em>without</em> the <code>fa-</code> prefix.
            </div>
            <input className="FormControl" placeholder="bolt" value={this.icon()} oninput={m.withAttr('value', this.icon)}/>
          </div>

          <div className="Form-group">
            {Button.component({
              type: 'submit',
              className: 'Button Button--primary EditGroupModal-save',
              loading: this.loading,
              children: 'Save Changes'
            })}
            {this.group.exists && this.group.id() !== Group.ADMINISTRATOR_ID ? (
              <button type="button" className="Button EditGroupModal-delete" onclick={this.delete.bind(this)}>
                Delete Group
              </button>
            ) : ''}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    this.group.save({
      nameSingular: this.nameSingular(),
      namePlural: this.namePlural(),
      color: this.color(),
      icon: this.icon()
    }).then(
      () => this.hide(),
      () => {
        this.loading = false;
        m.redraw();
      }
    );
  }

  delete() {
    if (confirm('Are you sure you want to delete this group? The group members will NOT be deleted.')) {
      this.group.delete().then(() => m.redraw());
      this.hide();
    }
  }
}

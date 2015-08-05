import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import GroupBadge from 'flarum/components/GroupBadge';
import Group from 'flarum/models/Group';

/**
 * The `EditUserModal` component displays a modal dialog with a login form.
 */
export default class EditUserModal extends Modal {
  constructor(...args) {
    super(...args);

    const user = this.props.user;

    this.username = m.prop(user.username() || '');
    this.email = m.prop(user.email() || '');
    this.setPassword = m.prop(false);
    this.password = m.prop(user.password() || '');
    this.groups = {};

    app.store.all('groups')
      .filter(group => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
      .forEach(group => this.groups[group.id()] = m.prop(user.groups().indexOf(group) !== -1));
  }

  className() {
    return 'EditUserModal Modal--small';
  }

  title() {
    return 'Edit User';
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>Username</label>
            <input className="FormControl" placeholder={app.trans('core.username')}
              value={this.username()}
              onchange={m.withAttr('value', this.username)} />
          </div>

          <div className="Form-group">
            <label>Email</label>
            <div>
              <input className="FormControl" placeholder={app.trans('core.email')}
                value={this.email()}
                onchange={m.withAttr('value', this.email)} />
            </div>
          </div>

          <div className="Form-group">
            <label>Password</label>
            <div>
              <label className="checkbox">
                <input type="checkbox" checked={this.setPassword()} onchange={e => {
                  this.setPassword(e.target.checked);
                  m.redraw(true);
                  if (e.target.checked) this.$('[name=password]').select();
                  m.redraw.strategy('none');
                }}/>
                Set new password
              </label>
              {this.setPassword() ? (
                <input className="FormControl" type="password" name="password" placeholder={app.trans('core.password')}
                  value={this.password()}
                  onchange={m.withAttr('value', this.password)} />
              ) : ''}
            </div>
          </div>

          <div className="Form-group EditUserModal-groups">
            <label>Groups</label>
            <div>
              {Object.keys(this.groups)
                .map(id => app.store.getById('groups', id))
                .map(group => (
                  <label className="checkbox">
                    <input type="checkbox"
                      checked={this.groups[group.id()]()}
                      disabled={this.props.user.id() === '1' && group.id() === Group.ADMINISTRATOR_ID}
                      onchange={m.withAttr('checked', this.groups[group.id()])}/>
                    {GroupBadge.component({group, label: ''})} {group.nameSingular()}
                  </label>
                ))}
            </div>
          </div>

          <div className="Form-group">
            {Button.component({
              className: 'Button Button--primary',
              type: 'submit',
              loading: this.loading,
              children: app.trans('core.save_changes')
            })}
          </div>
        </div>
      </div>
    );
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    const groups = Object.keys(this.groups)
      .filter(id => this.groups[id]())
      .map(id => app.store.getById('groups', id));

    const data = {
      username: this.username(),
      email: this.email(),
      relationships: {groups}
    };

    if (this.setPassword()) {
      data.password = this.password();
    }

    this.props.user.save(data).then(
      () => this.hide(),
      response => {
        this.loading = false;
        this.handleErrors(response);
      }
    );
  }
}

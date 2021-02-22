import Modal from './Modal';
import Button from './Button';
import GroupBadge from './GroupBadge';
import Group from '../models/Group';
import extractText from '../utils/extractText';
import ItemList from '../utils/ItemList';
import Stream from '../utils/Stream';

/**
 * The `EditUserModal` component displays a modal dialog with a login form.
 */
export default class EditUserModal extends Modal {
  oninit(vnode) {
    super.oninit(vnode);

    const user = this.attrs.user;

    this.username = Stream(user.username() || '');
    this.email = Stream(user.email() || '');
    this.isEmailConfirmed = Stream(user.isEmailConfirmed() || false);
    this.setPassword = Stream(false);
    this.password = Stream(user.password() || '');
    this.groups = {};

    app.store
      .all('groups')
      .filter((group) => [Group.GUEST_ID, Group.MEMBER_ID].indexOf(group.id()) === -1)
      .forEach((group) => (this.groups[group.id()] = Stream(user.groups().indexOf(group) !== -1)));
  }

  className() {
    return 'EditUserModal Modal--small';
  }

  title() {
    return app.translator.trans('core.lib.edit_user.title');
  }

  content() {
    const fields = this.fields().toArray();
    return (
      <div className="Modal-body">
        {fields.length > 1 ? <div className="Form">{this.fields().toArray()}</div> : app.translator.trans('core.lib.edit_user.nothing_available')}
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    if (app.session.user.canEditCredentials()) {
      items.add(
        'username',
        <div className="Form-group">
          <label>{app.translator.trans('core.lib.edit_user.username_heading')}</label>
          <input
            className="FormControl"
            placeholder={extractText(app.translator.trans('core.lib.edit_user.username_label'))}
            bidi={this.username}
            disabled={this.nonAdminEditingAdmin()}
          />
        </div>,
        40
      );

      if (app.session.user !== this.attrs.user) {
        items.add(
          'email',
          <div className="Form-group">
            <label>{app.translator.trans('core.lib.edit_user.email_heading')}</label>
            <div>
              <input
                className="FormControl"
                placeholder={extractText(app.translator.trans('core.lib.edit_user.email_label'))}
                bidi={this.email}
                disabled={this.nonAdminEditingAdmin()}
              />
            </div>
            {!this.isEmailConfirmed() && this.userIsAdmin(app.session.user) ? (
              <div>
                {Button.component(
                  {
                    className: 'Button Button--block',
                    loading: this.loading,
                    onclick: this.activate.bind(this),
                  },
                  app.translator.trans('core.lib.edit_user.activate_button')
                )}
              </div>
            ) : (
              ''
            )}
          </div>,
          30
        );

        items.add(
          'password',
          <div className="Form-group">
            <label>{app.translator.trans('core.lib.edit_user.password_heading')}</label>
            <div>
              <label className="checkbox">
                <input
                  type="checkbox"
                  onchange={(e) => {
                    this.setPassword(e.target.checked);
                    m.redraw.sync();
                    if (e.target.checked) this.$('[name=password]').select();
                    e.redraw = false;
                  }}
                  disabled={this.nonAdminEditingAdmin()}
                />
                {app.translator.trans('core.lib.edit_user.set_password_label')}
              </label>
              {this.setPassword() ? (
                <input
                  className="FormControl"
                  type="password"
                  name="password"
                  placeholder={extractText(app.translator.trans('core.lib.edit_user.password_label'))}
                  bidi={this.password}
                  disabled={this.nonAdminEditingAdmin()}
                />
              ) : (
                ''
              )}
            </div>
          </div>,
          20
        );
      }
    }

    if (app.session.user.canEditGroups()) {
      items.add(
        'groups',
        <div className="Form-group EditUserModal-groups">
          <label>{app.translator.trans('core.lib.edit_user.groups_heading')}</label>
          <div>
            {Object.keys(this.groups)
              .map((id) => app.store.getById('groups', id))
              .map((group) => (
                <label className="checkbox">
                  <input
                    type="checkbox"
                    bidi={this.groups[group.id()]}
                    disabled={group.id() === Group.ADMINISTRATOR_ID && (this.attrs.user === app.session.user || !this.userIsAdmin(app.session.user))}
                  />
                  {GroupBadge.component({ group, label: '' })} {group.nameSingular()}
                </label>
              ))}
          </div>
        </div>,
        10
      );
    }

    items.add(
      'submit',
      <div className="Form-group">
        {Button.component(
          {
            className: 'Button Button--primary',
            type: 'submit',
            loading: this.loading,
          },
          app.translator.trans('core.lib.edit_user.submit_button')
        )}
      </div>,
      -10
    );

    return items;
  }

  activate() {
    this.loading = true;
    const data = {
      username: this.username(),
      isEmailConfirmed: true,
    };
    this.attrs.user
      .save(data, { errorHandler: this.onerror.bind(this) })
      .then(() => {
        this.isEmailConfirmed(true);
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  data() {
    const data = {
      relationships: {},
    };

    if (this.attrs.user.canEditCredentials() && !this.nonAdminEditingAdmin()) {
      data.username = this.username();

      if (app.session.user !== this.attrs.user) {
        data.email = this.email();
      }

      if (this.setPassword()) {
        data.password = this.password();
      }
    }

    if (this.attrs.user.canEditGroups()) {
      data.relationships.groups = Object.keys(this.groups)
        .filter((id) => this.groups[id]())
        .map((id) => app.store.getById('groups', id));
    }

    return data;
  }

  onsubmit(e) {
    e.preventDefault();

    this.loading = true;

    this.attrs.user
      .save(this.data(), { errorHandler: this.onerror.bind(this) })
      .then(this.hide.bind(this))
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }

  nonAdminEditingAdmin() {
    return this.userIsAdmin(this.attrs.user) && !this.userIsAdmin(app.session.user);
  }

  /**
   * @internal
   * @protected
   */
  userIsAdmin(user) {
    return user.groups().some((g) => g.id() === Group.ADMINISTRATOR_ID);
  }
}

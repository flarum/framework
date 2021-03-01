import Modal from '../../common/components/Modal';
import Button from '../../common/components/Button';
import GroupBadge from '../../common/components/GroupBadge';
import Group from '../../common/models/Group';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';

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
    return app.translator.trans('core.forum.edit_user.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">{this.fields().toArray()}</div>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    items.add(
      'username',
      <div className="Form-group">
        <label>{app.translator.trans('core.forum.edit_user.username_heading')}</label>
        <input className="FormControl" placeholder={extractText(app.translator.trans('core.forum.edit_user.username_label'))} bidi={this.username} />
      </div>,
      40
    );

    if (app.session.user !== this.attrs.user) {
      items.add(
        'email',
        <div className="Form-group">
          <label>{app.translator.trans('core.forum.edit_user.email_heading')}</label>
          <div>
            <input className="FormControl" placeholder={extractText(app.translator.trans('core.forum.edit_user.email_label'))} bidi={this.email} />
          </div>
          {!this.isEmailConfirmed() ? (
            <div>
              {Button.component(
                {
                  className: 'Button Button--block',
                  loading: this.loading,
                  onclick: this.activate.bind(this),
                },
                app.translator.trans('core.forum.edit_user.activate_button')
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
          <label>{app.translator.trans('core.forum.edit_user.password_heading')}</label>
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
              />
              {app.translator.trans('core.forum.edit_user.set_password_label')}
            </label>
            {this.setPassword() ? (
              <input
                className="FormControl"
                type="password"
                name="password"
                placeholder={extractText(app.translator.trans('core.forum.edit_user.password_label'))}
                bidi={this.password}
              />
            ) : (
              ''
            )}
          </div>
        </div>,
        20
      );
    }

    items.add(
      'groups',
      <div className="Form-group EditUserModal-groups">
        <label>{app.translator.trans('core.forum.edit_user.groups_heading')}</label>
        <div>
          {Object.keys(this.groups)
            .map((id) => app.store.getById('groups', id))
            .map((group) => (
              <label className="checkbox">
                <input
                  type="checkbox"
                  bidi={this.groups[group.id()]}
                  disabled={this.attrs.user.id() === '1' && group.id() === Group.ADMINISTRATOR_ID}
                />
                {GroupBadge.component({ group, label: '' })} {group.nameSingular()}
              </label>
            ))}
        </div>
      </div>,
      10
    );

    items.add(
      'submit',
      <div className="Form-group">
        {Button.component(
          {
            className: 'Button Button--primary',
            type: 'submit',
            loading: this.loading,
          },
          app.translator.trans('core.forum.edit_user.submit_button')
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
    const groups = Object.keys(this.groups)
      .filter((id) => this.groups[id]())
      .map((id) => app.store.getById('groups', id));

    const data = {
      username: this.username(),
      relationships: { groups },
    };

    if (app.session.user !== this.attrs.user) {
      data.email = this.email();
    }

    if (this.setPassword()) {
      data.password = this.password();
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
}

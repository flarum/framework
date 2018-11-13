import Modal from '../../common/components/Modal';
import Button from '../../common/components/Button';
import GroupBadge from '../../common/components/GroupBadge';
import Group from '../../common/models/Group';
import extractText from '../../common/utils/extractText';
import ItemList from '../../common/utils/ItemList';

/**
 * The `EditUserModal` component displays a modal dialog with a login form.
 */
export default class EditUserModal extends Modal {
  init() {
    super.init();

    const user = this.props.user;

    this.username = m.prop(user.username() || '');
    this.email = m.prop(user.email() || '');
    this.isEmailConfirmed = m.prop(user.isEmailConfirmed() || false);
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
    return app.translator.trans('core.forum.edit_user.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <div className="Form">
          {this.fields().toArray()}
        </div>
      </div>
    );
  }

  fields() {
    const items = new ItemList();

    items.add('username', <div className="Form-group">
      <label>{app.translator.trans('core.forum.edit_user.username_heading')}</label>
      <input className="FormControl" placeholder={extractText(app.translator.trans('core.forum.edit_user.username_label'))}
             bidi={this.username} />
    </div>, 40);

    if (app.session.user !== this.props.user) {
      items.add('email', <div className="Form-group">
        <label>{app.translator.trans('core.forum.edit_user.email_heading')}</label>
        <div>
          <input className="FormControl"
                 placeholder={extractText(app.translator.trans('core.forum.edit_user.email_label'))}
                 bidi={this.email}/>
        </div>
        {!this.isEmailConfirmed() ? (
          <div>
            {Button.component({
              className: 'Button Button--block',
              children: app.translator.trans('core.forum.edit_user.activate_button'),
              loading: this.loading,
              onclick: this.activate.bind(this)
            })}
          </div>
        ) : ''}
      </div>, 30);

      items.add('password', <div className="Form-group">
        <label>{app.translator.trans('core.forum.edit_user.password_heading')}</label>
        <div>
          <label className="checkbox">
            <input type="checkbox" checked={this.setPassword()} onChange={e => {
              this.setPassword(e.target.checked);
              m.redraw(true);
              if (e.target.checked) this.$('[name=password]').select();
              m.redraw.strategy('none');
            }}/>
            {app.translator.trans('core.forum.edit_user.set_password_label')}
          </label>
          {this.setPassword() ? (
            <input className="FormControl" type="password" name="password"
                   placeholder={extractText(app.translator.trans('core.forum.edit_user.password_label'))}
                   bidi={this.password}/>
          ) : ''}
        </div>
      </div>, 20);

    }

    items.add('groups', <div className="Form-group EditUserModal-groups">
      <label>{app.translator.trans('core.forum.edit_user.groups_heading')}</label>
      <div>
        {Object.keys(this.groups)
          .map(id => app.store.getById('groups', id))
          .map(group => (
            <label className="checkbox">
              <input type="checkbox"
                     bidi={this.groups[group.id()]}
                     disabled={this.props.user.id() === '1' && group.id() === Group.ADMINISTRATOR_ID} />
              {GroupBadge.component({group, label: ''})} {group.nameSingular()}
            </label>
          ))}
      </div>
    </div>, 10);

    items.add('submit', <div className="Form-group">
      {Button.component({
        className: 'Button Button--primary',
        type: 'submit',
        loading: this.loading,
        children: app.translator.trans('core.forum.edit_user.submit_button')
      })}
    </div>, -10);

    return items;
  }

  activate() {
    this.loading = true;
    const data = {
      username: this.username(),
      isEmailConfirmed: true,
    };
    this.props.user.save(data, {errorHandler: this.onerror.bind(this)})
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
      .filter(id => this.groups[id]())
      .map(id => app.store.getById('groups', id));

    const data = {
      username: this.username(),
      relationships: {groups}
    };

    if (app.session.user !== this.props.user) {
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

    this.props.user.save(this.data(), {errorHandler: this.onerror.bind(this)})
      .then(this.hide.bind(this))
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}

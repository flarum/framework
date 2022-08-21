import app from '../../common/app';
import Modal, { IInternalModalAttrs } from './Modal';
import Button from './Button';
import GroupBadge from './GroupBadge';
import Group from '../models/Group';
import extractText from '../utils/extractText';
import ItemList from '../utils/ItemList';
import Stream from '../utils/Stream';
import type Mithril from 'mithril';
import type User from '../models/User';
import type { SaveAttributes, SaveRelationships } from '../Model';

export interface IEditUserModalAttrs extends IInternalModalAttrs {
  user: User;
}

export default class EditUserModal<CustomAttrs extends IEditUserModalAttrs = IEditUserModalAttrs> extends Modal<CustomAttrs> {
  protected username!: Stream<string>;
  protected email!: Stream<string>;
  protected isEmailConfirmed!: Stream<boolean>;
  protected setPassword!: Stream<boolean>;
  protected password!: Stream<string>;
  protected groups: Record<string, Stream<boolean>> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    const user = this.attrs.user;

    this.username = Stream(user.username() || '');
    this.email = Stream(user.email() || '');
    this.isEmailConfirmed = Stream(user.isEmailConfirmed() || false);
    this.setPassword = Stream(false as boolean);
    this.password = Stream(user.password() || '');

    const userGroups = user.groups() || [];

    app.store
      .all<Group>('groups')
      .filter((group) => ![Group.GUEST_ID, Group.MEMBER_ID].includes(group.id()!))
      .forEach((group) => (this.groups[group.id()!] = Stream(userGroups.includes(group))));
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

    if (this.attrs.user.canEditCredentials()) {
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
            {!this.isEmailConfirmed() && this.userIsAdmin(app.session.user) && (
              <div>
                <Button className="Button Button--block" loading={this.loading} onclick={this.activate.bind(this)}>
                  {app.translator.trans('core.lib.edit_user.activate_button')}
                </Button>
              </div>
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
                  onchange={(e: KeyboardEvent) => {
                    const target = e.target as HTMLInputElement;
                    this.setPassword(target.checked);
                    m.redraw.sync();
                    if (target.checked) this.$('[name=password]').select();
                    e.redraw = false;
                  }}
                  disabled={this.nonAdminEditingAdmin()}
                />
                {app.translator.trans('core.lib.edit_user.set_password_label')}
              </label>
              {this.setPassword() && (
                <input
                  className="FormControl"
                  type="password"
                  name="password"
                  placeholder={extractText(app.translator.trans('core.lib.edit_user.password_label'))}
                  bidi={this.password}
                  disabled={this.nonAdminEditingAdmin()}
                />
              )}
            </div>
          </div>,
          20
        );
      }
    }

    if (this.attrs.user.canEditGroups()) {
      items.add(
        'groups',
        <div className="Form-group EditUserModal-groups">
          <label>{app.translator.trans('core.lib.edit_user.groups_heading')}</label>
          <div>
            {Object.keys(this.groups)
              .map((id) => app.store.getById<Group>('groups', id))
              .filter(Boolean)
              .map(
                (group) =>
                  // Necessary because filter(Boolean) doesn't narrow out falsy values.
                  group && (
                    <label className="checkbox">
                      <input
                        type="checkbox"
                        bidi={this.groups[group.id()!]}
                        disabled={
                          group.id() === Group.ADMINISTRATOR_ID && (this.attrs.user === app.session.user || !this.userIsAdmin(app.session.user))
                        }
                      />
                      <GroupBadge group={group} label={null} /> {group.nameSingular()}
                    </label>
                  )
              )}
          </div>
        </div>,
        10
      );
    }

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary" type="submit" loading={this.loading}>
          {app.translator.trans('core.lib.edit_user.submit_button')}
        </Button>
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
    const data: SaveAttributes = {};
    const relationships: SaveRelationships = {};

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
      relationships.groups = Object.keys(this.groups)
        .filter((id) => this.groups[id]())
        .map((id) => app.store.getById<Group>('groups', id))
        .filter((g): g is Group => g instanceof Group);
    }

    data.relationships = relationships;

    return data;
  }

  onsubmit(e: SubmitEvent) {
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

  nonAdminEditingAdmin(): boolean {
    return this.userIsAdmin(this.attrs.user) && !this.userIsAdmin(app.session.user);
  }

  /**
   * @internal
   */
  protected userIsAdmin(user: User | null): boolean {
    return !!(user?.groups() || []).some((g) => g?.id() === Group.ADMINISTRATOR_ID);
  }
}

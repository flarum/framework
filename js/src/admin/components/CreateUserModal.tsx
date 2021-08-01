import app from '../../admin/app';
import type Mithril from 'mithril';
import type { ComponentAttrs } from '../../common/Component';
import Button from '../../common/components/Button';
import Modal from '../../common/components/Modal';
import Switch from '../../common/components/Switch';
import ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';
import EditUserModal from '../../common/components/EditUserModal';

interface ICreateUserModalState {
  username: string;
  email: string;
  isEmailConfirmed: boolean;
  password: string;
  showEditModalAfterClose: boolean;
}

/**
 * A Modal that allows admins to create a new user.
 */
export default class CreateUserModal extends Modal {
  state: ICreateUserModalState = {
    username: '',
    email: '',
    isEmailConfirmed: false,
    password: '',
    showEditModalAfterClose: true,
  };

  oninit(vnode: Mithril.Vnode<ComponentAttrs, this>) {
    super.oninit(vnode);
  }

  className() {
    return 'CreateUserModal';
  }

  title() {
    return app.translator.trans('core.admin.create_user.title');
  }

  content() {
    const fields = this.fields().toArray();

    console.log(this.state);

    return <div className="Modal-body">{fields}</div>;
  }

  fields() {
    const items = new ItemList();

    items.add(
      'username',
      <div className="Form-group">
        <label>
          {app.translator.trans('core.admin.create_user.username.label')}
          <input className="FormControl" {...this.twoWayLinkAttrs('username')} />
        </label>
      </div>,
      100
    );

    items.add(
      'password',
      <div className="Form-group">
        <label>
          {app.translator.trans('core.admin.create_user.password.label')}
          <input autocomplete="new-password" type="password" className="FormControl" {...this.twoWayLinkAttrs('password')} />
        </label>
      </div>,
      80
    );

    items.add(
      'email',
      [
        <div className="Form-group">
          <label>
            {app.translator.trans('core.admin.create_user.email.label')}
            <input type="email" className="FormControl" {...this.twoWayLinkAttrs('email')} />
          </label>
        </div>,
        <div className="Form-group">
          <Switch loading={this.loading} state={this.state.isEmailConfirmed} onchange={(val: boolean) => (this.state.isEmailConfirmed = val)}>
            {app.translator.trans('core.admin.create_user.email.confirmed')}
          </Switch>
        </div>,
      ],
      60
    );

    items.add(
      'openEditAfterClose',
      [
        <div className="Form-group">
          <label>
            <input
              type="checkbox"
              checked={this.state.showEditModalAfterClose}
              onchange={(e: InputEvent) => (this.state.showEditModalAfterClose = (e!.currentTarget as HTMLInputElement).checked)}
            />{' '}
            {app.translator.trans('core.admin.create_user.launch_edit_after_close')}
          </label>
        </div>,
      ],
      0
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button className="Button Button--primary" type="submit" loading={this.loading}>
          {app.translator.trans('core.admin.create_user.submit_button')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  private twoWayLinkAttrs(key: keyof typeof this.state, valueName: string = 'value', eventName: string = 'oninput') {
    return {
      [valueName]: this.state[key],
      [eventName]: (e: Event) => {
        (this.state[key] as ICreateUserModalState[keyof ICreateUserModalState]) = (e!.currentTarget as HTMLInputElement).value;
      },
    };
  }

  isDataValid(): boolean {
    const { username } = this.state;

    if (!username) return false;

    return true;
  }

  submitData(): { data: { attributes: Omit<ICreateUserModalState, 'showEditModalAfterClose'> } } {
    const { showEditModalAfterClose, ...data } = this.state;

    return { data: { attributes: data } };
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    this.loading = true;

    const body = this.submitData();

    app
      .request({
        url: `${app.forum.attribute('apiUrl')}/users`,
        method: 'POST',
        body,
        errorHandler: this.onerror.bind(this),
      })
      .then((response) => {
        this.hide();
        console.log(response);

        const user: User = app.store.pushPayload(response);

        // Add the missing groups relationship we can't include from the CreateUserController
        user.pushData({
          relationships: {
            groups: {
              data: [],
            },
          },
        });

        if (this.state.showEditModalAfterClose) {
          app.modal.show(EditUserModal, { user });
        }
      })
      .catch((e) => {
        this.loaded.call(this);
        this.onerror(e);
      });
  }
}

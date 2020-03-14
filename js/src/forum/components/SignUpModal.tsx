import Stream from 'mithril/stream';

import { ComponentProps } from '../../common/Component';
import Modal from '../../common/components/Modal';
import ItemList from '../../common/utils/ItemList';
import Button from '../../common/components/Button';

import LogInButtons from './LogInButtons';
import LogInModal from './LogInModal';

export interface SignUpModalProps extends ComponentProps {
    username?: string;
    email?: string;
    password?: string;

    /**
     * An email token to sign up with
     */
    token?: string;

    provided?: string[];
}

/**
 * The `SignUpModal` component displays a modal dialog with a singup form.
 */
export default class SignUpModal extends Modal<SignUpModalProps> {
    /**
     * The value of the username input.
     */
    username!: Stream<string>;

    /**
     * The value of the email input.
     */
    email!: Stream<string>;

    /**
     * The value of the password input.
     */
    password!: Stream<string>;

    oninit(vnode) {
        super.oninit(vnode);

        this.username = m.prop(this.props.username || '');

        this.email = m.prop(this.props.email || '');

        this.password = m.prop(this.props.password || '');
    }

    className() {
        return 'Modal--small SignUpModal';
    }

    title() {
        return app.translator.transText('core.forum.sign_up.title');
    }

    content() {
        return [<div className="Modal-body">{this.body()}</div>, <div className="Modal-footer">{this.footer()}</div>];
    }

    isProvided(field) {
        return this.props.provided && this.props.provided.indexOf(field) !== -1;
    }

    body() {
        return [this.props.token ? '' : <LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
    }

    fields() {
        const items = new ItemList();

        items.add(
            'username',
            <div className="Form-group">
                <input
                    className="FormControl"
                    name="username"
                    type="text"
                    placeholder={app.translator.transText('core.forum.sign_up.username_placeholder')}
                    value={this.username()}
                    onchange={m.withAttr('value', this.username)}
                    disabled={this.loading || this.isProvided('username')}
                />
            </div>,
            30
        );

        items.add(
            'email',
            <div className="Form-group">
                <input
                    className="FormControl"
                    name="email"
                    type="email"
                    placeholder={app.translator.transText('core.forum.sign_up.email_placeholder')}
                    value={this.email()}
                    onchange={m.withAttr('value', this.email)}
                    disabled={this.loading || this.isProvided('email')}
                />
            </div>,
            20
        );

        if (!this.props.token) {
            items.add(
                'password',
                <div className="Form-group">
                    <input
                        className="FormControl"
                        name="password"
                        type="password"
                        placeholder={app.translator.transText('core.forum.sign_up.password_placeholder')}
                        value={this.password()}
                        onchange={m.withAttr('value', this.password)}
                        disabled={this.loading}
                    />
                </div>,
                10
            );
        }

        items.add(
            'submit',
            <div className="Form-group">
                <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
                    {app.translator.trans('core.forum.sign_up.submit_button')}
                </Button>
            </div>,
            -10
        );

        return items;
    }

    footer() {
        return [
            <p className="SignUpModal-logIn">
                {app.translator.trans('core.forum.sign_up.log_in_text', {
                    a: <a onclick={this.logIn.bind(this)} />,
                })}
            </p>,
        ];
    }

    /**
     * Open the log in modal, prefilling it with an email/username/password if
     * the user has entered one.
     *
     * @public
     */
    logIn() {
        const props = {
            identification: this.email() || this.username(),
            password: this.password(),
        };

        app.modal.show(LogInModal, props);
    }

    onready() {
        if (this.props.username && !this.props.email) {
            this.$('[name=email]').select();
        } else {
            this.$('[name=username]').select();
        }
    }

    onsubmit(e) {
        e.preventDefault();

        this.loading = true;

        const data = this.submitData();

        app.request({
            url: app.forum.attribute('baseUrl') + '/register',
            method: 'POST',
            data,
            errorHandler: this.onerror.bind(this),
        }).then(() => window.location.reload(), this.loaded.bind(this));
    }

    /**
     * Get the data that should be submitted in the sign-up request.
     *
     * @return {Object}
     * @public
     */
    submitData() {
        const data = {
            username: this.username(),
            email: this.email(),
        };

        if (this.props.token) {
            data.token = this.props.token;
        } else {
            data.password = this.password();
        }

        return data;
    }
}

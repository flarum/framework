import Stream from 'mithril/stream';

import { ComponentProps } from '../../common/Component';
import Modal from '../../common/components/Modal';
import ItemList from '../../common/utils/ItemList';
import Button from '../../common/components/Button';

import LogInButtons from './LogInButtons';
import SignUpModal from './SignUpModal';

export interface LogInModalProps extends ComponentProps {
    identification?: string;
    password?: string;
    remember?: boolean;
}

/**
 * The `LogInModal` component displays a modal dialog with a login form.
 */
export default class LogInModal extends Modal<LogInModalProps> {
    /**
     * The value of the identification input.
     */
    identification!: Stream<string>;

    /**
     * The value of the password input.
     */
    password!: Stream<string>;

    /**
     * The value of the remember me input.
     */
    remember!: Stream<boolean>;

    oninit(vnode) {
        super.oninit(vnode);

        this.identification = m.prop(this.props.identification || '');

        this.password = m.prop(this.props.password || '');

        this.remember = m.prop(!!this.props.remember);
    }

    className(): string {
        return 'LogInModal Modal--small';
    }

    title(): string {
        return app.translator.transText('core.forum.log_in.title');
    }

    content() {
        return [<div className="Modal-body">{this.body()}</div>, <div className="Modal-footer">{this.footer()}</div>];
    }

    body() {
        return [<LogInButtons />, <div className="Form Form--centered">{this.fields().toArray()}</div>];
    }

    fields() {
        const items = new ItemList();

        items.add(
            'identification',
            <div className="Form-group">
                <input
                    className="FormControl"
                    name="identification"
                    type="text"
                    placeholder={app.translator.transText('core.forum.log_in.username_or_email_placeholder')}
                    bidi={this.identification}
                    disabled={this.loading}
                />
            </div>,
            30
        );

        items.add(
            'password',
            <div className="Form-group">
                <input
                    className="FormControl"
                    name="password"
                    type="password"
                    placeholder={app.translator.transText('core.forum.log_in.password_placeholder')}
                    bidi={this.password}
                    disabled={this.loading}
                />
            </div>,
            20
        );

        items.add(
            'remember',
            <div className="Form-group">
                <div>
                    <label className="checkbox">
                        <input type="checkbox" bidi={this.remember} disabled={this.loading} />
                        {app.translator.trans('core.forum.log_in.remember_me_label')}
                    </label>
                </div>
            </div>,
            10
        );

        items.add(
            'submit',
            <div className="Form-group">
                {Button.component({
                    className: 'Button Button--primary Button--block',
                    type: 'submit',
                    loading: this.loading,
                    children: app.translator.trans('core.forum.log_in.submit_button'),
                })}
            </div>,
            -10
        );

        return items;
    }

    footer() {
        return [
            <p className="LogInModal-forgotPassword">
                <a onclick={this.forgotPassword.bind(this)}>{app.translator.trans('core.forum.log_in.forgot_password_link')}</a>
            </p>,

            app.forum.attribute('allowSignUp') && (
                <p className="LogInModal-signUp">
                    {app.translator.trans('core.forum.log_in.sign_up_text', { a: <a onclick={this.signUp.bind(this)} /> })}
                </p>
            ),
        ];
    }

    /**
     * Open the forgot password modal, prefilling it with an email if the user has
     * entered one.
     *
     * @public
     */
    forgotPassword() {
        const email = this.identification();
        const props = email.indexOf('@') !== -1 ? { email } : undefined;

        app.modal.show(ForgotPasswordModal, props);
    }

    /**
     * Open the sign up modal, prefilling it with an email/username/password if
     * the user has entered one.
     *
     * @public
     */
    signUp() {
        const props = { password: this.password() };
        const identification = this.identification();
        props[identification.indexOf('@') !== -1 ? 'email' : 'username'] = identification;

        app.modal.show(SignUpModal, props);
    }

    oncreate(vnode) {
        super.oncreate(vnode);

        this.$(`[name="${this.identification() ? 'password' : 'identification'}"]`).select();
    }

    onsubmit(e) {
        e.preventDefault();

        this.loading = true;

        const identification = this.identification();
        const password = this.password();
        const remember = this.remember();

        app.session
            .login({ identification, password, remember }, { errorHandler: this.onerror.bind(this) })
            .then(() => window.location.reload(), this.loaded.bind(this));
    }

    onerror(error) {
        if (error.status === 401) {
            error.alert.attrs.children = app.translator.trans('core.forum.log_in.invalid_login_message');
        }

        super.onerror(error);
    }
}

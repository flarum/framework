import Form from 'flarum/common/components/Form';
import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import User from 'flarum/common/models/User';
import type Mithril from 'mithril';
import username from 'flarum/common/helpers/username';
import Button from 'flarum/common/components/Button';

interface DeleteUserModalAttrs extends IInternalModalAttrs {
  user: User;
}

export default class DeleteUserModal extends Modal<DeleteUserModalAttrs> {
  user!: User;
  loadingAnonymization: boolean = false;
  loadingDeletion: boolean = false;

  oninit(vnode: Mithril.Vnode<DeleteUserModalAttrs, this>) {
    super.oninit(vnode);

    this.user = this.attrs.user;
  }

  className() {
    return 'DeleteUserModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-gdpr.forum.delete_user.title', {
      username: username(this.user),
    });
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">
          <p className="helpText">
            {app.translator.trans('flarum-gdpr.forum.delete_user.text', {
              username: username(this.user),
            })}
          </p>
          <div className="Form-group">
            <Button
              className="Button Button--primary Button--block"
              onclick={() => this.defaultErasure()}
              loading={this.loading}
              disabled={this.loading}
            >
              {app.translator.trans('flarum-gdpr.forum.delete_user.modal_delete_button')}
            </Button>
          </div>
          {app.forum.attribute('erasureAnonymizationAllowed') && app.forum.attribute('erasureDeletionAllowed') && (
            <div>
              <div className="Form-group">
                <Button
                  className="Button Button--block"
                  onclick={() => this.specificErasure('anonymization')}
                  loading={this.loadingAnonymization}
                  disabled={this.loadingAnonymization}
                >
                  {app.translator.trans('flarum-gdpr.forum.process_erasure.anonymization_button')}
                </Button>
              </div>
              <div className="Form-group">
                <Button
                  className="Button Button--danger Button--block"
                  onclick={() => this.specificErasure('deletion')}
                  loading={this.loadingDeletion}
                  disabled={this.loadingDeletion}
                >
                  {app.translator.trans('flarum-gdpr.forum.process_erasure.deletion_button')}
                </Button>
              </div>
            </div>
          )}
        </Form>
      </div>
    );
  }

  defaultErasure() {
    this.loading = true;

    this.user.delete().then(
      () => {
        this.hide();
        this.loading = false;
        m.redraw();
      },
      () => {}
    );
  }

  specificErasure(mode: string) {
    if (mode === 'anonymization') {
      this.loadingAnonymization = true;
    } else {
      this.loadingDeletion = true;
    }

    this.user.delete({ gdprMode: mode }).then(
      () => {
        this.hide();
        this.loadingAnonymization = false;
        this.loadingDeletion = false;
        m.redraw();
      },
      () => []
    );
  }
}

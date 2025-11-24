import Form from 'flarum/common/components/Form';
import app from 'flarum/forum/app';
import FormModal from 'flarum/common/components/FormModal';
import Button from 'flarum/common/components/Button';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import type ErasureRequest from '../../common/models/ErasureRequest';

export default class RequestErasureModal extends FormModal {
  reason: Stream<string>;
  password: Stream<string>;
  user!: User | null;

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);

    this.reason = Stream('');
    this.password = Stream('');

    this.user = app.session.user;
  }

  className() {
    return 'RequestErasureModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-gdpr.forum.request_erasure.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <Form className="Form--centered">{this.fields().toArray()}</Form>
      </div>
    );
  }

  fields() {
    const items = new ItemList<Mithril.Children>();

    const currRequest = this.user?.erasureRequest() as ErasureRequest | null;

    if (currRequest && currRequest.status() !== 'cancelled') {
      items.add(
        'status',
        <div className="Form-group">
          <p className="helpText">{app.translator.trans(`flarum-gdpr.forum.request_erasure.status.${currRequest.status()}`)}</p>
        </div>
      );

      if (currRequest.reason()) {
        items.add(
          'reason',
          <div className="Form-group">
            <p className="helpText">{app.translator.trans('flarum-gdpr.forum.request_erasure.reason', { reason: currRequest.reason() })}</p>
          </div>
        );
      }

      items.add(
        'cancel',
        <div className="Form-group">
          {Button.component(
            {
              className: 'Button Button--primary Button--block',
              onclick: this.oncancel.bind(this),
              loading: this.loading,
            },
            app.translator.trans('flarum-gdpr.forum.request_erasure.cancel_button')
          )}
        </div>
      );
    } else {
      items.add('text', <p className="helpText">{app.translator.trans('flarum-gdpr.forum.request_erasure.text')}</p>);

      if (!app.forum.attribute('passwordlessSignUp')) {
        items.add(
          'password',
          <div className="Form-group">
            <input
              type="password"
              className="FormControl"
              bidi={this.password}
              placeholder={extractText(app.translator.trans('flarum-gdpr.forum.request_erasure.password_label'))}
            />
          </div>
        );
      }

      items.add(
        'reason',
        <div className="Form-group">
          <textarea
            className="FormControl"
            value={this.reason()}
            oninput={(e: Event) => {
              const target = e.target as HTMLTextAreaElement | null;
              if (target) {
                this.reason(target.value);
              }
            }}
            placeholder={extractText(app.translator.trans('flarum-gdpr.forum.request_erasure.reason_label'))}
            rows={6}
          ></textarea>
        </div>
      );

      items.add(
        'submit',
        <div className="Form-group">
          {Button.component(
            {
              className: 'Button Button--primary Button--block',
              type: 'submit',
              loading: this.loading,
            },
            app.translator.trans('flarum-gdpr.forum.request_erasure.request_button')
          )}
        </div>
      );
    }

    return items;
  }

  oncancel(e: Event) {
    this.loading = true;
    m.redraw();

    if (this.user) {
      const request = this.user.erasureRequest();

      app
        .request({
          method: 'POST',
          url: app.forum.attribute('apiUrl') + '/user-erasure-requests/' + request.id() + '/cancel',
        })
        .then(() => {
          this.loading = false;
          app.modal.close();
        });
    }
  }

  data() {
    // Status is set so that the proper confirmation message is displayed.
    return {
      reason: this.reason(),
    };
  }

  onsubmit(e: Event) {
    e.preventDefault();

    this.loading = true;

    app.store
      .createRecord<ErasureRequest>('user-erasure-requests')
      .save(this.data(), { meta: { password: this.password() } })
      .then((erasureRequest) => {
        if (this.user) {
          this.user.pushData({ relationships: { erasureRequest } });
        }
        this.loading = false;
        m.redraw();
      })
      .catch(() => {
        this.loading = false;
        m.redraw();
      });
  }
}

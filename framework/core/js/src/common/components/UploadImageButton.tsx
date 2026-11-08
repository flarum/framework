import app from '../app';
import Button from './Button';
import type { IButtonAttrs } from './Button';
import classList from '../utils/classList';
import type Mithril from 'mithril';
import Component from '../Component';
import Icon from './Icon';

export interface IUploadImageButtonAttrs extends IButtonAttrs {
  name: string;
  routePath: string;
  value?: string | null | (() => string | null);
  url?: string | null | (() => string | null);
  /**
   * Set when the image is set in code, such as by a theme, and can't be
   * changed here: the title of the extension that set it, or null.
   */
  lockedBy?: string | null;
}

export default class UploadImageButton<CustomAttrs extends IUploadImageButtonAttrs = IUploadImageButtonAttrs> extends Component<CustomAttrs> {
  loading = false;

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    let { name, value, url, lockedBy, ...attrs } = vnode.attrs as IButtonAttrs;

    attrs.loading = this.loading;
    attrs.className = classList(attrs.className, 'Button');

    if (typeof value === 'function') {
      value = value();
    }

    if (typeof url === 'function') {
      url = url();
    }

    if (lockedBy !== undefined) {
      return (
        <div className="UploadImageButton UploadImageButton--locked">
          {url ? (
            <div className="UploadImageButton-image">
              <img src={url} alt={name} />
            </div>
          ) : null}
          <p className="UploadImageButton-lock helpText">
            <Icon name="fas fa-lock" />{' '}
            {lockedBy
              ? app.translator.trans('core.admin.upload_image.set_by_extension_text', { extension: lockedBy })
              : app.translator.trans('core.admin.upload_image.set_in_code_text')}
          </p>
        </div>
      );
    }

    return (
      <div className="UploadImageButton">
        {value ? (
          <>
            <div className="UploadImageButton-image">
              <img src={url} alt={name} />
            </div>
            <Button onclick={this.remove.bind(this)} {...attrs}>
              {app.translator.trans('core.admin.upload_image.remove_button')}
            </Button>
          </>
        ) : (
          <Button onclick={this.upload.bind(this)} {...attrs}>
            {app.translator.trans('core.admin.upload_image.upload_button')}
          </Button>
        )}
      </div>
    );
  }

  upload() {
    if (this.loading) return;

    const $input = $('<input type="file">');

    $input
      .appendTo('body')
      .hide()
      .trigger('click')
      .on('change', (e) => {
        const body = new FormData();
        // @ts-ignore
        body.append(this.attrs.name, $(e.target)[0].files[0]);

        this.loading = true;
        m.redraw();

        app
          .request({
            method: 'POST',
            url: this.resourceUrl(),
            serialize: (raw) => raw,
            body,
          })
          .then(this.success.bind(this), this.failure.bind(this));
      });
  }

  remove() {
    this.loading = true;
    m.redraw();

    app
      .request({
        method: 'DELETE',
        url: this.resourceUrl(),
      })
      .then(this.success.bind(this), this.failure.bind(this));
  }

  resourceUrl() {
    return app.forum.attribute('apiUrl') + '/' + this.attrs.routePath;
  }

  /**
   * After a successful upload/removal, reload the page.
   */
  protected success(response: any) {
    window.location.reload();
  }

  /**
   * If upload/removal fails, stop loading.
   */
  protected failure(response: any) {
    this.loading = false;
    m.redraw();
  }
}

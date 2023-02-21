import app from '../app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import Stream from '../../common/utils/Stream';
import type AccessToken from '../../common/models/AccessToken';
import type { SaveAttributes } from '../../common/Model';
import type Mithril from 'mithril';

export interface INewAccessTokenModalAttrs extends IInternalModalAttrs {
  onsuccess: (token: AccessToken) => void;
}

export default class NewAccessTokenModal<CustomAttrs extends INewAccessTokenModalAttrs = INewAccessTokenModalAttrs> extends Modal<CustomAttrs> {
  protected titleInput = Stream('');

  className(): string {
    return 'Modal--small NewAccessTokenModal';
  }

  title(): Mithril.Children {
    return app.translator.trans('core.forum.security.new_access_token_modal.title');
  }

  content(): Mithril.Children {
    const titleLabel = app.translator.trans('core.forum.security.new_access_token_modal.title_placeholder');

    return (
      <div className="Modal-body">
        <div className="Form Form--centered">
          <div className="Form-group">
            <input type="text" className="FormControl" bidi={this.titleInput} placeholder={titleLabel} aria-label={titleLabel} />
          </div>
          <div className="Form-group">
            <Button className="Button Button--primary Button--block" type="submit" loading={this.loading}>
              {app.translator.trans('core.forum.security.new_access_token_modal.submit_button')}
            </Button>
          </div>
        </div>
      </div>
    );
  }

  submitData(): SaveAttributes {
    return {
      title: this.titleInput(),
    };
  }

  onsubmit(e: SubmitEvent) {
    super.onsubmit(e);
    e.preventDefault();

    this.loading = true;

    app.store
      .createRecord<AccessToken>('access-tokens')
      .save(this.submitData())
      .then((token) => {
        this.attrs.onsuccess(token);
        app.modal.close();
      })
      .finally(this.loaded.bind(this));
  }
}

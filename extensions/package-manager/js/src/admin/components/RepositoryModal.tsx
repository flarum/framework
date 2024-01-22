import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Mithril from 'mithril';
import app from 'flarum/admin/app';
import Select from 'flarum/common/components/Select';
import Stream from 'flarum/common/utils/Stream';
import Button from 'flarum/common/components/Button';
import { type Repository } from './ConfigureComposer';

export interface IRepositoryModalAttrs extends IInternalModalAttrs {
  onsubmit: (repository: Repository, key: string) => void;
  name?: string;
  repository?: Repository;
}

export default class RepositoryModal<CustomAttrs extends IRepositoryModalAttrs = IRepositoryModalAttrs> extends Modal<CustomAttrs> {
  protected name!: Stream<string>;
  protected repository!: Stream<Repository>;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.name = Stream(this.attrs.name || '');
    this.repository = Stream(this.attrs.repository || { type: 'composer', url: '' });
  }

  className(): string {
    return 'RepositoryModal Modal--small';
  }

  title(): Mithril.Children {
    const context = this.attrs.repository ? 'edit' : 'add';
    return app.translator.trans(`flarum-extension-manager.admin.composer.${context}_repository_label`);
  }

  content(): Mithril.Children {
    const types = {
      composer: app.translator.trans('flarum-extension-manager.admin.composer.repositories.types.composer'),
      vcs: app.translator.trans('flarum-extension-manager.admin.composer.repositories.types.vcs'),
      path: app.translator.trans('flarum-extension-manager.admin.composer.repositories.types.path'),
    };

    return (
      <div className="Modal-body">
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.add_modal.name_label')}</label>
          <input className="FormControl" bidi={this.name} />
        </div>
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.add_modal.type_label')}</label>
          <Select
            options={types}
            value={this.repository().type}
            onchange={(value: 'composer' | 'vcs' | 'path') => this.repository({ ...this.repository(), type: value })}
          />
        </div>
        <div className="Form-group">
          <label>{app.translator.trans('flarum-extension-manager.admin.composer.repositories.add_modal.url')}</label>
          <input
            className="FormControl"
            onchange={(e: Event) => this.repository({ ...this.repository(), url: (e.target as HTMLInputElement).value })}
            value={this.repository().url}
          />
        </div>
        <div className="Form-group">
          <Button className="Button Button--primary" onclick={this.submit.bind(this)}>
            {app.translator.trans('flarum-extension-manager.admin.composer.repositories.add_modal.submit_button')}
          </Button>
        </div>
      </div>
    );
  }

  submit() {
    this.attrs.onsubmit(this.repository(), this.name());
    this.hide();
  }
}

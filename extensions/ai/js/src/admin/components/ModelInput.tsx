import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import { canReload, findModel, type ModelLists, modelStatus } from '../utils/models';

export interface ModelInputAttrs extends ComponentAttrs {
  id: string;
  value: string;
  oninput: (value: string) => void;
  /** The connection whose models to offer, or null to offer none. */
  connection: string | null;
  lists: ModelLists;
  placeholder?: string;
  maxlength?: number;
  /** The model used when the field is empty, which is checked too. */
  defaultModel?: string | null;
  /**
   * A connection that isn't saved yet, whose models are listed under
   * `connection`. Listed once the field is focused, not as its settings are
   * typed in.
   */
  candidate?: { name: string; connection: object } | null;
}

/**
 * A model name, suggested from the connection's list, and still free to type,
 * so a model the provider doesn't list can be used. Says when the model is
 * deprecated or not offered.
 */
export default class ModelInput extends Component<ModelInputAttrs> {
  oncreate(vnode: Mithril.VnodeDOM<ModelInputAttrs, this>) {
    super.oncreate(vnode);

    this.load();
  }

  onupdate(vnode: Mithril.VnodeDOM<ModelInputAttrs, this>) {
    super.onupdate(vnode);

    this.load();
  }

  load(focused = false) {
    const { connection, lists, candidate } = this.attrs;

    if (!connection || lists.get(connection) || (candidate && !focused)) return;

    lists.load(connection, false, candidate || undefined);
  }

  view() {
    const { id, value, oninput, connection, lists, placeholder, maxlength, defaultModel, candidate } = this.attrs;
    const list = connection ? lists.get(connection) : undefined;
    const models = list?.models || [];
    const listId = id + '-models';
    const checked = value.trim() || defaultModel || '';
    const status = checked && list?.status === 'loaded' ? modelStatus(list.models, checked) : 'unknown';
    const retiresAt = status === 'deprecated' ? findModel(models, checked)?.retiresAt : null;

    return (
      <span className="AiModelInput">
        <span className="AiModelInput-field">
          <input
            id={id}
            className="FormControl"
            value={value}
            placeholder={placeholder}
            maxlength={maxlength}
            spellcheck={false}
            list={models.length ? listId : undefined}
            aria-invalid={status === 'missing' ? 'true' : undefined}
            oninput={(e: InputEvent) => oninput((e.target as HTMLInputElement).value)}
            onfocus={() => this.load(true)}
          />
          {!!connection &&
            (list?.status === 'loading' ? (
              <LoadingIndicator display="inline" size="small" />
            ) : (
              canReload(list) && (
                <Button
                  className="Button Button--icon Button--link"
                  icon="fas fa-rotate"
                  aria-label={app.translator.trans('flarum-ai.admin.models.refresh_button')}
                  onclick={() => lists.load(connection, true, candidate || undefined)}
                />
              )
            ))}
        </span>
        {!!models.length && (
          <datalist id={listId}>
            {models.map((model) => (
              <option value={model.id}>{model.name && model.name !== model.id ? model.name : null}</option>
            ))}
          </datalist>
        )}
        {status === 'deprecated' && (
          <span className="helpText AiModelInput-status AiModelInput-status--deprecated">
            {retiresAt
              ? app.translator.trans('flarum-ai.admin.models.retires_text', { model: checked, date: dayjs(retiresAt).format('ll') })
              : app.translator.trans('flarum-ai.admin.models.deprecated_text', { model: checked })}
          </span>
        )}
        {status === 'missing' && (
          <span className="helpText AiModelInput-status AiModelInput-status--missing">
            {app.translator.trans('flarum-ai.admin.models.not_offered_text', { model: checked, connection })}
          </span>
        )}
        {list?.status === 'failed' && (
          <span className="helpText AiModelInput-status">{app.translator.trans('flarum-ai.admin.models.load_error_text')}</span>
        )}
      </span>
    );
  }
}

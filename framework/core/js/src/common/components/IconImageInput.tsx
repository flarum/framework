import app from '../app';
import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import IconImage from './IconImage';
import classList from '../utils/classList';
import type IconImageState from '../states/IconImageState';
import type Mithril from 'mithril';

export interface IIconImageInputAttrs extends ComponentAttrs {
  state: IconImageState;
  id?: string;
}

/**
 * Choose or remove an image to show instead of a model's Font Awesome icon.
 * The change is held in `state` until the form saves it.
 */
export default class IconImageInput<CustomAttrs extends IIconImageInputAttrs = IIconImageInputAttrs> extends Component<CustomAttrs> {
  static ACCEPT = 'image/png,image/jpeg,image/gif,image/webp,image/bmp';

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { state, id, className } = vnode.attrs;
    const url = state.url();

    return (
      <div className={classList('IconImageInput', className)}>
        {url && (
          <div className="IconImageInput-preview">
            <IconImage src={url} srcset={state.srcset()} />
          </div>
        )}
        <input
          type="file"
          id={id}
          className="IconImageInput-file"
          accept={IconImageInput.ACCEPT}
          onchange={(e: Event) => {
            const input = e.target as HTMLInputElement;
            const file = input.files?.[0];

            if (file) state.set(file);

            // Clear the input so choosing the same file again still fires.
            input.value = '';
          }}
        />
        <Button className="Button" icon="fas fa-upload" onclick={() => this.choose()}>
          {url ? app.translator.trans('core.lib.icon_image_input.replace_button') : app.translator.trans('core.lib.icon_image_input.upload_button')}
        </Button>
        {url && (
          <Button className="Button Button--danger" icon="fas fa-times" onclick={() => state.remove()}>
            {app.translator.trans('core.lib.icon_image_input.remove_button')}
          </Button>
        )}
      </div>
    );
  }

  choose() {
    (this.element.querySelector('.IconImageInput-file') as HTMLInputElement).click();
  }
}

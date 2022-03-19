import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';

interface IPlaceholderAttrs extends ComponentAttrs {
  text: Mithril.Children;
}

/**
 * The `Placeholder` component displays a muted text with some call to action,
 * usually used as an empty state.
 */
export default class Placeholder<CustomAttrs extends IPlaceholderAttrs = IPlaceholderAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    return (
      <div className="Placeholder">
        <p>{this.attrs.text}</p>
      </div>
    );
  }
}

import Component from '../Component';

/**
 * The `Placeholder` component displays a muted text with some call to action,
 * usually used as an empty state.
 *
 * ### Attrs
 *
 * - `text`
 */
export default class Placeholder extends Component {
  view() {
    return (
      <div className="Placeholder">
        <p>{this.attrs.text}</p>
      </div>
    );
  }
}

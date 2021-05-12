/**
 * The `ComposerPostPreview` component renders Markdown as HTML using the
 * TextFormatter library, polling a data source for changes every 50ms. This is
 * done to prevent expensive redraws on e.g. every single keystroke, while
 * still retaining the perception of live updates for the user.
 *
 * ### Attrs
 *
 * - `composer` The state of the composer controlling this preview.
 * - `className` A CSS class for the element surrounding the preview.
 * - `surround` A callback that can execute code before and after re-render, e.g. for scroll anchoring.
 */
export default class ComposerPostPreview extends Component<import("../../common/Component").ComponentAttrs> {
    static initAttrs(attrs: any): void;
    constructor();
    updateInterval: number | undefined;
}
import Component from "../../common/Component";

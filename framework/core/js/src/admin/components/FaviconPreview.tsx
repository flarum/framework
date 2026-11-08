import app from '../../admin/app';
import Component from '../../common/Component';

/**
 * The favicon at the sizes browsers show it in tabs: 16px, and 32px on
 * high-resolution screens.
 */
export default class FaviconPreview extends Component {
  view() {
    const favicon = app.forum.attribute<string | null>('faviconUrl');

    if (!favicon) return null;

    return (
      <div className="FaviconPreview">
        {[16, 32].map((size) => (
          <span className="FaviconPreview-tab">
            <img src={favicon} alt="" width={size} height={size} />
            <span className="FaviconPreview-title">{app.forum.attribute<string>('title')}</span>
          </span>
        ))}
      </div>
    );
  }
}

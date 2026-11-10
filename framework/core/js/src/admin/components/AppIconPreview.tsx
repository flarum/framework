import app from '../../admin/app';
import Component from '../../common/Component';

/**
 * The app icon as phones show it: rounded off on iPhones and iPads, and cut
 * to a circle, the strictest of Android's shapes, on Android.
 */
export default class AppIconPreview extends Component {
  view() {
    const appleTouchIcon = app.forum.attribute<string | null>('appleTouchIconUrl');
    const maskableIcon = app.forum.attribute<string | null>('maskableIconUrl');

    return (
      <div className="AppIconPreview">
        {appleTouchIcon ? (
          <figure className="AppIconPreview-item">
            <img className="AppIconPreview-icon AppIconPreview-icon--apple" src={appleTouchIcon} alt="" width="60" height="60" />
            <figcaption>{app.translator.trans('core.admin.appearance.app_icon_preview_apple_label')}</figcaption>
          </figure>
        ) : null}
        {maskableIcon ? (
          <figure className="AppIconPreview-item">
            <img className="AppIconPreview-icon AppIconPreview-icon--android" src={maskableIcon} alt="" width="60" height="60" />
            <figcaption>{app.translator.trans('core.admin.appearance.app_icon_preview_android_label')}</figcaption>
          </figure>
        ) : null}
      </div>
    );
  }
}

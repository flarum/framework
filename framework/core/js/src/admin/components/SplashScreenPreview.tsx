import app from '../../admin/app';
import Component from '../../common/Component';
import classList from '../../common/utils/classList';
import isDark from '../../common/utils/isDark';

/**
 * The splash screen as phones show it: on iPhones and iPads, the image drawn
 * for them; on Android, the maskable app icon with the forum's name below.
 */
export default class SplashScreenPreview extends Component {
  view() {
    const splashScreen = app.forum.attribute<string | null>('splashScreenUrl');
    const maskableIcon = app.forum.attribute<string | null>('maskableIconUrl');

    if (!splashScreen || !maskableIcon) return null;

    const background = app.forum.attribute<string>('splashScreenBackground');

    return (
      <div className="SplashScreenPreview">
        <figure className="SplashScreenPreview-item">
          <img className="SplashScreenPreview-screen" src={splashScreen} alt="" width="90" height="195" loading="lazy" />
          <figcaption>{app.translator.trans('core.admin.appearance.app_icon_preview_apple_label')}</figcaption>
        </figure>
        <figure className="SplashScreenPreview-item">
          <div
            className={classList('SplashScreenPreview-screen SplashScreenPreview-screen--android', {
              'SplashScreenPreview-screen--dark': isDark(background),
            })}
            style={{ background }}
          >
            <img className="SplashScreenPreview-icon" src={maskableIcon} alt="" width="28" height="28" />
            <span className="SplashScreenPreview-name">{app.forum.attribute<string>('title')}</span>
          </div>
          <figcaption>{app.translator.trans('core.admin.appearance.app_icon_preview_android_label')}</figcaption>
        </figure>
      </div>
    );
  }
}

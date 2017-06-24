/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import flarum from 'flarum';
import Modal from 'flarum/lib/components/Modal';

export default class AddExtensionModal extends Modal {
  /**
   * @inheritdoc
   */
  className() {
    return super.className() + ' AddExtensionModal Modal--small';
  }

  /**
   * @inheritdoc
   */
  title() {
    return flarum.translator.trans('admin.add_extension.title');
  }

  /**
   * @inheritdoc
   */
  content() {
    return (
      <div className="Modal-body">
        <p>{flarum.translator.trans('admin.add_extension.temporary_text')}</p>
        <p>{flarum.translator.trans('admin.add_extension.install_text', {a: <a href="https://discuss.flarum.org/t/extensions" target="_blank"/>})}</p>
        <p>{flarum.translator.trans('admin.add_extension.developer_text', {a: <a href="http://flarum.org/docs/extend" target="_blank"/>})}</p>
      </div>
    );
  }
}

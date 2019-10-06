/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import Component from '../../common/Component';

export default class DashboardWidget extends Component {
  view() {
    return <div className={'DashboardWidget ' + this.className()}>{this.content()}</div>;
  }

  /**
   * Get the class name to apply to the widget.
   *
   * @return {String}
   */
  className() {
    return '';
  }

  /**
   * Get the content of the widget.
   *
   * @return {VirtualElement}
   */
  content() {
    return [];
  }
}

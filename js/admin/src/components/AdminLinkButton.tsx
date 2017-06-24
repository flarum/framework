/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import LinkButton from 'flarum/lib/components/LinkButton';

/**
 *
 */
export default class AdminLinkButton extends LinkButton {
  /**
   * @inheritdoc
   */
  className() {
    return super.className() + ' AdminLinkButton';
  }

  /**
   * @inheritdoc
   */
  content() {
    const content = super.content();

    content.push(<div className="AdminLinkButton-description">{this.attrs.description}</div>);

    return content;
  }
}

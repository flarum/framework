import Button from 'flarum/components/Button';
import extract from 'flarum/utils/extract';

import reply from 'flarum/mentions/utils/reply';

export default class PostQuoteButton extends Button {
  view() {
    const post = extract(this.props, 'post');
    const content = extract(this.props, 'content');

    this.props.className = 'Button PostQuoteButton';
    this.props.icon = 'quote-left';
    this.props.children = app.translator.trans('flarum-mentions.forum.post.quote_button');
    this.props.onclick = () => {
      this.hide();
      reply(post, content);
    };
    this.props.onmousedown = (e) => e.stopPropagation();

    return super.view();
  }

  config(isInitialized) {
    if (isInitialized) return;

    $(document).on('mousedown', this.hide.bind(this));
  }

  show(left, top) {
    const $this = this.$();

    $this.show()
      .css('top', top - $this.outerHeight() - 5)
      .css('left', left);
  }

  hide() {
    this.$().hide();
  }
}

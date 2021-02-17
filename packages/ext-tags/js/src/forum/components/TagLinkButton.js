import Link from 'flarum/components/Link';
import LinkButton from 'flarum/components/LinkButton';
import classList from 'flarum/utils/classList';
import tagIcon from '../../common/helpers/tagIcon';

export default class TagLinkButton extends LinkButton {
  view(vnode) {
    const tag = this.attrs.model;
    const active = this.constructor.isActive(this.attrs);
    const description = tag && tag.description();
    const className = classList([
      'TagLinkButton',
      'hasIcon',
      this.attrs.className,
      tag.isChild() && 'child',
    ]);

    return (
      <Link className={className} href={this.attrs.route}
        style={active && tag ? {color: tag.color()} : ''}
        title={description || ''}>
        {tagIcon(tag, {className: 'Button-icon'})}
        <span className="Button-label">
          {tag ? tag.name() : app.translator.trans('flarum-tags.forum.index.untagged_link')}
        </span>
      </Link>
    );
  }

  static initAttrs(attrs) {
    super.initAttrs(attrs);

    const tag = attrs.model;

    attrs.params.tags = tag ? tag.slug() : 'untagged';
    attrs.route = app.route('tag', attrs.params);
  }
}

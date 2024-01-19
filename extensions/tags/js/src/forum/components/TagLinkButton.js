import Link from 'flarum/common/components/Link';
import LinkButton from 'flarum/common/components/LinkButton';
import classList from 'flarum/common/utils/classList';
import tagIcon from '../../common/helpers/tagIcon';

export default class TagLinkButton extends LinkButton {
  view(vnode) {
    const tag = this.attrs.model;
    const description = tag && tag.description();
    const className = classList('TagLinkButton hasIcon', { child: tag.isChild() }, this.attrs.className);

    return (
      <Link className={className} href={this.attrs.route} style={tag ? { '--color': tag.color() } : undefined} title={description || undefined}>
        {tagIcon(tag, { className: 'Button-icon' })}
        <span className="Button-label">{tag ? tag.name() : app.translator.trans('flarum-tags.forum.index.untagged_link')}</span>
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

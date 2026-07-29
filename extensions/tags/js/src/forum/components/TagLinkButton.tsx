import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import LinkButton from 'flarum/common/components/LinkButton';
import classList from 'flarum/common/utils/classList';
import ItemList from 'flarum/common/utils/ItemList';
import listItems from 'flarum/common/helpers/listItems';
import type Mithril from 'mithril';

import tagIcon from '../../common/helpers/tagIcon';
import type Tag from '../../common/models/Tag';

export default class TagLinkButton extends LinkButton {
  view(vnode: Mithril.Vnode<any, this>) {
    const tag = this.attrs.model as Tag | undefined;
    const description = tag && tag.description();
    const className = classList('TagLinkButton hasIcon', { child: tag?.isChild() }, this.attrs.className);

    return (
      <Link className={className} href={this.attrs.route} style={tag ? { '--color': tag.color() } : undefined} title={description || undefined}>
        {listItems(this.linkItems(tag).toArray())}
      </Link>
    );
  }

  /**
   * Build the contents of the tag link. Exposed as an ItemList so extensions can
   * add to it (e.g. a realtime typing dot) without overriding the whole view.
   */
  linkItems(tag?: Tag): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('icon', tagIcon(tag, { className: 'Button-icon' }), 100);

    items.add('label', <span className="Button-label">{tag ? tag.name() : app.translator.trans('flarum-tags.forum.index.untagged_link')}</span>, 90);

    return items;
  }

  static initAttrs(attrs: any) {
    super.initAttrs(attrs);

    const tag = attrs.model;

    attrs.params = attrs.params || {};
    attrs.params.tags = tag ? tag.slug() : 'untagged';
    attrs.route = app.route('tag', attrs.params);
  }
}

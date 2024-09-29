import Component from 'flarum/common/Component';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import tagIcon from '../../common/helpers/tagIcon';
import classList from 'flarum/common/utils/classList';
import ItemList from 'flarum/common/utils/ItemList';

export default class TagHero extends Component {
  view() {
    const tag = this.attrs.model;
    const color = tag.color();

    return (
      <header
        className={classList('Hero', 'TagHero', { 'TagHero--colored': color, [textContrastClass(color)]: color })}
        style={color ? { '--hero-bg': color } : undefined}
      >
        <div className="container">{this.viewItems().toArray()}</div>
      </header>
    );
  }

  /**
   * @returns {ItemList}
   */
  viewItems() {
    const items = new ItemList();

    items.add('content', <div className="containerNarrow">{this.contentItems().toArray()}</div>, 80);

    return items;
  }

  /**
   * @returns {ItemList}
   */
  contentItems() {
    const items = new ItemList();
    const tag = this.attrs.model;

    items.add(
      'tag-title',
      <h1 className="Hero-title">
        {tag.icon() && tagIcon(tag, {}, { useColor: false })} {tag.name()}
      </h1>,
      100
    );

    items.add('tag-subtitle', <div className="Hero-subtitle">{tag.description()}</div>, 90);

    return items;
  }
}

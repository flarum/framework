import Component from 'flarum/common/Component';
import textContrastClass from 'flarum/common/helpers/textContrastClass';
import tagIcon from '../../common/helpers/tagIcon';
import classList from 'flarum/common/utils/classList';

export default class TagHero extends Component {
  view() {
    const tag = this.attrs.model;
    const color = tag.color();

    return (
      <header
        className={classList('Hero', 'TagHero', { 'TagHero--colored': color, [textContrastClass(color)]: color })}
        style={color ? { '--hero-bg': color } : undefined}
      >
        <div className="container">
          <div className="containerNarrow">
            <h1 className="Hero-title">
              {tag.icon() && tagIcon(tag, {}, { useColor: false })} {tag.name()}
            </h1>
            <div className="Hero-subtitle">{tag.description()}</div>
          </div>
        </div>
      </header>
    );
  }
}

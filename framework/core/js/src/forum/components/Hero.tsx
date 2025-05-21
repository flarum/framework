import app from '../app';
import Component from '../../common/Component';
import classList from '../../common/utils/classList';

import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export interface IHeroAttrs {}

export default abstract class Hero<CustomAttrs extends IHeroAttrs = IHeroAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Vnode | null {
    return (
      <header className={classList('Hero', this.className())} style={this.style() ?? undefined}>
        {this.viewItems().toArray()}
      </header>
    );
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('container', <div className="container">{this.bodyItems().toArray()}</div>, 100);

    return items;
  }

  /**
   * @example
   * ```ts
   * className(): string {
   *  return 'WelcomeHero';
   *  }
   */
  abstract className(): string;

  /**
   *
   * @example
   * ```ts
   * style(): Record<string, string> {
   *   return {
   *     backgroundColor: '#e7672e',
   *   };
   * ```
   */
  protected style(): Record<string, string> | undefined {
    return undefined;
  }

  abstract bodyItems(): ItemList<Mithril.Children>;
}

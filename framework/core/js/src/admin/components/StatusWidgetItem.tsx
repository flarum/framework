import Component, { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import Icon from '../../common/components/Icon';

export interface StatusWidgetItemAttrs extends ComponentAttrs {
  /**
   * The label/header for this status item
   */
  label: Mithril.Children;

  /**
   * The value/content to display
   */
  value: Mithril.Children;

  /**
   * Optional icon to display next to the label
   */
  icon?: string;
}

export default class StatusWidgetItem<CustomAttrs extends StatusWidgetItemAttrs = StatusWidgetItemAttrs> extends Component<CustomAttrs> {
  view() {
    return <li className={classList('StatusWidgetItem', this.attrs.className)}>{this.content()}</li>;
  }

  /**
   * Build the main content of the status item.
   * Can be overridden to completely customize the layout.
   */
  content(): Mithril.Children {
    const { icon } = this.attrs;

    return (
      <>
        {icon && <i className={icon} />}
        <div className="StatusWidgetItem-content">
          {this.labelView()}
          {this.valueView()}
        </div>
      </>
    );
  }

  /**
   * Build an ItemList of the status item's contents.
   * Extensions can easily add, remove, or modify parts.
   */
  contentItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('icon', this.iconView(), 100);
    items.add('label', this.labelView(), 90);
    items.add('value', this.valueView(), 70);

    return items;
  }

  /**
   * Render the icon (if provided).
   */
  iconView(): Mithril.Children {
    const { icon } = this.attrs;

    if (!icon) return <></>;

    return <Icon name={icon} />;
  }

  /**
   * Render the label.
   */
  labelView(): Mithril.Children {
    return <strong className="StatusWidgetItem-label">{this.attrs.label}</strong>;
  }

  /**
   * Render the value.
   */
  valueView(): Mithril.Children {
    return <span className="StatusWidgetItem-value">{this.attrs.value}</span>;
  }
}

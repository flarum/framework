import app from '../../app';
import Component, { type ComponentAttrs } from '../../../common/Component';
import Dropdown from '../../../common/components/Dropdown';
import extractText from '../../../common/utils/extractText';
import type Mithril from 'mithril';

/**
 * One toggleable column as `ColumnsDropdown` lists it.
 */
export interface ColumnsDropdownItem {
  key: string;
  name: Mithril.Children;
  visible: boolean;
}

export interface ColumnsDropdownAttrs extends ComponentAttrs {
  columns: ColumnsDropdownItem[];
  onchange: (key: string, visible: boolean) => void;
}

/**
 * A "Columns" dropdown with one checkbox per toggleable column. The menu stays open while boxes are
 * ticked.
 */
export default class ColumnsDropdown<Attrs extends ColumnsDropdownAttrs = ColumnsDropdownAttrs> extends Component<Attrs> {
  view(): Mithril.Children {
    const label = app.translator.trans('core.admin.resource.table.columns_button');

    return (
      <Dropdown
        className="ResourceTable-columns"
        buttonClassName="Button"
        icon="fas fa-columns"
        label={label}
        accessibleToggleLabel={extractText(app.translator.trans('core.admin.resource.table.columns_label'))}
      >
        {this.attrs.columns.map((column) => (
          <label className="checkbox ResourceTable-columnToggle" onclick={(e: MouseEvent) => e.stopPropagation()}>
            <input
              type="checkbox"
              name={column.key}
              checked={column.visible}
              onchange={(e: Event) => this.attrs.onchange(column.key, (e.target as HTMLInputElement).checked)}
            />
            {column.name}
          </label>
        ))}
      </Dropdown>
    );
  }
}

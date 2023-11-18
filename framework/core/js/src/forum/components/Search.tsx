import app from '../../forum/app';
import Component, { ComponentAttrs } from '../../common/Component';
import extractText from '../../common/utils/extractText';
import Input from '../../common/components/Input';
import SearchState from '../../common/states/SearchState';
import SearchModal from './SearchModal';
import type Mithril from 'mithril';

export interface SearchAttrs extends ComponentAttrs {
  /** The type of alert this is. Will be used to give the alert a class name of `Alert--{type}`. */
  state: SearchState;
}

/**
 * The `Search` component displays a menu of as-you-type results from a variety
 * of sources.
 *
 * The search box will be 'activated' if the app's search state's
 * getInitialSearch() value is a truthy value. If this is the case, an 'x'
 * button will be shown next to the search field, and clicking it will clear the search.
 *
 * ATTRS:
 *
 * - state: SearchState instance.
 */
export default class Search<T extends SearchAttrs = SearchAttrs> extends Component<T, SearchState> {
  /**
   * The instance of `SearchState` for this component.
   */
  protected searchState!: SearchState;

  oninit(vnode: Mithril.Vnode<T, this>) {
    super.oninit(vnode);

    this.searchState = this.attrs.state;
  }

  view() {
    // Hide the search view if no sources were loaded
    if (app.search.sources().isEmpty()) return <div></div>;

    const searchLabel = extractText(app.translator.trans('core.forum.header.search_placeholder'));

    return (
      <div role="search" aria-label={app.translator.trans('core.forum.header.search_role_label')}>
        <Input
          type="search"
          className="Search-input"
          clearable={this.searchState.getValue()}
          clearLabel={app.translator.trans('core.forum.header.search_clear_button_accessible_label')}
          prefixIcon="fas fa-search"
          aria-label={searchLabel}
          readonly={true}
          placeholder={searchLabel}
          value={this.searchState.getValue()}
          inputAttrs={{
            onfocus: () =>
              setTimeout(() => {
                this.$('input').blur() && app.modal.show(SearchModal, { searchState: this.searchState });
              }, 150),
          }}
          // onchange={(value: string) => this.searchState.setValue(value)}
        />
      </div>
    );
  }
}

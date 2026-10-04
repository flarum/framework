import app from 'flarum/forum/app';
import SearchModal from 'flarum/common/components/SearchModal';
import SearchState from 'flarum/common/states/SearchState';
import Button from 'flarum/common/components/Button';
import GlobalDiscussionsSearchSource from 'flarum/forum/components/GlobalDiscussionsSearchSource';
import GlobalPostsSearchSource from 'flarum/forum/components/GlobalPostsSearchSource';
import type Mithril from 'mithril';
import DeckPickerModal, { type IDeckPickerModalAttrs } from './DeckPickerModal';

export interface IDeckFilterModalAttrs extends IDeckPickerModalAttrs {
  /** Whether a query can be used, e.g. that it's filters only. */
  accept: (query: string) => boolean;
  invalidText?: Mithril.Children;
  onapply: (query: string) => void;
}

/**
 * Writing a column's filters in the forum's search modal, with its filter
 * suggestions and highlighting, and the discussions they match as a preview.
 * The result is the query itself, not one of the results.
 */
export default class DeckFilterModal<CustomAttrs extends IDeckFilterModalAttrs = IDeckFilterModalAttrs> extends DeckPickerModal<CustomAttrs> {
  protected error: Mithril.Children = null;

  /**
   * @param resource Whose filters to offer, e.g. 'discussions'.
   */
  static openFor(
    resource: string,
    attrs: {
      title: Mithril.Children;
      value: string;
      accept: (query: string) => boolean;
      invalidText?: Mithril.Children;
      onapply: (query: string) => void;
    }
  ): void {
    const searchState = new SearchState();
    searchState.setValue(attrs.value);

    const source = resource === 'posts' ? new GlobalPostsSearchSource() : new GlobalDiscussionsSearchSource();

    app.modal.show(DeckFilterModal, { ...attrs, sources: [source], searchState, onchange: () => {}, onpick: () => {} }, true);
  }

  className(): string {
    return super.className() + ' DeckFilterModal';
  }

  content(): Mithril.Children {
    return [
      super.content(),
      <div className="Modal-footer DeckFilterModal-footer">
        {this.error && <p className="DeckFilterModal-error">{this.error}</p>}
        <Button className="Button Button--primary" type="button" onclick={() => this.apply()}>
          {app.translator.trans('flarum-deck.forum.add_column.use_filters_button')}
        </Button>
      </div>,
    ];
  }

  // The picker turns these off; here they're the point.
  gambits(): JSX.Element[] {
    return SearchModal.prototype.gambits.call(this);
  }

  gambifyInput(): Mithril.Children {
    return SearchModal.prototype.gambifyInput.call(this);
  }

  /** Enter takes a highlighted suggestion, as in search, and otherwise uses the query. */
  selectResult() {
    const item = this.getItem(this.index);

    if (item.length && !item.attr('data-id')) {
      SearchModal.prototype.selectResult.call(this);
      return;
    }

    this.apply();
  }

  /** Results are only a preview of what the column will show. */
  protected pick(): void {}

  protected apply(): void {
    const query = this.query().trim();

    if (!this.attrs.accept(query)) {
      this.error = this.attrs.invalidText ?? null;
      m.redraw();
      return;
    }

    this.attrs.onapply(query);
    this.hide();
  }
}

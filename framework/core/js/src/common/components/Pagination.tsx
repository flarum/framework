import Component, { ComponentAttrs } from '../Component';
import Button from './Button';
import app from '../../admin/app';
import extractText from '../utils/extractText';

export interface IPaginationInterface extends ComponentAttrs {
  total: number;
  perPage: number;
  currentPage: number;
  loadingPageNumber?: number;
  onChange: (page: number) => void;
}

export default class Pagination<CustomAttrs extends IPaginationInterface = IPaginationInterface> extends Component<CustomAttrs> {
  view() {
    const { total, perPage, currentPage, loadingPageNumber, onChange } = this.attrs;

    const totalPageCount = Math.ceil(total / perPage);
    const moreData = totalPageCount > currentPage;

    return (
      <nav className="Pagination">
        <Button
          disabled={currentPage === 1}
          title={app.translator.trans('core.lib.pagination.first_button')}
          onclick={() => onChange(1)}
          icon="fas fa-step-backward"
          className="Button Button--icon Pagination-first"
        />
        <Button
          disabled={currentPage === 1}
          title={app.translator.trans('core.lib.pagination.back_button')}
          onclick={() => onChange(currentPage - 1)}
          icon="fas fa-chevron-left"
          className="Button Button--icon Pagination-back"
        />
        <span className="Pagination-pageNumber">
          {app.translator.trans('core.lib.pagination.page_counter', {
            // https://technology.blog.gov.uk/2020/02/24/why-the-gov-uk-design-system-team-changed-the-input-type-for-numbers/
            current: (
              <input
                type="text"
                inputmode="numeric"
                pattern="[0-9]*"
                value={loadingPageNumber ?? currentPage}
                aria-label={extractText(app.translator.trans('core.lib.pagination.go_to_page_textbox_a11y_label'))}
                autocomplete="off"
                className="FormControl Pagination-input"
                onchange={(e: InputEvent) => {
                  const target = e.target as HTMLInputElement;
                  let pageNumber = parseInt(target.value);

                  if (isNaN(pageNumber)) {
                    // Invalid value, reset to current page
                    target.value = (currentPage + 1).toString();
                    return;
                  }

                  if (pageNumber < 1) {
                    // Lower constraint
                    pageNumber = 1;
                  } else if (pageNumber > totalPageCount) {
                    // Upper constraint
                    pageNumber = totalPageCount;
                  }

                  target.value = pageNumber.toString();

                  onChange(pageNumber);
                }}
              />
            ),
            currentNum: currentPage,
            total: totalPageCount,
          })}
        </span>
        <Button
          disabled={!moreData}
          title={app.translator.trans('core.lib.pagination.next_button')}
          onclick={() => onChange(currentPage + 1)}
          icon="fas fa-chevron-right"
          className="Button Button--icon Pagination-next"
        />
        <Button
          disabled={!moreData}
          title={app.translator.trans('core.lib.pagination.last_button')}
          onclick={() => onChange(totalPageCount)}
          icon="fas fa-step-forward"
          className="Button Button--icon Pagination-last"
        />
      </nav>
    );
  }
}

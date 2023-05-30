import app from 'flarum/admin/app';
import ItemList from 'flarum/common/utils/ItemList';
import generateElementId from 'flarum/admin/utils/generateElementId';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';

import Mithril from 'mithril';
import Button from 'flarum/common/components/Button';

import dayjs from 'dayjs';
import dayjsUtc from 'dayjs/plugin/utc';

dayjs.extend(dayjsUtc);

export interface IDateSelection {
  /**
   * Timestamp (seconds, not ms) for start date
   */
  start: number;
  /**
   * Timestamp (seconds, not ms) for end date
   */
  end: number;
}

export interface IStatisticsWidgetDateSelectionModalAttrs extends IInternalModalAttrs {
  onModalSubmit: (dates: IDateSelection) => void;
  value?: IDateSelection;
}

interface IStatisticsWidgetDateSelectionModalState {
  inputs: {
    startDateVal: string;
    endDateVal: string;
  };
  ids: {
    startDate: string;
    endDate: string;
  };
}

export default class StatisticsWidgetDateSelectionModal extends Modal<IStatisticsWidgetDateSelectionModalAttrs> {
  /* @ts-expect-error core typings don't allow us to set the type of the state attr :( */
  state: IStatisticsWidgetDateSelectionModalState = {
    inputs: {
      startDateVal: dayjs().format('YYYY-MM-DD'),
      endDateVal: dayjs().format('YYYY-MM-DD'),
    },
    ids: {
      startDate: generateElementId(),
      endDate: generateElementId(),
    },
  };

  oninit(vnode: Mithril.Vnode<IStatisticsWidgetDateSelectionModalAttrs, this>) {
    super.oninit(vnode);

    if (this.attrs.value) {
      this.state.inputs = {
        startDateVal: dayjs.utc(this.attrs.value.start * 1000).format('YYYY-MM-DD'),
        endDateVal: dayjs.utc(this.attrs.value.end * 1000).format('YYYY-MM-DD'),
      };
    }
  }

  className(): string {
    return 'StatisticsWidgetDateSelectionModal Modal--small';
  }

  title(): Mithril.Children {
    return app.translator.trans('flarum-statistics.admin.date_selection_modal.title');
  }

  content(): Mithril.Children {
    return <div className="Modal-body">{this.items().toArray()}</div>;
  }

  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('intro', <p>{app.translator.trans('flarum-statistics.admin.date_selection_modal.description')}</p>, 100);

    items.add(
      'date_start',
      <div className="Form-group">
        <label htmlFor={this.state.ids.startDate}>{app.translator.trans('flarum-statistics.admin.date_selection_modal.start_date')}</label>
        <input
          type="date"
          id={this.state.ids.startDate}
          value={this.state.inputs.startDateVal}
          onchange={this.updateState('startDateVal')}
          className="FormControl"
        />
      </div>,
      90
    );

    items.add(
      'date_end',
      <div className="Form-group">
        <label htmlFor={this.state.ids.endDate}>{app.translator.trans('flarum-statistics.admin.date_selection_modal.end_date')}</label>
        <input
          type="date"
          id={this.state.ids.endDate}
          value={this.state.inputs.endDateVal}
          onchange={this.updateState('endDateVal')}
          className="FormControl"
        />
      </div>,
      80
    );

    items.add(
      'submit',
      <Button className="Button Button--primary" type="submit">
        {app.translator.trans('flarum-statistics.admin.date_selection_modal.submit_button')}
      </Button>,
      0
    );

    return items;
  }

  updateState(field: keyof IStatisticsWidgetDateSelectionModalState['inputs']): (e: InputEvent) => void {
    return (e: InputEvent) => {
      this.state.inputs[field] = (e.currentTarget as HTMLInputElement).value;
    };
  }

  submitData(): IDateSelection {
    // We force 'zulu' time (UTC)
    return {
      start: Math.floor(+dayjs.utc(this.state.inputs.startDateVal + 'Z') / 1000),
      // Ensures that the end date is the end of the day
      end: Math.floor(
        +dayjs
          .utc(this.state.inputs.endDateVal + 'Z')
          .hour(23)
          .minute(59)
          .second(59)
          .millisecond(999) / 1000
      ),
    };
  }

  onsubmit(e: SubmitEvent): void {
    e.preventDefault();

    const data = this.submitData();

    if (data.end < data.start) {
      this.alertAttrs = {
        type: 'error',
        controls: app.translator.trans('flarum-statistics.admin.date_selection_modal.errors.end_before_start'),
      };
      return;
    }

    this.attrs.onModalSubmit(data);
    this.hide();
  }
}

export interface ChartDataset {
  name: string;
  values: number[];
}

export interface ChartData {
  labels: string[];
  datasets: ChartDataset[];
}

/**
 * Stands in for frappe-charts, capturing the data the widget hands it so tests
 * can assert on what would have been plotted.
 */
export class Chart {
  static lastData: ChartData | null = null;

  parent: HTMLElement;
  data: ChartData;

  constructor(parent: HTMLElement, options: { data: ChartData }) {
    this.parent = parent;
    this.data = options.data;

    Chart.lastData = options.data;
  }

  update(data: ChartData) {
    this.data = data;

    Chart.lastData = data;
  }

  export() {}
}

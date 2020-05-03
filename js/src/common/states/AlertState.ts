import { AlertData } from '../components/Alert';

export default class AlertState {
    data: AlertData;
    key: number;

    constructor(data: AlertData, key = Date.now()) {
        this.data = data;
        this.key = key;
    }
}

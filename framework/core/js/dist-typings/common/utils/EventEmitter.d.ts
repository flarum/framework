export default class EventEmitter {
    protected events: any;
    constructor();
    on(event: string, listener: Function): EventEmitter;
    emit(event: string, ...args: any[]): void;
}

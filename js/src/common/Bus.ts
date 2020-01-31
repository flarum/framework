import forEach from 'lodash/forEach';
import get from 'lodash/get';
import set from 'lodash/set';

export default class Bus {
    subscribers = {};

    subscribe(event, callable) {
        set(this.subscribers, event + '[]', callable);
    }

    dispatch(event, args: any = null) {
        forEach(get(this.subscribers, event), function(listener) {
            listener(event, args);
        });
    }
}

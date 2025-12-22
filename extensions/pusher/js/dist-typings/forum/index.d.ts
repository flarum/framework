import Pusher, { Channel } from 'pusher-js';
export type PusherBinding = {
    channels: {
        main: Channel;
        user: Channel | null;
    };
    pusher: Pusher;
};

import { } from './bsumm'
Nova.booting((Vue, router) => {
    Vue.component('MapBox', require('./components/Card'));
    Vue.component('MapBoxDetail', require('./components/Detail'));
})

import Echo from 'laravel-echo'

window.Pusher = require('pusher-js');

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'api_key',
    wsHost: window.location.hostname,
    wsPort: 6001,
    disableStats: true,
});
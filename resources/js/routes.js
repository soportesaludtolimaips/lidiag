import Vue from 'vue';
import Route from 'vue-route';

Vue.use(Route)

export default new Route({
    routes: [
        { path: '/', component: require('./components/plantilla/Content') },
    ]
})


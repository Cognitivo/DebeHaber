/*
|--------------------------------------------------------------------------
| Laravel Spark Bootstrap
|--------------------------------------------------------------------------
|
| First, we will load all of the "core" dependencies for Spark which are
| libraries such as Vue and jQuery. This also loads the Spark helpers
| for things such as HTTP calls, forms, and form validation errors.
|
| Next, we'll create the root Vue application for Spark. This will start
| the entire application and attach it to the DOM. Of course, you may
| customize this script as you desire and load your own components.
|
*/

//Base Components
require("spark-bootstrap");
require("./components/bootstrap");
require("vue-tour/dist/vue-tour.css");

import Vue from "vue";
import BootstrapVue from "bootstrap-vue";
import VueRouter from "vue-router";
import Router from "./router";
import i18n from "./plugins/i18n";
import VueGoogleCharts from "vue-google-charts";
import VueSweetalert2 from "vue-sweetalert2";
import VueMask from "v-mask";
import VueSnackbar from "vue-snack";
import "vue-snack/dist/vue-snack.min.css";

Vue.use(VueSnackbar);
Vue.use(VueMask);
Vue.use(VueSweetalert2);
Vue.use(VueGoogleCharts);
Vue.use(BootstrapVue);
Vue.use(VueRouter);
Vue.use(require("vue-shortkey"));

Vue.config.productionTip = false;

Spark.forms.register = {
    language: "en"
};

const router = new VueRouter({
    mode: "history",
    routes: Router
});

const app = new Vue({
    i18n,
    router,
    mixins: [require("spark")]
});

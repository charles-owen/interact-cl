import InteractClosedVue from './Vue/InteractClosed.vue';
import InteractMainVue from './Vue/InteractMain.vue';

const VueHelper = Site.VueHelper

/**
 * Interact when presented as an element on an existing page.
 *
 * See InteractPageView for presentation as a complete page.
 * @param {Site} site
 * @param {Interact} interact Interact object
 * @param {Element} element
 * @constructor
 */
export const InteractView = function(site, interact, element) {

    let template = `<div id="cl-interact"><component :is="current" @open="open" :data="data"></component></div>`;

    const data = JSON.parse(element.textContent);

    const store = site.store;

    const app = VueHelper.createApp({
        data: function () {
            return {
                data: data,
                current: 'interact-closed'
            }
        },
        components: {
            interactClosed: InteractClosedVue,
            interactMain: InteractMainVue
        },
        template: template,
        created() {
            if(this.data.open) {
                this.open();
            }
        },
        methods: {
            open() {
                this.current = 'interact-main';
            }
        }
    })

    app.config.globalProperties.$site = site
    app.config.globalProperties.$interact = interact
    app.use(store)
    VueHelper.mount(app, element)
}

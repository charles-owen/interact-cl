import InteractClosedVue from './Vue/InteractClosed.vue';
import InteractMainVue from './Vue/InteractMain.vue';

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

    new site.Vue({
        el: element,
	    site,
        store,
	    interact,
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
}

import InteractClosedVue from './Vue/InteractClosed.vue';
import InteractMainVue from './Vue/InteractMain.vue';


export const InteractView = function(site, element) {

    let template = `<div><component :is="current" @open="open" :data="data"></component></div>`;

    const data = JSON.parse(element.textContent);

    const store = site.store;

    new site.Vue({
        el: element,
        store,
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

import InteractMainVue from './Vue/InteractMain.vue';
import { createRouter, createWebHistory } from 'vue-router'

const VueHelper = Site.VueHelper

/**
 * Interact when presented as a complete Vue-based page.
 *
 * This version includes the header and footer and supports routing.
 * See InteractView for Interact added to a page.
 * @param {Site} site
 * @param {Interact} interact Interact object
 * @param {Element} element
 * @constructor
 */
export const InteractPageView = function(site, interact, element) {
	let template = `<div id="cl-interact" class="cl-interact-page"><site-header title="Interact!"></site-header>
<div class="content"></div>
<router-view :data="data"></router-view>
<site-footer></site-footer>
</div>`;

	const data = JSON.parse(element.textContent);

	const store = site.store;

	const Header = site.info.header.component();
	const Footer = site.info.footer.component();

	const routes = [
		{path: site.root + '/cl/interact', component: InteractMainVue},
		{path: site.root + '/cl/interact/:id', component: InteractMainVue, props: true}
	];

	const router = createRouter({
		history: createWebHistory(),
		routes: routes,
	})

	const app = VueHelper.createApp({
		data: function () {
			return {
				data: data
			}
		},
		components: {
			'site-header': Header,
			'site-footer': Footer
		},
		template: template,
		methods: {

		}
	})

	app.config.globalProperties.$site = site
	app.config.globalProperties.$interact = interact
	app.use(store)
	app.use(router)
	VueHelper.mount(app, element)
}
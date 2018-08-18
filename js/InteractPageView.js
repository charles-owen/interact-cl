import InteractMainVue from './Vue/InteractMain.vue';

export const InteractPageView = function(site, element) {
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

	const router = new Site.VueRouter({
		routes: routes,
		mode: 'history'
	})

	new site.Vue({
		el: element,
		store,
		router,
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
		created() {
		},
		methods: {

		}
	})
}
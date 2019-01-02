<template>
  <div class="content cl-autoanswer">
    <p>This page allows for testing of autoanswer responses.</p>
    <h3>Interaction</h3>
    <interact-editor v-model="text"></interact-editor>
    <br><button @click.prevent="submit">Submit</button>
    <h3>Response</h3>
    <div class="cl-response" v-html="response"></div>
  </div>
</template>

<script>
	import EditorVue from '../Vue/Editor.vue';

	const UserVueBase = Site.UserVueBase;

	export default {
		'extends': UserVueBase,
		props: ['json'],
		data: function () {
			return {
				text: '',
				response: '<p class="center">Enter a message to see the response</p>'
			}
		},
		components: {
			interactEditor: EditorVue
		},
		mounted() {
			this.setTitle('Vinnie Vue');
		},
		methods: {
			submit() {
				console.log('submit');

				let params = {
					text: this.text
				}

				this.$site.api.get('/api/interact/autoanswer', params)
					.then((response) => {
						if (!response.hasError()) {
							this.response = response.getData('autoanswer').attributes;
						} else {
							Site.toast(this, response);
						}

					})
					.catch((error) => {
						Site.toast(this, error);
					});
			}
		}
	}
</script>
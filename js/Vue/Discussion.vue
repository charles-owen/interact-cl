<template>
  <div class="cl-discuss">
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <cl-menu v-if="staff || self">
      <a><img :src="root + '/vendor/cl/site/img/menubars.png'"></a>
      <ul>
        <li><a @click.prevent="editMe"><img :src="root + '/vendor/cl/site/img/pen16.png'"> Edit</a></li>
        <li><a @click.prevent="deleteMe"><img :src="root + '/vendor/cl/site/img/x.png'"> Delete</a></li>
        <li v-if="staff"><a @click.prevent="endorse"><img :src="root + '/vendor/cl/interact/img/check16.png'"> Endorse</a>
        </li>
      </ul>
    </cl-menu>
      <h4>
        <span class="time">{{date}}</span>{{discussion.by}}
        <span v-if="endorsements !== null" class="cl-endorse"><img :src="root + '/vendor/cl/site/img/check.png'">{{endorsements}}</span>
      </h4>
      <div ref="message" v-if="!editing" v-html="displayMessage"></div>
      <form v-else method="post" @submit.prevent="submit">
        <interact-editor v-model="message"></interact-editor>
        <p><input type="submit" value="Post"> <input type="button" value="Cancel" @click.prevent="cancel"></p>
      </form>
    <div class="cl-history" v-for="history in discussion.history" :key="history.time">{{showHistory(history)}}</div>
  </div>

</template>


<script>
	/**
   * Discussion Vue component
   *
   * Presents a single discussion item with editing options
   * @constructor DiscussionVue
   */
  import Dialog from 'dialog-cl';
	import {TimeFormatter} from 'site-cl/js/TimeFormatter';
	import MenuVue from 'site-cl/js/UI/Menu.vue'
	import {Member} from 'course-cl/js/Members/Member';
	import {Interaction} from '../Models/Interaction';
	import EditorVue from './Editor.vue';
	import MaskVue from 'site-cl/js/Vue/Mask.vue';

	export default {
		props: ['discussion'],
		data: function () {
			return {
				root: Site.root,
				date: '',
				user: this.$store.state.user.user,
				staff: false,
				self: false,
				editing: false,
				message: '',
				mask: false,
        endorsements: null,
        displayMessage: ''
			}
		},
		components: {
			interactEditor: EditorVue,
			clMenu: MenuVue,
			maskVue: MaskVue
		},
		mounted() {
			this.staff = this.user.atLeast(Member.STAFF);
			this.take();
		},
		methods: {
			take() {
        this.self = this.discussion.by === 'Me';
        this.date = TimeFormatter.relativeUNIX(this.discussion.time, null, 'ddd, M-DD-YYYY h:mm:ssa');
        if(this.discussion.endorse !== undefined && this.discussion.endorse.length > 0) {
        	let msg = '';
	        const roles = Member.prototype.getRoles();

	        for(let endorse of this.discussion.endorse) {
        		if(msg.length > 0) {
        			msg += ' and ';
            }

	          let roleName = roles[endorse.role].name;
        		if(roleName === 'Admin') {
        			roleName = 'Instructor';
            }

            msg += endorse.name + ', ' + roleName;
          }

          if(this.discussion.endorse.length > 1) {
        		msg += ' endorse';
          } else {
        		msg += ' endorses';
          }

        	this.endorsements = msg + ' this contribution!';
        } else {
        	this.endorsements = null;
        }

		  //
		  // Find the @ links and wrap them in an a tag
		  //
		  const re = /@([0-9]+)/g;
		  this.displayMessage = this.discussion.message.replace(re, '<a class="cl-interact-link" data-value="$1">$&</a>')

		  this.$nextTick(function() {
			  const elements = this.$refs.message.querySelectorAll('a.cl-interact-link');
			  for(let element of elements) {
				  element.onclick = (event) => {
					  this.$emit('select', event.target.dataset.value);
				  }
			  }

		  });

      },
			deleteMe() {
				new Dialog.MessageBox('Are you sure?', 'Are you sure you want to delete this discussion?',
					Dialog.MessageBox.OKCANCEL, () => {
						Site.api.post('/api/interact/discussion/' + this.discussion.id + '/delete', {})
							.then((response) => {
								if (!response.hasError()) {
									const interaction = new Interaction(response.getData('interaction').attributes);
									this.$emit('reloaded', interaction);
								} else {
									Site.toast(this, response);
								}

							})
							.catch((error) => {
								Site.toast(this, error);
							});
					});

			},
			editMe() {
				this.message = this.discussion.message;
				this.editing = true;
			},
			cancel() {
				this.editing = false;
			},
			submit() {
        this.mask = true;

        let params = {
          message: this.message
        }
        Site.api.post('/api/interact/discussion/' + this.discussion.id + '/edit', params)
            .then((response) => {
                this.mask = false;
                if (!response.hasError()) {
                	this.editing = false;
	                const interaction = new Interaction(response.getData('interaction').attributes);
	                this.$emit('reloaded', interaction);
                } else {
                    Site.toast(this, response);
                }

            })
            .catch((error) => {
        	      this.mask = false;
                Site.toast(this, error);
            });

			},
      showHistory(history) {
				if(history.op === 'edit') {
			    const time = TimeFormatter.relativeUNIX(history.time, null, 'ddd, M-DD-YYYY h:mm:ssa');
					return `Edited ${time} by ${history.by}`;
        }
      },
      endorse() {
	      Site.api.post('/api/interact/discussion/' + this.discussion.id + '/endorse', {})
		      .then((response) => {
			      if (!response.hasError()) {
			      	console.log(response);
				      const interaction = new Interaction(response.getData('interaction').attributes);
				      this.$emit('reloaded', interaction);
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
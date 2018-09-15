<template>
  <div :class="cls">
    <h2 class="cl-banner"><button class="cl-start" @click.prevent="click"><img :src="root + '/vendor/cl/interact/img/logo16.png'" width="16" height="16"> Interact!</button>
      <span class="cl-info">{{info}}</span>
      <span class="cl-message"></span></h2>
  </div>
</template>

<script>
	import {Member} from 'course-cl/js/Members/Member';

  export default {
  	props: ['data'],
      data: function() {
          return {
              root: Site.root,
   	          cls: 'cl-interact',
              info: ''
          }
      },
      mounted() {
	      const user = this.$store.state.user.user;
	      if(user.atLeast(Member.STAFF)) {
		      this.cls += ' cl-staff';
	      }

  		  if(this.data.categories.length === 1) {
          let params = {
            assign: this.data.categories[0].tag,
            section: this.data.section
          };

          Site.api.get('/api/interact/summaries/stats', params)
              .then((response) => {
                  if (!response.hasError()) {
                  	const stats = response.getData('interact-stats').attributes;
                  	this.info = '';
                  	if(stats.questions > 0) {
                  		this.info = stats.questions + (stats.questions === 1 ? ' question' : ' questions');
                    }

                    if(stats.announcements > 0) {
                  		if(this.info.length > 0) {
                  			this.info += ', ';
                      }

                      this.info += stats.announcements + (stats.announcements === 1 ? ' announcement' : ' announcements');
                    }

                  } else {
                      Site.toast(this, response);
                  }

              })
              .catch((error) => {
                  Site.toast(this, error);
              });
        }
      },
      methods: {
          click() {
              this.$emit('open');
          }
      }
  }
</script>
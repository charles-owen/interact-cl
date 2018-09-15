<template>
  <div class="cl-discussions">
    <h3>DISCUSSION</h3>
    <discussion v-for="discussion in interaction.discussions" :key="discussion.id"
              :interaction="interaction" :discussion="discussion" @reloaded="reloaded" @select="select"></discussion>

    <template v-if="!closed">
      <new-discussion :data="data" :interaction="interaction" @reloaded="reloaded"></new-discussion>
      <p v-if="interactives.length > 0" class=""><span v-html="display(interactives)"></span></p>
    </template>
  </div>
</template>

<script>
    import NewDiscussionVue from './NewDiscussion.vue';
    import DiscussionVue from './Discussion.vue';
    import {Member} from 'course-cl/js/Members/Member';
    import {Interaction} from '../Models/Interaction';

    export default {
        props: ['data', 'interaction', 'interactives'],
        data: function () {
            return {
                root: Site.root,
                closed: false
            }
        },
        watch: {
        	  interaction() {
        	  	this.take();
            }
        },
        components: {
            newDiscussion: NewDiscussionVue,
            discussion: DiscussionVue
        },
        mounted() {
        	  this.take();
        },
        methods: {
        	take() {
	          this.closed = this.interaction.state === Interaction.CLOSED;
          },
            reloaded(interaction) {
            	this.$emit('reloaded', interaction);
            },
            select(id) {
            	this.$emit('select', id);
            },
            display(interactives) {
	            const roles = Member.prototype.getRoles();

	            let ret = '';

	            for(const active of interactives) {
	            	if(ret !== '') {
	            		ret += ' and ';
                }
	              const roleName = roles[active.role].name;
	              if(active.name !== null) {
		              ret += active.name + ', ' + roleName;
	              } else {
		              ret += roleName;
	              }
              }

              if(interactives.length > 1) {
	              ret += ' are discussing';
              } else {
	              ret += ' is discussing';
              }

              return ret;
            }
        }
    }

</script>
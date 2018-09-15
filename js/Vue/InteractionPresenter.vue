<template>
  <div v-if="interaction !== null">
    <interaction :data="data" :interaction="interaction" @reloaded="reloaded" @deleted="deleted" @select="select"></interaction>
    <discussions :data="data" :interaction="interaction" :interactives="interactives" @reloaded="reloaded" @select="select"></discussions>
  </div>
</template>

<script>
  /**
   * The right side/interaction view
   * @constructor InteractPresenterVue
   */
  import {Interaction} from '../Models/Interaction';
  import DiscussionsVue from './Discussions.vue';
  import InteractionVue from './Interaction.vue';


  export default {
      props: ['selected', 'data'],
      data: function() {
          return {
              root: this.$site.root,
              interaction: null,
              interactives: []
          }
      },
      watch: {
	      selected: function() {
              this.fetch();
              this.interactives = [];
          }
      },
      components: {
          interaction: InteractionVue,
          discussions: DiscussionsVue
      },
      mounted() {
          this.fetch();
          this.$interact.presenter = this;
      },
      beforeDestroy() {
      	  this.$interact.presenter = null;
      },
      methods: {
          fetch() {
              if(this.selected === 0) {
	                this.interaction = null;
                  return;
              }

              this.$site.api.get('/api/interact/interaction/' + this.selected, {})
                  .then((response) => {
                      if (!response.hasError()) {
                      	this.take(response);
                      } else {
                      	this.interaction = null;
	                      this.$site.toast(this, response);
                      }

                  })
                  .catch((error) => {
              	    this.interaction = null;
	                  this.$site.toast(this, error);
                  });
          },
          take(response) {
          	const data = response.getData('interaction');
          	if(data !== null) {
	            this.interaction = new Interaction(response.getData('interaction').attributes);
	            this.$emit('reloaded', this.interaction);
            }
          },
          deleted(interaction) {
          	  this.$emit('deleted', interaction);
          },
          reloaded(interaction) {
          	this.interaction = interaction;
          	this.$emit('reloaded', interaction);
          },
          select(id) {
          	this.$emit('select', id);
          },
          prePolling(params) {
            if(this.interaction !== null) {
            	let query = {id: this.selected, after: this.interaction.time};
	            params.interaction = query;
            }
          },
          postPolling(response) {
            this.take(response);
            const data = response.getData('interactives');

            if(data !== null) {
            	this.interactives = data.attributes;
            	//console.log(this.interactives);
            } else {
            	this.interactives = [];
            }
          }
      }
  }

</script>



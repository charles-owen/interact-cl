<template>
  <div v-if="interaction !== null">
    <interaction :data="data" :interaction="interaction" @reloaded="reloaded" @deleted="deleted" @select="select"></interaction>
    <discussions :interaction="interaction" @reloaded="reloaded" @select="select"></discussions>
  </div>
</template>

<script>
    /**
     * @file
     * The right side/interaction view
     */
  import {Interaction} from '../Models/Interaction';
  import DiscussionsVue from './Discussions.vue';
  import InteractionVue from './Interaction.vue';


  export default {
      props: ['selected', 'data'],
      data: function() {
          return {
              root: Site.root,
              interaction: null
          }
      },
      watch: {
	      selected: function() {
              this.fetch();
          }
      },
      components: {
          interaction: InteractionVue,
          discussions: DiscussionsVue
      },
      mounted() {
          this.fetch();
      },
      methods: {
          fetch() {
              if(this.selected === 0) {
	                this.interaction = null;
                  return;
              }

              Site.api.get('/api/interact/interaction/' + this.selected, {})
                  .then((response) => {
                      if (!response.hasError()) {
                         this.interaction = new Interaction(response.getData('interaction').attributes);
                         this.$emit('reloaded', this.interaction);
                      } else {
	                        this.interaction = null;
                          Site.toast(this, response);
                      }

                  })
                  .catch((error) => {
              	    this.interaction = null;
                    Site.toast(this, error);
                  });
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
          }
      }
  }

</script>



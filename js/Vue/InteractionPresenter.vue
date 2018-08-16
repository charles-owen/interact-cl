<template>
  <div v-if="interaction !== null">
    <interaction :interaction="interaction"></interaction>
    <discussions :interaction="interaction"></discussions>
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
      props: ['summary'],
      data: function() {
          return {
              root: Site.root,
              interaction: null
          }
      },
      watch: {
          summary: function() {
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
              this.interaction = null;

              if(this.summary === null) {
                  return;
              }

              let params = {

              }
              Site.api.get('/api/interact/interaction/' + this.summary.id, params)
                  .then((response) => {
                      if (!response.hasError()) {
                         this.interaction = new Interaction(response.getData('interaction').attributes);

                         console.log(this.interaction);

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



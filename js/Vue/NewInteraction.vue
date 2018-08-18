<template>
  <div class="cl-interact-form" >
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <h3 class="cl-interaction-heading">New Interaction</h3>
    <interaction-form :data="data" @submit="submit" @cancel="cancel"></interaction-form>
  </div>
</template>

<script>
  import {Interaction} from '../Models/Interaction';
  import {Member} from 'course-cl/js/Members/Member';
  import MaskVue from 'site-cl/js/Vue/Mask.vue';
  import InteractionFormVue from './InteractionForm.vue';

  export default {
      props: ['data'],
      data: function() {
          return {
            mask: false
          }
      },
      components: {
          interactionForm: InteractionFormVue,
          maskVue: MaskVue
      },
      methods: {
          submit(data) {
              this.mask = true;
              Site.api.post('/api/interact/interaction', data)
                  .then((response) => {
                      this.mask = false;

                      if (!response.hasError()) {
                          const interaction = response.getData('interaction').attributes;
                          this.$emit('interaction', interaction);
                      } else {
                          Site.toast(this, response);
                      }

                  })
                  .catch((error) => {
                      this.mask = false;
                      Site.toast(this, error);
                  });
          },
          cancel() {
            this.$emit('cancel');
          }
      }
  }
</script>
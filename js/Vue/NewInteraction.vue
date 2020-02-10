<template>
  <div class="cl-interact-form" >
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <h3 class="cl-interaction-heading">New Interaction</h3>
    <interaction-form :data="data" @submit="submit" @cancel="cancel"></interaction-form>
  </div>
</template>

<script>
  import {Interaction} from '../Models/Interaction';
  import InteractionFormVue from './InteractionForm.vue';

  const Member = Site.Member;

  export default {
    props: ['data'],
    data: function () {
      return {
        mask: false
      }
    },
    components: {
      interactionForm: InteractionFormVue,
      maskVue: Site.MaskVue
    },
    methods: {
      submit(data) {
        this.mask = true;
        this.$site.api.post('/api/interact/interaction', data)
                .then((response) => {
                  this.mask = false;

                  if (!response.hasError()) {
                    const interaction = response.getData('interaction').attributes;
                    this.$emit('interaction', interaction);
                  } else {
                    this.$site.toast(this, response);
                  }

                })
                .catch((error) => {
                  this.mask = false;
                  this.$site.toast(this, error);
                });
      },
      cancel() {
        this.$emit('cancel');
      }
    }
  }
</script>
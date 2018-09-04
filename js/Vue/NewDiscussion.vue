<template>
  <div class="cl-discuss-form">
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <h4 class="discuss-heading">Contribute to the discussion</h4>
    <form method="post" @submit.prevent="submit">
      <component ref="editor" :is="editor" v-model="text" :discussion="true" :canned="data.interact.canned"></component>
      <p><input type="submit" value="Post"></p>
    </form>
  </div>
</template>

<script>
  import EditorVue from './Editor.vue';
  import MaskVue from 'site-cl/js/Vue/Mask.vue';
  import {Interaction} from '../Models/Interaction';

  export default {
      props: ['data', 'interaction'],
      data: function() {
          return {
              text: '',
              mask: false,
              editor: EditorVue
          }
      },
      components: {
          maskVue: MaskVue
      },
      mounted() {
      },
      methods: {
          submit() {
              let params = {
                message: this.text
              }

              this.mask = true;

              Site.api.post('/api/interact/discuss/' + this.interaction.id, params)
                  .then((response) => {
                      this.mask = false;
                      if (!response.hasError()) {
  	                      const interaction = new Interaction(response.getData('interaction').attributes);
                          this.$emit('reloaded', interaction);
                          this.text = '';
                          this.$refs.editor.reset();
                      } else {
                          Site.toast(this, response);
                      }

                  })
                  .catch((error) => {
                      this.mask = false;
                      Site.toast(this, error);
                  });

          }
      }
  }


</script>
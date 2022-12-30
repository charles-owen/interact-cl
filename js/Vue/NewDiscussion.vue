<template>
  <div class="cl-discuss-form">
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <h4>Contribute to the discussion</h4>
    <form method="post" @submit.prevent="submit">
      <editor ref="editor" v-model="text" :discussion="true" :canned="data.interact.canned"></editor>
      <p><input type="submit" value="Post"></p>
    </form>
  </div>
</template>

<script>

import EditorVue from './Editor.vue';
import {Interaction} from '../Models/Interaction';

  /**
   * A view window for new discussions on an interaction.
   *
   * Displays the editor and sends new discussions to the server.
   *
   * @constructor NewDiscussionVue
   */
  export default {
    props: ['data', 'interaction'],
    emits: ['relaoded'],
    data: function () {
      return {
        text: '',
        mask: false,
        active: false,
        activeId: 0
      }
    },
    watch: {
      interaction() {
        // If we change to a different interaction,
        // we need to reset the editor.
        if (+this.interaction.id !== this.activeId) {
          this.activeId = +this.interaction.id;
          this.text = '';
          this.$refs.editor.reset();
          if (this.active) {
            this.active = false;
            this.$interact.setActive(null);
          }
        }
      },
      /// Called whenever text is added to this.text. Basically
      /// whenever a user types a key
      text() {
        if(!this.active) {
          // If we are not active and have added some text, we become active
          if (this.text.length > 0) {
            this.active = true;
            this.$interact.setActive(this.interaction.id);
          }
        } else {
          // If we are active and have deleted all entered text, we become inactive
          if(this.text.length === 0) {
            this.active = false;
            this.$interact.setActive(null);
          }
        }

      }
    },
    components: {
      maskVue: Site.MaskVue,
      editor: EditorVue
    },
    mounted() {
      this.activeId = +this.interaction.id;
    },
    beforeUnmount() {
      this.$interact.setActive(null);
    },
    methods: {
      submit() {
        let params = {
          message: this.text
        }

        this.mask = true;

        this.$site.api.post('/api/interact/discuss/' + this.interaction.id, params)
                .then((response) => {
                  this.mask = false;
                  if (!response.hasError()) {
                    const interaction = new Interaction(response.getData('interaction').attributes);
                    this.$emit('reloaded', interaction);
                    this.text = '';
                    this.active = false;
                    this.$interact.setActive(null);
                    this.$refs.editor.reset();
                    this.text = ''
                  } else {
                    console.log('error')
                    this.$site.toast(this, response);
                  }

                })
                .catch((error) => {
                  this.mask = false;
                  this.$site.toast(this, error);
                });

      }
    }
  }


</script>
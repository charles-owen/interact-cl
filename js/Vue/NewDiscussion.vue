<template>
  <div class="cl-discuss-form">
    <mask-vue :mask="mask">Communicating with server...</mask-vue>
    <h4>Contribute to the discussion</h4>
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
              editor: EditorVue,
              active: false
          }
      },
      watch: {
      	  interaction() {
      	  	  this.text = '';
	            this.$refs.editor.reset();
      	  	  if(this.active) {
      	  	  	this.active = false;
		            this.$interact.setActive(null);
              }
          },
      	  text() {
      	  	if(!this.active && this.text.length > 0) {
      	  		this.active = true;
      	  		this.$interact.setActive(this.interaction.id);
            }
          }
      },
      components: {
          maskVue: MaskVue
      },
      mounted() {
      },
      beforeDestroy() {
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
                          this.$refs.editor.reset();
                      } else {
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
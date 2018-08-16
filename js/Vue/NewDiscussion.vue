<template>
  <div class="cl-discuss-form">
    <h4 class="discuss-heading">Contribute to the discussion</h4>
    <form method="post" @submit.prevent="submit">
      <interact-editor v-model="text" :discussion="true"></interact-editor>
      <p><input type="submit" value="Post"></p>
    </form>
  </div>
</template>

<script>
  import EditorVue from './Editor.vue';

  export default {
      props: ['interaction'],
      data: function() {
          return {
              text: ''
          }
      },
      components: {
          interactEditor: EditorVue
      },
      methods: {
          submit() {
              let params = {
                message: this.text
              }
              Site.api.post('/api/interact/discuss/' + this.interaction.id, params)
                  .then((response) => {
                      if (!response.hasError()) {
              console.log(response);
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
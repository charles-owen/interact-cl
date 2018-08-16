<template>
  <div >
    <div :class="mask"><p>Communicating with server...</p></div>
    <h3 class="cl-interaction-heading">New Interaction</h3>

    <form action="post" @submit.prevent="submit">
      <div class="cl-options">
        <div>
          <label><input type="radio" v-model="interactionType" :value="question"> Question</label>
          <label><input type="radio" v-model="interactionType" :value="announcement"> Announcement</label>
        </div>
        <div>
          <label v-if="staff"><input type="checkbox" v-model="pin"> Pin</label>
          <label><input type="checkbox" v-model="private"> Private (only staff can see)</label>
        </div>
        <div v-if="staff">
          <label><input type="checkbox" v-model="sendall"> Email to all users (staff only)</label>
        </div>
      </div>

      <p class="cl-summary"><label>{{typeName}} Summary:
        <input ref="summary" v-model="summary" class="summary" type="text" :placeholder="'A short summary of your ' + typeName.toLowerCase()" value=""></label></p>
      <interact-editor :prompt="typeName + ':'" v-model="text"></interact-editor>
      <p><input type="submit" value="Post"> <input type="button" value="Cancel" @click.prevent="cancel">
        <label>Category: <select v-model="category">
          <option v-for="category in data.categories" :value="category.tag" :key="category.tag">{{category.name}}</option>
        </select></label>
      </p>
    </form>

  </div>
</template>

<script>
  import EditorVue from './Editor.vue';
  import {Interaction} from '../Models/Interaction';
  import {Member} from 'course-cl/js/Members/Member';

  export default {
      props: ['data'],
      data: function() {
          return {
            question: Interaction.QUESTION,
            announcement: Interaction.ANNOUNCEMENT,
            user: null,
            staff: false,
            mask: 'cl-mask',

            interactionType: Interaction.QUESTION,
            pin: false,
            'private': false,
            sendall: false,
            category: 'general',
            summary: '',
            text: ''
          }
      },
      computed: {
          typeName: function() {
              return this.interactionType === Interaction.ANNOUNCEMENT ?
                  'Announcement' : 'Question';
          }
      },
      components: {
          interactEditor: EditorVue
      },
      mounted() {
          this.user = this.$store.state.user.user;
          this.staff = this.user.atLeast(Member.STAFF);
      },
      methods: {
          submit() {
              let summary = this.summary.trim();
              if(summary.length === 0) {
                  Site.toast(this, 'Must provide a ' + this.typeName.toLowerCase() + ' summary');
                  this.$refs['summary'].focus();
                  return;
              }

              if(this.text.trim().length === 0) {
                  Site.toast(this, 'Must provide a ' + this.typeName.toLowerCase());
                  return;
              }

              const data = {
                  type: this.interactionType,
                  assign: this.category,
                  section: null,
                  summary: summary,
                  message: this.text
              }

              if(this.pin) {
                  data.pin = true;
              }

              if(this.private) {
                  data.private = true;
              }

              if(this.sendall) {
                  data.sendall = true;
              }

              this.mask = 'cl-mask mask';
              Site.api.post('/api/interact/interaction', data)
                  .then((response) => {
                      this.mask = 'cl-mask';

                      if (!response.hasError()) {
              console.log(response);
                      } else {
                          Site.toast(this, response);
                      }

                  })
                  .catch((error) => {
                      Site.toast(this, error);
                  });
              console.log(data);
          },
          cancel() {
            this.$emit('cancel');
          }
      }
  }
</script>
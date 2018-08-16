<template>
  <div class="cl-interact">
    <form class="search">
      <h2 class="banner"><button class="ask" type="button" @click.prevent="ask"><img :src="root + '/vendor/cl/interact/img/logo16.png'" width="16" height="16"> ¿Ask a Question?</button> Interact!
        <span class="message"></span><span class="search"><input type="text" placeholder="Search..." name="search"><button type="submit">Search</button></span>
      </h2>
    </form>
    <div class="cl-interact-body">
      <interactions :summaries="summaries" :selected="selected" @select="select"></interactions>
      <div class="cl-interaction-presenter">
        <new-interaction v-if="composing" :data="data" @cancel="cancelComposing"></new-interaction>
        <template v-else>
          <welcome v-if="selected === null"></welcome>
          <interaction-view v-else :summary="selected"></interaction-view>
        </template>
      </div>
    </div>
  </div>
</template>

<script>
  import InteractionsVue from './Interactions.vue';
  import InteractionPresenterVue from './InteractionPresenter.vue';
  import NewInteractionVue from './NewInteraction.vue';
  import WelcomeVue from './Welcome.vue';
  import {Summaries} from '../Models/Summaries';

  export default {
      props: ['data'],
      data: function() {
          return {
              root: Site.root,
              summaries: new Summaries(),
              selected: null,     // Currently selected interaction
              composing: false   // True if we are entering a new interaction
          }
      },
      components: {
          interactions: InteractionsVue,
          interactionView: InteractionPresenterVue,
          newInteraction: NewInteractionVue,
          welcome: WelcomeVue
      },
      created() {
      },
      mounted() {
        this.summaries.fetch();
      },
      methods: {
          ask() {
              this.selected = null;
              this.composing = true;
          },
          cancelComposing() {
              this.compsing = false;
          },
          select(item) {
              if(this.composing) {
                  return;
              }

              this.selected = item;
          }
      }
  }
</script>
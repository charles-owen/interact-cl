<template>
  <div class="cl-interact">
    <form class="search">
      <h2 class="banner"><button class="ask" type="button" @click.prevent="ask"><img :src="root + '/vendor/cl/interact/img/logo16.png'" width="16" height="16"> ¿Ask a Question?</button> Interact!
        <span class="message"></span><span class="search"><input type="text" placeholder="Search..." name="search"><button type="submit">Search</button></span>
      </h2>
    </form>
    <div class="cl-interact-body">
      <interactions :interactions="interactItems"></interactions>
      <div class="cl-interaction">
        <component :is="interactionComponent" :data="data" @cancel="cancel"></component>
      </div>
    </div>
  </div>
</template>

<script>
  import InteractionsVue from './Interactions.vue';
  import InteractionVue from './Interaction.vue';
  import NewInteractionVue from './NewInteraction.vue';
  import WelcomeVue from './Welcome.vue';
  import {InteractItem} from '../Models/InteractItem';

  export default {
      props: ['data'],
      data: function() {
          return {
              root: Site.root,
              interactItems: [],
              interactionComponent: 'welcome'
          }
      },
      components: {
          interactions: InteractionsVue,
          interaction: InteractionVue,
          newInteraction: NewInteractionVue,
          welcome: WelcomeVue
      },
      created() {
          this.interactItems = this.dummyData();
      },
      methods: {
          ask() {
              this.interactionComponent = 'new-interaction';
          },
          cancel() {
              if(this.interactionComponent === 'new-interaction') {
                  this.interactionComponent = 'welcome';
              }
          },
          dummyData() {
              let items = [];

              items.push({
                  id: 352,
                  pin: true,
                  time: (new Date('9/16/2017')).getTime() / 1000,
                  type: 'Q',
                  summary: 'Error Help',
                  summarized: 'In CSE335 menu, I am not able to click the Error Help tab. Is something wrong with how I configured? I am working from my windows laptop.',
                  discussions: 1,
                  attribution: 'Step 1/Dealing with Errors',
                  closed: false
              });


              items.push({
                  id: 121,
                  pin: false,
                  time: (new Date('9/7/2017 12:23pm')).getTime() / 1000,
                  type: 'Q',
                  summary: 'memory leak',
                  summarized: 'i still have memory leak after deleting animals in the farm.ccp. Is there any way to find and fix it?',
                  discussions: 0,
                  attribution: 'Step 1/Count the eyes',
                  closed: false
              });


              items.push({
                  id: 153,
                  pin: false,
                  time: (new Date('9/7/2017 9:35am')).getTime() / 1000,
                  type: 'Q',
                  summary: 'How do I know number of each animal in the farm?',
                  summarized: 'I added a virtual function to CAnimal and modified it for minions and I have the default const for cow and chicken but I am having trouble calculating the total number of eyes in the farm. How do I go about this?',
                  discussions: 2,
                  attribution: 'Step 1/Count the eyes',
                  closed: false
              });

              return items;
          }
      }
  }
</script>
<template>
  <div :class="cls">
    <form class="search">
      <h2 class="cl-banner"><button @click.prevent="ask"><img :src="root + '/vendor/cl/interact/img/logo16.png'" width="16" height="16"> ¿Ask a Question?</button> Interact!
       <span class="cl-search"><!-- <input type="text" placeholder="Search..." name="search"><button type="submit">Search</button> -->
        <a :href="root + '/cl/help/interact'" target="_blank"><img :src="root + '/vendor/cl/interact/img/help.png'"></a>
       </span>
      </h2>
    </form>
    <div class="cl-interact-body">
      <interactions :summaries="summaries" :selected="selected" @select="select"></interactions>
      <div class="cl-interaction-presenter">
        <new-interaction v-if="composing" :data="data" @cancel="cancelComposing" @interaction="newInteraction"></new-interaction>
        <template v-else>
          <welcome v-if="selected === 0"></welcome>
          <interaction-presenter v-else :data="data" :selected="selected" @discussed="discussed"
                                 @deleted="deleted" @reloaded="reloaded" @select="select"></interaction-presenter>
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

  const Member = Site.Member;


  /**
   * Interact main window
   * @constructor InteractMainVue
   */
  export default {
      props: ['data', 'id'],
      data: function() {
          return {
              root: Site.root,
	            cls: 'cl-interact',
              summaries: null,
              selected: 0,        // Currently selected interaction ID, 0 if none
              composing: false   // True if we are entering a new interaction
          }
      },
      components: {
          interactions: InteractionsVue,
          interactionPresenter: InteractionPresenterVue,
          newInteraction: NewInteractionVue,
          welcome: WelcomeVue
      },
      watch: {
      	id() {
      		this.select(this.id);
        }
      },
      created() {
      	this.summaries = new Summaries(this.data);
      	this.$interact.summaries = this.summaries;
      },
      mounted() {
	      const user = this.$store.state.user.user;
	      if(user.atLeast(Member.STAFF)) {
	      	this.cls += ' cl-staff';
        }

        this.summaries.fetch(() => {
        	if(this.id !== undefined) {
        		this.select(this.id);
          } else {
	          this.selectFirst();
          }
        });

	      this.$interact.startPolling();
      },
      beforeDestroy() {
        this.$interact.endPolling();
      },
      methods: {
      	selectFirst() {
	        if(this.selected === 0 && this.summaries.summaries.length > 0) {
	        	this.select(this.summaries.summaries[0].id);
	        }
        },
          ask() {
              this.saveSelected = this.selected;
              this.select(0);
              this.composing = true;
          },
          cancelComposing() {
              this.composing = false;
              this.select(this.saveSelected);
          },
          select(id) {
      		  if(id !== this.id && this.$router !== undefined) {
      		  	if(id === 0) {
		          this.$router.push(Site.root + '/cl/interact');
              } else {
		            this.$router.push(Site.root + '/cl/interact/' + id);
              }
            }

            if(this.composing) {
                return;
            }

            this.selected = id;
          },
          newInteraction(interaction) {
              if(!this.composing) {
                  return;
              }

              this.composing = false;
              this.summaries.add(interaction);
              this.select(interaction.id);
          },
          discussed(interaction) {
              this.summaries.add(interaction);
	          this.select(interaction.id);
          },
          deleted(interaction) {
          	this.summaries.remove(interaction);
          	this.selected = 0;
          	this.selectFirst();
          },
          reloaded(interaction) {
	          this.summaries.add(interaction);
	          this.select(interaction.id);
          }
      }
  }
</script>
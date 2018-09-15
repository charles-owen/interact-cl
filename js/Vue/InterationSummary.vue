<template>
  <div :class="cls" :id="'q' + item.id" @click.prevent="click">
    <h3>
    <a><img v-if="item.pin" :src="root + '/vendor/cl/interact/img/pin25.png'" alt="Pinned" width="17" height="25">
        <img v-if="item.type === announce" :src="root + '/vendor/cl/interact/img/megaphone25.png'" alt="Annoucement" width="29" height="26"> {{item.summary}}</a>
    </h3>
    <p class="link">@{{item.id}} {{item.discussionsCnt}}<span>{{date}}</span></p>
    <p>{{item.summarized}}</p>
    <p class="cl-attribution" v-html="item.attribution"></p></div>
</template>

<script>
  import {Interaction} from '../Models/Interaction';
  import {TimeFormatter} from 'site-cl/js/TimeFormatter';
  import {Member} from 'course-cl/js/Members/Member';

  export default {
      props: ['item', 'selected'],
      data: function() {
          return {
              root: Site.root,
              announce: Interaction.ANNOUNCEMENT,
              date: '',
              cls: '',
	            user: this.$store.state.user.user,
	            staff: false
          }
      },
      watch: {
      	  item() {
      	  	this.set();
          },
          selected() {
      	  	this.set();
          }
      },
      mounted() {
	      this.staff = this.user.atLeast(Member.STAFF);
      	this.set();

      },
      methods: {
          click() {
              this.$emit('select', this.item.id);
          },
          set() {
          	this.cls = 'cl-interact-summary';

	          this.date = TimeFormatter.relativeUNIX(this.item.time, null, 'M-DD-YYYY'); //  TimeFormatter.absoluteUNIX(this.interaction.created, 'short');
	          if(this.selected) {
		          this.cls += ' cl-selected';
	          }

	          switch(this.item.state) {
		          case Interaction.PENDING:
			          this.cls += ' cl-pending';
			          break;

		          case Interaction.ANSWERED:
			          this.cls += ' cl-answered';
			          break;

		          case Interaction.CLOSED:
			          this.cls += ' cl-closed';
			          break;

		          case Interaction.RESOLVED:
			          this.cls += ' cl-resolved';
			          break;
	          }

	          if(this.staff && this.item.escalated !== undefined) {
	          	this.cls += ' cl-escalated';
            }
          }
      }
  }
</script>
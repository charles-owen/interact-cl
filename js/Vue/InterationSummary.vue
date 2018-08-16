<template>
  <div :class="selected ? 'cl-interact-summary cl-selected' : 'cl-interact-summary'" :id="'q' + item.id" @click.prevent="click">
    <h3>
    <a><img v-if="item.pin" :src="root + '/vendor/cl/interact/img/pin25.png'" alt="Pinned" width="17" height="25">
        <img v-if="item.type === announce" :src="root + '/vendor/cl/interact/img/megaphone25.png'" alt="Annoucement" width="29" height="26"> {{item.summary}}</a>
    </h3>
    <p class="link">@{{item.id}} {{item.discussionsCnt}}<span>{{date}}</span></p>
    <p>{{item.summarized}}</p>
    <p class="cl-attribution">{{item.attribution}}</p></div>
</template>

<script>
  import {Interaction} from '../Models/Interaction';
  import {TimeFormatter} from 'site-cl/js/TimeFormatter';

  export default {
      props: ['item', 'selected'],
      data: function() {
          return {
              root: Site.root,
              announce: Interaction.ANNOUNCEMENT,
              date: ''
          }
      },
      mounted() {
          this.date = TimeFormatter.relativeUNIX(this.item.time, null, 'M-DD-YYYY'); //  TimeFormatter.absoluteUNIX(this.interaction.created, 'short');
      },
      methods: {
          click() {
              this.$emit('select', this.item);
          }
      }
  }
</script>
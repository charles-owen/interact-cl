<template>
  <div class="cl-interaction">
      <h3 class="cl-interaction-heading">
        <span class="cl-menu"><a><img :src="root + '/vendor/cl/site/img/menubars.png'"></a>
          <ul>
            <li><a class="edit"><img :src="root + '/vendor/cl/site/img/pen16.png'"></a> <a
              class="edit" href="javascript:;">Edit</a></li>
            <li><a class="delete"><img :src="root + '/vendor/cl/site/img/x.png'"></a> <a
              class="delete" href="javascript:;">Delete</a></li>
            <li><a class="grading" href="/cbowen/cse335/lib/grading/assignmentgrader.php?tag=step1&amp;id=190"
                   target="INTERACT_GRADING"><img :src="root + '/vendor/cl/interact/img/grading.png'"></a> <a
              class="grading" href="/cbowen/cse335/lib/grading/assignmentgrader.php?tag=step1&amp;id=190"
              target="INTERACT_GRADING">Grading page</a></li>
            <li><a class="close" href="javascript:;"><img :src="root + '/vendor/cl/interact/img/close.png'"></a> <a
              class="close" href="javascript:;">Close discussion</a></li>
          </ul>
        </span>
        <span>{{date}}<br>
<button class="follow"><img :src="root + '/vendor/cl/interact/img/following.png'" alt="Following" height="16"
                            width="92"></button> @{{interaction.id}}</span>
        <img v-if="interaction.pin" :src="root + '/vendor/cl/interact/img/pin25.png'" alt="Pinned" width="17" height="25">
        <img v-if="interaction.type === announce" :src="root + '/vendor/cl/interact/img/megaphone25.png'" alt="Annoucement" width="29" height="26">
        {{interaction.summary}}
      </h3>
      <p class="link">{{interactionLabel}} {{interaction.by}} / <span v-html="interaction.attribution"></span>
      </p>
      <div v-html="interaction.message"></div>
  </div>
</template>

<script>
  import {Interaction} from '../Models/Interaction';
  import {TimeFormatter} from 'site-cl/js/TimeFormatter';

  export default {
      props: ['interaction'],
      data: function() {
          return {
              root: Site.root,
              interactionLabel: '',
              announce: Interaction.ANNOUNCEMENT,
              date: ''
          }
      },
      mounted() {
          console.log(this.interaction);
          if(this.interaction.private) {
            if(this.interaction.type === Interaction.ANNOUNCEMENT) {
                this.interactionLabel = 'Private announcement by';
            } else {
                this.interactionLabel = 'Private question by';
            }
          } else {
              if(this.interaction.type === Interaction.ANNOUNCEMENT) {
                  this.interactionLabel = 'Announcement by';
              } else {
                  this.interactionLabel = 'Question by';
              }
          }

          this.date = TimeFormatter.relativeUNIX(this.interaction.created, null, 'ddd, M-DD-YYYY h:mm:ssa'); //  TimeFormatter.absoluteUNIX(this.interaction.created, 'short');
      }
  }
</script>
<template>
  <div class="cl-discuss" id="d885">
      <h4>
        <div class="cl-menu"><a href="javascript:;"><img :src="root + '/vendor/cl/site/img/menubars.png'"></a>
          <ul>
            <li><a class="edit" href="javascript:;"><img :src="root + '/vendor/cl/site/img/pen16.png'"></a> <a
              class="edit" href="javascript:;">Edit</a></li>
            <li><a class="delete" href="javascript:;"><img :src="root + '/vendor/cl/site/img/x.png'"></a> <a
              class="delete" href="javascript:;">Delete</a></li>
            <li><a class="endorse" href="javascript:;"><img
              :src="root + '/vendor/cl/interact/img/check16.png'"></a> <a class="endorse" href="javascript:;">Endorse</a>
            </li>
          </ul>
        </div>
        <span class="time">{{date}}</span>{{discussion.by}}
      </h4>
      <div v-html="discussion.message"></div>
  </div>

</template>


<script>
    import {TimeFormatter} from 'site-cl/js/TimeFormatter';

    export default {
        props: ['discussion'],
        data: function () {
            return {
                root: Site.root,
                date: ''
            }
        },
        mounted() {
            this.date = TimeFormatter.relativeUNIX(this.discussion.time, null, 'ddd, M-DD-YYYY h:mm:ssa');
        }
    }

</script>
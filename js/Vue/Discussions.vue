<template>
  <div class="cl-discussions">
    <h3>DISCUSSION</h3>
    <discussion v-for="discussion in interaction.discussions" :key="discussion.id"
                :discussion="discussion" @reloaded="reloaded" @select="select"></discussion>

    <new-discussion :interaction="interaction" @reloaded="reloaded"></new-discussion>
  </div>
</template>

<script>
    import NewDiscussionVue from './NewDiscussion.vue';
    import DiscussionVue from './Discussion.vue';

    export default {
        props: ['interaction'],
        data: function () {
            return {
                root: Site.root
            }
        },
        components: {
            newDiscussion: NewDiscussionVue,
            discussion: DiscussionVue
        },
        methods: {
            reloaded(interaction) {
            	this.$emit('reloaded', interaction);
            },
            select(id) {
            	this.$emit('select', id);
            }
        }
    }

</script>
<template>
  <div class="cl-interactions">
    <interaction-summary v-for="item in summaries.summaries" :selected="item.id === selected" :item="item" :key="item.id" @select="select"></interaction-summary>
    <div v-if="summaries.more" class="cl-more"><a @click.prevent="more">...more...</a></div>
  </div>
</template>

<script>
import InteractionSummaryVue from './InterationSummary.vue';

export default {
  props: ['summaries', 'selected'],
  emits: ['select'],
  components: {
    interactionSummary: InteractionSummaryVue
  },
  mounted() {
  },
  methods: {
    more() {
      this.summaries.fetch();
    },
    select(id) {
      this.$emit('select', id);
    }
  }
}
</script>
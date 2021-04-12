<template>
  <content-card heading="Configure Monolog Channel">
    <p class="text-center hint-text"><i class="fa fa-info"></i> No configuration available for Monolog driver at this time.</p>
  </content-card>
</template>

<script>
import mutations from '@/mixins/mutations';
import asyncImports from '@/mixins/async_imports';

import ContentCard from '@/components/Cards/ContentCard';

export default {
  name: 'MonologChannel',
  components: {
    ContentCard,
  },
  mixins: [asyncImports, mutations],
};
</script>

<style scoped></style>

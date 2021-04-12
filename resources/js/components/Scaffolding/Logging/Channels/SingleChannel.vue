<template>
  <content-card heading="Configure Single Channel">
    <common-configuration
      default-path="storage/logs/laravel.log"
      channel-name="Single"
      input-mutation-path="logging/channels/single/path"
      level="info"
      level-path="logging/channels/single/level"
      @loaded="loading = false"
    />
  </content-card>
</template>

<script>
import ContentCard from '@/components/Cards/ContentCard';
import CommonConfiguration from '@/components/Scaffolding/Logging/Channels/Common/CommonConfiguration';

export default {
  name: 'SingleChannel',
  components: {
    CommonConfiguration,
    ContentCard,
  },
  data() {
    return {
      loading: false,
    };
  },
};
</script>

<style scoped></style>

<template>
  <laravel-mix-container heading="Assets Compilation (Laravel Mix)"
                         :required-modules="['asset-post-processing', 'copy-assets']" show-paths :layout-id="layoutId"/>
</template>

<script>
import LaravelMixContainer from '@/components/Scaffolding/Assets/Mix/LaravelMixContainer';

export default {
  name: 'AssetsCompilation',
  props: {
    layoutId: [String, Number],
  },
  components: { LaravelMixContainer },
  methods: {

  },
};
</script>

<style scoped>

</style>

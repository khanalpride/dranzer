<template>
  <row>
    <column centered>
      <text-block danger>
        Custom layout definition is removed once you've downloaded the project.
        This is because <app-name /> deletes the associated template that is
        required to populate this module.
      </text-block>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import AppName from '@/components/Typography/Decorated/AppName';

export default {
  name: 'LockedLayoutBuilder',
  components: {
    AppName, TextBlock, Row, Column,
  },
};
</script>

<style scoped>

</style>

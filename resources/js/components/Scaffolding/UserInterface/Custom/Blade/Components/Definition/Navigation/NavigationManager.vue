<template>
  <row>
    <column>
      <tabs-manager :tabs="tabs" :path="`ui/custom/config/tabs/navigation/active/${layoutId}`">
        <template slot="header">
          <navigation-section :base-layout-contents="baseLayoutContents"
                              :views="views"
                              :layout-id="layoutId" />
        </template>
        <template slot="footer">
          <navigation-section :base-layout-contents="baseLayoutContents"
                              :views="views"
                              :layout-id="layoutId"
                              section="footer" />
        </template>
      </tabs-manager>
    </column>
  </row>
</template>

<script>
import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import TabsManager from '@/components/Tabs/TabsManager';
import NavigationSection
  from '@/components/Scaffolding/UserInterface/Custom/Blade/Components/Definition/Navigation/NavigationSection';

export default {
  name: 'NavigationManager',
  components: {
    NavigationSection,
    TabsManager,
    Row,
    Column,
  },
  props: {
    layoutId: {},
    views: Array,
    baseLayoutContents: String,
  },
  data() {
    return {
      tabs: [
        {
          id: 'header',
          label: 'Header',
        },
        {
          id: 'footer',
          label: 'Footer',
        },
      ],
    };
  },
};
</script>

<style scoped>

</style>

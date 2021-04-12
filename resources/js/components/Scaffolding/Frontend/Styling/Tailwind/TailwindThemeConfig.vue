<template>
  <content-container>
    <tabs-manager :tabs="tabs" path="config/tailwind/theme/tabs/active">
      <template slot="screens">
        <tailwind-theme-screen-config />
      </template>
      <template slot="colors">
        <tailwind-theme-color-config />
      </template>
      <template slot="spacing">
        <tailwind-theme-space-config />
      </template>
    </tabs-manager>
  </content-container>
</template>

<script>
import ContentContainer from '@/components/Content/ContentContainer';
import TabsManager from '@/components/Tabs/TabsManager';
import TailwindThemeScreenConfig
  from '@/components/Scaffolding/Frontend/Styling/Tailwind/Theme/TailwindThemeScreenConfig';
import TailwindThemeColorConfig
  from '@/components/Scaffolding/Frontend/Styling/Tailwind/Theme/TailwindThemeColorConfig';
import TailwindThemeSpaceConfig
  from '@/components/Scaffolding/Frontend/Styling/Tailwind/Theme/TailwindThemeSpaceConfig';

export default {
  name: 'TailwindThemeConfig',
  components: {
    TailwindThemeSpaceConfig,
    TailwindThemeColorConfig,
    TailwindThemeScreenConfig,
    TabsManager,
    ContentContainer,
  },
  data() {
    return {
      tabs: [
        {
          id: 'screens',
          label: 'Screens',
        },
        {
          id: 'colors',
          label: 'Colors',
        },
        {
          id: 'spacing',
          label: 'Spacing',
        },
      ],
    };
  },
};
</script>

<style scoped>

</style>

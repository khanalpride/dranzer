<template>
  <scaffolding-component-container heading="Manage Roles and Permissions" :loading="loading || fetchingMutations || fetchingMutation">
    <row>
      <column centered>
        <separated>
          <text-block info hinted>
            The roles and permissions are registered in the <code class="padding-5 text-primary bold">PermissionsServiceProvider</code>
            which are then consumed by an <span class="text-primary">Admin Panel</span>.
          </text-block>

          <text-block :no-margin="false" push5 danger v-if="!hasAdminLayout && (persistableRoles.length || persistablePermissions.length)">
            These roles and permissions will not be created since you have not yet created an admin panel.
          </text-block>
        </separated>
      </column>
    </row>
    <row>
     <column size="4" offset="4">
       <pg-labeled-input v-model="permission"
                         label="Permission Name"
                         placeholder="Press enter to create..."
                         @keyup.enter.native="addPermission" />
     </column>

      <column size="4" offset="4" v-if="permissions.length">
        <form-input-title :title="`Permissions (${permissions.length})`" />
        <el-select multiple
                   filterable
                   collapse-tags
                   class="el-sel-full-width"
                   :value="permissionNames"
                   placeholder="Select or create permissions..."
                   no-data-text="No Permissions Found"
                   no-match-text="No Permissions Found"
                   @change="onPermissionsUpdated">
          <el-option :label="permission.name"
                     :key="permission.id"
                     :value="permission.name"
                     v-for="permission in permissions" />
        </el-select>
      </column>
    </row>
    <row v-if="permissions.length">
      <column>
        <separator />
      </column>
      <column size="4" offset="4">
        <pg-labeled-input v-model="role"
                          label="Role Name"
                          placeholder="Press enter to create..."
                          @keyup.enter.native="addRole" />
      </column>
      <column size="4" offset="4" v-if="roles.length">
        <form-input-title :title="`Roles (${roles.length})`" />
        <el-select filterable
                   collapse-tags
                   :value="selectedRole ? selectedRole.name : null"
                   class="el-sel-full-width"
                   placeholder="Select a role..."
                   no-data-text="No Roles Found"
                   no-match-text="No Roles Found"
                   @change="onRoleSelected">
          <el-option :label="role.name"
                     :key="role.id"
                     :value="role.name"
                     v-for="role in roles" />
        </el-select>
      </column>
      <column push10 size="4" offset="4" v-if="selectedRole">
        <form-input-title :title="`Associated Permissions (${selectedRole.permissions.length})`" />
        <el-select multiple
                   filterable
                   collapse-tags
                   class="el-sel-full-width"
                   placeholder="Select permissions to associate..."
                   no-data-text="No Associated Permissions Found"
                   no-match-text="No Associated Permissions Found"
                   :value="selectedRole.permissions"
                   @change="onRolePermissionsUpdated($event, selectedRole)">
          <el-option :label="permission.name"
                     :key="permission.id"
                     :value="permission.name"
                     v-for="permission in permissions" />
        </el-select>
      </column>
      <column size="4" offset="4" push15 v-if="selectedRole">
        <a href="#" class="text-danger bold" @click.prevent="deleteRole(selectedRole)"><i class="fa fa-trash"></i> Delete Role</a>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import Separator from '@/components/Layout/Separator';
import TextBlock from '@/components/Typography/Decorated/TextBlock';
import Separated from '@/components/Layout/Separated';

export default {
  name: 'RolesManager',
  mixins: [asyncImports, mutations],
  components: {
    Separated,
    TextBlock,
    Separator,
    FormInputTitle,
    PgLabeledInput,
    Row,
    Column,
    ScaffoldingComponentContainer,
  },
  data() {
    return {
      loading: false,

      permissions: [],
      roles: [],

      permission: '',
      role: '',

      selectedRole: null,

      layouts: [],
    };
  },
  computed: {
    permissionNames() {
      return this.permissions.map((p) => p.name);
    },
    roleNames() {
      return this.roles.map((r) => r.name);
    },
    persistablePermissions() {
      return this.permissions;
    },
    persistableRoles() {
      return this.roles;
    },
    hasAdminLayout() {
      return this.layouts.find((layout) => layout.type === 'admin');
    },
  },
  watch: {
    persistablePermissions: {
      handler() {
        this.persistPermissions();
      },
      deep: true,
    },
    persistableRoles: {
      handler() {
        this.persistRoles();
      },
      deep: true,
    },
  },
  async created() {
    this.registerMutable('Permissions', 'authorization/permissions', {
      then: (value) => this.permissions = value || [],
    });

    this.registerMutable('Roles', 'authorization/roles', {
      then: (value) => this.roles = value || [],
    });

    this.loading = true;
    await this.syncLayouts();
    this.loading = false;
  },
  methods: {
    async syncLayouts() {
      const { data } = await this.mutation({ path: 'ui/layouts/', like: true, refresh: true });
      this.layouts = data.value ? data.value.map((v) => v.value) : [];
    },

    addPermission() {
      const permissionName = this.permission.trim();

      if (permissionName === '') {
        return;
      }

      const permissionIndex = this.permissions.findIndex((p) => p.name.trim().toLowerCase() === permissionName.toLowerCase());

      if (permissionIndex < 0) {
        this.permissions.push({
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          name: permissionName,
        });
      }

      this.permission = '';
    },

    addRole() {
      const roleName = this.role.trim();

      if (roleName === '') {
        return;
      }

      const roleIndex = this.roles.findIndex((r) => r.name.trim().toLowerCase() === roleName.toLowerCase());

      if (roleIndex < 0) {
        this.roles.push({
          id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
          name: roleName,
          permissions: [],
        });
      }

      this.selectedRole = null;
      this.role = '';
    },

    deleteRole(role) {
      if (!role) {
        return;
      }

      const roleIndex = this.roles.findIndex((r) => r.id === role.id);

      if (roleIndex > -1) {
        this.roles.splice(roleIndex, 1);
        this.selectedRole = null;
      }
    },

    onPermissionsUpdated(permissions) {
      this.permissions = this.permissions.filter((p) => permissions.includes(p.name));

      this.roles.forEach((role) => {
        role.permissions = role.permissions.filter((p) => this.permissions.find((per) => per.name === p));
      });
    },

    onRoleSelected(roleName) {
      const role = this.roles.find((r) => r.name === roleName);

      if (!role) {
        this.selectedRole = null;
        return;
      }

      this.selectedRole = role;
    },

    onRolePermissionsUpdated(permissions, role) {
      role.permissions = permissions;
    },

    persistPermissions() {
      const payload = {
        name: 'Permissions',
        path: 'authorization/permissions',
        value: this.persistablePermissions,
      };

      this.mutate(payload);
    },

    persistRoles() {
      const payload = {
        name: 'Roles',
        path: 'authorization/roles',
        value: this.persistableRoles,
      };

      this.mutate(payload);
    },
  },
};
</script>

<style scoped>

</style>

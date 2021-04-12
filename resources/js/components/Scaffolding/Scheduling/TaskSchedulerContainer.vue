<template>
  <scaffolding-component-container heading="Configure Scheduled Tasks" :loading="loading || fetchingMutations">
    <row>
      <column size="8" offset="2" centered>
        <p class="text-primary">Cron Entry</p>
        <separator/>
        <code class="padding-5">
          * * * * * cd <span class="text-danger">/project-root</span> && php artisan schedule:run >> /dev/null
          2>&1
        </code>
        <p class="text-primary hint-text m-t-10">
          <i class="fa fa-info"></i> This allows Laravel to check your scheduled tasks every minute and execute them.
          Append this entry to your cron with the <code class="padding-5">crontab -e</code> shell command.
        </p>
        <separator/>
      </column>
      <column>
        <row>
          <column size="4" offset="4">
            <pg-labeled-input v-model="taskName"
                              :label="'Task Name' + (shell ? ' (Optional)' : '')"
                              placeholder="Press enter to create..."
                              @keyup.enter.native="createTask" />
          </column>
          <column size="4" offset="4">
            <pg-check-box v-model="shell" no-margin class="p-b-10" label="Shell Task" />
          </column>
          <column :push10="index > 0"
                  size="8"
                  offset="2"
                  :key="task.id"
                  v-for="(task, index) in tasks">
            <row>
              <column>
                <pg-check-box :value="true"
                              :label="getTaskLabel(task)"
                              @change="onTaskStateToggled($event, task)" />
              </column>

              <column size="10" class="m-l-30">
                <row>
                  <column v-if="!task.shell">
                    <row>
                      <column>
                        <form-input-title :centered="false" title="Command Signature" />
                        <pg-input v-model="task.signature" @input="persistTask(task)" />
                      </column>
                      <column push5>
                        <form-input-title :centered="false" title="Command Description" />
                        <pg-input v-model="task.description" @input="persistTask(task)" />
                      </column>
                    </row>

                  </column>
                  <column push5 v-else>
                    <row push5>
                      <column>
                        <form-input-title :centered="false" title="Shell Command" />
                        <textarea v-focus
                                    :rows="getShellEditorRowCount(task)"
                                    class="form-control"
                                    v-model="task.shellCmd"
                                    placeholder="Enter shell command..."
                                    @input="persistTask(task)"/>
                      </column>
                    </row>
                  </column>

                  <column push5>
                    <row push5>
                      <column>
                        <form-input-title :centered="false" title="Choose Frequencies" />
                        <simple-select filterable clearable v-model="frequency"
                                       :entities="frequencies"
                                       @change="frequencySelected(task)">
                          <template slot-scope="{ entity }">
                            <el-option :label="entity.name" :value="entity.value" :key="entity.id" />
                          </template>
                        </simple-select>
                      </column>
                    </row>

                    <row push10 v-if="task.frequencies && task.frequencies.length">
                      <column>
                        <form-input-title :centered="false" title="Runs (drag and drop to re-arrange)" />
                        <draggable v-model="task.frequencies" group="freq">
                          <row :key="f.id" v-for="f in task.frequencies">
                            <column class="m-l-10">
                              <p class="no-margin">
                                <span class="text-info"><i class="fa fa-arrow-right small"></i> {{ f.name }}</span>
                                <span class="m-l-10">
                                  <a href="#" class="text-danger" @click.prevent="onDeleteTaskFrequency(f, task)">
                                    <i class="fa fa-close"></i>
                                  </a>
                                </span>
                              </p>
                            </column>
                          </row>
                        </draggable>
                      </column>
                    </row>
                  </column>
                </row>
              </column>
            </row>
          </column>
        </row>
      </column>
    </row>
  </scaffolding-component-container>
</template>

<script>
import Draggable from 'vuedraggable';

import Row from '@/components/Layout/Grid/Row';
import Column from '@/components/Layout/Grid/Column';
import ScaffoldingComponentContainer from '@/components/Scaffolding/Containers/ScaffoldingComponentContainer';
import asyncImports from '@/mixins/async_imports';
import mutations from '@/mixins/mutations';
import PgLabeledInput from '@/components/Forms/PgLabeledInput';
import Separator from '@/components/Layout/Separator';
import PgCheckBox from '@/components/Forms/Checkbox/PgCheckBox';
import PgInput from '@/components/Forms/PgInput';
import taskFrequencies from '@/data/scheduler/task_frequencies';
import FormInputTitle from '@/components/Typography/FormInputTitle';
import SimpleSelect from '@/components/Select/SimpleSelect';
import { mapState } from 'vuex';

export default {
  name: 'TaskSchedulerContainer',
  mixins: [asyncImports, mutations],
  components: {
    SimpleSelect,
    FormInputTitle,
    PgInput,
    Draggable,
    PgCheckBox,
    Separator,
    PgLabeledInput,
    ScaffoldingComponentContainer,
    Column,
    Row,
  },
  data() {
    return {
      loading: false,

      shell: false,

      taskName: '',

      tasks: [],

      frequency: null,

      frequencies: taskFrequencies,
    };
  },
  computed: {
    ...mapState('project', ['project']),
  },
  async created() {
    this.loading = true;
    await this.syncTasks();
    this.loading = false;
  },
  methods: {
    async syncTasks() {
      const { data } = await this.mutation({ path: 'tasks/', like: true });
      this.tasks = data.value ? data.value.map((v) => v.value) : this.tasks;
    },
    persistTask(task) {
      const name = 'Scheduled Task';
      const path = `tasks/${task.id}`;

      const payload = {
        name,
        path,
        value: task,
      };

      this.mutate(payload);
    },

    createTask() {
      const id = Math.round(Math.random() * Number.MAX_SAFE_INTEGER);

      const name = this.taskName.trim() !== '' ? this.taskName : '';

      if (!this.shell && name === '') {
        return;
      }

      if (!this.shell) {
        const task = this.tasks.find((t) => t.name === name);
        if (task) {
          this.taskName = '';
          return;
        }
      }

      const task = {
        id,
        name,
        signature: '',
        description: '',
        shell: this.shell,
        shellCmd: '',
        frequencies: [],
      };

      this.tasks.push(task);

      this.persistTask(task);

      this.taskName = '';
    },

    getTaskLabel(task) {
      let taskName = task.name && task.name.trim() !== '' ? task.name.trim() : null;
      taskName = taskName ? taskName + (task.shell ? ' <code class="text-danger padding-5">Shell</code>' : '') : 'Scheduled Task';
      return taskName;
    },

    getShellEditorRowCount(task) {
      const cmd = task.shellCmd;

      if (cmd === '') {
        return 1;
      }

      return this.str.substrCount(cmd, '\n') + 1;
    },

    frequencySelected(task) {
      task.frequencies.push({
        id: Math.round(Math.random() * Number.MAX_SAFE_INTEGER),
        name: this.frequencies.find((f) => f.value === this.frequency).name,
        value: this.frequency,
        acceptsDay: true,
        acceptsTime: false,
      });

      this.frequency = null;

      this.persistTask(task);
    },

    async deleteTask(task) {
      if (this.project && this.project.downloaded) {
        return;
      }

      const tIndex = this.tasks.findIndex((t) => t.id === task.id);
      if (tIndex > -1) {
        const { status } = await this.deleteMutation(`tasks/${task.id}`);
        if (status === 201 || status === 404) {
          this.tasks.splice(tIndex, 1);
        }
      }
    },

    onDeleteTaskFrequency(frequency, task) {
      if (this.project && this.project.downloaded) {
        return;
      }

      const fIndex = task.frequencies.findIndex((f) => f.id === frequency.id);

      if (fIndex > -1) {
        task.frequencies.splice(fIndex, 1);
        this.persistTask(task);
      }
    },

    onTaskStateToggled(active, task) {
      if (!active) {
        this.deleteTask(task);
      }
    },
  },
};
</script>

<style scoped>

</style>

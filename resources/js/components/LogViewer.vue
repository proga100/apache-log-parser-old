<template>
  <div class="log-viewer">
    <div class="filters mb-4">
      <div class="row">
        <div class="col-md-3">
          <input v-model="filters.ip_address" class="form-control" placeholder="IP Address">
        </div>
        <div class="col-md-2">
          <input v-model="filters.status_code" class="form-control" placeholder="Status Code">
        </div>
        <div class="col-md-3">
          <input v-model="filters.date_from" type="datetime-local" class="form-control">
        </div>
        <div class="col-md-3">
          <input v-model="filters.date_to" type="datetime-local" class="form-control">
        </div>
        <div class="col-md-1">
          <button @click="applyFilters" class="btn btn-primary">Filter</button>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="table table-striped">
        <thead>
          <tr>
            <th @click="sort('ip_address')" class="sortable col-ip">
              IP Address
              <span v-if="sortField === 'ip_address'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('request_method')" class="sortable col-method">
              Method
              <span v-if="sortField === 'request_method'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('request_path')" class="sortable col-path">
              Path
              <span v-if="sortField === 'request_path'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('status_code')" class="sortable col-status">
              Status
              <span v-if="sortField === 'status_code'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="sort('request_time')" class="sortable col-time">
              Time
              <span v-if="sortField === 'request_time'">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
            </th>
            <th class="col-agent">User Agent</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs.data" :key="log.id">
            <td class="col-ip">{{ log.ip_address }}</td>
            <td class="col-method">{{ log.request_method }}</td>
            <td class="col-path">{{ log.request_path }}</td>
            <td :class="['col-status', getStatusClass(log.status_code)]">{{ log.status_code }}</td>
            <td class="col-time">{{ formatDate(log.request_time) }}</td>
            <td class="col-agent">{{ log.user_agent }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination mt-4">
      <nav>
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: !logs.prev_page_url }">
            <a class="page-link" href="#" @click.prevent="changePage(logs.current_page - 1)">Previous</a>
          </li>
          <li v-for="page in logs.last_page" :key="page" class="page-item" :class="{ active: page === logs.current_page }">
            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: !logs.next_page_url }">
            <a class="page-link" href="#" @click.prevent="changePage(logs.current_page + 1)">Next</a>
          </li>
        </ul>
      </nav>
    </div>

    <div class="upload-section mt-4">
      <h3>Upload Log File</h3>
      <input type="file" @change="handleFileUpload" accept=".log,.txt" class="form-control">
      <button @click="uploadFile" class="btn btn-success mt-2" :disabled="!selectedFile">Upload</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'LogViewer',
  data() {
    return {
      logs: {
        data: [],
        current_page: 1,
        last_page: 1,
        prev_page_url: null,
        next_page_url: null
      },
      filters: {
        ip_address: '',
        status_code: '',
        date_from: '',
        date_to: ''
      },
      sortField: 'request_time',
      sortDirection: 'desc',
      selectedFile: null
    }
  },
  methods: {
    async fetchLogs() {
      try {
        const params = {
          page: this.logs.current_page,
          sort_field: this.sortField,
          sort_direction: this.sortDirection,
          ...this.filters
        };
        const response = await axios.get('/api/logs', { params });
        this.logs = response.data;
      } catch (error) {
        console.error('Error fetching logs:', error);
      }
    },
    sort(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      } else {
        this.sortField = field;
        this.sortDirection = 'asc';
      }
      this.fetchLogs();
    },
    changePage(page) {
      if (page >= 1 && page <= this.logs.last_page) {
        this.logs.current_page = page;
        this.fetchLogs();
      }
    },
    applyFilters() {
      this.logs.current_page = 1;
      this.fetchLogs();
    },
    formatDate(date) {
      return new Date(date).toLocaleString();
    },
    getStatusClass(status) {
      if (status >= 500) return 'text-danger';
      if (status >= 400) return 'text-warning';
      if (status >= 300) return 'text-info';
      return 'text-success';
    },
    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
    },
    async uploadFile() {
      if (!this.selectedFile) return;

      const formData = new FormData();
      formData.append('log_file', this.selectedFile);

      try {
        await axios.post('/api/logs/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        this.selectedFile = null;
        this.fetchLogs();
      } catch (error) {
        console.error('Error uploading file:', error);
      }
    }
  },
  mounted() {
    this.fetchLogs();
  }
}
</script>

<style scoped>
.table-container {
  overflow-x: auto;
  margin-bottom: 1rem;
}

table {
  width: 100%;
  table-layout: fixed;
  white-space: nowrap;
}

th, td {
  padding: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.col-ip {
  width: 120px;
}

.col-method {
  width: 80px;
}

.col-path {
  width: 30%;
  max-width: 400px;
}

.col-status {
  width: 80px;
  text-align: center;
}

.col-time {
  width: 160px;
}

.col-agent {
  width: calc(100% - 840px);
  min-width: 200px;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: #f8f9fa;
}

/* Add tooltip for truncated content */
td {
  position: relative;
}

td:hover::after {
  content: attr(title);
  position: absolute;
  left: 0;
  top: 100%;
  z-index: 1;
  background: #333;
  color: white;
  padding: 5px;
  border-radius: 3px;
  white-space: normal;
  max-width: 300px;
  word-wrap: break-word;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .col-path {
    width: 25%;
  }
  
  .col-agent {
    width: calc(100% - 740px);
  }
}

@media (max-width: 768px) {
  .table-container {
    overflow-x: scroll;
  }
  
  table {
    min-width: 900px;
  }
}
</style> 
import './bootstrap';
import { createApp } from 'vue'
import LogViewer from './components/LogViewer.vue'
import axios from 'axios'

// Настройка базового URL для axios
axios.defaults.baseURL = '/'

const app = createApp(LogViewer)
app.mount('#app') 
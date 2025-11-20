import axios from 'axios';

axios.defaults.baseURL = import.meta.env.VITE_APP_URL || 'https://gutenberg.test/api/';
axios.defaults.withCredentials = true;

export default axios;

import { createRouter, createWebHistory } from 'vue-router'
import HomePage from './HomePage.vue'  // your main page with categories
import BookDetails from './BookDetails.vue' // page to show books of selected category

const routes = [
    {
        path: '/',
        name: 'HomePage',
        component: HomePage
    },
    {
        path: '/books/:category_id/:category_name',
        name: 'BookDetails',
        component: BookDetails,
        props: true // allows category_id to be accessed as prop
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router

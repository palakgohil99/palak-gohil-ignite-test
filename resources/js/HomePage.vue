<template>
    <div class="h-screen w-screen overflow-hidden bg-[#f7f5ff] text-gray-900 flex flex-col">

        <!-- Header -->
        <header ref="headerRef" class="header-bg pt-10 pb-6 text-center px-4 flex-shrink-0">
            <h1 class="text-4xl sm:text-5xl font-semibold text-[#5E56E7]">Gutenberg Project</h1>
            <p class="mt-3 text-base sm:text-lg font-semibold text-[#333333] max-w-2xl mx-auto">
                A social cataloging website that allows you to freely search its database of books,
                annotations, and reviews.
            </p>
        </header>

        <!-- Scrollable Categories Section -->
        <main ref="mainRef" class="flex-1 w-full overflow-y-auto" :style="{ height: mainHeight }">

            <div class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 p-6 pb-12">
                <CategoryCard v-for="cat in categories" :key="cat.id" :label="truncate(cat.name)" :title="cat.name" :icon="getIconForCategory(cat.name)"
                    @select="goToBooks(cat.id, cat.name)" />

                <!-- Infinite scroll sentinel -->
                <div ref="sentinel" class="w-full col-span-full h-4"></div>
            </div>
        </main>

        <!-- Loader -->
        <div v-if="loading" class="py-2 text-center text-gray-600 flex-shrink-0">
            Loading more categories...
        </div>

    </div>
</template>


<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import CategoryCard from './components/CategoryCard.vue'
import axios from './axios'
import { useRouter } from 'vue-router'

import AdventureIcon from '../images/Adventure.svg'
import PhilosophyIcon from '../images/Philosophy.svg'
import DramaIcon from '../images/Drama.svg'
import PoliticsIcon from '../images/Politics.svg'
import HistoryIcon from '../images/History.svg'
import HumorIcon from '../images/Humour.svg'
import FictionIcon from '../images/Fiction.svg'
import DefaultIcon from '../images/tag.svg'

const router = useRouter()

const categories = ref([])
const page = ref(1)
const loading = ref(false)
const hasMore = ref(true)

const headerRef = ref(null)
const mainRef = ref(null)
const sentinel = ref(null)
let observer = null

const iconsMap = {
    adventure: AdventureIcon,
    philosophy: PhilosophyIcon,
    drama: DramaIcon,
    politics: PoliticsIcon,
    history: HistoryIcon,
    humor: HumorIcon,
    fiction: FictionIcon
    // add more as needed
};

// Full height minus header height
const mainHeight = ref('calc(100vh - 180px)')

const updateMainHeight = () => {
    if (headerRef.value) {
        const h = headerRef.value.getBoundingClientRect().height
        mainHeight.value = `calc(100vh - ${h}px)`
    }
}

const truncate = (text, length = 50) =>
    text?.length > length ? text.slice(0, length) + '...' : text

const fetchCategories = async () => {
    if (loading.value || !hasMore.value) return

    loading.value = true
    const res = await axios.get(`get-categories?page=${page.value}`)
    const results = res.data.results || []

    if (!results.length) {
        hasMore.value = false
    } else {
        categories.value.push(...results)
        page.value++
    }
    loading.value = false
}

const createObserver = () => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting) fetchCategories()
        },
        { root: mainRef.value, rootMargin: '150px' }
    )
    observer.observe(sentinel.value)
}

const goToBooks = (id, name) => {
    router.push({ name: 'BookDetails', params: { category_id: id, category_name: name } })
}

onMounted(() => {
    updateMainHeight()
    window.addEventListener('resize', updateMainHeight)

    fetchCategories()
    createObserver()
})

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateMainHeight)
    if (observer) observer.disconnect()
})

const getIconForCategory = (catName) => {
    let categoryString = catName.toLowerCase();

    // Loop through keys and check if the string contains the keyword
    for (const key in iconsMap) {
        if (categoryString.includes(key)) {
            return iconsMap[key];
        }
    }

    return DefaultIcon; // defaul
}
</script>


<style scoped>
html,
body {
    height: 100%;
    overflow: hidden;
    font-family: 'Montserrat' !important;
    /* PREVENT BROWSER SCROLL */
}
</style>


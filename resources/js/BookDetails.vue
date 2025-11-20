<template>
    <div class="h-screen bg-gray-50 p-4 md:p-8 lg:p-12 flex">
        <div class="max-w-7xl mx-auto w-full flex flex-col h-full">

            <!-- Fixed Header Section -->
            <div class="sticky top-0 z-10 bg-gray-50 pb-4">
                <header class="flex items-center mb-4">
                    <router-link :to="{ name: 'HomePage' }"
                        class="text-3xl text-indigo-700 mr-4 cursor-pointer hover:text-indigo-900 transition-colors">
                        <img src="../images/Back.svg" alt="">
                    </router-link>
                    <h1 class="text-2xl sm:text-3xl font-semibold text-indigo-700">
                        {{ category_name }}
                    </h1>
                </header>

                <!-- Search Bar -->
                <div class="mt-8">
                    <div class="relative max-w-4xl">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>

                        <input v-model="searchQuery" type="search" placeholder="Search" class="search-input" />
                    </div>
                </div>
            </div>

            <!-- Scrollable Books Section (only this area scrolls) -->
            <div id="scrollArea" ref="scrollAreaRef" class="flex-1 overflow-y-auto pr-1 mt-8">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-5
                      gap-x-4 gap-y-10 sm:gap-y-12 bg-[#F8F7FF] w-full pb-4 px-2">

                    <div v-for="book in books" :key="book.id"
                        class="flex flex-col items-center text-center group cursor-pointer" @click="openBook(book)">

                        <!-- Book Cover -->
                        <div
                            class="w-full aspect-[2/3] max-w-[160px] shadow-xl mb-2
                          transform transition-transform duration-300 group-hover:scale-[1.02] border-2 rounded-[8px] border-gray-100">

                            <div :class="[book.coverClass, 'w-full h-full flex items-center justify-center']">
                                <img :src="`https://www.gutenberg.org/cache/epub/${book.gutenberg_id}/pg${book.gutenberg_id}.cover.medium.jpg`"
                                    class="w-full h-full object-cover block rounded-[8px]" />
                            </div>
                        </div>

                        <!-- Book Title -->
                        <div class="w-full max-w-[160px] px-1">
                            <p class="text-[14px] mt-2 text-left font-semibold text-gray-800 line-clamp-2 leading-snug cursor-pointer">
                                {{ book.title }}
                            </p>

                            <p class="text-[14px] text-left text-gray-500 mt-0.5" v-for="author in book.authors" :key="author.name">
                                {{ author.name }}
                            </p>
                        </div>
                    </div>

                    <!-- Sentinel for Infinite Scroll (keep this outside the book items) -->
                    <div ref="sentinel" class="w-full col-span-full h-6"></div>
                </div>

                <!-- Loader -->
                <div v-if="loading" class="text-center py-4 text-gray-500">
                    Loading more books...
                </div>

                <div v-if="!loading && books.length === 0" class="text-center py-6 text-gray-500">
                    No books found.
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import axios from "./axios";
import { debounce } from "lodash";

const books = ref([]);
const searchQuery = ref("");
const loading = ref(false);
const nextUrl = ref(null);      // backend pagination next page URL
const hasMore = ref(true);

const scrollAreaRef = ref(null);
const sentinel = ref(null);
let observer = null;

const props = defineProps({
    category_id: String,
    category_name: String
});

let category_name = props.category_name;

/* ----------------- HELPERS ----------------- */
const PRIORITY_FORMATS = ["html", "pdf", "txt"];

const getBestBookUrl = (formats) => {
    if (!formats) return null;
    const findUrl = (type) => {
        for (const mime in formats) {
            const url = formats[mime].toLowerCase();
            if (url.includes(".zip")) continue;
            if (type === "html" && (mime.includes("html") || url.includes("-h.htm"))) return formats[mime];
            if (type === "pdf" && (mime.includes("pdf") || url.endsWith(".pdf"))) return formats[mime];
            if (type === "txt" && (mime.includes("text/plain") || url.endsWith(".txt"))) return formats[mime];
        }
        return null;
    };
    for (const type of PRIORITY_FORMATS) {
        const result = findUrl(type);
        if (result) return result;
    }
    return null;
};

const openBook = (book) => {
    const url = getBestBookUrl(book.formats);
    if (url) window.open(url, "_blank");
};

/* ----------------- DEBUG HELPERS ----------------- */
const log = (...args) => {
    // toggle console logging here if you want: set to false to silence
    const enabled = true;
    if (enabled) console.log("[BooksList]", ...args);
};

/* ----------------- FETCH BOOKS ----------------- */
const safeGetUrl = (urlOrPath) => {
    // If backend returns relative path ("/get-books?page=2") keep it, axios baseURL will handle.
    // If it returns absolute url, just return it too.
    // If urlOrPath is null/empty, return null.
    if (!urlOrPath) return null;
    return urlOrPath;
};

const fetchBooks = async (reset = false) => {
    if (loading.value) {
        log("fetchBooks blocked: loading in progress");
        return;
    }
    if (!hasMore.value && !reset) {
        log("fetchBooks blocked: no more pages");
        return;
    }

    loading.value = true;
    try {
        let url;
        if (reset) {
            // initial/first page — construct explicit query to ensure backend returns next URL
            url = `/get-books?category_id=${props.category_id}&search=${encodeURIComponent(searchQuery.value)}`;
        } else {
            // use nextUrl returned by backend
            url = nextUrl.value;
        }

        url = safeGetUrl(url);
        if (!url) {
            log("No URL to load (url is null). Stopping fetch.");
            loading.value = false;
            return;
        }

        log("Fetching URL:", url);
        const response = await axios.get(url);
        const data = response.data.results || [];

        if (reset) {
            books.value = data;
            // scroll to top of scroll area when doing a fresh search/reset
            const root = scrollAreaRef.value;
            if (root) root.scrollTop = 0;
        } else {
            // append new items
            // optional dedupe guard (if your API might return duplicates)
            if (data && data.length) {
                books.value.push(...data);
            }
        }

        // update next url & hasMore
        nextUrl.value = response.data.next || null;
        hasMore.value = !!nextUrl.value;
        log("Updated nextUrl:", nextUrl.value, "hasMore:", hasMore.value, "totalBooks:", books.value.length);

        // wait DOM updates (sentinel moved/added), then attach observer
        await nextTick();
        createObserver();
    } catch (err) {
        console.error("[BooksList] Error loading books:", err);
    } finally {
        loading.value = false;
    }
};

/* ----------------- OBSERVER ----------------- */
const createObserver = () => {
    // disconnect existing observer to avoid duplicates
    if (observer) {
        try { observer.disconnect(); } catch (e) { /* ignore */ }
        observer = null;
    }

    const rootEl = scrollAreaRef.value;
    const sentinelEl = sentinel.value;

    if (!rootEl || !sentinelEl) {
        log("createObserver aborted: missing root or sentinel", { rootEl: !!rootEl, sentinelEl: !!sentinelEl });
        return;
    }

    // Use a relatively large rootMargin so we begin loading before the user reaches the exact bottom.
    // This prevents blank screens on slow networks.
    const options = {
        root: rootEl,
        rootMargin: "400px 0px 400px 0px", // top right bottom left -> trigger early
        threshold: 0
    };

    observer = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (!entry) return;

        log("Observer callback — isIntersecting:", entry.isIntersecting, "intersectionRatio:", entry.intersectionRatio);

        if (entry.isIntersecting && hasMore.value && !loading.value) {
            log("Sentinel intersecting -> load next page");
            // detach observer now to prevent multiple calls while loading
            try { observer.disconnect(); } catch (e) { }
            // fetch next page
            fetchBooks(false);
        }
    }, options);

    // Start observing sentinel
    observer.observe(sentinelEl);
    log("Observer attached", options);
};

/* ----------------- SEARCH (RESET) ----------------- */
const debouncedSearch = debounce(() => {
    hasMore.value = true;
    nextUrl.value = null;
    fetchBooks(true);
}, 500);

watch(searchQuery, () => debouncedSearch());

/* ----------------- LIFECYCLE ----------------- */
onMounted(async () => {
    log("mounted -> initial fetch");
    await fetchBooks(true);
    await nextTick();
    createObserver();
});

onBeforeUnmount(() => {
    if (observer) {
        try { observer.disconnect(); } catch (e) { }
        observer = null;
    }
});
</script>

<style scoped>
.search-input {
    width: 100%;
    padding-left: 3rem;
    padding-right: 1rem;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    height: 40px;
    font-size: 18px;
    color: #374151;
    background: #f0f0f6;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    outline: none;
    transition: all 0.2s ease;
    font-weight: bold;
}

.search-input:focus {
    border-color: #5e56e7;
    box-shadow: none;
}
</style>

<script setup>
  import { ref, onMounted, computed, onBeforeUnmount } from 'vue'
  import axios from 'axios'
  import { route } from 'ziggy-js'
  import flagshiplogo from '@/Media/logo/ijnflagship.png'
  import mislogo from '@/Media/logo/Logo MIS-01.png'

  // Data state
  const iclData = ref([])
  const currentPage = ref(1)
  const itemsPerPage = 13

  // Clock state
  const time = ref('')
  const date = ref('')

  function updateClock() {
    const now = new Date()
    time.value = now.toLocaleTimeString('en-GB', { hour12: false })
    date.value = now.toLocaleDateString('en-GB', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  }

  let clockInterval = null
  let fetchInterval = null

  // Fetch ICL listing
  const fetchData = async () => {
    try {
      const response = await axios.get(route('display.geticllisting'))
      iclData.value = response.data.data

      // Optional safety check if current page exceeds new data length
      if ((currentPage.value - 1) * itemsPerPage >= iclData.value.length) {
        currentPage.value = 1
      }
    } catch (error) {
      console.error('Error fetching ICL data:', error)
    }
  }

  // Computed paginated data
  const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return iclData.value.slice(start, start + itemsPerPage)
  })

  // Auto-slide every 10 seconds
  const startCarousel = () => {
    setInterval(() => {
      if (currentPage.value * itemsPerPage >= iclData.value.length) {
        currentPage.value = 1
      } else {
        currentPage.value++
      }
    }, 10000)
  }

  // Lifecycle
  onMounted(() => {
    fetchData().then(startCarousel)
    clockInterval = setInterval(updateClock, 1000) // update clock every second
    fetchInterval = setInterval(fetchData, 30000) // refresh API every 30s
  })

  onBeforeUnmount(() => {
    clearInterval(clockInterval)
    clearInterval(fetchInterval)
  })
</script>

<template>
  <div class="min-h-screen bg-slate-200 p-6">
    <!-- Header -->
    <div class="max-w-10xl mx-auto bg-gradient-to-r from-violet-600 to-zinc-50 shadow-lg rounded-lg p-6 mb-8 flex items-center justify-between h-20">
      
      <!-- Left: Clock & Date -->
      <div class="flex flex-col text-left leading-tight">
        <span class="text-xl font-bold text-white">{{ time }}</span>
        <span class="text-sm text-white">{{ date }}</span>
      </div>

      <!-- Center: Title -->
      <h1 class="text-4xl font-black text-dark pl-40"> ICL Display </h1>
      <!-- Right: Logos -->
      <div class="flex gap-2">
        <img :src="flagshiplogo" alt="Logo" class="h-20 object-contain"/>
        <img :src="mislogo" alt="Logo" class="h-18 object-contain"/>
      </div>
    </div>

    <!-- Table Container -->
    <div class="max-w-10xl mx-auto bg-fuchsia-50 shadow-lg rounded-lg p-6">
      <!-- <h2 class="text-xl font-semibold text-gray-700 mb-4 text-center">Data Listing</h2> -->

      <table class="min-w-full border border-gray-200">
        <thead class="bg-slate-200 text-center text-xl">
          <tr>
            <th class="px-4 py-2 border-b">MRN</th>
            <th class="px-4 py-2 border-b">PROCEDURE</th>
            <th class="px-4 py-2 border-b">CONSULTANT</th>
            <th class="px-4 py-2 border-b">REG NO.</th>
            <th class="px-4 py-2 border-b">LAB</th>
            <th class="px-4 py-2 border-b">TIME IN</th>
            <th class="px-4 py-2 border-b">STATUS</th>
          </tr>
        </thead>

        <!-- Transition effect for page change -->
        <transition name="fade" mode="out-in">
          <tbody class="text-center" :key="currentPage">
            <tr v-for="(item, index) in paginatedData" :key="index" class="odd:bg-white even:bg-slate-100">
              <td class="px-4 py-2 border-b font-semibold text-xl">{{ item.mrn || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">{{ item.initialProcedure || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">{{ item.consultant || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">{{ item.cathRegNo || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">{{ item.lab || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">{{ item.timeIn || '-' }}</td>
              <td class="px-4 py-2 border-b text-xl">
                <span
                  v-if="item.status"
                  :class="[
                    'px-2 py-1 rounded-full text-dark text-sm font-semibold',
                    item.status === 'Initial'
                      ? 'bg-yellow-500'
                      : item.status === 'Done'
                      ? 'bg-green-500'
                      : item.status === 'Postpone'
                      ? 'bg-red-500'
                      : 'bg-gray-400']">
                  {{ item.status }}
                </span>
                <span v-else>-</span>
              </td>            
            </tr>
            <tr v-if="paginatedData.length === 0">
              <td colspan="7" class="px-4 py-2 border-b text-center">No data found</td>
            </tr>
          </tbody>
        </transition>
      </table>
    </div>
  </div>
</template>

<style scoped>
/* Fade effect */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.outlined-title {
  -webkit-text-stroke: 2px rgb(255, 255, 255);
}

</style>

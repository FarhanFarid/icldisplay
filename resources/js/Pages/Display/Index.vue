<script setup>
	import { ref, onMounted, computed, onBeforeUnmount } from 'vue'
	import axios from 'axios'
	import { route } from 'ziggy-js'
	import flagshiplogo from '@/Media/logo/ijnflagship.png'
	import mislogo from '@/Media/logo/Logo MIS-01.png'

	// Data state
	const iclData = ref([])
	const currentPage = ref(1)
	const itemsPerPage = 15

	const counter = ref({
		initial: 0,
		ongoing: 0,
		done: 0
	})

	const statusPriority = {
		'Initial': 1,
		'initial': 1,
		'Done': 3,
		'done': 3
	}

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
	let carouselInterval = null // <— for auto page change

	// Fetch ICL listing
	const fetchData = async () => {
		try {
			const response = await axios.get(route('display.geticllisting'))

			console.log(response.data)

			// table data
			iclData.value = response.data.data.sort((a, b) => {
				// 1️⃣ Status priority
				const getStatusRank = (item) => {
					const status = item.status ?? ''
					return statusPriority[status] ?? 2
				}

				const statusA = getStatusRank(a)
				const statusB = getStatusRank(b)

				if (statusA !== statusB) {
					return statusA - statusB   // Initial ↑ , Done ↓
				}

				// 2️⃣ Same status → sort by timeIn (latest first)
				const parseTime = (t) => {
					if (!t) return null
					const [hours, minutes] = t.split(':').map(Number)
					return hours * 60 + minutes
				}

				const timeA = parseTime(a.timeIn)
				const timeB = parseTime(b.timeIn)

				if (timeA === null && timeB === null) return 0
				if (timeA === null) return 1
				if (timeB === null) return -1

				return timeB - timeA
			})

			// ✅ SAFE counter assignment
			const apiCounter = response.data.counter || {}

			counter.value.initial = apiCounter.initial ?? 0
			counter.value.ongoing = apiCounter.ongoing ?? 0
			counter.value.done    = apiCounter.done ?? 0

			if ((currentPage.value - 1) * itemsPerPage >= iclData.value.length) {
				currentPage.value = 1
			}

		} catch (error) {
			console.error('Error fetching ICL data:', error)

			// fallback reset (optional)
			counter.value.initial = 0
			counter.value.ongoing = 0
			counter.value.done    = 0
		}
	}


	// Computed paginated data
	const paginatedData = computed(() => {
		const start = (currentPage.value - 1) * itemsPerPage
		return iclData.value.slice(start, start + itemsPerPage)
	})

	// Total number of pages
	const totalPages = computed(() => {
		return Math.ceil(iclData.value.length / itemsPerPage)
	})

	// Manual navigation functions
	const nextPage = () => {
		if (currentPage.value < totalPages.value) {
			currentPage.value++
		} else {
			currentPage.value = 1
		}
		resetCarousel() // reset auto timer
	}

	const prevPage = () => {
		if (currentPage.value > 1) {
			currentPage.value--
		} else {
			currentPage.value = totalPages.value
		}
		resetCarousel()
	}

	// Auto-slide every 10 seconds
	const startCarousel = () => {
		carouselInterval = setInterval(() => {
			nextPage()
		}, 10000)
	}

	// Reset timer when manually clicked
	const resetCarousel = () => {
		clearInterval(carouselInterval)
		startCarousel()
	}

	// Lifecycle
	onMounted(() => {
		fetchData().then(startCarousel)
		clockInterval = setInterval(updateClock, 1000)
		fetchInterval = setInterval(fetchData, 30000)
	})

	onBeforeUnmount(() => {
		clearInterval(clockInterval)
		clearInterval(fetchInterval)
		clearInterval(carouselInterval)
	})
</script>

<template>
	<div class="relative min-h-screen bg-slate-200 p-6 overflow-hidden">
		<!-- Left/Right full-height navigation zones -->
		<div 
			class="absolute top-0 left-0 h-full w-1/6 bg-transparent hover:bg-gray-300/10 cursor-pointer z-20 flex items-center justify-start group"
			@click="prevPage"
			title="Previous Page">
			<span class="text-gray-400 text-5xl opacity-0 group-hover:opacity-70 pl-4 transition-opacity duration-300">←</span>
		</div>
  
		<div 
			class="absolute top-0 right-0 h-full w-1/6 bg-transparent hover:bg-gray-300/10 cursor-pointer z-20 flex items-center justify-end group"
			@click="nextPage"
			title="Next Page">
			<span class="text-gray-400 text-5xl opacity-0 group-hover:opacity-70 pr-4 transition-opacity duration-300">→</span>
		</div>
  
		<!-- Header -->
		<div class="max-w-10xl mx-auto bg-gradient-to-r from-violet-600 to-zinc-50 shadow-lg rounded-lg p-6 mb-2 flex items-center justify-between h-20 relative z-10">
			<div class="flex flex-col text-left leading-tight">
				<span class="text-xl font-bold text-white">{{ time }}</span>
				<span class="text-sm text-white">{{ date }}</span>
			</div>
			<h1 class="text-4xl font-black text-dark pl-40">ICL Display</h1>
			<div class="flex gap-2">
				<img :src="flagshiplogo" alt="Logo" class="h-20 object-contain" />
				<img :src="mislogo" alt="Logo" class="h-18 object-contain" />
			</div>
		</div>

		<!-- Status Summary Box -->
		<div class="max-w-10xl mx-auto bg-white shadow-md rounded-lg px-8 py-3 mb-8 flex items-center justify-center gap-16 h-14">
	
			<div class="flex items-center gap-2">
				<span class="text-sm font-semibold text-gray-600">INITIAL</span>
				<span class="text-lg font-bold text-yellow-600">
					{{ counter.initial }}
				</span>
			</div>

			<div class="h-6 w-px bg-gray-300"></div>

			<div class="flex items-center gap-2">
				<span class="text-sm font-semibold text-gray-600">ONGOING</span>
				<span class="text-lg font-bold text-blue-600">
					{{ counter.ongoing }}
				</span>
			</div>

			<div class="h-6 w-px bg-gray-300"></div>

			<div class="flex items-center gap-2">
				<span class="text-sm font-semibold text-gray-600">DONE</span>
				<span class="text-lg font-bold text-green-600">
					{{ counter.done }}
				</span>
			</div>

		</div>

  
		<!-- Table Container -->
		<div class="max-w-10xl mx-auto bg-fuchsia-50 shadow-lg rounded-lg p-6 overflow-hidden relative z-10">
				<table class="min-w-full border border-gray-200">
					<thead class="bg-slate-200 text-center text-xl">
						<tr>
						<th class="px-4 py-2 border-b">No.</th>
						<th class="px-4 py-2 border-b">MRN</th>
						<th class="px-4 py-2 border-b">PROCEDURE</th>
						<th class="px-4 py-2 border-b">CONSULTANT</th>
						<th class="px-4 py-2 border-b">LAB</th>
						<th class="px-4 py-2 border-b">TIME IN</th>
						<th class="px-4 py-2 border-b">STATUS</th>
						<th class="px-4 py-2 border-b">REMARKS</th>
						</tr>
					</thead>
	
				<transition name="fade" mode="out-in">
					<tbody class="text-center" :key="currentPage">
						<tr v-for="(item, index) in paginatedData" :key="index" class="odd:bg-white even:bg-slate-100">
							<td class="px-4 py-2 border-b font-semibold text-xl">
							{{ (currentPage - 1) * itemsPerPage + index + 1 }}
							</td>
							<td class="px-4 py-2 border-b font-semibold text-xl">{{ item.mrn || '-' }}</td>
							<td class="px-4 py-2 border-b text-xl">{{ item.initialProcedure || '-' }}</td>
							<td class="px-4 py-2 border-b text-xl">{{ item.consultant || '-' }}</td>
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
										: 'bg-gray-400'
									]">
									{{ item.status }}
								</span>
								<span v-else>-</span>
							</td>
							<td class="px-4 py-2 border-b text-xl">{{ item.overallRemarks || '-' }}</td>
						</tr>
						<tr v-if="paginatedData.length === 0">
							<td colspan="8" class="px-4 py-2 border-b text-center">No data found</td>
						</tr>
					</tbody>
				</transition>
			</table>
	
			<!-- Page indicator -->
			<div class="text-center mt-4 text-lg font-semibold text-gray-700">
				Page {{ currentPage }} / {{ totalPages }}
			</div>
		</div>
	</div>
</template>
  

<style scoped>
	.fade-enter-active, .fade-leave-active {
		transition: opacity 0.5s ease;
	}
	.fade-enter-from, .fade-leave-to {
		opacity: 0;
	}
</style>

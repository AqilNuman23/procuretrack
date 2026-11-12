<script setup>
import { onMounted, ref } from 'vue';

const message = ref('Connecting to the API...');

onMounted(async () => {
    try {
        const response = await fetch('/api/v1/status', {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Request failed: ${response.status}`);
        }

        const data = await response.json();
        message.value = data.message;
    } catch {
        message.value = 'Could not connect to the API.';
    }
});
</script>

<template>
    <main class="min-h-screen bg-slate-100 px-6 py-16">
        <section class="mx-auto max-w-xl rounded-2xl bg-white p-8 shadow-sm">
            <h1 class="text-3xl font-bold text-indigo-600">
                ProcureTrack
            </h1>

            <p class="mt-4 text-slate-600" role="status">
                {{ message }}
            </p>
        </section>
    </main>
</template>
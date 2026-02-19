<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    type: 'terms' | 'privacy';
}>();
</script>

<template>
    <section id="sign-in" class="w-100">
        <div class="d-flex align-items-center flex-column h-100 p-3-5">
            <div class="d-flex gap-4 flex-column w-100 align-items-center">
                <img src="assets/imgs/Flag_of_Lebanon.png" class="flag" alt="Flag of Lebanon" />
            </div>
            <div class="form-container d-flex flex-column gap-4" style="max-width: 560px;">
                <h1 class="text-white text-center mb-0">
                    {{ type === 'terms' ? 'Terms of Service' : 'Privacy Policy' }}
                </h1>
                <p class="text-white-50 mb-0">
                    {{ type === 'terms'
                        ? 'Terms of service content will be available here. This is a placeholder.'
                        : 'Privacy policy content will be available here. This is a placeholder.'
                    }}
                </p>
                <Link
                    :href="route('register')"
                    class="btn btn-custom-2 btn-custom mt-2"
                    style="background-color: rgb(102, 175, 219);"
                >
                    Back to Sign up
                </Link>
            </div>
        </div>
    </section>
</template>

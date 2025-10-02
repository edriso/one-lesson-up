<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Shield, ShieldCheck } from 'lucide-vue-next';

interface Props {
  twoFactorEnabled: boolean;
  requiresConfirmation: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  twoFactorEnabled: false,
  requiresConfirmation: false,
});
</script>

<template>
  <Head title="Two-Factor Authentication" />
  
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-foreground mb-2">
          Two-Factor Authentication
        </h1>
        <p class="text-muted-foreground">
          Add an extra layer of security to your account
        </p>
      </div>

      <!-- Two-Factor Status -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Shield v-if="!twoFactorEnabled" class="h-5 w-5 text-muted-foreground" />
            <ShieldCheck v-else class="h-5 w-5 text-green-500" />
            Two-Factor Authentication
          </CardTitle>
          <CardDescription>
            {{ twoFactorEnabled ? 'Your account is protected with two-factor authentication' : 'Secure your account with two-factor authentication' }}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!twoFactorEnabled" class="space-y-4">
            <p class="text-sm text-muted-foreground">
              Two-factor authentication adds an additional layer of security to your account by requiring a second form of verification.
            </p>
            <Button>
              Enable Two-Factor Authentication
            </Button>
          </div>
          <div v-else class="space-y-4">
            <p class="text-sm text-green-600">
              ✓ Two-factor authentication is currently enabled on your account.
            </p>
            <Button variant="outline">
              Disable Two-Factor Authentication
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<template>
  <!-- key nach Pfad: Editor-Ansichten (z. B. /angebote/:id) mounten bei
       Wechsel auf eine andere ID sauber neu (onMounted läuft erneut). -->
  <RouterView v-slot="{ Component, route }">
    <component :is="Component" :key="route.path" />
  </RouterView>
</template>

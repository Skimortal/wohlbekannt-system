import { onBeforeUnmount, onMounted } from 'vue'

// Nutzer-Aktivitäten, die die Inaktivitäts-Uhr zurücksetzen.
const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click']

/**
 * Meldet den Nutzer nach `timeoutMs` ohne Interaktion ab. Jede Maus-/Tastatur-
 * aktivität setzt die Uhr zurück, damit aktives Arbeiten nie ausloggt. Reset
 * wird auf max. 1×/Sekunde gedrosselt (mousemove feuert sonst sehr oft).
 */
export function useIdleLogout(timeoutMs: number, onIdle: () => void): void {
  let timer: ReturnType<typeof setTimeout> | undefined
  let lastReset = 0

  const arm = () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(onIdle, timeoutMs)
  }
  const onActivity = () => {
    const now = Date.now()
    if (now - lastReset < 1000) return
    lastReset = now
    arm()
  }

  onMounted(() => {
    ACTIVITY_EVENTS.forEach((e) => window.addEventListener(e, onActivity, { passive: true }))
    arm()
  })
  onBeforeUnmount(() => {
    if (timer) clearTimeout(timer)
    ACTIVITY_EVENTS.forEach((e) => window.removeEventListener(e, onActivity))
  })
}

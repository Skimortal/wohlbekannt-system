// Money is integer cents everywhere in the API. The UI formats for display
// and converts user input back to cents.

export function money(cents: number): string {
  // de-DE -> "1.234,56" (dot thousands separator), matching the PDF output.
  return (cents / 100).toLocaleString('de-DE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}

export function eur(cents: number, currency = 'EUR'): string {
  return `${money(cents)} ${currency}`
}

/** "1.234,56" or "1234.56" -> 123456 cents */
export function toCents(value: string | number): number {
  if (typeof value === 'number') return Math.round(value * 100)
  const normalized = value.trim().replace(/\./g, '').replace(',', '.')
  const n = parseFloat(normalized)
  return Number.isFinite(n) ? Math.round(n * 100) : 0
}

/** cents -> editable string "1234,56" (no thousands separator) */
export function centsToInput(cents: number): string {
  return (cents / 100).toFixed(2).replace('.', ',')
}

export function formatDate(iso: string | null | undefined): string {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('de-AT', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

/** YYYY-MM-DD for <input type=date> */
export function isoDate(iso: string | null | undefined): string {
  if (!iso) return ''
  return iso.slice(0, 10)
}
